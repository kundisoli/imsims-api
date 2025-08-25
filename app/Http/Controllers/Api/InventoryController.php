<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PostgreSQL\Product;
use App\Models\PostgreSQL\InventoryRecord;
use App\Models\PostgreSQL\StockMovement;
use App\Models\MongoDB\InventoryActivity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class InventoryController extends Controller
{
    /**
     * Get inventory overview
     */
    public function overview(): JsonResponse
    {
        $cacheKey = "inventory_overview";
        
        $overview = Cache::remember($cacheKey, 300, function () {
            return [
                'total_products' => Product::active()->count(),
                'total_inventory_value' => $this->getTotalInventoryValue(),
                'low_stock_products' => Product::lowStock()->count(),
                'out_of_stock_products' => $this->getOutOfStockCount(),
                'recent_movements' => $this->getRecentMovements(10),
                'top_products_by_value' => $this->getTopProductsByValue(5),
                'warehouse_summary' => $this->getWarehouseSummary()
            ];
        });

        return response()->json($overview);
    }

    /**
     * Get inventory by warehouse
     */
    public function byWarehouse(Request $request, int $warehouseId): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');

        $query = InventoryRecord::with(['product', 'location'])
                                ->where('warehouse_id', $warehouseId)
                                ->where('quantity', '>', 0);

        if ($search) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('sku', 'ILIKE', "%{$search}%");
            });
        }

        $inventory = $query->orderBy('quantity', 'desc')->paginate($perPage);

        return response()->json($inventory);
    }

    /**
     * Adjust stock levels
     */
    public function adjustStock(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'adjustments' => 'required|array',
            'adjustments.*.product_id' => 'required|exists:products,id',
            'adjustments.*.warehouse_id' => 'required|exists:warehouses,id',
            'adjustments.*.location_id' => 'nullable|exists:locations,id',
            'adjustments.*.quantity_change' => 'required|integer',
            'adjustments.*.reason' => 'required|string|in:damage,theft,expired,adjustment,initial',
            'adjustments.*.notes' => 'nullable|string',
            'adjustments.*.unit_cost' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $results = [];
            foreach ($request->adjustments as $adjustment) {
                $result = $this->processStockAdjustment($adjustment);
                $results[] = $result;
            }

            DB::commit();
            
            // Clear cache
            Cache::tags(['inventory'])->flush();

            return response()->json([
                'message' => 'Stock adjustments completed successfully',
                'results' => $results
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to adjust stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Transfer stock between locations
     */
    public function transferStock(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'from_location_id' => 'nullable|exists:locations,id',
            'to_warehouse_id' => 'required|exists:warehouses,id',
            'to_location_id' => 'nullable|exists:locations,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $product = Product::findOrFail($request->product_id);
            
            // Check if sufficient stock exists at source location
            $sourceInventory = InventoryRecord::where('product_id', $request->product_id)
                                             ->where('warehouse_id', $request->from_warehouse_id)
                                             ->where('location_id', $request->from_location_id)
                                             ->first();

            if (!$sourceInventory || $sourceInventory->available_quantity < $request->quantity) {
                return response()->json([
                    'message' => 'Insufficient stock at source location'
                ], 400);
            }

            // Create outbound movement from source
            $outboundMovement = StockMovement::create([
                'product_id' => $request->product_id,
                'warehouse_id' => $request->from_warehouse_id,
                'location_id' => $request->from_location_id,
                'user_id' => auth()->id(),
                'type' => StockMovement::TYPE_OUT,
                'reason' => StockMovement::REASON_TRANSFER,
                'quantity' => $request->quantity,
                'notes' => $request->notes,
                'unit_cost' => $product->cost_price,
                'total_cost' => $product->cost_price * $request->quantity
            ]);

            // Create inbound movement to destination
            $inboundMovement = StockMovement::create([
                'product_id' => $request->product_id,
                'warehouse_id' => $request->to_warehouse_id,
                'location_id' => $request->to_location_id,
                'user_id' => auth()->id(),
                'type' => StockMovement::TYPE_IN,
                'reason' => StockMovement::REASON_TRANSFER,
                'quantity' => $request->quantity,
                'notes' => $request->notes,
                'unit_cost' => $product->cost_price,
                'total_cost' => $product->cost_price * $request->quantity
            ]);

            // Update source inventory
            $sourceInventory->decrement('quantity', $request->quantity);
            $sourceInventory->updateAvailableQuantity();

            // Update or create destination inventory
            $destinationInventory = InventoryRecord::firstOrCreate([
                'product_id' => $request->product_id,
                'warehouse_id' => $request->to_warehouse_id,
                'location_id' => $request->to_location_id
            ]);
            
            $destinationInventory->increment('quantity', $request->quantity);
            $destinationInventory->updateAvailableQuantity();

            // Log activity
            InventoryActivity::logActivity([
                'product_id' => $request->product_id,
                'user_id' => auth()->id(),
                'warehouse_id' => $request->from_warehouse_id,
                'location_id' => $request->from_location_id,
                'activity_type' => 'stock_transfer_out',
                'description' => "Transferred {$request->quantity} units to warehouse {$request->to_warehouse_id}",
                'quantity_before' => $sourceInventory->quantity + $request->quantity,
                'quantity_after' => $sourceInventory->quantity,
                'quantity_changed' => -$request->quantity,
                'reference_type' => StockMovement::class,
                'reference_id' => $outboundMovement->id,
                'metadata' => [
                    'transfer_to_warehouse' => $request->to_warehouse_id,
                    'transfer_to_location' => $request->to_location_id
                ]
            ]);

            InventoryActivity::logActivity([
                'product_id' => $request->product_id,
                'user_id' => auth()->id(),
                'warehouse_id' => $request->to_warehouse_id,
                'location_id' => $request->to_location_id,
                'activity_type' => 'stock_transfer_in',
                'description' => "Received {$request->quantity} units from warehouse {$request->from_warehouse_id}",
                'quantity_before' => $destinationInventory->quantity - $request->quantity,
                'quantity_after' => $destinationInventory->quantity,
                'quantity_changed' => $request->quantity,
                'reference_type' => StockMovement::class,
                'reference_id' => $inboundMovement->id,
                'metadata' => [
                    'transfer_from_warehouse' => $request->from_warehouse_id,
                    'transfer_from_location' => $request->from_location_id
                ]
            ]);

            DB::commit();

            // Clear cache
            Cache::tags(['inventory'])->flush();

            return response()->json([
                'message' => 'Stock transfer completed successfully',
                'outbound_movement' => $outboundMovement,
                'inbound_movement' => $inboundMovement
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to transfer stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get stock movement history
     */
    public function movementHistory(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $productId = $request->get('product_id');
        $warehouseId = $request->get('warehouse_id');
        $type = $request->get('type');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = StockMovement::with(['product', 'warehouse', 'location', 'user']);

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        $movements = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($movements);
    }

    /**
     * Process individual stock adjustment
     */
    private function processStockAdjustment(array $adjustment): array
    {
        $product = Product::findOrFail($adjustment['product_id']);
        
        // Get or create inventory record
        $inventoryRecord = InventoryRecord::firstOrCreate([
            'product_id' => $adjustment['product_id'],
            'warehouse_id' => $adjustment['warehouse_id'],
            'location_id' => $adjustment['location_id']
        ]);

        $quantityBefore = $inventoryRecord->quantity;
        $quantityAfter = $quantityBefore + $adjustment['quantity_change'];

        // Prevent negative stock
        if ($quantityAfter < 0) {
            throw new \Exception("Adjustment would result in negative stock for product {$product->name}");
        }

        // Create stock movement record
        $movement = StockMovement::create([
            'product_id' => $adjustment['product_id'],
            'warehouse_id' => $adjustment['warehouse_id'],
            'location_id' => $adjustment['location_id'],
            'user_id' => auth()->id(),
            'type' => $adjustment['quantity_change'] > 0 ? StockMovement::TYPE_IN : StockMovement::TYPE_OUT,
            'reason' => $adjustment['reason'],
            'quantity' => abs($adjustment['quantity_change']),
            'notes' => $adjustment['notes'] ?? null,
            'unit_cost' => $adjustment['unit_cost'] ?? $product->cost_price,
            'total_cost' => ($adjustment['unit_cost'] ?? $product->cost_price) * abs($adjustment['quantity_change'])
        ]);

        // Update inventory record
        $inventoryRecord->quantity = $quantityAfter;
        $inventoryRecord->updateAvailableQuantity();

        // Log activity
        InventoryActivity::logActivity([
            'product_id' => $adjustment['product_id'],
            'user_id' => auth()->id(),
            'warehouse_id' => $adjustment['warehouse_id'],
            'location_id' => $adjustment['location_id'],
            'activity_type' => 'stock_adjustment',
            'description' => "Stock adjusted: {$adjustment['quantity_change']} units ({$adjustment['reason']})",
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'quantity_changed' => $adjustment['quantity_change'],
            'reference_type' => StockMovement::class,
            'reference_id' => $movement->id,
            'metadata' => [
                'reason' => $adjustment['reason'],
                'notes' => $adjustment['notes'] ?? null
            ]
        ]);

        return [
            'product_id' => $adjustment['product_id'],
            'product_name' => $product->name,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'quantity_changed' => $adjustment['quantity_change'],
            'movement_id' => $movement->id
        ];
    }

    /**
     * Helper methods
     */
    private function getTotalInventoryValue(): float
    {
        return DB::table('inventory_records')
            ->join('products', 'inventory_records.product_id', '=', 'products.id')
            ->sum(DB::raw('inventory_records.quantity * products.cost_price'));
    }

    private function getOutOfStockCount(): int
    {
        return InventoryRecord::where('quantity', 0)->count();
    }

    private function getRecentMovements(int $limit)
    {
        return StockMovement::with(['product', 'warehouse', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    private function getTopProductsByValue(int $limit)
    {
        return DB::table('inventory_records')
            ->join('products', 'inventory_records.product_id', '=', 'products.id')
            ->select('products.name', 'products.sku', 
                DB::raw('SUM(inventory_records.quantity * products.cost_price) as total_value'))
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderBy('total_value', 'desc')
            ->limit($limit)
            ->get();
    }

    private function getWarehouseSummary()
    {
        return DB::table('inventory_records')
            ->join('warehouses', 'inventory_records.warehouse_id', '=', 'warehouses.id')
            ->join('products', 'inventory_records.product_id', '=', 'products.id')
            ->select('warehouses.name', 'warehouses.id',
                DB::raw('COUNT(DISTINCT inventory_records.product_id) as product_count'),
                DB::raw('SUM(inventory_records.quantity) as total_quantity'),
                DB::raw('SUM(inventory_records.quantity * products.cost_price) as total_value'))
            ->groupBy('warehouses.id', 'warehouses.name')
            ->get();
    }
}