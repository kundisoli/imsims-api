<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public routes (if needed for frontend without authentication)
Route::prefix('v1')->group(function () {
    // Authentication routes (you might want to add these later)
    // Route::post('/login', [AuthController::class, 'login']);
    // Route::post('/register', [AuthController::class, 'register']);
});

// Protected routes
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    
    // Dashboard and Analytics
    Route::prefix('dashboard')->controller(DashboardController::class)->group(function () {
        Route::get('/', 'index')->name('dashboard.index');
        Route::get('/analytics', 'analytics')->name('dashboard.analytics');
        Route::get('/alerts', 'alerts')->name('dashboard.alerts');
        Route::get('/location-summary', 'locationSummary')->name('dashboard.location-summary');
    });

    // Categories Management
    Route::apiResource('categories', CategoryController::class);
    Route::prefix('categories')->controller(CategoryController::class)->group(function () {
        Route::get('/{category}/children', 'children')->name('categories.children');
        Route::get('/tree', 'tree')->name('categories.tree');
    });

    // Suppliers Management
    Route::apiResource('suppliers', SupplierController::class);
    Route::prefix('suppliers')->controller(SupplierController::class)->group(function () {
        Route::get('/{supplier}/products', 'products')->name('suppliers.products');
        Route::get('/{supplier}/performance', 'performance')->name('suppliers.performance');
    });

    // Products Management
    Route::apiResource('products', ProductController::class);
    Route::prefix('products')->controller(ProductController::class)->group(function () {
        Route::get('/low-stock', 'lowStock')->name('products.low-stock');
        Route::get('/{product}/stock-levels', 'stockLevels')->name('products.stock-levels');
        Route::post('/import', 'import')->name('products.import');
        Route::get('/export', 'export')->name('products.export');
        Route::post('/bulk-update', 'bulkUpdate')->name('products.bulk-update');
    });

    // Stock Management
    Route::apiResource('stocks', StockController::class);
    Route::prefix('stocks')->controller(StockController::class)->group(function () {
        Route::get('/locations', 'locations')->name('stocks.locations');
        Route::get('/expiring', 'expiring')->name('stocks.expiring');
        Route::get('/expired', 'expired')->name('stocks.expired');
        Route::post('/adjust', 'adjust')->name('stocks.adjust');
        Route::post('/transfer', 'transfer')->name('stocks.transfer');
        Route::get('/valuation', 'valuation')->name('stocks.valuation');
    });

    // Stock Movements Management
    Route::apiResource('stock-movements', StockMovementController::class);
    Route::prefix('stock-movements')->controller(StockMovementController::class)->group(function () {
        Route::get('/statistics', 'statistics')->name('stock-movements.statistics');
        Route::post('/bulk-create', 'bulkCreate')->name('stock-movements.bulk-create');
        Route::get('/export', 'export')->name('stock-movements.export');
    });

    // Reports and Analytics
    Route::prefix('reports')->group(function () {
        Route::get('/inventory-summary', [DashboardController::class, 'inventorySummary'])
            ->name('reports.inventory-summary');
        Route::get('/stock-movements', [StockMovementController::class, 'report'])
            ->name('reports.stock-movements');
        Route::get('/low-stock', [ProductController::class, 'lowStockReport'])
            ->name('reports.low-stock');
        Route::get('/expiry', [StockController::class, 'expiryReport'])
            ->name('reports.expiry');
        Route::get('/valuation', [StockController::class, 'valuationReport'])
            ->name('reports.valuation');
    });

    // Utility Routes
    Route::prefix('utils')->group(function () {
        Route::get('/movement-types', function () {
            return response()->json([
                'success' => true,
                'data' => \App\Models\StockMovement::getTypes(),
            ]);
        })->name('utils.movement-types');

        Route::get('/movement-reasons', function () {
            return response()->json([
                'success' => true,
                'data' => \App\Models\StockMovement::getReasons(),
            ]);
        })->name('utils.movement-reasons');

        Route::get('/units-of-measure', function () {
            return response()->json([
                'success' => true,
                'data' => [
                    'pcs' => 'Pieces',
                    'kg' => 'Kilograms',
                    'g' => 'Grams',
                    'l' => 'Liters',
                    'ml' => 'Milliliters',
                    'box' => 'Boxes',
                    'pack' => 'Packs',
                    'dozen' => 'Dozens',
                    'm' => 'Meters',
                    'cm' => 'Centimeters',
                ],
            ]);
        })->name('utils.units-of-measure');
    });

    // Search and Suggestions
    Route::prefix('search')->group(function () {
        Route::get('/products', [ProductController::class, 'search'])->name('search.products');
        Route::get('/suggestions', function (Request $request) {
            $query = $request->get('q', '');
            $type = $request->get('type', 'products');
            
            if (strlen($query) < 2) {
                return response()->json(['success' => true, 'data' => []]);
            }

            switch ($type) {
                case 'products':
                    $results = \App\Models\Product::where('name', 'ILIKE', "%{$query}%")
                        ->orWhere('sku', 'ILIKE', "%{$query}%")
                        ->limit(10)
                        ->get(['id', 'name', 'sku']);
                    break;
                case 'categories':
                    $results = \App\Models\Category::where('name', 'ILIKE', "%{$query}%")
                        ->limit(10)
                        ->get(['id', 'name']);
                    break;
                case 'suppliers':
                    $results = \App\Models\Supplier::where('name', 'ILIKE', "%{$query}%")
                        ->orWhere('company_name', 'ILIKE', "%{$query}%")
                        ->limit(10)
                        ->get(['id', 'name', 'company_name']);
                    break;
                default:
                    $results = [];
            }

            return response()->json(['success' => true, 'data' => $results]);
        })->name('search.suggestions');
    });

    // Audit Logs (MongoDB)
    Route::prefix('audit-logs')->group(function () {
        Route::get('/', function (Request $request) {
            $perPage = min($request->get('per_page', 15), 100);
            $modelType = $request->get('model_type');
            $action = $request->get('action');
            $userId = $request->get('user_id');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            $query = \App\Models\AuditLog::with('user:id,name');

            if ($modelType) {
                $query->where('model_type', $modelType);
            }

            if ($action) {
                $query->where('action', $action);
            }

            if ($userId) {
                $query->where('user_id', $userId);
            }

            if ($dateFrom) {
                $query->where('performed_at', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->where('performed_at', '<=', $dateTo);
            }

            $logs = $query->orderBy('performed_at', 'desc')->paginate($perPage);

            return response()->json(['success' => true, 'data' => $logs]);
        })->name('audit-logs.index');

        Route::get('/{id}', function ($id) {
            $log = \App\Models\AuditLog::with('user:id,name')->findOrFail($id);
            return response()->json(['success' => true, 'data' => $log]);
        })->name('audit-logs.show');
    });

    // Health Check
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'Inventory Management API is running',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0',
        ]);
    })->name('health');
});

// Catch-all route for API
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
    ], 404);
});
