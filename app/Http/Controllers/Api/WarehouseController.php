<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PostgreSQL\Warehouse;
use App\Models\MongoDB\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class WarehouseController extends Controller
{
    /**
     * Display a listing of warehouses
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $isActive = $request->get('is_active');

        $cacheKey = "warehouses_index_" . md5(serialize($request->all()));
        
        $warehouses = Cache::remember($cacheKey, 600, function () use ($perPage, $search, $isActive) {
            $query = Warehouse::with(['manager', 'locations']);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                      ->orWhere('code', 'ILIKE', "%{$search}%")
                      ->orWhere('city', 'ILIKE', "%{$search}%");
                });
            }

            if ($isActive !== null) {
                $query->where('is_active', $isActive);
            }

            return $query->orderBy('name')->paginate($perPage);
        });

        // Add computed attributes
        $warehouses->getCollection()->transform(function ($warehouse) {
            $warehouse->total_inventory_value = $warehouse->total_inventory_value;
            $warehouse->total_products = $warehouse->total_products;
            return $warehouse;
        });

        return response()->json($warehouses);
    }

    /**
     * Store a newly created warehouse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:warehouses,code',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'manager_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $warehouse = Warehouse::create($request->all());

            // Log the creation
            AuditLog::logCreated($warehouse);

            // Clear cache
            Cache::tags(['warehouses'])->flush();

            $warehouse->load(['manager', 'locations']);

            return response()->json([
                'message' => 'Warehouse created successfully',
                'warehouse' => $warehouse
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create warehouse',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified warehouse
     */
    public function show(int $id): JsonResponse
    {
        $cacheKey = "warehouse_{$id}";
        
        $warehouse = Cache::remember($cacheKey, 600, function () use ($id) {
            return Warehouse::with(['manager', 'locations.inventoryRecords.product', 'inventoryRecords.product'])
                           ->findOrFail($id);
        });

        // Add computed attributes
        $warehouse->total_inventory_value = $warehouse->total_inventory_value;
        $warehouse->total_products = $warehouse->total_products;

        return response()->json($warehouse);
    }

    /**
     * Update the specified warehouse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:50|unique:warehouses,code,' . $id,
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'manager_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $oldValues = $warehouse->getOriginal();
            $warehouse->update($request->all());

            // Log the update
            AuditLog::logUpdated($warehouse, $oldValues, $warehouse->getChanges());

            // Clear cache
            Cache::forget("warehouse_{$id}");
            Cache::tags(['warehouses'])->flush();

            $warehouse->load(['manager', 'locations']);

            return response()->json([
                'message' => 'Warehouse updated successfully',
                'warehouse' => $warehouse
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update warehouse',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified warehouse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $warehouse = Warehouse::findOrFail($id);

            // Check if warehouse has inventory
            if ($warehouse->inventoryRecords()->where('quantity', '>', 0)->count() > 0) {
                return response()->json([
                    'message' => 'Cannot delete warehouse with existing inventory'
                ], 400);
            }

            // Log the deletion
            AuditLog::logDeleted($warehouse);

            $warehouse->delete();

            // Clear cache
            Cache::forget("warehouse_{$id}");
            Cache::tags(['warehouses'])->flush();

            return response()->json([
                'message' => 'Warehouse deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete warehouse',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get warehouse locations
     */
    public function locations(int $id, Request $request): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($id);
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $isActive = $request->get('is_active');

        $query = $warehouse->locations()->with(['inventoryRecords.product']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('code', 'ILIKE', "%{$search}%")
                  ->orWhere('type', 'ILIKE', "%{$search}%");
            });
        }

        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }

        $locations = $query->orderBy('name')->paginate($perPage);

        // Add computed attributes
        $locations->getCollection()->transform(function ($location) {
            $location->utilization_percentage = $location->utilization_percentage;
            $location->available_capacity = $location->available_capacity;
            return $location;
        });

        return response()->json($locations);
    }

    /**
     * Get warehouse inventory summary
     */
    public function inventorySummary(int $id): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($id);

        $cacheKey = "warehouse_{$id}_inventory_summary";
        
        $summary = Cache::remember($cacheKey, 300, function () use ($warehouse) {
            $inventoryRecords = $warehouse->inventoryRecords()
                ->with(['product.category', 'location'])
                ->where('quantity', '>', 0)
                ->get();

            $totalProducts = $inventoryRecords->count();
            $totalQuantity = $inventoryRecords->sum('quantity');
            $totalValue = $inventoryRecords->sum(function ($record) {
                return $record->quantity * $record->product->cost_price;
            });

            $lowStockProducts = $inventoryRecords->filter(function ($record) {
                return $record->quantity <= $record->product->reorder_point;
            });

            $categoryBreakdown = $inventoryRecords->groupBy('product.category.name')
                ->map(function ($group) {
                    return [
                        'product_count' => $group->count(),
                        'total_quantity' => $group->sum('quantity'),
                        'total_value' => $group->sum(function ($record) {
                            return $record->quantity * $record->product->cost_price;
                        })
                    ];
                });

            return [
                'warehouse_id' => $warehouse->id,
                'warehouse_name' => $warehouse->name,
                'total_products' => $totalProducts,
                'total_quantity' => $totalQuantity,
                'total_value' => $totalValue,
                'low_stock_count' => $lowStockProducts->count(),
                'low_stock_products' => $lowStockProducts->map(function ($record) {
                    return [
                        'product_id' => $record->product_id,
                        'product_name' => $record->product->name,
                        'sku' => $record->product->sku,
                        'current_quantity' => $record->quantity,
                        'reorder_point' => $record->product->reorder_point
                    ];
                }),
                'category_breakdown' => $categoryBreakdown,
                'location_utilization' => $warehouse->locations->map(function ($location) {
                    return [
                        'location_id' => $location->id,
                        'location_name' => $location->name,
                        'utilization_percentage' => $location->utilization_percentage,
                        'available_capacity' => $location->available_capacity
                    ];
                })
            ];
        });

        return response()->json($summary);
    }

    /**
     * Get warehouse performance metrics
     */
    public function performanceMetrics(int $id, Request $request): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($id);
        $days = $request->get('days', 30);
        $startDate = now()->subDays($days);

        $cacheKey = "warehouse_{$id}_performance_metrics_{$days}";
        
        $metrics = Cache::remember($cacheKey, 900, function () use ($warehouse, $startDate) {
            $stockMovements = $warehouse->stockMovements()
                ->with(['product'])
                ->where('created_at', '>=', $startDate)
                ->get();

            $inboundMovements = $stockMovements->where('type', 'in');
            $outboundMovements = $stockMovements->where('type', 'out');

            $topProducts = $stockMovements->groupBy('product_id')
                ->map(function ($movements) {
                    $product = $movements->first()->product;
                    return [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'sku' => $product->sku,
                        'total_movements' => $movements->count(),
                        'total_quantity' => $movements->sum('quantity')
                    ];
                })
                ->sortByDesc('total_movements')
                ->take(10)
                ->values();

            return [
                'warehouse_id' => $warehouse->id,
                'warehouse_name' => $warehouse->name,
                'period_days' => $startDate->diffInDays(now()),
                'total_movements' => $stockMovements->count(),
                'inbound_movements' => $inboundMovements->count(),
                'outbound_movements' => $outboundMovements->count(),
                'inbound_quantity' => $inboundMovements->sum('quantity'),
                'outbound_quantity' => $outboundMovements->sum('quantity'),
                'net_movement' => $inboundMovements->sum('quantity') - $outboundMovements->sum('quantity'),
                'average_daily_movements' => round($stockMovements->count() / max($startDate->diffInDays(now()), 1), 2),
                'top_products_by_activity' => $topProducts
            ];
        });

        return response()->json($metrics);
    }
}