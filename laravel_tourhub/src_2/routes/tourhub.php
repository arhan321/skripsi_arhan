<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\RecommendationController;
// use App\Http\Controllers\Web\RecommendationController;

Route::middleware(['web'])
    ->prefix('tourhub')
    ->name('tourhub.')
    ->group(function (): void {
        Route::get('/rekomendasi', [RecommendationController::class, 'index'])
            ->name('recommendation.index');

        Route::post('/rekomendasi', [RecommendationController::class, 'recommend'])
            ->name('recommendation.store');

        Route::get('/ml-health', [RecommendationController::class, 'health'])
            ->name('ml.health');
    });