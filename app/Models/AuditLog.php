<?php

namespace App\Models;

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
        'performed_at',
        'additional_data',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'additional_data' => 'array',
        'performed_at' => 'datetime',
    ];

    protected $dates = [
        'performed_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the user who performed the action.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Create an audit log entry.
     */
    public static function log(string $action, string $modelType, int $modelId, array $oldValues = [], array $newValues = [], array $additionalData = []): self
    {
        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'performed_at' => now(),
            'additional_data' => $additionalData,
        ]);
    }

    /**
     * Log a create action.
     */
    public static function logCreate(string $modelType, int $modelId, array $newValues, array $additionalData = []): self
    {
        return self::log('create', $modelType, $modelId, [], $newValues, $additionalData);
    }

    /**
     * Log an update action.
     */
    public static function logUpdate(string $modelType, int $modelId, array $oldValues, array $newValues, array $additionalData = []): self
    {
        return self::log('update', $modelType, $modelId, $oldValues, $newValues, $additionalData);
    }

    /**
     * Log a delete action.
     */
    public static function logDelete(string $modelType, int $modelId, array $oldValues, array $additionalData = []): self
    {
        return self::log('delete', $modelType, $modelId, $oldValues, [], $additionalData);
    }

    /**
     * Log a stock movement.
     */
    public static function logStockMovement(int $productId, string $type, int $quantity, string $reason, array $additionalData = []): self
    {
        return self::log('stock_movement', 'Product', $productId, [], [
            'type' => $type,
            'quantity' => $quantity,
            'reason' => $reason,
        ], $additionalData);
    }
}