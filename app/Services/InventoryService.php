<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class InventoryService
{
    /**
     * Add stock for a product.
     */
    public function addStock(int $productId, int $quantity, string $location = 'main', array $options = []): StockMovement
    {
        return DB::transaction(function () use ($productId, $quantity, $location, $options) {
            $product = Product::findOrFail($productId);
            
            $stock = Stock::firstOrCreate([
                'product_id' => $productId,
                'location' => $location,
                'batch_number' => $options['batch_number'] ?? null,
            ], [
                'quantity' => 0,
                'cost_per_unit' => $options['cost_per_unit'] ?? $product->cost_price,
                'expiry_date' => $options['expiry_date'] ?? null,
            ]);

            $stock->increment('quantity', $quantity);

            $movement = StockMovement::create([
                'product_id' => $productId,
                'stock_id' => $stock->id,
                'user_id' => auth()->id(),
                'type' => StockMovement::TYPE_IN,
                'quantity' => $quantity,
                'reason' => $options['reason'] ?? StockMovement::REASON_PURCHASE,
                'reference_number' => $options['reference_number'] ?? null,
                'notes' => $options['notes'] ?? null,
                'cost_per_unit' => $stock->cost_per_unit,
                'total_cost' => $quantity * $stock->cost_per_unit,
                'location_to' => $location,
                'performed_at' => now(),
            ]);

            // Log the operation
            AuditLog::logStockMovement($productId, 'in', $quantity, $movement->reason);

            // Clear cache
            $this->clearProductCache($productId);

            return $movement;
        });
    }

    /**
     * Remove stock for a product using FIFO method.
     */
    public function removeStock(int $productId, int $quantity, string $location = 'main', array $options = []): array
    {
        return DB::transaction(function () use ($productId, $quantity, $location, $options) {
            $product = Product::findOrFail($productId);
            
            // Check available stock
            $availableStock = $this->getAvailableStock($productId, $location);
            if ($availableStock < $quantity) {
                throw new \Exception("Insufficient stock. Available: {$availableStock}, Requested: {$quantity}");
            }

            $movements = [];
            $remainingQuantity = $quantity;

            // Get stocks using FIFO (First In, First Out)
            $stocks = Stock::where('product_id', $productId)
                ->where('location', $location)
                ->where('quantity', '>', 0)
                ->orderBy('created_at')
                ->get();

            foreach ($stocks as $stock) {
                if ($remainingQuantity <= 0) break;

                $deductQuantity = min($stock->quantity, $remainingQuantity);
                $stock->decrement('quantity', $deductQuantity);

                $movement = StockMovement::create([
                    'product_id' => $productId,
                    'stock_id' => $stock->id,
                    'user_id' => auth()->id(),
                    'type' => StockMovement::TYPE_OUT,
                    'quantity' => -$deductQuantity,
                    'reason' => $options['reason'] ?? StockMovement::REASON_SALE,
                    'reference_number' => $options['reference_number'] ?? null,
                    'notes' => $options['notes'] ?? null,
                    'cost_per_unit' => $stock->cost_per_unit,
                    'total_cost' => $deductQuantity * $stock->cost_per_unit,
                    'location_from' => $location,
                    'performed_at' => now(),
                ]);

                $movements[] = $movement;
                $remainingQuantity -= $deductQuantity;

                // Log each movement
                AuditLog::logStockMovement($productId, 'out', $deductQuantity, $movement->reason);
            }

            // Clear cache
            $this->clearProductCache($productId);

            return $movements;
        });
    }

    /**
     * Transfer stock between locations.
     */
    public function transferStock(int $productId, int $quantity, string $fromLocation, string $toLocation, array $options = []): array
    {
        return DB::transaction(function () use ($productId, $quantity, $fromLocation, $toLocation, $options) {
            if ($fromLocation === $toLocation) {
                throw new \Exception('Source and destination locations cannot be the same');
            }

            // Remove from source location
            $outMovements = $this->removeStock($productId, $quantity, $fromLocation, [
                'reason' => StockMovement::REASON_TRANSFER,
                'reference_number' => $options['reference_number'] ?? null,
                'notes' => $options['notes'] ?? "Transfer to {$toLocation}",
            ]);

            // Add to destination location
            $inMovement = $this->addStock($productId, $quantity, $toLocation, [
                'reason' => StockMovement::REASON_TRANSFER,
                'reference_number' => $options['reference_number'] ?? null,
                'notes' => $options['notes'] ?? "Transfer from {$fromLocation}",
                'cost_per_unit' => $outMovements[0]->cost_per_unit ?? null,
            ]);

            return [
                'out_movements' => $outMovements,
                'in_movement' => $inMovement,
            ];
        });
    }

    /**
     * Adjust stock quantity (can be positive or negative).
     */
    public function adjustStock(int $productId, int $adjustment, string $location = 'main', array $options = []): StockMovement
    {
        return DB::transaction(function () use ($productId, $adjustment, $location, $options) {
            $product = Product::findOrFail($productId);
            
            $stock = Stock::firstOrCreate([
                'product_id' => $productId,
                'location' => $location,
                'batch_number' => $options['batch_number'] ?? null,
            ], [
                'quantity' => 0,
                'cost_per_unit' => $options['cost_per_unit'] ?? $product->cost_price,
            ]);

            $stock->increment('quantity', $adjustment);

            $movement = StockMovement::create([
                'product_id' => $productId,
                'stock_id' => $stock->id,
                'user_id' => auth()->id(),
                'type' => StockMovement::TYPE_ADJUSTMENT,
                'quantity' => $adjustment,
                'reason' => $options['reason'] ?? StockMovement::REASON_ADJUSTMENT,
                'reference_number' => $options['reference_number'] ?? null,
                'notes' => $options['notes'] ?? null,
                'cost_per_unit' => $stock->cost_per_unit,
                'total_cost' => abs($adjustment) * $stock->cost_per_unit,
                'location_to' => $adjustment > 0 ? $location : null,
                'location_from' => $adjustment < 0 ? $location : null,
                'performed_at' => now(),
            ]);

            // Log the operation
            AuditLog::logStockMovement($productId, 'adjustment', $adjustment, $movement->reason);

            // Clear cache
            $this->clearProductCache($productId);

            return $movement;
        });
    }

    /**
     * Get available stock for a product at a specific location.
     */
    public function getAvailableStock(int $productId, string $location = null): int
    {
        $cacheKey = "stock:available:{$productId}:" . ($location ?? 'all');

        return Cache::remember($cacheKey, 300, function () use ($productId, $location) {
            $query = Stock::where('product_id', $productId);
            
            if ($location) {
                $query->where('location', $location);
            }

            return $query->sum('quantity');
        });
    }

    /**
     * Get stock breakdown by location for a product.
     */
    public function getStockByLocation(int $productId): array
    {
        $cacheKey = "stock:by_location:{$productId}";

        return Cache::remember($cacheKey, 600, function () use ($productId) {
            return Stock::where('product_id', $productId)
                ->selectRaw('location, SUM(quantity) as total_quantity, COUNT(*) as batch_count')
                ->groupBy('location')
                ->get()
                ->toArray();
        });
    }

    /**
     * Get products with low stock.
     */
    public function getLowStockProducts(): array
    {
        $cacheKey = 'inventory:low_stock';

        return Cache::remember($cacheKey, 600, function () {
            return Product::with(['category', 'supplier'])
                ->whereHas('stocks', function ($query) {
                    $query->selectRaw('product_id, SUM(quantity) as total_quantity')
                          ->groupBy('product_id')
                          ->havingRaw('SUM(quantity) <= products.minimum_stock');
                })
                ->get()
                ->toArray();
        });
    }

    /**
     * Get products that are expiring soon.
     */
    public function getExpiringSoonProducts(int $days = 30): array
    {
        $cacheKey = "inventory:expiring_soon:{$days}";

        return Cache::remember($cacheKey, 600, function () use ($days) {
            return Stock::with(['product'])
                ->where('expiry_date', '<=', now()->addDays($days))
                ->where('expiry_date', '>', now())
                ->where('quantity', '>', 0)
                ->orderBy('expiry_date')
                ->get()
                ->toArray();
        });
    }

    /**
     * Get expired products.
     */
    public function getExpiredProducts(): array
    {
        $cacheKey = 'inventory:expired';

        return Cache::remember($cacheKey, 300, function () {
            return Stock::with(['product'])
                ->where('expiry_date', '<', now())
                ->where('quantity', '>', 0)
                ->orderBy('expiry_date')
                ->get()
                ->toArray();
        });
    }

    /**
     * Calculate inventory valuation.
     */
    public function calculateInventoryValuation(): array
    {
        $cacheKey = 'inventory:valuation';

        return Cache::remember($cacheKey, 1800, function () {
            $costValuation = Stock::join('products', 'stocks.product_id', '=', 'products.id')
                ->sum(DB::raw('stocks.quantity * stocks.cost_per_unit'));

            $sellingValuation = Stock::join('products', 'stocks.product_id', '=', 'products.id')
                ->sum(DB::raw('stocks.quantity * products.price'));

            return [
                'cost_valuation' => $costValuation,
                'selling_valuation' => $sellingValuation,
                'potential_profit' => $sellingValuation - $costValuation,
                'profit_margin' => $costValuation > 0 ? (($sellingValuation - $costValuation) / $costValuation) * 100 : 0,
            ];
        });
    }

    /**
     * Reserve stock for orders.
     */
    public function reserveStock(int $productId, int $quantity, string $location = 'main', string $reference = null): bool
    {
        return DB::transaction(function () use ($productId, $quantity, $location, $reference) {
            $availableStock = $this->getAvailableStock($productId, $location);
            
            if ($availableStock < $quantity) {
                return false;
            }

            $stocks = Stock::where('product_id', $productId)
                ->where('location', $location)
                ->where('quantity', '>', 0)
                ->orderBy('created_at')
                ->get();

            $remainingQuantity = $quantity;
            foreach ($stocks as $stock) {
                if ($remainingQuantity <= 0) break;

                $reserveQuantity = min($stock->quantity, $remainingQuantity);
                $stock->increment('reserved_quantity', $reserveQuantity);
                $stock->decrement('quantity', $reserveQuantity);
                
                $remainingQuantity -= $reserveQuantity;
            }

            // Log the reservation
            AuditLog::log('reserve_stock', 'Product', $productId, [], [
                'quantity' => $quantity,
                'location' => $location,
                'reference' => $reference,
            ]);

            $this->clearProductCache($productId);
            return true;
        });
    }

    /**
     * Release reserved stock.
     */
    public function releaseReservedStock(int $productId, int $quantity, string $location = 'main', string $reference = null): bool
    {
        return DB::transaction(function () use ($productId, $quantity, $location, $reference) {
            $stocks = Stock::where('product_id', $productId)
                ->where('location', $location)
                ->where('reserved_quantity', '>', 0)
                ->orderBy('created_at')
                ->get();

            $remainingQuantity = $quantity;
            foreach ($stocks as $stock) {
                if ($remainingQuantity <= 0) break;

                $releaseQuantity = min($stock->reserved_quantity, $remainingQuantity);
                $stock->decrement('reserved_quantity', $releaseQuantity);
                $stock->increment('quantity', $releaseQuantity);
                
                $remainingQuantity -= $releaseQuantity;
            }

            // Log the release
            AuditLog::log('release_stock', 'Product', $productId, [], [
                'quantity' => $quantity,
                'location' => $location,
                'reference' => $reference,
            ]);

            $this->clearProductCache($productId);
            return true;
        });
    }

    /**
     * Clear product-related cache.
     */
    private function clearProductCache(int $productId): void
    {
        Cache::forget("stock:available:{$productId}:all");
        Cache::forget("stock:by_location:{$productId}");
        Cache::forget("product:{$productId}:stock_levels");
        Cache::forget("product:{$productId}:details");
        Cache::forget('inventory:low_stock');
        Cache::forget('inventory:valuation');
        
        // Clear location-specific cache
        $locations = Stock::where('product_id', $productId)->distinct()->pluck('location');
        foreach ($locations as $location) {
            Cache::forget("stock:available:{$productId}:{$location}");
        }
    }
}