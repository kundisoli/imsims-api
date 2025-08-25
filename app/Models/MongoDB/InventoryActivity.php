<?php

namespace App\Models\MongoDB;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\SoftDeletes;

class InventoryActivity extends Model
{
    use SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'inventory_activities';

    protected $fillable = [
        'product_id',
        'user_id',
        'warehouse_id',
        'location_id',
        'activity_type',
        'description',
        'quantity_before',
        'quantity_after',
        'quantity_changed',
        'reference_type',
        'reference_id',
        'metadata',
        'ip_address',
        'user_agent',
        'timestamp'
    ];

    protected $casts = [
        'metadata' => 'array',
        'timestamp' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $dates = [
        'timestamp',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * Log inventory activity
     */
    public static function logActivity(array $data): self
    {
        $data['timestamp'] = now();
        $data['ip_address'] = request()->ip();
        $data['user_agent'] = request()->userAgent();

        return static::create($data);
    }

    /**
     * Get activities by product
     */
    public static function getByProduct(int $productId, int $limit = 50)
    {
        return static::where('product_id', $productId)
            ->orderBy('timestamp', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get activities by date range
     */
    public static function getByDateRange($startDate, $endDate)
    {
        return static::whereBetween('timestamp', [$startDate, $endDate])
            ->orderBy('timestamp', 'desc')
            ->get();
    }

    /**
     * Get activities by user
     */
    public static function getByUser(int $userId, int $limit = 100)
    {
        return static::where('user_id', $userId)
            ->orderBy('timestamp', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get activity statistics
     */
    public static function getStatistics($startDate = null, $endDate = null): array
    {
        $query = static::query();

        if ($startDate && $endDate) {
            $query->whereBetween('timestamp', [$startDate, $endDate]);
        }

        $activities = $query->get();

        return [
            'total_activities' => $activities->count(),
            'activities_by_type' => $activities->groupBy('activity_type')->map->count(),
            'most_active_products' => $activities->groupBy('product_id')->map->count()->sortDesc()->take(10),
            'most_active_users' => $activities->groupBy('user_id')->map->count()->sortDesc()->take(10),
        ];
    }
}