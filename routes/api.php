<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ClientApiController;

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

// Public routes (no authentication required)
Route::post('/clients/register', [ClientApiController::class, 'register']);
Route::post('/clients/login', [ClientApiController::class, 'login']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/clients/profile', [ClientApiController::class, 'profile']);
    Route::put('/clients/profile', [ClientApiController::class, 'updateProfile']);
    Route::get('/clients/status', [ClientApiController::class, 'getStatus']);
    Route::post('/clients/logout', [ClientApiController::class, 'logout']);
    Route::post('/clients/refresh-token', [ClientApiController::class, 'refreshToken']);

    // Products routes
    Route::get('/clients/products', [ClientApiController::class, 'getProducts']);
    Route::get('/clients/products/low-stock', [ClientApiController::class, 'getLowStockProducts']);
    Route::get('/clients/products/{id}', [ClientApiController::class, 'getProduct']);
    Route::post('/clients/products', [ClientApiController::class, 'storeProduct']);
    Route::put('/clients/products/{id}', [ClientApiController::class, 'updateProduct']);
    Route::delete('/clients/products/{id}', [ClientApiController::class, 'deleteProduct']);
});

