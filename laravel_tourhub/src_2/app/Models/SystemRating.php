<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SystemRating extends Model
{
    protected $fillable = [
        'user_id',
        'recommendation_log_id',
        'rating',
        'comment',
        'source',
        'platform',
        'metadata',
        'rated_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'metadata' => 'array',
        'rated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Riwayat rekomendasi di sini hanya konteks awal saat user memberi rating.
     * Rating sistem tetap satu kali per user, bukan satu kali per recommendation log.
     */
    public function recommendationLog(): BelongsTo
    {
        return $this->belongsTo(RecommendationLog::class);
    }

    public function getLabelAttribute(): string
    {
        return match ((int) $this->rating) {
            1 => 'Kurang membantu',
            2 => 'Cukup kurang',
            3 => 'Cukup membantu',
            4 => 'Membantu',
            5 => 'Sangat membantu',
            default => 'Belum diberi rating',
        };
    }

    public function getStarsAttribute(): string
    {
        $rating = max(0, min(5, (int) $this->rating));

        return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
    }
}
