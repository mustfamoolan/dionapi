<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NotificationController;

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

// Notification API routes (Admin only)
Route::middleware(['auth:sanctum', 'api.admin'])->prefix('notifications')->group(function () {
    Route::post('/send-to-user', [NotificationController::class, 'sendToUser']);
    Route::post('/send-to-multiple', [NotificationController::class, 'sendToMultiple']);
    Route::post('/send-to-all', [NotificationController::class, 'sendToAll']);
});
