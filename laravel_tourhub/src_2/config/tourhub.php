<?php

return [
    /*
    |--------------------------------------------------------------------------
    | TourHub ML / FastAPI Service
    |--------------------------------------------------------------------------
    |
    | Laravel tetap menjadi API utama untuk mobile dan admin. FastAPI dipakai
    | khusus untuk proses Content-Based Filtering + CARS. Setelah admin mengubah
    | data destinasi, Laravel bisa meminta FastAPI me-reload dataset.
    |
    */

    'ml_base_url' => env('TOURHUB_ML_BASE_URL', 'https://machine_learning.djncloud.my.id'),
    'ml_timeout' => (int) env('TOURHUB_ML_TIMEOUT', 30),
    'ml_api_key' => env('TOURHUB_ML_API_KEY', '123'),

    /*
    |--------------------------------------------------------------------------
    | Internal API Key Laravel <-> FastAPI
    |--------------------------------------------------------------------------
    |
    | Key ini dipakai FastAPI untuk mengambil data destinasi aktif dari Laravel.
    | Samakan nilainya dengan LARAVEL_INTERNAL_KEY di service FastAPI.
    |
    */

    'internal_api_key' => env('TOURHUB_INTERNAL_API_KEY', env('TOURHUB_ML_API_KEY', '123')),

    /*
    |--------------------------------------------------------------------------
    | Dataset CSV Awal
    |--------------------------------------------------------------------------
    |
    | Seeder TouristDestinationSeeder memakai file CSV ini sebagai data awal agar
    | admin tidak perlu input manual 1.452 destinasi satu per satu.
    |
    */

    'dataset_csv_path' => env(
        'TOURHUB_DATASET_CSV_PATH',
        base_path('../../data/bali_tourist_destination.csv')
    ),
];
