<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tourist_destinations', function (Blueprint $table): void {
            $table->id();
            $table->string('id_tempat')->unique();
            $table->string('nama_tempat_wisata');
            $table->string('kategori', 50)->index();
            $table->string('tipe_wisata', 30)->nullable()->index();
            $table->string('kecamatan', 100)->nullable()->index();
            $table->string('kabupaten_kota', 100)->nullable()->index();
            $table->decimal('rating', 3, 2)->default(0);
            $table->unsignedInteger('jumlah_rating')->default(0);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('link_google_maps')->nullable();
            $table->text('link_gambar')->nullable();
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'kategori']);
            $table->index(['is_active', 'kabupaten_kota', 'kecamatan']);
            $table->index(['rating', 'jumlah_rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tourist_destinations');
    }
};
