<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Admin Routes (Protected)
Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/clients', [AdminController::class, 'clients'])->name('clients');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');

    // Users API routes
    Route::get('/users/data', [AdminController::class, 'getUsers'])->name('users.data');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::get('/users/{id}', [AdminController::class, 'getUser'])->name('users.get');
    Route::put('/users/{id}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{id}', [AdminController::class, 'deleteUser'])->name('users.delete');

    // Clients API routes
    Route::get('/clients/data', [AdminController::class, 'getClients'])->name('clients.data');
});

// Template demo routes (keep for template pages)
Route::get('{page}', [DashboardController::class, 'index'])->where('page', '[A-Za-z0-9\-]+');
