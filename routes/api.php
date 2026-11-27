
<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SavingsDepositController;
use App\Http\Controllers\Api\SavingsGoalController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('login-google', [AuthController::class, 'loginGoogle'])->middleware('throttle:10,1');
Route::post('register', [AuthController::class, 'register']);
Route::post('verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:10,1');
Route::post('resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:3,1');

Route::middleware('auth:api')->group(function () {
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('throttle:30,1');
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    Route::apiResource('users', UserController::class)->only(['destroy']);

    Route::apiResource('transactions', TransactionController::class);

    Route::prefix('budgets')->group(function () {
        Route::get('/', [BudgetController::class, 'index']);
        Route::post('/allocations', [BudgetController::class, 'storeAllocation']);
        Route::delete('/allocations/{id}', [BudgetController::class, 'destroyAllocation']);
        Route::get('/rollover/check', [BudgetController::class, 'checkRollover']);
        Route::post('/rollover', [BudgetController::class, 'processRollover']);
    });

    Route::prefix('savings-goals')->group(function () {
        Route::get('/{savingsGoalId}/deposits', [SavingsDepositController::class, 'index']);
        Route::post('/{savingsGoalId}/deposits', [SavingsDepositController::class, 'store']);
    });
    Route::apiResource('savings-goals', SavingsGoalController::class);

    Route::prefix('notifications')->group(function () {
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('/{id}/read', [NotificationController::class, 'update']);
        Route::patch('/read-all', [NotificationController::class, 'markAllAsRead']);
    });
    Route::apiResource('notifications', NotificationController::class)->only(['index']);

    Route::apiResource('settings', SettingController::class)->except('index');
});
