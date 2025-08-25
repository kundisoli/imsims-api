<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CacheService
{
    const DEFAULT_TTL = 3600; // 1 hour
    const SHORT_TTL = 300;    // 5 minutes
    const LONG_TTL = 86400;   // 24 hours

    /**
     * Cache keys for different data types
     */
    const KEYS = [
        'products' => 'products',
        'categories' => 'categories',
        'warehouses' => 'warehouses',
        'inventory' => 'inventory',
        'suppliers' => 'suppliers',
        'reports' => 'reports'
    ];

    /**
     * Get cached data with fallback
     */
    public static function remember(string $key, int $ttl, callable $callback)
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Store data in cache
     */
    public static function put(string $key, $value, int $ttl = self::DEFAULT_TTL): bool
    {
        return Cache::put($key, $value, $ttl);
    }

    /**
     * Get data from cache
     */
    public static function get(string $key, $default = null)
    {
        return Cache::get($key, $default);
    }

    /**
     * Forget cached data
     */
    public static function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Flush cache by tags
     */
    public static function flushByTags(array $tags): bool
    {
        return Cache::tags($tags)->flush();
    }

    /**
     * Cache product data
     */
    public static function cacheProduct(int $productId, $data, int $ttl = self::DEFAULT_TTL): bool
    {
        $key = self::getProductKey($productId);
        return Cache::tags([self::KEYS['products']])->put($key, $data, $ttl);
    }

    /**
     * Get cached product
     */
    public static function getCachedProduct(int $productId)
    {
        $key = self::getProductKey($productId);
        return Cache::tags([self::KEYS['products']])->get($key);
    }

    /**
     * Clear product cache
     */
    public static function clearProductCache(int $productId): bool
    {
        $key = self::getProductKey($productId);
        return Cache::tags([self::KEYS['products']])->forget($key);
    }

    /**
     * Cache inventory overview
     */
    public static function cacheInventoryOverview($data, int $ttl = self::SHORT_TTL): bool
    {
        $key = 'inventory:overview';
        return Cache::tags([self::KEYS['inventory']])->put($key, $data, $ttl);
    }

    /**
     * Get cached inventory overview
     */
    public static function getCachedInventoryOverview()
    {
        $key = 'inventory:overview';
        return Cache::tags([self::KEYS['inventory']])->get($key);
    }

    /**
     * Cache warehouse inventory
     */
    public static function cacheWarehouseInventory(int $warehouseId, $data, int $ttl = self::DEFAULT_TTL): bool
    {
        $key = "warehouse:{$warehouseId}:inventory";
        return Cache::tags([self::KEYS['warehouses'], self::KEYS['inventory']])->put($key, $data, $ttl);
    }

    /**
     * Cache low stock products
     */
    public static function cacheLowStockProducts($data, int $ttl = self::SHORT_TTL): bool
    {
        $key = 'products:low_stock';
        return Cache::tags([self::KEYS['products'], self::KEYS['inventory']])->put($key, $data, $ttl);
    }

    /**
     * Cache category tree
     */
    public static function cacheCategoryTree($data, int $ttl = self::LONG_TTL): bool
    {
        $key = 'categories:tree';
        return Cache::tags([self::KEYS['categories']])->put($key, $data, $ttl);
    }

    /**
     * Cache reports
     */
    public static function cacheReport(string $reportName, array $params, $data, int $ttl = self::DEFAULT_TTL): bool
    {
        $key = "reports:{$reportName}:" . md5(serialize($params));
        return Cache::tags([self::KEYS['reports']])->put($key, $data, $ttl);
    }

    /**
     * Get cached report
     */
    public static function getCachedReport(string $reportName, array $params)
    {
        $key = "reports:{$reportName}:" . md5(serialize($params));
        return Cache::tags([self::KEYS['reports']])->get($key);
    }

    /**
     * Clear all inventory related caches
     */
    public static function clearInventoryCaches(): bool
    {
        return Cache::tags([
            self::KEYS['inventory'],
            self::KEYS['products'],
            self::KEYS['warehouses']
        ])->flush();
    }

    /**
     * Clear all product related caches
     */
    public static function clearProductCaches(): bool
    {
        return Cache::tags([self::KEYS['products']])->flush();
    }

    /**
     * Clear all category related caches
     */
    public static function clearCategoryCaches(): bool
    {
        return Cache::tags([self::KEYS['categories']])->flush();
    }

    /**
     * Clear all warehouse related caches
     */
    public static function clearWarehouseCaches(): bool
    {
        return Cache::tags([self::KEYS['warehouses']])->flush();
    }

    /**
     * Clear all report caches
     */
    public static function clearReportCaches(): bool
    {
        return Cache::tags([self::KEYS['reports']])->flush();
    }

    /**
     * Get cache statistics
     */
    public static function getCacheStatistics(): array
    {
        try {
            $redis = Redis::connection();
            
            $info = $redis->info('memory');
            $keyspace = $redis->info('keyspace');
            
            $stats = [
                'memory_used' => $info['used_memory_human'] ?? 'N/A',
                'memory_peak' => $info['used_memory_peak_human'] ?? 'N/A',
                'total_keys' => 0,
                'keyspace_info' => []
            ];

            if (isset($keyspace['db0'])) {
                preg_match('/keys=(\d+)/', $keyspace['db0'], $matches);
                $stats['total_keys'] = (int)($matches[1] ?? 0);
            }

            return $stats;
        } catch (\Exception $e) {
            return [
                'error' => 'Unable to retrieve cache statistics',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Warm up critical caches
     */
    public static function warmUpCaches(): array
    {
        $results = [];

        try {
            // Warm up category tree
            $categories = \App\Models\PostgreSQL\Category::active()
                ->with(['children' => function ($query) {
                    $query->active()->orderBy('sort_order')->orderBy('name');
                }])
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
            
            self::cacheCategoryTree($categories);
            $results['categories'] = 'success';

            // Warm up low stock products
            $lowStockProducts = \App\Models\PostgreSQL\Product::lowStock()
                ->with(['category', 'supplier'])
                ->active()
                ->get();
            
            self::cacheLowStockProducts($lowStockProducts);
            $results['low_stock_products'] = 'success';

            // Warm up inventory overview (basic stats)
            $overview = [
                'total_products' => \App\Models\PostgreSQL\Product::active()->count(),
                'low_stock_count' => $lowStockProducts->count(),
                'cache_warmed_at' => now()
            ];
            
            self::cacheInventoryOverview($overview);
            $results['inventory_overview'] = 'success';

        } catch (\Exception $e) {
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Generate product cache key
     */
    private static function getProductKey(int $productId): string
    {
        return "product:{$productId}";
    }

    /**
     * Generate cache key with prefix
     */
    public static function generateKey(string $prefix, ...$parts): string
    {
        $keyParts = array_filter([$prefix, ...$parts]);
        return implode(':', $keyParts);
    }

    /**
     * Set cache with automatic expiration based on data type
     */
    public static function setWithAutoExpiration(string $key, $value, string $type = 'default'): bool
    {
        $ttl = match ($type) {
            'fast_changing' => self::SHORT_TTL,
            'static' => self::LONG_TTL,
            default => self::DEFAULT_TTL
        };

        return self::put($key, $value, $ttl);
    }

    /**
     * Batch cache operations
     */
    public static function putMany(array $items, int $ttl = self::DEFAULT_TTL): bool
    {
        try {
            foreach ($items as $key => $value) {
                Cache::put($key, $value, $ttl);
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get multiple cache items
     */
    public static function getMany(array $keys): array
    {
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = Cache::get($key);
        }
        return $results;
    }
}