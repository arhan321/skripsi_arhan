<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RecommendationLog;
use App\Services\TourHubMlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Throwable;

final class RecommendationProxyController extends Controller
{
    public function __invoke(Request $request, TourHubMlService $ml)
    {
        $payload = $request->validate([
            'kategori_preferensi' => ['required', 'array', 'min:1'],
            'kategori_preferensi.*' => ['required', Rule::in(['Alam', 'Budaya', 'Rekreasi', 'Umum'])],
            'kabupaten_kota' => ['nullable', 'string', 'max:150'],
            'kecamatan' => ['nullable', 'string', 'max:150'],
            'keywords' => ['nullable', 'array'],
            'keywords.*' => ['string', 'max:100'],
            'min_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'top_n' => ['required', 'integer', 'min:1', 'max:50'],
            'weather' => ['nullable', Rule::in(['cerah', 'hujan', 'mendung', 'berawan', 'unknown'])],
            'visit_day' => ['nullable', Rule::in(['weekday', 'weekend'])],
            'is_high_season' => ['nullable', 'boolean'],
            'use_bmkg' => ['nullable', 'boolean'],
            'bmkg_adm4' => ['nullable', 'string', 'max:50'],
        ]);

        $payload = array_merge([
            'kabupaten_kota' => null,
            'kecamatan' => null,
            'keywords' => [],
            'min_rating' => null,
            'weather' => null,
            'visit_day' => null,
            'is_high_season' => false,
            'use_bmkg' => false,
            'bmkg_adm4' => null,
        ], $payload);

        $startedAt = microtime(true);

        try {
            $result = $ml->recommend($payload);
            $responseTimeMs = (int) round((microtime(true) - $startedAt) * 1000);

            RecommendationLog::create([
                'user_id' => Auth::id(),
                'weather_source' => data_get($result, 'weather_source'),
                'weather_used' => data_get($result, 'weather_used'),
                'total_candidates' => data_get($result, 'total_candidates'),
                'response_time_ms' => $responseTimeMs,
                'request_payload' => $payload,
                'response_payload' => $result,
                'status' => 'success',
            ]);

            return response()->json([
                'success' => true,
                'response_time_ms' => $responseTimeMs,
                'data' => $result,
            ]);
        } catch (Throwable $e) {
            $responseTimeMs = (int) round((microtime(true) - $startedAt) * 1000);

            RecommendationLog::create([
                'user_id' => Auth::id(),
                'response_time_ms' => $responseTimeMs,
                'request_payload' => $payload,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
