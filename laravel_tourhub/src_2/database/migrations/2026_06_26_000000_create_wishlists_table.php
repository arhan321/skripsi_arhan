<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlists', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('recommendation_log_id')
                ->nullable()
                ->constrained('recommendation_logs')
                ->nullOnDelete();

            $table->string('destination_key', 64);
            $table->string('destination_id')->nullable()->index();
            $table->string('destination_name');
            $table->string('category')->nullable();
            $table->string('tourism_type')->nullable();
            $table->string('subdistrict')->nullable();
            $table->string('city')->nullable();
            $table->decimal('rating', 4, 2)->nullable();
            $table->unsignedInteger('review_count')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('google_maps_url')->nullable();
            $table->text('image_url')->nullable();
            $table->text('reason')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'destination_key'], 'wishlists_user_destination_unique');
            $table->index(['user_id', 'created_at'], 'wishlists_user_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};
