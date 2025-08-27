<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public route
Route::get('/', fn() => Inertia::render('Welcome'))->name('home');

// Protected routes (authenticated users)
Route::middleware(['auth','verified'])->group(function () {
    Route::get('dashboard', fn() => Inertia::render('Dashboard'))->name('dashboard');
    Route::get('stores', fn() => Inertia::render('Stores'))->name('stores');
    Route::get('product', fn() => Inertia::render('Products'))->name('products');
    Route::get('category', fn() => Inertia::render('Category'))->name('category');
    Route::get('suppliers', fn() => Inertia::render('Suppliers'))->name('suppliers');
    Route::get('billing', fn() => Inertia::render('Billing'))->name('billing');
    Route::get('orders', fn() => Inertia::render('Orders'))->name('orders');
    Route::get('delivery', fn() => Inertia::render('Delivery'))->name('delivery');
    Route::get('report', fn() => Inertia::render('Report'))->name('report');
    Route::get('help', fn() => Inertia::render('Help'))->name('help');
});

// Redis test route
Route::get('/redis-test', function () {
    Cache::put('test_key', 'Hello from Redis!', 60);
    return "Redis says: " . Cache::get('test_key');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
