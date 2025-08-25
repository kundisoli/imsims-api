<?php

namespace App\Models\PostgreSQL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'warehouse_id',
        'name',
        'code',
        'type',
        'aisle',
        'rack',
        'shelf',
        'bin',
        'barcode',
        'is_active',
        'capacity',
        'temperature_controlled',
        'hazardous_materials'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'temperature_controlled' => 'boolean',
        'hazardous_materials' => 'boolean',
        'capacity' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the warehouse that owns the location
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the inventory records for this location
     */
    public function inventoryRecords(): HasMany
    {
        return $this->hasMany(InventoryRecord::class);
    }

    /**
     * Get the stock movements for this location
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Scope to get only active locations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get full location path
     */
    public function getFullPathAttribute(): string
    {
        $parts = array_filter([
            $this->warehouse->name,
            $this->aisle ? "Aisle {$this->aisle}" : null,
            $this->rack ? "Rack {$this->rack}" : null,
            $this->shelf ? "Shelf {$this->shelf}" : null,
            $this->bin ? "Bin {$this->bin}" : null,
        ]);
        
        return implode(' > ', $parts);
    }

    /**
     * Get current utilization percentage
     */
    public function getUtilizationPercentageAttribute(): float
    {
        if (!$this->capacity) {
            return 0;
        }

        $currentStock = $this->inventoryRecords()->sum('quantity');
        return ($currentStock / $this->capacity) * 100;
    }

    /**
     * Check if location is at capacity
     */
    public function isAtCapacity(): bool
    {
        if (!$this->capacity) {
            return false;
        }

        $currentStock = $this->inventoryRecords()->sum('quantity');
        return $currentStock >= $this->capacity;
    }

    /**
     * Get available capacity
     */
    public function getAvailableCapacityAttribute(): int
    {
        if (!$this->capacity) {
            return 0;
        }

        $currentStock = $this->inventoryRecords()->sum('quantity');
        return max(0, $this->capacity - $currentStock);
    }
}