<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SubtaskController;
use App\Http\Controllers\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::get('/oauth/google', [AuthController::class, 'oAuthUrl']);
        Route::get('/oauth/google/callback', [AuthController::class, 'oAuthCallback']);
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });
    Route::get('plans', [PlanController::class, 'index']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('tasks', TaskController::class)->only(['index', 'store', 'show', 'destroy']);
        Route::post('tasks/{id}', [TaskController::class, 'update']);
        Route::apiResource('subtasks', SubtaskController::class)->only(['index', 'store', 'destroy']);
        Route::post('subtasks/{id}', [SubtaskController::class, 'update']);
        Route::post('subtasks', [SubtaskController::class, 'changeStatus']);
        Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show', 'destroy']);
        Route::apiResource('payments', PaymentController::class)->only(['index', 'store', 'show']);
    });
    Route::post('/payments/callback', [PaymentController::class, 'callback']);
});
