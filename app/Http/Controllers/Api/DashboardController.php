<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private const CACHE_TTL = 1800; // 30 minutes

    /**
     * Get dashboard overview data.
     */
    public function index(): JsonResponse
    {
        $cacheKey = 'dashboard:overview';

        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return [
                'totals' => $this->getTotals(),
                'low_stock_products' => $this->getLowStockProducts(),
                'recent_movements' => $this->getRecentMovements(),
                'top_categories' => $this->getTopCategories(),
                'inventory_value' => $this->getInventoryValue(),
                'expiring_soon' => $this->getExpiringSoonProducts(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get inventory analytics.
     */
    public function analytics(Request $request): JsonResponse
    {
        $period = $request->get('period', '30d');
        $cacheKey = "dashboard:analytics:{$period}";

        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($period) {
            $dateFrom = $this->getDateFromPeriod($period);

            return [
                'stock_movements_trend' => $this->getStockMovementsTrend($dateFrom),
                'inventory_turnover' => $this->getInventoryTurnover($dateFrom),
                'category_distribution' => $this->getCategoryDistribution(),
                'supplier_performance' => $this->getSupplierPerformance($dateFrom),
                'cost_analysis' => $this->getCostAnalysis($dateFrom),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get real-time alerts.
     */
    public function alerts(): JsonResponse
    {
        $cacheKey = 'dashboard:alerts';

        $alerts = Cache::remember($cacheKey, 300, function () { // 5 minutes cache
            $alerts = [];

            // Low stock alerts
            $lowStockCount = Product::whereHas('stocks', function ($query) {
                $query->selectRaw('product_id, SUM(quantity) as total_quantity')
                      ->groupBy('product_id')
                      ->havingRaw('SUM(quantity) <= products.minimum_stock');
            })->count();

            if ($lowStockCount > 0) {
                $alerts[] = [
                    'type' => 'low_stock',
                    'title' => 'Low Stock Alert',
                    'message' => "{$lowStockCount} products are running low on stock",
                    'severity' => 'warning',
                    'count' => $lowStockCount,
                ];
            }

            // Expiring products alerts
            $expiringCount = Stock::where('expiry_date', '<=', now()->addDays(30))
                ->where('expiry_date', '>', now())
                ->where('quantity', '>', 0)
                ->count();

            if ($expiringCount > 0) {
                $alerts[] = [
                    'type' => 'expiring_soon',
                    'title' => 'Products Expiring Soon',
                    'message' => "{$expiringCount} products will expire within 30 days",
                    'severity' => 'warning',
                    'count' => $expiringCount,
                ];
            }

            // Expired products alerts
            $expiredCount = Stock::where('expiry_date', '<', now())
                ->where('quantity', '>', 0)
                ->count();

            if ($expiredCount > 0) {
                $alerts[] = [
                    'type' => 'expired',
                    'title' => 'Expired Products',
                    'message' => "{$expiredCount} products have expired",
                    'severity' => 'danger',
                    'count' => $expiredCount,
                ];
            }

            // Overstocked products alerts
            $overstockedCount = Product::whereHas('stocks', function ($query) {
                $query->selectRaw('product_id, SUM(quantity) as total_quantity')
                      ->groupBy('product_id')
                      ->havingRaw('SUM(quantity) >= products.maximum_stock');
            })->count();

            if ($overstockedCount > 0) {
                $alerts[] = [
                    'type' => 'overstocked',
                    'title' => 'Overstocked Products',
                    'message' => "{$overstockedCount} products are overstocked",
                    'severity' => 'info',
                    'count' => $overstockedCount,
                ];
            }

            return $alerts;
        });

        return response()->json([
            'success' => true,
            'data' => $alerts,
        ]);
    }

    /**
     * Get inventory summary by location.
     */
    public function locationSummary(): JsonResponse
    {
        $cacheKey = 'dashboard:location_summary';

        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return Stock::select('location')
                ->selectRaw('COUNT(DISTINCT product_id) as product_count')
                ->selectRaw('SUM(quantity) as total_quantity')
                ->selectRaw('SUM(quantity * cost_per_unit) as total_value')
                ->groupBy('location')
                ->orderBy('total_value', 'desc')
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get totals for dashboard overview.
     */
    private function getTotals(): array
    {
        return [
            'total_products' => Product::where('is_active', true)->count(),
            'total_categories' => Category::where('is_active', true)->count(),
            'total_suppliers' => Supplier::where('is_active', true)->count(),
            'total_stock_value' => Stock::join('products', 'stocks.product_id', '=', 'products.id')
                ->sum(DB::raw('stocks.quantity * stocks.cost_per_unit')),
            'total_movements_today' => StockMovement::whereDate('performed_at', today())->count(),
        ];
    }

    /**
     * Get low stock products.
     */
    private function getLowStockProducts(): array
    {
        return Product::with(['category', 'supplier'])
            ->whereHas('stocks', function ($query) {
                $query->selectRaw('product_id, SUM(quantity) as total_quantity')
                      ->groupBy('product_id')
                      ->havingRaw('SUM(quantity) <= products.minimum_stock');
            })
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get recent stock movements.
     */
    private function getRecentMovements(): array
    {
        return StockMovement::with(['product:id,name,sku', 'user:id,name'])
            ->select(['id', 'product_id', 'user_id', 'type', 'quantity', 'reason', 'performed_at'])
            ->orderBy('performed_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get top categories by value.
     */
    private function getTopCategories(): array
    {
        return Category::select('categories.id', 'categories.name')
            ->selectRaw('SUM(stocks.quantity * stocks.cost_per_unit) as total_value')
            ->selectRaw('COUNT(DISTINCT products.id) as product_count')
            ->join('products', 'categories.id', '=', 'products.category_id')
            ->join('stocks', 'products.id', '=', 'stocks.product_id')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('total_value', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    /**
     * Get total inventory value.
     */
    private function getInventoryValue(): array
    {
        $totalCost = Stock::sum(DB::raw('quantity * cost_per_unit'));
        $totalSelling = Stock::join('products', 'stocks.product_id', '=', 'products.id')
            ->sum(DB::raw('stocks.quantity * products.price'));

        return [
            'total_cost_value' => $totalCost,
            'total_selling_value' => $totalSelling,
            'potential_profit' => $totalSelling - $totalCost,
            'profit_margin' => $totalCost > 0 ? (($totalSelling - $totalCost) / $totalCost) * 100 : 0,
        ];
    }

    /**
     * Get products expiring soon.
     */
    private function getExpiringSoonProducts(): array
    {
        return Stock::with(['product:id,name,sku'])
            ->select(['product_id', 'batch_number', 'quantity', 'expiry_date'])
            ->where('expiry_date', '<=', now()->addDays(30))
            ->where('expiry_date', '>', now())
            ->where('quantity', '>', 0)
            ->orderBy('expiry_date')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get stock movements trend.
     */
    private function getStockMovementsTrend(string $dateFrom): array
    {
        return StockMovement::selectRaw('DATE(performed_at) as date, type, COUNT(*) as count, SUM(ABS(quantity)) as total_quantity')
            ->where('performed_at', '>=', $dateFrom)
            ->groupBy('date', 'type')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->toArray();
    }

    /**
     * Get inventory turnover.
     */
    private function getInventoryTurnover(string $dateFrom): array
    {
        $outMovements = StockMovement::selectRaw('product_id, SUM(ABS(quantity)) as total_out')
            ->where('type', StockMovement::TYPE_OUT)
            ->where('performed_at', '>=', $dateFrom)
            ->groupBy('product_id')
            ->pluck('total_out', 'product_id');

        $averageInventory = Stock::selectRaw('product_id, AVG(quantity) as avg_quantity')
            ->groupBy('product_id')
            ->pluck('avg_quantity', 'product_id');

        $turnover = [];
        foreach ($outMovements as $productId => $totalOut) {
            $avgInventory = $averageInventory[$productId] ?? 1;
            $turnover[$productId] = $avgInventory > 0 ? $totalOut / $avgInventory : 0;
        }

        return $turnover;
    }

    /**
     * Get category distribution.
     */
    private function getCategoryDistribution(): array
    {
        return Category::select('name')
            ->selectRaw('COUNT(products.id) as product_count')
            ->selectRaw('SUM(stocks.quantity) as total_quantity')
            ->leftJoin('products', 'categories.id', '=', 'products.category_id')
            ->leftJoin('stocks', 'products.id', '=', 'stocks.product_id')
            ->groupBy('categories.id', 'categories.name')
            ->having('product_count', '>', 0)
            ->get()
            ->toArray();
    }

    /**
     * Get supplier performance.
     */
    private function getSupplierPerformance(string $dateFrom): array
    {
        return Supplier::select('suppliers.name')
            ->selectRaw('COUNT(DISTINCT products.id) as product_count')
            ->selectRaw('SUM(stocks.quantity * stocks.cost_per_unit) as total_value')
            ->selectRaw('COUNT(stock_movements.id) as movement_count')
            ->join('products', 'suppliers.id', '=', 'products.supplier_id')
            ->leftJoin('stocks', 'products.id', '=', 'stocks.product_id')
            ->leftJoin('stock_movements', function ($join) use ($dateFrom) {
                $join->on('products.id', '=', 'stock_movements.product_id')
                     ->where('stock_movements.performed_at', '>=', $dateFrom);
            })
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderBy('total_value', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get cost analysis.
     */
    private function getCostAnalysis(string $dateFrom): array
    {
        $movements = StockMovement::selectRaw('type, SUM(total_cost) as total_cost, AVG(cost_per_unit) as avg_cost')
            ->where('performed_at', '>=', $dateFrom)
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        return [
            'stock_in_cost' => $movements[StockMovement::TYPE_IN]->total_cost ?? 0,
            'stock_out_value' => $movements[StockMovement::TYPE_OUT]->total_cost ?? 0,
            'average_cost_per_unit' => $movements->avg('avg_cost') ?? 0,
            'cost_by_type' => $movements->toArray(),
        ];
    }

    /**
     * Get date from period string.
     */
    private function getDateFromPeriod(string $period): string
    {
        return match ($period) {
            '7d' => now()->subDays(7)->format('Y-m-d'),
            '30d' => now()->subDays(30)->format('Y-m-d'),
            '90d' => now()->subDays(90)->format('Y-m-d'),
            '1y' => now()->subYear()->format('Y-m-d'),
            default => now()->subDays(30)->format('Y-m-d'),
        };
    }
}
