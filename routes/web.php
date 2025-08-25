<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

// 🔥 Redis Test Route
Route::get('/redis-test', function () {
    // Store a value in Redis for 60 seconds
    Cache::put('test_key', 'Hello from Redis!', 60);

    // Retrieve the value
    $value = Cache::get('test_key');

    return "Redis says: " . $value;
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
