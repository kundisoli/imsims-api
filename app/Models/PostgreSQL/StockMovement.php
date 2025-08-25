<?php

namespace App\Models\PostgreSQL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    const TYPE_IN = 'in';
    const TYPE_OUT = 'out';
    const TYPE_TRANSFER = 'transfer';
    const TYPE_ADJUSTMENT = 'adjustment';

    const REASON_PURCHASE = 'purchase';
    const REASON_SALE = 'sale';
    const REASON_RETURN = 'return';
    const REASON_DAMAGE = 'damage';
    const REASON_THEFT = 'theft';
    const REASON_EXPIRED = 'expired';
    const REASON_TRANSFER = 'transfer';
    const REASON_ADJUSTMENT = 'adjustment';
    const REASON_INITIAL = 'initial';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'location_id',
        'user_id',
        'type',
        'reason',
        'quantity',
        'reference_type',
        'reference_id',
        'notes',
        'unit_cost',
        'total_cost'
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the product that owns the stock movement
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse that owns the stock movement
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the location that owns the stock movement
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the user that created the stock movement
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Get the reference model (polymorphic)
     */
    public function reference()
    {
        return $this->morphTo();
    }

    /**
     * Scope for inbound movements
     */
    public function scopeInbound($query)
    {
        return $query->where('type', self::TYPE_IN);
    }

    /**
     * Scope for outbound movements
     */
    public function scopeOutbound($query)
    {
        return $query->where('type', self::TYPE_OUT);
    }

    /**
     * Scope for movements by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get movement types
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_IN => 'Stock In',
            self::TYPE_OUT => 'Stock Out',
            self::TYPE_TRANSFER => 'Transfer',
            self::TYPE_ADJUSTMENT => 'Adjustment'
        ];
    }

    /**
     * Get movement reasons
     */
    public static function getReasons(): array
    {
        return [
            self::REASON_PURCHASE => 'Purchase',
            self::REASON_SALE => 'Sale',
            self::REASON_RETURN => 'Return',
            self::REASON_DAMAGE => 'Damage',
            self::REASON_THEFT => 'Theft',
            self::REASON_EXPIRED => 'Expired',
            self::REASON_TRANSFER => 'Transfer',
            self::REASON_ADJUSTMENT => 'Adjustment',
            self::REASON_INITIAL => 'Initial Stock'
        ];
    }
}