<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PostgreSQL\Category;
use App\Models\MongoDB\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $parentId = $request->get('parent_id');
        $isActive = $request->get('is_active');
        $tree = $request->get('tree', false);

        if ($tree) {
            return $this->getCategoryTree();
        }

        $cacheKey = "categories_index_" . md5(serialize($request->all()));
        
        $categories = Cache::remember($cacheKey, 600, function () use ($perPage, $search, $parentId, $isActive) {
            $query = Category::with(['parent', 'children', 'products']);

            if ($search) {
                $query->where('name', 'ILIKE', "%{$search}%");
            }

            if ($parentId !== null) {
                $query->where('parent_id', $parentId);
            }

            if ($isActive !== null) {
                $query->where('is_active', $isActive);
            }

            return $query->orderBy('sort_order')->orderBy('name')->paginate($perPage);
        });

        return response()->json($categories);
    }

    /**
     * Get category tree structure
     */
    public function getCategoryTree(): JsonResponse
    {
        $cacheKey = "categories_tree";
        
        $tree = Cache::remember($cacheKey, 3600, function () {
            $categories = Category::active()
                ->with(['children' => function ($query) {
                    $query->active()->orderBy('sort_order')->orderBy('name');
                }])
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            return $this->buildCategoryTree($categories);
        });

        return response()->json($tree);
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
            'sort_order' => 'integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Validate parent relationship to prevent circular references
        if ($request->parent_id) {
            $parent = Category::find($request->parent_id);
            if (!$parent) {
                return response()->json(['error' => 'Parent category not found'], 404);
            }
        }

        try {
            $category = Category::create($request->all());

            // Log the creation
            AuditLog::logCreated($category);

            // Clear cache
            Cache::tags(['categories'])->flush();

            $category->load(['parent', 'children']);

            return response()->json([
                'message' => 'Category created successfully',
                'category' => $category
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified category
     */
    public function show(int $id): JsonResponse
    {
        $cacheKey = "category_{$id}";
        
        $category = Cache::remember($cacheKey, 600, function () use ($id) {
            return Category::with(['parent', 'children', 'products.supplier'])
                          ->findOrFail($id);
        });

        return response()->json($category);
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
            'sort_order' => 'integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Validate parent relationship to prevent circular references
        if ($request->has('parent_id') && $request->parent_id) {
            if ($this->wouldCreateCircularReference($id, $request->parent_id)) {
                return response()->json([
                    'error' => 'Cannot set parent - would create circular reference'
                ], 400);
            }
        }

        try {
            $oldValues = $category->getOriginal();
            $category->update($request->all());

            // Log the update
            AuditLog::logUpdated($category, $oldValues, $category->getChanges());

            // Clear cache
            Cache::forget("category_{$id}");
            Cache::tags(['categories'])->flush();

            $category->load(['parent', 'children']);

            return response()->json([
                'message' => 'Category updated successfully',
                'category' => $category
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified category
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $category = Category::findOrFail($id);

            // Check if category has products
            if ($category->products()->count() > 0) {
                return response()->json([
                    'message' => 'Cannot delete category with associated products'
                ], 400);
            }

            // Check if category has child categories
            if ($category->children()->count() > 0) {
                return response()->json([
                    'message' => 'Cannot delete category with child categories'
                ], 400);
            }

            // Log the deletion
            AuditLog::logDeleted($category);

            $category->delete();

            // Clear cache
            Cache::forget("category_{$id}");
            Cache::tags(['categories'])->flush();

            return response()->json([
                'message' => 'Category deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get products in a category
     */
    public function products(int $id, Request $request): JsonResponse
    {
        $category = Category::findOrFail($id);
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');

        $query = $category->products()->with(['supplier']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('sku', 'ILIKE', "%{$search}%");
            });
        }

        $products = $query->orderBy('name')->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Build category tree recursively
     */
    private function buildCategoryTree($categories): array
    {
        $tree = [];

        foreach ($categories as $category) {
            $node = [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'sort_order' => $category->sort_order,
                'products_count' => $category->products()->count(),
                'children' => []
            ];

            if ($category->children->isNotEmpty()) {
                $node['children'] = $this->buildCategoryTree($category->children);
            }

            $tree[] = $node;
        }

        return $tree;
    }

    /**
     * Check if setting parent would create circular reference
     */
    private function wouldCreateCircularReference(int $categoryId, int $parentId): bool
    {
        $current = Category::find($parentId);
        
        while ($current) {
            if ($current->id === $categoryId) {
                return true;
            }
            $current = $current->parent;
        }

        return false;
    }
}