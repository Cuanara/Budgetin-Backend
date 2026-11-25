
<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\SavingsGoalController;
use App\Http\Controllers\Api\SavingsDepositController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('login-google', [AuthController::class, 'loginGoogle'])->middleware('throttle:10,1');
Route::post('register', [AuthController::class, 'register']);
Route::post('refresh', [AuthController::class, 'refresh'])->middleware('throttle:30,1');

Route::middleware('auth:api')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    // User
    Route::apiResource('users', UserController::class)->only(['update', 'destroy']);

    // Transaction
    Route::apiResource('transactions', TransactionController::class);

    // Savings Deposits
    Route::get('savings-goals/{savingsGoalId}/deposits', [SavingsDepositController::class, 'index']);

    Route::post('savings-goals/{savingsGoalId}/deposits', [SavingsDepositController::class, 'store']);

    Route::apiResource('savings-goals', SavingsGoalController::class);
});
