<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('system_ratings')) {
            return;
        }

        // Jika sebelumnya sudah ada banyak rating untuk user yang sama,
        // simpan data terakhir saja agar konsepnya menjadi satu rating per user.
        $idsToKeep = DB::table('system_ratings')
            ->selectRaw('MAX(id) as id')
            ->groupBy('user_id')
            ->pluck('id')
            ->filter()
            ->values()
            ->all();

        if (count($idsToKeep) > 0) {
            DB::table('system_ratings')
                ->whereNotIn('id', $idsToKeep)
                ->delete();
        }

        Schema::table('system_ratings', function (Blueprint $table): void {
            try {
                $table->dropUnique('system_ratings_user_recommendation_unique');
            } catch (Throwable $exception) {
                // Index lama mungkin belum ada di beberapa environment.
            }
        });

        Schema::table('system_ratings', function (Blueprint $table): void {
            try {
                $table->unique('user_id', 'system_ratings_user_id_unique');
            } catch (Throwable $exception) {
                // Index mungkin sudah ada jika migration pernah dicoba sebelumnya.
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('system_ratings')) {
            return;
        }

        Schema::table('system_ratings', function (Blueprint $table): void {
            try {
                $table->dropUnique('system_ratings_user_id_unique');
            } catch (Throwable $exception) {
                // Abaikan jika index tidak ada.
            }
        });

        Schema::table('system_ratings', function (Blueprint $table): void {
            try {
                $table->unique(
                    ['user_id', 'recommendation_log_id'],
                    'system_ratings_user_recommendation_unique'
                );
            } catch (Throwable $exception) {
                // Abaikan jika index tidak bisa dibuat ulang.
            }
        });
    }
};
