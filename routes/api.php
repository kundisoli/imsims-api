<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StoreController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are for JSON APIs, separate from web pages.
| They are automatically prefixed with /api in URLs.
|
*/

// Example: GET http://127.0.0.1:8000/api/stores
Route::get('/stores', [StoreController::class, 'index']);
