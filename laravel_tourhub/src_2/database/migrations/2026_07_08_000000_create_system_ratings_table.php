<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_ratings', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('recommendation_log_id')
                ->nullable()
                ->constrained('recommendation_logs')
                ->nullOnDelete();

            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->string('source')->default('recommendation_success');
            $table->string('platform')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('rated_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['user_id', 'recommendation_log_id'],
                'system_ratings_user_recommendation_unique'
            );

            $table->index(['user_id', 'created_at'], 'system_ratings_user_created_at_index');
            $table->index(['rating', 'created_at'], 'system_ratings_rating_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_ratings');
    }
};
