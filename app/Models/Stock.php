<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'location',
        'quantity',
        'reserved_quantity',
        'minimum_quantity',
        'maximum_quantity',
        'batch_number',
        'expiry_date',
        'cost_per_unit',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'minimum_quantity' => 'integer',
        'maximum_quantity' => 'integer',
        'expiry_date' => 'date',
        'cost_per_unit' => 'decimal:2',
    ];

    /**
     * Get the product that owns the stock.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the available quantity (total - reserved).
     */
    public function getAvailableQuantityAttribute(): int
    {
        return $this->quantity - $this->reserved_quantity;
    }

    /**
     * Get the total value of this stock.
     */
    public function getTotalValueAttribute(): float
    {
        return $this->quantity * $this->cost_per_unit;
    }

    /**
     * Check if the stock is expired.
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if the stock is expiring soon (within 30 days).
     */
    public function isExpiringSoon(): bool
    {
        return $this->expiry_date && $this->expiry_date->diffInDays(now()) <= 30;
    }

    /**
     * Check if the stock is low.
     */
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->minimum_quantity;
    }

    /**
     * Check if the stock is overstocked.
     */
    public function isOverstocked(): bool
    {
        return $this->quantity >= $this->maximum_quantity;
    }
}
