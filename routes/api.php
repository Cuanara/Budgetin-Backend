
<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\SavingsGoalController;
use App\Http\Controllers\Api\SavingsDepositController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WishlistController;
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

    Route::apiResource('users', UserController::class)->only(['update', 'destroy']);

    Route::apiResource('transactions', TransactionController::class);

    Route::get('budgets', [BudgetController::class, 'index']);
    Route::post('budgets/allocations', [BudgetController::class, 'storeAllocation']);
    Route::delete('budgets/allocations/{id}', [BudgetController::class, 'destroyAllocation']);
    Route::get('budgets/rollover/check', [BudgetController::class, 'checkRollover']);
    Route::post('budgets/rollover', [BudgetController::class, 'processRollover']);

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::patch('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::apiResource('wishlists', WishlistController::class);

    Route::apiResource('transactions', TransactionController::class);

    // Savings Deposits
    Route::get('savings-goals/{savingsGoalId}/deposits', [SavingsDepositController::class, 'index']);

    Route::post('savings-goals/{savingsGoalId}/deposits', [SavingsDepositController::class, 'store']);

    Route::apiResource('savings-goals', SavingsGoalController::class);
});
