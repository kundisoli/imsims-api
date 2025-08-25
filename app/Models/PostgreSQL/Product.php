<?php

namespace App\Models\PostgreSQL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'sku',
        'barcode',
        'category_id',
        'supplier_id',
        'cost_price',
        'selling_price',
        'min_stock_level',
        'max_stock_level',
        'reorder_point',
        'unit_of_measure',
        'weight',
        'dimensions',
        'is_active',
        'is_trackable'
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'dimensions' => 'json',
        'is_active' => 'boolean',
        'is_trackable' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the category that owns the product
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the supplier that owns the product
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the inventory records for this product
     */
    public function inventoryRecords(): HasMany
    {
        return $this->hasMany(InventoryRecord::class);
    }

    /**
     * Get the stock movements for this product
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Scope to get only active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get products with low stock
     */
    public function scopeLowStock($query)
    {
        return $query->whereHas('inventoryRecords', function ($q) {
            $q->whereRaw('quantity <= reorder_point');
        });
    }

    /**
     * Get current stock quantity
     */
    public function getCurrentStockAttribute(): int
    {
        return $this->inventoryRecords()
            ->sum('quantity');
    }

    /**
     * Get profit margin
     */
    public function getProfitMarginAttribute(): float
    {
        if ($this->cost_price > 0) {
            return (($this->selling_price - $this->cost_price) / $this->cost_price) * 100;
        }
        return 0;
    }

    /**
     * Check if product is low on stock
     */
    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->reorder_point;
    }
}