<?php

namespace App\Models;

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
        'price',
        'cost_price',
        'category_id',
        'supplier_id',
        'minimum_stock',
        'maximum_stock',
        'unit_of_measure',
        'weight',
        'dimensions',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'minimum_stock' => 'integer',
        'maximum_stock' => 'integer',
        'weight' => 'decimal:3',
        'dimensions' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the supplier that owns the product.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the stock records for the product.
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * Get the stock movements for the product.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get the current stock quantity.
     */
    public function getCurrentStock(): int
    {
        return $this->stocks()->sum('quantity');
    }

    /**
     * Check if product is low on stock.
     */
    public function isLowStock(): bool
    {
        return $this->getCurrentStock() <= $this->minimum_stock;
    }

    /**
     * Check if product is overstocked.
     */
    public function isOverstocked(): bool
    {
        return $this->getCurrentStock() >= $this->maximum_stock;
    }

    /**
     * Get the profit margin.
     */
    public function getProfitMargin(): float
    {
        if ($this->cost_price == 0) {
            return 0;
        }
        
        return (($this->price - $this->cost_price) / $this->cost_price) * 100;
    }
}
