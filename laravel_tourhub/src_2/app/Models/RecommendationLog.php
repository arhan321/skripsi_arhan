<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendationLog extends Model
{
    protected $fillable = [
        'user_id',
        'weather_source',
        'weather_used',
        'total_candidates',
        'response_time_ms',
        'request_payload',
        'response_payload',
        'status',
        'error_message',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'total_candidates' => 'integer',
        'response_time_ms' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTopDestinationNameAttribute(): ?string
    {
        return data_get($this->response_payload, 'recommendations.0.nama_tempat_wisata');
    }
}
