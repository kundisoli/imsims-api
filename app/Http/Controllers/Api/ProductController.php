<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Display a listing of products.
     */
    public function index(Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $perPage = min($request->get('per_page', 15), 100);
        $search = $request->get('search');
        $categoryId = $request->get('category_id');
        $supplierId = $request->get('supplier_id');
        $isActive = $request->get('is_active');

        $cacheKey = "products:list:" . md5(serialize($request->query()));

        $products = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($search, $categoryId, $supplierId, $isActive, $perPage) {
            $query = Product::with(['category', 'supplier'])
                ->withCount('stocks');

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

            return $query->orderBy('name')->paginate($perPage);
        });

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => 'required|string|max:100|unique:products,sku',
            'barcode' => 'nullable|string|max:100|unique:products,barcode',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'minimum_stock' => 'required|integer|min:0',
            'maximum_stock' => 'required|integer|min:1|gte:minimum_stock',
            'unit_of_measure' => 'required|string|max:50',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $product = Product::create($request->validated());
        $product->load(['category', 'supplier']);

        // Log the creation
        AuditLog::logCreate('Product', $product->id, $product->toArray());

        // Clear cache
        $this->clearProductCache();

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product,
        ], 201);
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): JsonResponse
    {
        $cacheKey = "product:{$product->id}:details";

        $productData = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($product) {
            return $product->load([
                'category',
                'supplier',
                'stocks' => function ($query) {
                    $query->where('quantity', '>', 0);
                },
                'stockMovements' => function ($query) {
                    $query->latest()->limit(10)->with('user');
                }
            ]);
        });

        return response()->json([
            'success' => true,
            'data' => $productData,
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product->id)],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->ignore($product->id)],
            'price' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'minimum_stock' => 'required|integer|min:0',
            'maximum_stock' => 'required|integer|min:1|gte:minimum_stock',
            'unit_of_measure' => 'required|string|max:50',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $oldValues = $product->toArray();
        $product->update($request->validated());
        $product->load(['category', 'supplier']);

        // Log the update
        AuditLog::logUpdate('Product', $product->id, $oldValues, $product->toArray());

        // Clear cache
        $this->clearProductCache($product->id);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $product,
        ]);
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product): JsonResponse
    {
        $oldValues = $product->toArray();

        if ($product->stocks()->where('quantity', '>', 0)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete product with existing stock',
            ], 400);
        }

        $product->delete();

        // Log the deletion
        AuditLog::logDelete('Product', $product->id, $oldValues);

        // Clear cache
        $this->clearProductCache($product->id);

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }

    /**
     * Get low stock products.
     */
    public function lowStock(): JsonResponse
    {
        $cacheKey = "products:low_stock";

        $products = Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return Product::with(['category', 'supplier'])
                ->whereHas('stocks', function ($query) {
                    $query->selectRaw('product_id, SUM(quantity) as total_quantity')
                          ->groupBy('product_id')
                          ->havingRaw('SUM(quantity) <= products.minimum_stock');
                })
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Get product stock levels.
     */
    public function stockLevels(Product $product): JsonResponse
    {
        $cacheKey = "product:{$product->id}:stock_levels";

        $stockLevels = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($product) {
            return $product->stocks()
                ->selectRaw('location, SUM(quantity) as total_quantity, COUNT(*) as batch_count')
                ->groupBy('location')
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => [
                'product' => $product->only(['id', 'name', 'sku']),
                'current_stock' => $product->getCurrentStock(),
                'minimum_stock' => $product->minimum_stock,
                'maximum_stock' => $product->maximum_stock,
                'is_low_stock' => $product->isLowStock(),
                'is_overstocked' => $product->isOverstocked(),
                'locations' => $stockLevels,
            ],
        ]);
    }

    /**
     * Clear product-related cache.
     */
    private function clearProductCache(?int $productId = null): void
    {
        // Clear list cache
        Cache::forget('products:list:*');
        Cache::forget('products:low_stock');

        if ($productId) {
            Cache::forget("product:{$productId}:details");
            Cache::forget("product:{$productId}:stock_levels");
        }
    }
}
