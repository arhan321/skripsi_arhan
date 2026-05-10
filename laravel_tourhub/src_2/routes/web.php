<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| TourHub Web Routes
|--------------------------------------------------------------------------
|
| Route ini untuk halaman simulasi rekomendasi TourHub.
| Halaman utama:
| /tourhub/rekomendasi
|
*/
require __DIR__ . '/tourhub.php';