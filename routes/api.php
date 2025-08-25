<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\InventoryController;

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

Route::prefix('v1')->group(function () {
    // Authentication routes (public)
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    // Protected routes (require authentication)
    Route::middleware(['auth:sanctum'])->group(function () {
        // Auth user routes
        Route::get('me', [AuthController::class, 'me']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);

        // Inventory routes (require inventory access)
        Route::middleware(['inventory.permission'])->group(function () {
            
            // Products (read access)
            Route::get('products', [ProductController::class, 'index']);
            Route::get('products/{id}', [ProductController::class, 'show']);
            Route::get('products/{id}/stock-summary', [ProductController::class, 'stockSummary']);
            Route::get('products/reports/low-stock', [ProductController::class, 'lowStock']);
            
            // Product management (requires manage_products permission)
            Route::middleware(['inventory.permission:manage_products'])->group(function () {
                Route::post('products', [ProductController::class, 'store']);
                Route::put('products/{id}', [ProductController::class, 'update']);
                Route::delete('products/{id}', [ProductController::class, 'destroy']);
            });
            
            // Categories (read access)
            Route::get('categories', [CategoryController::class, 'index']);
            Route::get('categories/{id}', [CategoryController::class, 'show']);
            Route::get('categories/tree/all', [CategoryController::class, 'getCategoryTree']);
            Route::get('categories/{id}/products', [CategoryController::class, 'products']);
            
            // Category management (requires manage_categories permission)
            Route::middleware(['inventory.permission:manage_categories'])->group(function () {
                Route::post('categories', [CategoryController::class, 'store']);
                Route::put('categories/{id}', [CategoryController::class, 'update']);
                Route::delete('categories/{id}', [CategoryController::class, 'destroy']);
            });
            
            // Warehouses (read access)
            Route::get('warehouses', [WarehouseController::class, 'index']);
            Route::get('warehouses/{id}', [WarehouseController::class, 'show']);
            Route::get('warehouses/{id}/locations', [WarehouseController::class, 'locations']);
            Route::get('warehouses/{id}/inventory-summary', [WarehouseController::class, 'inventorySummary']);
            Route::get('warehouses/{id}/performance-metrics', [WarehouseController::class, 'performanceMetrics']);
            
            // Warehouse management (requires manage_warehouses permission)
            Route::middleware(['inventory.permission:manage_warehouses'])->group(function () {
                Route::post('warehouses', [WarehouseController::class, 'store']);
                Route::put('warehouses/{id}', [WarehouseController::class, 'update']);
                Route::delete('warehouses/{id}', [WarehouseController::class, 'destroy']);
            });
            
            // Inventory Management (read access)
            Route::get('inventory/overview', [InventoryController::class, 'overview']);
            Route::get('inventory/warehouse/{warehouseId}', [InventoryController::class, 'byWarehouse']);
            Route::get('inventory/movement-history', [InventoryController::class, 'movementHistory']);
            
            // Stock adjustments (requires adjust_stock permission)
            Route::middleware(['inventory.permission:adjust_stock'])->group(function () {
                Route::post('inventory/adjust-stock', [InventoryController::class, 'adjustStock']);
            });
            
            // Stock transfers (requires transfer_stock permission)
            Route::middleware(['inventory.permission:transfer_stock'])->group(function () {
                Route::post('inventory/transfer-stock', [InventoryController::class, 'transferStock']);
            });
        });
    });
});