<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\DashboardController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');

// Protected Routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Notifications API
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead']);

    // Tenant-specific routes that require the TenantMiddleware
    Route::middleware([\App\Http\Middleware\TenantMiddleware::class])->group(function () {
        
        // Deliveries API
        Route::get('/deliveries', [DeliveryController::class, 'index']);
        
        // Part 9.1 & 6.1: CSV Imports API
        Route::post('/v1/imports', [DeliveryController::class, 'import']);
        
        // Part 4.2 & 3.3: Dashboard & Reports
        Route::get('/reports/dashboard', [DashboardController::class, 'index']);
    });
});
