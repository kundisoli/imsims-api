<?php

namespace App\Models\PostgreSQL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'received_quantity',
        'notes'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'received_quantity' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the purchase order that owns this item
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the product for this item
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calculate total price on save
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->total_price = $item->quantity * $item->unit_price;
        });
    }

    /**
     * Get remaining quantity to receive
     */
    public function getRemainingQuantityAttribute(): int
    {
        return $this->quantity - $this->received_quantity;
    }

    /**
     * Check if item is fully received
     */
    public function isFullyReceived(): bool
    {
        return $this->received_quantity >= $this->quantity;
    }

    /**
     * Get received percentage for this item
     */
    public function getReceivedPercentageAttribute(): float
    {
        if ($this->quantity == 0) {
            return 0;
        }

        return ($this->received_quantity / $this->quantity) * 100;
    }
}