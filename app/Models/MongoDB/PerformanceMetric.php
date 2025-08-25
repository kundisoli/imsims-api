<?php

namespace App\Models\MongoDB;

use MongoDB\Laravel\Eloquent\Model;

class PerformanceMetric extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'performance_metrics';

    protected $fillable = [
        'endpoint',
        'method',
        'response_time',
        'memory_usage',
        'cpu_usage',
        'query_count',
        'cache_hits',
        'cache_misses',
        'status_code',
        'user_id',
        'ip_address',
        'timestamp',
        'additional_data'
    ];

    protected $casts = [
        'response_time' => 'float',
        'memory_usage' => 'integer',
        'cpu_usage' => 'float',
        'query_count' => 'integer',
        'cache_hits' => 'integer',
        'cache_misses' => 'integer',
        'status_code' => 'integer',
        'additional_data' => 'array',
        'timestamp' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $dates = [
        'timestamp',
        'created_at',
        'updated_at'
    ];

    /**
     * Log performance metric
     */
    public static function logMetric(array $data): self
    {
        $data['timestamp'] = now();
        return static::create($data);
    }

    /**
     * Get metrics by endpoint
     */
    public static function getByEndpoint(string $endpoint, int $limit = 100)
    {
        return static::where('endpoint', $endpoint)
            ->orderBy('timestamp', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get slow queries (response time > threshold)
     */
    public static function getSlowQueries(float $threshold = 1000, int $limit = 50)
    {
        return static::where('response_time', '>', $threshold)
            ->orderBy('response_time', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get performance statistics
     */
    public static function getStatistics($startDate = null, $endDate = null): array
    {
        $query = static::query();

        if ($startDate && $endDate) {
            $query->whereBetween('timestamp', [$startDate, $endDate]);
        }

        $metrics = $query->get();

        if ($metrics->isEmpty()) {
            return [
                'total_requests' => 0,
                'avg_response_time' => 0,
                'max_response_time' => 0,
                'min_response_time' => 0,
                'avg_memory_usage' => 0,
                'avg_query_count' => 0,
                'cache_hit_rate' => 0,
                'endpoints_by_popularity' => [],
                'slowest_endpoints' => []
            ];
        }

        $totalCacheRequests = $metrics->sum(fn($m) => $m->cache_hits + $m->cache_misses);
        $cacheHitRate = $totalCacheRequests > 0 ? ($metrics->sum('cache_hits') / $totalCacheRequests) * 100 : 0;

        return [
            'total_requests' => $metrics->count(),
            'avg_response_time' => $metrics->avg('response_time'),
            'max_response_time' => $metrics->max('response_time'),
            'min_response_time' => $metrics->min('response_time'),
            'avg_memory_usage' => $metrics->avg('memory_usage'),
            'avg_query_count' => $metrics->avg('query_count'),
            'cache_hit_rate' => $cacheHitRate,
            'endpoints_by_popularity' => $metrics->groupBy('endpoint')->map->count()->sortDesc()->take(10),
            'slowest_endpoints' => $metrics->groupBy('endpoint')->map(fn($group) => $group->avg('response_time'))->sortDesc()->take(10)
        ];
    }

    /**
     * Get hourly performance trends
     */
    public static function getHourlyTrends($date = null): array
    {
        $date = $date ?: now()->format('Y-m-d');
        
        $metrics = static::whereDate('timestamp', $date)->get();
        
        $hourlyData = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $hourMetrics = $metrics->filter(function ($metric) use ($hour) {
                return $metric->timestamp->hour === $hour;
            });

            $hourlyData[$hour] = [
                'hour' => $hour,
                'request_count' => $hourMetrics->count(),
                'avg_response_time' => $hourMetrics->avg('response_time') ?: 0,
                'avg_memory_usage' => $hourMetrics->avg('memory_usage') ?: 0,
                'error_rate' => $hourMetrics->where('status_code', '>=', 400)->count() / max($hourMetrics->count(), 1) * 100
            ];
        }

        return $hourlyData;
    }

    /**
     * Clean old metrics (older than specified days)
     */
    public static function cleanOldMetrics(int $daysToKeep = 30): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        return static::where('timestamp', '<', $cutoffDate)->delete();
    }
}