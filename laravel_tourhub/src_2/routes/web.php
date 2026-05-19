<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\UserDashboardController;
use App\Http\Controllers\Web\RecommendationController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

Route::get('/', function () {
    return view('auth.login');
});

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
|
| Route untuk user yang belum login.
|
*/

Route::middleware('guest')->group(function (): void {
    /*
     * Alias login default Laravel.
     * Ini penting karena middleware auth biasanya mencari route bernama "login".
     */
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::get('/user/login', [AuthenticatedSessionController::class, 'create'])
        ->name('user.login');

    Route::post('/user/login', [AuthenticatedSessionController::class, 'store'])
        ->name('user.login.store');

    Route::get('/user/register', [RegisteredUserController::class, 'create'])
        ->name('user.register');

    Route::post('/user/register', [RegisteredUserController::class, 'store'])
        ->name('user.register.store');
});

/*
|--------------------------------------------------------------------------
| Authenticated User Routes
|--------------------------------------------------------------------------
|
| Route untuk user yang sudah login.
|
*/

Route::middleware('auth')->group(function (): void {
    Route::post('/user/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('user.logout');

    Route::get('/user/dashboard', [UserDashboardController::class, 'index'])
        ->name('user.dashboard');

    Route::get('/user/recommendation-history/{recommendationLog}', [UserDashboardController::class, 'show'])
        ->name('user.recommendation-history.show');

    /*
    |--------------------------------------------------------------------------
    | TourHub Recommendation Routes
    |--------------------------------------------------------------------------
    |
    | Halaman simulasi rekomendasi TourHub.
    | Diproteksi auth supaya setiap hasil rekomendasi tersimpan per user.
    |
    */

    Route::prefix('tourhub')
        ->name('tourhub.')
        ->group(function (): void {
            Route::get('/rekomendasi', [RecommendationController::class, 'index'])
                ->name('recommendation.index');

            Route::post('/rekomendasi', [RecommendationController::class, 'recommend'])
                ->name('recommendation.store');

            Route::get('/ml-health', [RecommendationController::class, 'health'])
                ->name('ml.health');
        });
});

