<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\RecommendationLog;
use Illuminate\Http\JsonResponse;
use App\Services\TourHubMlService;
use Illuminate\Contracts\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

final class RecommendationController extends Controller
{
    private const VIEW_PATH = 'tourhub.recommendation.index';

    public function index(): View
    {
        return view(self::VIEW_PATH, [
            'defaultBaseUrl' => config('tourhub.ml_base_url'),
            'latestLogs' => RecommendationLog::query()
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }

    public function health(TourHubMlService $ml): JsonResponse
    {
        try {
            return response()->json($ml->health());
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function recommend(Request $request, TourHubMlService $ml): View|RedirectResponse
    {
        $validated = $request->validate([
            'kategori_preferensi' => ['required', 'array', 'min:1'],
            'kategori_preferensi.*' => [
                'required',
                Rule::in(['Alam', 'Budaya', 'Rekreasi', 'Umum']),
            ],

            'kabupaten_kota' => ['nullable', 'string', 'max:150'],
            'kecamatan' => ['nullable', 'string', 'max:150'],
            'keywords' => ['nullable', 'string', 'max:255'],

            'min_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'top_n' => ['required', 'integer', 'min:1', 'max:50'],

            'weather' => [
                'nullable',
                Rule::in(['cerah', 'hujan', 'mendung', 'berawan', 'unknown']),
            ],

            'visit_day' => [
                'nullable',
                Rule::in(['weekday', 'weekend']),
            ],

            'is_high_season' => ['nullable', 'boolean'],
            'use_bmkg' => ['nullable', 'boolean'],
            'bmkg_adm4' => ['nullable', 'string', 'max:50'],
        ]);

        $payload = [
            'kategori_preferensi' => $validated['kategori_preferensi'],
            'kabupaten_kota' => $validated['kabupaten_kota'] ?? null,
            'kecamatan' => $validated['kecamatan'] ?? null,
            'keywords' => $this->parseKeywords($validated['keywords'] ?? null),
            'min_rating' => isset($validated['min_rating'])
                ? (float) $validated['min_rating']
                : null,
            'top_n' => (int) $validated['top_n'],
            'weather' => $validated['weather'] ?? null,
            'visit_day' => $validated['visit_day'] ?? null,
            'is_high_season' => (bool) ($validated['is_high_season'] ?? false),
            'use_bmkg' => (bool) ($validated['use_bmkg'] ?? false),
            'bmkg_adm4' => $validated['bmkg_adm4'] ?? null,
        ];

        $startedAt = microtime(true);

        try {
            $result = $ml->recommend($payload);

            $responseTimeMs = (int) round((microtime(true) - $startedAt) * 1000);

            RecommendationLog::query()->create([
                'user_id' => Auth::id(),
                'weather_source' => data_get($result, 'weather_source'),
                'weather_used' => data_get($result, 'weather_used'),
                'total_candidates' => data_get($result, 'total_candidates'),
                'response_time_ms' => $responseTimeMs,
                'request_payload' => $payload,
                'response_payload' => $result,
                'status' => 'success',
            ]);

            return view(self::VIEW_PATH, [
                'defaultBaseUrl' => config('tourhub.ml_base_url'),
                'payload' => $payload,
                'result' => $result,
                'responseTimeMs' => $responseTimeMs,
                'latestLogs' => RecommendationLog::query()
                    ->latest()
                    ->take(5)
                    ->get(),
            ]);
        } catch (Throwable $e) {
            $responseTimeMs = (int) round((microtime(true) - $startedAt) * 1000);

            RecommendationLog::query()->create([
                'user_id' => Auth::id(),
                'response_time_ms' => $responseTimeMs,
                'request_payload' => $payload,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'ml_api' => $e->getMessage(),
                ]);
        }
    }

    private function parseKeywords(?string $keywords): array
    {
        if (! $keywords) {
            return [];
        }

        return collect(explode(',', $keywords))
            ->map(fn (string $keyword): string => trim($keyword))
            ->filter()
            ->values()
            ->all();
    }
}