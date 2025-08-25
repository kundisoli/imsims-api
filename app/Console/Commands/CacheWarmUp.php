<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CacheService;

class CacheWarmUp extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cache:warm-up {--force : Force cache refresh}';

    /**
     * The console command description.
     */
    protected $description = 'Warm up application caches for better performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cache warm-up process...');

        if ($this->option('force')) {
            $this->info('Clearing existing caches...');
            CacheService::clearInventoryCaches();
            CacheService::clearProductCaches();
            CacheService::clearCategoryCaches();
            CacheService::clearWarehouseCaches();
            CacheService::clearReportCaches();
        }

        $this->info('Warming up caches...');
        
        $bar = $this->output->createProgressBar(4);
        $bar->start();

        // Warm up category tree
        $this->warmUpCategories();
        $bar->advance();

        // Warm up low stock products
        $this->warmUpLowStockProducts();
        $bar->advance();

        // Warm up inventory overview
        $this->warmUpInventoryOverview();
        $bar->advance();

        // Warm up warehouse summaries
        $this->warmUpWarehouseSummaries();
        $bar->advance();

        $bar->finish();
        $this->newLine();

        $results = CacheService::warmUpCaches();
        
        if (isset($results['error'])) {
            $this->error('Cache warm-up failed: ' . $results['error']);
            return Command::FAILURE;
        }

        $this->info('Cache warm-up completed successfully!');
        
        // Display results
        foreach ($results as $key => $status) {
            if ($key !== 'error') {
                $this->line("✓ {$key}: {$status}");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Warm up category caches
     */
    private function warmUpCategories(): void
    {
        try {
            $categories = \App\Models\PostgreSQL\Category::active()
                ->with(['children' => function ($query) {
                    $query->active()->orderBy('sort_order')->orderBy('name');
                }])
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            CacheService::cacheCategoryTree($categories, CacheService::LONG_TTL);
            
        } catch (\Exception $e) {
            $this->warn("Failed to warm up categories: {$e->getMessage()}");
        }
    }

    /**
     * Warm up low stock products cache
     */
    private function warmUpLowStockProducts(): void
    {
        try {
            $lowStockProducts = \App\Models\PostgreSQL\Product::lowStock()
                ->with(['category', 'supplier', 'inventoryRecords'])
                ->active()
                ->get();

            CacheService::cacheLowStockProducts($lowStockProducts, CacheService::SHORT_TTL);
            
        } catch (\Exception $e) {
            $this->warn("Failed to warm up low stock products: {$e->getMessage()}");
        }
    }

    /**
     * Warm up inventory overview cache
     */
    private function warmUpInventoryOverview(): void
    {
        try {
            $totalProducts = \App\Models\PostgreSQL\Product::active()->count();
            $lowStockCount = \App\Models\PostgreSQL\Product::lowStock()->count();
            $outOfStockCount = \App\Models\PostgreSQL\InventoryRecord::where('quantity', 0)->count();
            
            $totalValue = \DB::table('inventory_records')
                ->join('products', 'inventory_records.product_id', '=', 'products.id')
                ->sum(\DB::raw('inventory_records.quantity * products.cost_price'));

            $overview = [
                'total_products' => $totalProducts,
                'total_inventory_value' => $totalValue,
                'low_stock_products' => $lowStockCount,
                'out_of_stock_products' => $outOfStockCount,
                'cache_warmed_at' => now()
            ];

            CacheService::cacheInventoryOverview($overview, CacheService::SHORT_TTL);
            
        } catch (\Exception $e) {
            $this->warn("Failed to warm up inventory overview: {$e->getMessage()}");
        }
    }

    /**
     * Warm up warehouse summary caches
     */
    private function warmUpWarehouseSummaries(): void
    {
        try {
            $warehouses = \App\Models\PostgreSQL\Warehouse::active()->get();
            
            foreach ($warehouses as $warehouse) {
                $summary = [
                    'warehouse_id' => $warehouse->id,
                    'warehouse_name' => $warehouse->name,
                    'total_products' => $warehouse->total_products,
                    'total_inventory_value' => $warehouse->total_inventory_value,
                    'cache_warmed_at' => now()
                ];

                CacheService::cacheWarehouseInventory($warehouse->id, $summary, CacheService::DEFAULT_TTL);
            }
            
        } catch (\Exception $e) {
            $this->warn("Failed to warm up warehouse summaries: {$e->getMessage()}");
        }
    }
}