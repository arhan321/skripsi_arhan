<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TourHubLocationController;
use App\Http\Controllers\Api\RecommendationProxyController;
use App\Http\Controllers\Api\RecommendationHistoryController;

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/tourhub/locations', TourHubLocationController::class);

    Route::post('/tourhub/recommend', RecommendationProxyController::class);

    Route::get('/tourhub/history', [RecommendationHistoryController::class, 'index']);
    Route::get('/tourhub/history/{recommendationLog}', [RecommendationHistoryController::class, 'show']);
});