<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerMembershipController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\QRCodeController;
use App\Http\Controllers\Api\RewardController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

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

// Public Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

// Protected Routes (Require Authentication)
Route::middleware('auth:sanctum')->group(function () {

    // Authentication & Profile
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
    });

    // Customer Memberships
    Route::prefix('memberships')->group(function () {
        Route::get('/', [CustomerMembershipController::class, 'index']);
        Route::get('/{tenant_slug}', [CustomerMembershipController::class, 'show']);
        Route::get('/{tenant_slug}/points', [CustomerMembershipController::class, 'points']);
        Route::post('/{tenant_slug}/join', [CustomerMembershipController::class, 'join']);
    });

    // Tenant-Specific Routes
    Route::prefix('tenants/{tenant_slug}')->group(function () {

        // Rewards
        Route::get('/rewards', [RewardController::class, 'index']);
        Route::get('/rewards/{reward_id}', [RewardController::class, 'show']);
        Route::post('/rewards/{reward_id}/redeem', [RewardController::class, 'redeem']);

        // Redemptions
        Route::get('/redemptions', [RewardController::class, 'redemptions']);

        // Transactions
        Route::get('/transactions', [TransactionController::class, 'index']);
        Route::get('/transactions/stats', [TransactionController::class, 'stats']);

        // QR Code
        Route::get('/qr-code', [QRCodeController::class, 'show']);
    });

    // Global Transactions (across all memberships)
    Route::get('/transactions', [TransactionController::class, 'all']);

    // QR Codes
    Route::prefix('qr-codes')->group(function () {
        Route::get('/', [QRCodeController::class, 'index']);
    });

    // QR Code Validation (separate endpoint for staff validation)
    Route::post('/qr-code/validate', [QRCodeController::class, 'validateQrCode']);

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        Route::post('/device-token', [NotificationController::class, 'updateDeviceToken']);
    });
});

// Health Check Route
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is running',
        'timestamp' => now()->toIso8601String(),
        'version' => '1.0.0',
    ], 200);
});
