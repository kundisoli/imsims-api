<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    const TYPE_IN = 'in';
    const TYPE_OUT = 'out';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_TRANSFER = 'transfer';

    const REASON_PURCHASE = 'purchase';
    const REASON_SALE = 'sale';
    const REASON_RETURN = 'return';
    const REASON_DAMAGED = 'damaged';
    const REASON_EXPIRED = 'expired';
    const REASON_LOST = 'lost';
    const REASON_FOUND = 'found';
    const REASON_TRANSFER = 'transfer';
    const REASON_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'product_id',
        'stock_id',
        'user_id',
        'type',
        'quantity',
        'reason',
        'reference_number',
        'notes',
        'cost_per_unit',
        'total_cost',
        'location_from',
        'location_to',
        'performed_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'cost_per_unit' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'performed_at' => 'datetime',
    ];

    /**
     * Get the product that owns the stock movement.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the stock record associated with the movement.
     */
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    /**
     * Get the user who performed the movement.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all available movement types.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_IN => 'Stock In',
            self::TYPE_OUT => 'Stock Out',
            self::TYPE_ADJUSTMENT => 'Adjustment',
            self::TYPE_TRANSFER => 'Transfer',
        ];
    }

    /**
     * Get all available movement reasons.
     */
    public static function getReasons(): array
    {
        return [
            self::REASON_PURCHASE => 'Purchase',
            self::REASON_SALE => 'Sale',
            self::REASON_RETURN => 'Return',
            self::REASON_DAMAGED => 'Damaged',
            self::REASON_EXPIRED => 'Expired',
            self::REASON_LOST => 'Lost',
            self::REASON_FOUND => 'Found',
            self::REASON_TRANSFER => 'Transfer',
            self::REASON_ADJUSTMENT => 'Adjustment',
        ];
    }

    /**
     * Check if the movement is inbound.
     */
    public function isInbound(): bool
    {
        return in_array($this->type, [self::TYPE_IN, self::TYPE_ADJUSTMENT]) && $this->quantity > 0;
    }

    /**
     * Check if the movement is outbound.
     */
    public function isOutbound(): bool
    {
        return in_array($this->type, [self::TYPE_OUT, self::TYPE_ADJUSTMENT]) && $this->quantity < 0;
    }
}
