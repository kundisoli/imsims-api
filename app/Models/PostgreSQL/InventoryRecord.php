<?php

namespace App\Models\PostgreSQL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'location_id',
        'quantity',
        'reserved_quantity',
        'available_quantity',
        'last_counted_at',
        'last_movement_at'
    ];

    protected $casts = [
        'last_counted_at' => 'datetime',
        'last_movement_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the product that owns the inventory record
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse that owns the inventory record
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the location that owns the inventory record
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Calculate available quantity
     */
    public function getAvailableQuantityAttribute(): int
    {
        return $this->quantity - $this->reserved_quantity;
    }

    /**
     * Update available quantity after movement
     */
    public function updateAvailableQuantity(): void
    {
        $this->update([
            'available_quantity' => $this->quantity - $this->reserved_quantity,
            'last_movement_at' => now()
        ]);
    }

    /**
     * Reserve stock
     */
    public function reserveStock(int $quantity): bool
    {
        if ($this->available_quantity >= $quantity) {
            $this->increment('reserved_quantity', $quantity);
            $this->updateAvailableQuantity();
            return true;
        }
        return false;
    }

    /**
     * Release reserved stock
     */
    public function releaseStock(int $quantity): void
    {
        $this->decrement('reserved_quantity', min($quantity, $this->reserved_quantity));
        $this->updateAvailableQuantity();
    }
}