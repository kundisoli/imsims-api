<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\CategoryController;

Route::get('/', fn() => Inertia::render('Welcome'))->name('home');

Route::middleware(['auth','verified'])->group(function () {
    Route::get('dashboard', fn() => Inertia::render('dashboard'))->name('dashboard');
    Route::get('stores', fn() => Inertia::render('Stores'))->name('stores');
    Route::get('product', fn() => Inertia::render('Products'))->name('products');
    Route::get('category', fn() => Inertia::render('Category'))->name('category');
    Route::get('suppliers', fn() => Inertia::render('Suppliers'))->name('suppliers');
    Route::get('billing', fn() => Inertia::render('Billing'))->name('billing');
    Route::get('orders', fn() => Inertia::render('Orders'))->name('orders');
    Route::get('delivery', fn() => Inertia::render('Delivery'))->name('delivery');
    Route::get('report', fn() => Inertia::render('Report'))->name('report');
    Route::get('help', fn() => Inertia::render('Help'))->name('help');

    // 🔹 Hybrid PostgreSQL + MongoDB category API routes
    Route::prefix('api')->group(function () {
        Route::get('categories', [CategoryController::class, 'index'])->name('api.categories.index');
        Route::post('categories', [CategoryController::class, 'store'])->name('api.categories.store');
        Route::put('categories/{id}', [CategoryController::class, 'update'])->name('api.categories.update');
        Route::get('category-details/{id}', [CategoryController::class, 'details'])->name('api.categories.details');
        Route::post('category-details/{id}', [CategoryController::class, 'saveDetails'])->name('api.categories.saveDetails');
    });
});

// 🔥 Redis Test Route
Route::get('/redis-test', function () {
    Cache::put('test_key', 'Hello from Redis!', 60);
    $value = Cache::get('test_key');
    return "Redis says: " . $value;
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
