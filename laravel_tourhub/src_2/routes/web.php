<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\Web\WishlistController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\User\UserDashboardController;
use App\Http\Controllers\Web\RecommendationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

/*
|--------------------------------------------------------------------------
| Landing Page
|--------------------------------------------------------------------------
|
| Halaman utama dibuat seperti landing page/public page.
| User umum boleh melihat landing page tanpa login.
| Jika ingin memakai fitur rekomendasi, user tetap diarahkan login terlebih dahulu.
|
*/
Route::get('/', function () {
    return view('landing');
})->name('landing');

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

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])
        ->name('password.request');

    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])
        ->name('password.email');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])
        ->name('password.reset');

    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])
        ->name('password.update');
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

    Route::get('/user/wishlist', [WishlistController::class, 'index'])
        ->name('user.wishlist.index');

    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])
        ->name('wishlist.toggle');

    Route::delete('/wishlist/{wishlist}', [WishlistController::class, 'destroy'])
        ->name('wishlist.destroy');

    Route::get('/user/profile', [ProfileController::class, 'edit'])
        ->name('user.profile.edit');

    Route::put('/user/profile', [ProfileController::class, 'update'])
        ->name('user.profile.update');

    Route::patch('/user/profile', [ProfileController::class, 'update'])
        ->name('user.profile.patch');

    /*
    |--------------------------------------------------------------------------
    | TourHub Recommendation Routes
    |--------------------------------------------------------------------------
    |
    | Halaman rekomendasi TourHub.
    | Diproteksi auth supaya hanya user login yang bisa menggunakan fitur rekomendasi.
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
