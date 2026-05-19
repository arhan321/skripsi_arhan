<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('weather_source')->nullable();
            $table->string('weather_used')->nullable();
            $table->unsignedInteger('total_candidates')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('status')->default('success');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('weather_used');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_logs');
    }
};
