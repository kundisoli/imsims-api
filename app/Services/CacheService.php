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
     * Cache product data with tags.
     */
    public function cacheProduct(int $productId, array $data, int $ttl = self::DEFAULT_TTL): void
    {
        $key = "product:{$productId}:details";
        Cache::put($key, $data, $ttl);
        
        // Add to product cache set for bulk invalidation
        Redis::sadd('cache:products', $key);
        Redis::expire('cache:products', $ttl);
    }

    /**
     * Cache inventory data with optimized TTL.
     */
    public function cacheInventoryData(string $key, $data, int $ttl = self::DEFAULT_TTL): void
    {
        Cache::put($key, $data, $ttl);
        
        // Add to inventory cache set
        Redis::sadd('cache:inventory', $key);
        Redis::expire('cache:inventory', $ttl);
    }

    /**
     * Cache dashboard data with shorter TTL for real-time updates.
     */
    public function cacheDashboardData(string $key, $data): void
    {
        Cache::put($key, $data, self::SHORT_TTL);
        
        // Add to dashboard cache set
        Redis::sadd('cache:dashboard', $key);
        Redis::expire('cache:dashboard', self::SHORT_TTL);
    }

    /**
     * Cache analytics data with longer TTL.
     */
    public function cacheAnalyticsData(string $key, $data): void
    {
        Cache::put($key, $data, self::LONG_TTL);
        
        // Add to analytics cache set
        Redis::sadd('cache:analytics', $key);
        Redis::expire('cache:analytics', self::LONG_TTL);
    }

    /**
     * Get cached data with fallback.
     */
    public function remember(string $key, int $ttl, callable $callback)
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Get cached data or return default.
     */
    public function get(string $key, $default = null)
    {
        return Cache::get($key, $default);
    }

    /**
     * Store data in cache.
     */
    public function put(string $key, $data, int $ttl = self::DEFAULT_TTL): void
    {
        Cache::put($key, $data, $ttl);
    }

    /**
     * Forget specific cache key.
     */
    public function forget(string $key): void
    {
        Cache::forget($key);
    }

    /**
     * Clear all product-related cache.
     */
    public function clearProductCache(int $productId = null): void
    {
        if ($productId) {
            // Clear specific product cache
            $patterns = [
                "product:{$productId}:*",
                "stock:*:{$productId}:*",
                "stock:*:{$productId}",
            ];
            
            foreach ($patterns as $pattern) {
                $this->clearByPattern($pattern);
            }
        } else {
            // Clear all product-related cache
            $this->clearCacheSet('cache:products');
        }
        
        // Clear related cache
        $this->clearInventoryCache();
    }

    /**
     * Clear inventory-related cache.
     */
    public function clearInventoryCache(): void
    {
        $this->clearCacheSet('cache:inventory');
        
        // Clear specific inventory keys
        $keys = [
            'inventory:low_stock',
            'inventory:expired',
            'inventory:valuation',
        ];
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        
        // Clear expiring soon cache with different day patterns
        for ($days = 1; $days <= 90; $days += 7) {
            Cache::forget("inventory:expiring_soon:{$days}");
        }
    }

    /**
     * Clear dashboard cache.
     */
    public function clearDashboardCache(): void
    {
        $this->clearCacheSet('cache:dashboard');
        
        $keys = [
            'dashboard:overview',
            'dashboard:alerts',
            'dashboard:location_summary',
        ];
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        
        // Clear analytics cache with different periods
        $periods = ['7d', '30d', '90d', '1y'];
        foreach ($periods as $period) {
            Cache::forget("dashboard:analytics:{$period}");
        }
    }

    /**
     * Clear stock movement cache.
     */
    public function clearStockMovementCache(): void
    {
        // Clear stock movement statistics cache
        $this->clearByPattern('stock_movements:stats:*');
    }

    /**
     * Clear cache by pattern.
     */
    public function clearByPattern(string $pattern): void
    {
        $keys = Redis::keys($pattern);
        if (!empty($keys)) {
            Redis::del($keys);
        }
    }

    /**
     * Clear cache set members.
     */
    public function clearCacheSet(string $setKey): void
    {
        $members = Redis::smembers($setKey);
        if (!empty($members)) {
            foreach ($members as $key) {
                Cache::forget($key);
            }
            Redis::del($setKey);
        }
    }

    /**
     * Get cache statistics.
     */
    public function getCacheStats(): array
    {
        $info = Redis::info('memory');
        
        return [
            'used_memory' => $info['used_memory_human'] ?? 'N/A',
            'used_memory_peak' => $info['used_memory_peak_human'] ?? 'N/A',
            'total_keys' => $this->getTotalKeys(),
            'cache_sets' => [
                'products' => Redis::scard('cache:products'),
                'inventory' => Redis::scard('cache:inventory'),
                'dashboard' => Redis::scard('cache:dashboard'),
                'analytics' => Redis::scard('cache:analytics'),
            ],
        ];
    }

    /**
     * Get total number of cache keys.
     */
    private function getTotalKeys(): int
    {
        try {
            return Redis::dbsize();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Warm up cache for frequently accessed data.
     */
    public function warmUpCache(): void
    {
        // Warm up low stock products
        $this->remember('inventory:low_stock', self::DEFAULT_TTL, function () {
            return app(InventoryService::class)->getLowStockProducts();
        });

        // Warm up inventory valuation
        $this->remember('inventory:valuation', self::LONG_TTL, function () {
            return app(InventoryService::class)->calculateInventoryValuation();
        });

        // Warm up expiring products
        $this->remember('inventory:expiring_soon:30', self::DEFAULT_TTL, function () {
            return app(InventoryService::class)->getExpiringSoonProducts(30);
        });

        // Warm up dashboard overview
        $this->remember('dashboard:overview', self::SHORT_TTL, function () {
            // This would call the dashboard controller's data methods
            return [];
        });
    }

    /**
     * Clear all application cache.
     */
    public function clearAll(): void
    {
        $this->clearProductCache();
        $this->clearInventoryCache();
        $this->clearDashboardCache();
        $this->clearStockMovementCache();
        
        // Clear all cache sets
        $sets = ['cache:products', 'cache:inventory', 'cache:dashboard', 'cache:analytics'];
        foreach ($sets as $set) {
            Redis::del($set);
        }
        
        // Optionally flush all Redis data (use with caution)
        // Redis::flushdb();
    }

    /**
     * Check if cache is healthy.
     */
    public function isHealthy(): bool
    {
        try {
            Redis::ping();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Optimize cache by removing expired keys.
     */
    public function optimize(): array
    {
        $stats = ['removed_keys' => 0, 'errors' => []];
        
        try {
            // Get all cache sets and check their members
            $sets = ['cache:products', 'cache:inventory', 'cache:dashboard', 'cache:analytics'];
            
            foreach ($sets as $set) {
                $members = Redis::smembers($set);
                foreach ($members as $key) {
                    if (!Cache::has($key)) {
                        Redis::srem($set, $key);
                        $stats['removed_keys']++;
                    }
                }
            }
        } catch (\Exception $e) {
            $stats['errors'][] = $e->getMessage();
        }
        
        return $stats;
    }

    /**
     * Create cache key with namespace.
     */
    public function createKey(string $namespace, ...$parts): string
    {
        $parts = array_filter($parts, function ($part) {
            return $part !== null && $part !== '';
        });
        
        return implode(':', array_merge([$namespace], $parts));
    }

    /**
     * Cache data with automatic cache set management.
     */
    public function cacheWithSet(string $setName, string $key, $data, int $ttl = self::DEFAULT_TTL): void
    {
        Cache::put($key, $data, $ttl);
        Redis::sadd("cache:{$setName}", $key);
        Redis::expire("cache:{$setName}", $ttl);
    }

    /**
     * Get or set cache with automatic key generation.
     */
    public function getOrSet(string $namespace, callable $callback, int $ttl = self::DEFAULT_TTL, ...$keyParts)
    {
        $key = $this->createKey($namespace, ...$keyParts);
        return $this->remember($key, $ttl, $callback);
    }
}