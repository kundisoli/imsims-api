<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PostgreSQL\Product;
use App\Models\MongoDB\ProductMetadata;
use App\Models\MongoDB\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of products
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $categoryId = $request->get('category_id');
        $supplierId = $request->get('supplier_id');
        $isActive = $request->get('is_active');
        $lowStock = $request->get('low_stock');

        $cacheKey = "products_index_" . md5(serialize($request->all()));
        
        $products = Cache::remember($cacheKey, 300, function () use ($perPage, $search, $categoryId, $supplierId, $isActive, $lowStock) {
            $query = Product::with(['category', 'supplier', 'inventoryRecords']);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                      ->orWhere('sku', 'ILIKE', "%{$search}%")
                      ->orWhere('barcode', 'ILIKE', "%{$search}%");
                });
            }

            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }

            if ($supplierId) {
                $query->where('supplier_id', $supplierId);
            }

            if ($isActive !== null) {
                $query->where('is_active', $isActive);
            }

            if ($lowStock) {
                $query->lowStock();
            }

            return $query->orderBy('name')->paginate($perPage);
        });

        // Add metadata for each product
        $products->getCollection()->transform(function ($product) {
            $metadata = ProductMetadata::getByProduct($product->id);
            $product->metadata = $metadata ? $metadata->toArray() : null;
            return $product;
        });

        return response()->json($products);
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku|max:100',
            'description' => 'nullable|string',
            'barcode' => 'nullable|string|max:100',
            'category_id' => 'nullable|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'min_stock_level' => 'required|integer|min:0',
            'max_stock_level' => 'required|integer|min:0',
            'reorder_point' => 'required|integer|min:0',
            'unit_of_measure' => 'required|string|max:50',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|array',
            'is_active' => 'boolean',
            'is_trackable' => 'boolean',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $productData = $request->except(['metadata']);
            $product = Product::create($productData);

            // Save metadata if provided
            if ($request->has('metadata')) {
                ProductMetadata::updateOrCreateForProduct($product->id, $request->metadata);
            }

            // Log the creation
            AuditLog::logCreated($product);

            // Clear cache
            Cache::tags(['products'])->flush();

            $product->load(['category', 'supplier']);
            
            return response()->json([
                'message' => 'Product created successfully',
                'product' => $product
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified product
     */
    public function show(int $id): JsonResponse
    {
        $cacheKey = "product_{$id}";
        
        $product = Cache::remember($cacheKey, 600, function () use ($id) {
            return Product::with(['category', 'supplier', 'inventoryRecords.warehouse', 'stockMovements'])
                          ->findOrFail($id);
        });

        // Get metadata
        $metadata = ProductMetadata::getByProduct($id);
        $product->metadata = $metadata ? $metadata->toArray() : null;

        return response()->json($product);
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'sku' => 'sometimes|required|string|max:100|unique:products,sku,' . $id,
            'description' => 'nullable|string',
            'barcode' => 'nullable|string|max:100',
            'category_id' => 'nullable|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'cost_price' => 'sometimes|required|numeric|min:0',
            'selling_price' => 'sometimes|required|numeric|min:0',
            'min_stock_level' => 'sometimes|required|integer|min:0',
            'max_stock_level' => 'sometimes|required|integer|min:0',
            'reorder_point' => 'sometimes|required|integer|min:0',
            'unit_of_measure' => 'sometimes|required|string|max:50',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|array',
            'is_active' => 'boolean',
            'is_trackable' => 'boolean',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $oldValues = $product->getOriginal();
            $productData = $request->except(['metadata']);
            $product->update($productData);

            // Update metadata if provided
            if ($request->has('metadata')) {
                ProductMetadata::updateOrCreateForProduct($product->id, $request->metadata);
            }

            // Log the update
            AuditLog::logUpdated($product, $oldValues, $product->getChanges());

            // Clear cache
            Cache::forget("product_{$id}");
            Cache::tags(['products'])->flush();

            $product->load(['category', 'supplier']);

            return response()->json([
                'message' => 'Product updated successfully',
                'product' => $product
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified product
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            
            // Log the deletion
            AuditLog::logDeleted($product);
            
            $product->delete();

            // Clear cache
            Cache::forget("product_{$id}");
            Cache::tags(['products'])->flush();

            return response()->json([
                'message' => 'Product deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get low stock products
     */
    public function lowStock(): JsonResponse
    {
        $cacheKey = "products_low_stock";
        
        $products = Cache::remember($cacheKey, 300, function () {
            return Product::with(['category', 'supplier', 'inventoryRecords'])
                          ->lowStock()
                          ->active()
                          ->get();
        });

        return response()->json($products);
    }

    /**
     * Get product stock summary
     */
    public function stockSummary(int $id): JsonResponse
    {
        $product = Product::with(['inventoryRecords.warehouse', 'inventoryRecords.location'])
                          ->findOrFail($id);

        $stockSummary = [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'total_stock' => $product->current_stock,
            'reorder_point' => $product->reorder_point,
            'is_low_stock' => $product->isLowStock(),
            'locations' => $product->inventoryRecords->map(function ($record) {
                return [
                    'warehouse' => $record->warehouse->name,
                    'location' => $record->location ? $record->location->full_path : 'No specific location',
                    'quantity' => $record->quantity,
                    'reserved_quantity' => $record->reserved_quantity,
                    'available_quantity' => $record->available_quantity,
                ];
            })
        ];

        return response()->json($stockSummary);
    }
}