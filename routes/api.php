<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthController;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Service Health & Monitoring (Microservices / Orchestrators)
Route::get('/health', HealthController::class);

// Authentication (JWT)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::get('/user', [AuthController::class, 'me'])->middleware('auth:api');

Route::get('/hello', function () {
    return ApiResponse::success([
        'framework' => 'Laravel',
        'version' => app()->version(),
        'status' => 'active',
    ], 'Data retrieved successfully');
});
