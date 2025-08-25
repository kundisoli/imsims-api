<?php

namespace App\Models\MongoDB;

use MongoDB\Laravel\Eloquent\Model;

class AuditLog extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'audit_logs';

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'timestamp',
        'session_id',
        'request_id'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
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
     * Log an audit event
     */
    public static function logEvent(array $data): self
    {
        $data = array_merge($data, [
            'timestamp' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'session_id' => session()->getId(),
            'request_id' => request()->header('X-Request-ID', uniqid())
        ]);

        return static::create($data);
    }

    /**
     * Log model creation
     */
    public static function logCreated($model, array $attributes = []): self
    {
        return static::logEvent([
            'user_id' => auth()->id(),
            'action' => 'created',
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'new_values' => $attributes ?: $model->getAttributes()
        ]);
    }

    /**
     * Log model update
     */
    public static function logUpdated($model, array $oldValues = [], array $newValues = []): self
    {
        return static::logEvent([
            'user_id' => auth()->id(),
            'action' => 'updated',
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'old_values' => $oldValues ?: $model->getOriginal(),
            'new_values' => $newValues ?: $model->getChanges()
        ]);
    }

    /**
     * Log model deletion
     */
    public static function logDeleted($model): self
    {
        return static::logEvent([
            'user_id' => auth()->id(),
            'action' => 'deleted',
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'old_values' => $model->getAttributes()
        ]);
    }

    /**
     * Get audit trail for a model
     */
    public static function getModelAuditTrail(string $modelType, $modelId, int $limit = 50)
    {
        return static::where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->orderBy('timestamp', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get user activity logs
     */
    public static function getUserActivity(int $userId, int $limit = 100)
    {
        return static::where('user_id', $userId)
            ->orderBy('timestamp', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit statistics
     */
    public static function getStatistics($startDate = null, $endDate = null): array
    {
        $query = static::query();

        if ($startDate && $endDate) {
            $query->whereBetween('timestamp', [$startDate, $endDate]);
        }

        $logs = $query->get();

        return [
            'total_actions' => $logs->count(),
            'actions_by_type' => $logs->groupBy('action')->map->count(),
            'models_by_type' => $logs->groupBy('model_type')->map->count(),
            'most_active_users' => $logs->groupBy('user_id')->map->count()->sortDesc()->take(10),
        ];
    }
}