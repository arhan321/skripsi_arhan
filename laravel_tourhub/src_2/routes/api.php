<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ApiProfileController;
use App\Http\Controllers\Api\TourHubLocationController;
use App\Http\Controllers\Api\ApiPasswordResetController;
use App\Http\Controllers\Api\RecommendationProxyController;
use App\Http\Controllers\Api\RecommendationHistoryController;
use App\Http\Controllers\Api\WishlistController as ApiWishlistController;

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [ApiPasswordResetController::class, 'sendResetLinkEmail']);
    Route::post('/reset-password', [ApiPasswordResetController::class, 'reset']);
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/tourhub/locations', TourHubLocationController::class);
    Route::post('/tourhub/recommend', RecommendationProxyController::class);
    Route::get('/tourhub/history', [RecommendationHistoryController::class, 'index']);
    Route::get('/tourhub/history/{recommendationLog}', [RecommendationHistoryController::class, 'show']);

    Route::get('/tourhub/wishlist', [ApiWishlistController::class, 'index']);
    Route::post('/tourhub/wishlist/toggle', [ApiWishlistController::class, 'toggle']);
    Route::delete('/tourhub/wishlist/{wishlist}', [ApiWishlistController::class, 'destroy']);

    Route::get('/user/profile', [ApiProfileController::class, 'show'])
        ->name('api.user.profile.show');

    Route::put('/user/profile', [ApiProfileController::class, 'update'])
        ->name('api.user.profile.update');

    Route::patch('/user/profile', [ApiProfileController::class, 'update'])
        ->name('api.user.profile.patch');

    // Opsional untuk mobile yang lebih mudah memakai POST dibanding PUT/PATCH.
    Route::post('/user/profile/update', [ApiProfileController::class, 'update'])
        ->name('api.user.profile.update.post');
});
