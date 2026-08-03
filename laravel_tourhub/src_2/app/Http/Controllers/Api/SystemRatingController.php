<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RecommendationLog;
use App\Models\SystemRating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class SystemRatingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rating = SystemRating::query()
            ->with([
                'recommendationLog:id,user_id,weather_source,weather_used,total_candidates,response_time_ms,status,created_at',
            ])
            ->where('user_id', $request->user()->id)
            ->latest('rated_at')
            ->first();

        return response()->json([
            'success' => true,
            'message' => $rating
                ? 'Rating sistem user berhasil diambil.'
                : 'User belum memberikan rating sistem.',
            'data' => $rating,
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        $existingRating = SystemRating::query()
            ->where('user_id', $userId)
            ->latest('rated_at')
            ->first();

        $latestSuccessfulRecommendation = RecommendationLog::query()
            ->where('user_id', $userId)
            ->where('status', 'success')
            ->latest()
            ->first();

        $requiresRating = $existingRating === null && $latestSuccessfulRecommendation !== null;

        return response()->json([
            'success' => true,
            'message' => $requiresRating
                ? 'User sudah memiliki rekomendasi berhasil dan belum memberi rating sistem.'
                : ($existingRating ? 'User sudah memberi rating sistem.' : 'Belum ada rekomendasi berhasil untuk diberi rating sistem.'),
            'data' => [
                'requires_system_rating' => $requiresRating,
                'has_system_rating' => $existingRating !== null,
                'recommendation_log_id' => $latestSuccessfulRecommendation?->id,
                'rating' => $existingRating,
                'rating_prompt' => $requiresRating
                    ? $this->ratingPrompt($latestSuccessfulRecommendation)
                    : null,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recommendation_log_id' => ['nullable', 'integer', 'exists:recommendation_logs,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'source' => ['nullable', 'string', 'max:100'],
            'platform' => ['nullable', 'string', 'max:50'],
            'metadata' => ['nullable', 'array'],
        ]);

        $userId = (int) $request->user()->id;
        $recommendationLog = $this->resolveRecommendationLogForRating(
            userId: $userId,
            recommendationLogId: isset($validated['recommendation_log_id'])
                ? (int) $validated['recommendation_log_id']
                : null
        );

        $rating = SystemRating::query()->updateOrCreate(
            [
                'user_id' => $userId,
            ],
            [
                'recommendation_log_id' => $recommendationLog->id,
                'rating' => (int) $validated['rating'],
                'comment' => $validated['comment'] ?? null,
                'source' => $validated['source'] ?? 'system_rating',
                'platform' => $validated['platform'] ?? null,
                'metadata' => $validated['metadata'] ?? null,
                'rated_at' => now(),
            ]
        );

        $rating->load([
            'recommendationLog:id,user_id,weather_source,weather_used,total_candidates,response_time_ms,status,created_at',
        ]);

        return response()->json([
            'success' => true,
            'message' => $rating->wasRecentlyCreated
                ? 'Terima kasih, rating sistem berhasil disimpan.'
                : 'Rating sistem berhasil diperbarui.',
            'data' => $rating,
        ], $rating->wasRecentlyCreated ? 201 : 200);
    }

    public function show(Request $request, SystemRating $systemRating): JsonResponse
    {
        $this->authorizeUserRating($request, $systemRating);

        $systemRating->load([
            'recommendationLog:id,user_id,weather_source,weather_used,total_candidates,response_time_ms,status,created_at',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Detail rating sistem berhasil diambil.',
            'data' => $systemRating,
        ]);
    }

    public function update(Request $request, SystemRating $systemRating): JsonResponse
    {
        $this->authorizeUserRating($request, $systemRating);

        $validated = $request->validate([
            'rating' => ['sometimes', 'required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'source' => ['nullable', 'string', 'max:100'],
            'platform' => ['nullable', 'string', 'max:50'],
            'metadata' => ['nullable', 'array'],
        ]);

        $systemRating->fill([
            'rating' => $validated['rating'] ?? $systemRating->rating,
            'comment' => array_key_exists('comment', $validated) ? $validated['comment'] : $systemRating->comment,
            'source' => $validated['source'] ?? $systemRating->source,
            'platform' => array_key_exists('platform', $validated) ? $validated['platform'] : $systemRating->platform,
            'metadata' => array_key_exists('metadata', $validated) ? $validated['metadata'] : $systemRating->metadata,
            'rated_at' => now(),
        ])->save();

        $systemRating->load([
            'recommendationLog:id,user_id,weather_source,weather_used,total_candidates,response_time_ms,status,created_at',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rating sistem berhasil diperbarui.',
            'data' => $systemRating,
        ]);
    }

    public function destroy(Request $request, SystemRating $systemRating): JsonResponse
    {
        $this->authorizeUserRating($request, $systemRating);

        $systemRating->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rating sistem berhasil dihapus.',
        ]);
    }

    private function resolveRecommendationLogForRating(int $userId, ?int $recommendationLogId): RecommendationLog
    {
        if ($recommendationLogId !== null) {
            $recommendationLog = RecommendationLog::query()
                ->where('id', $recommendationLogId)
                ->where('user_id', $userId)
                ->where('status', 'success')
                ->first();
        } else {
            $recommendationLog = RecommendationLog::query()
                ->where('user_id', $userId)
                ->where('status', 'success')
                ->latest()
                ->first();
        }

        if ($recommendationLog === null) {
            throw ValidationException::withMessages([
                'recommendation_log_id' => [
                    'Rating sistem hanya dapat diberikan setelah user memiliki minimal satu rekomendasi yang berhasil.',
                ],
            ]);
        }

        return $recommendationLog;
    }

    private function authorizeUserRating(Request $request, SystemRating $systemRating): void
    {
        abort_if(
            (int) $systemRating->user_id !== (int) $request->user()->id,
            403,
            'Kamu tidak punya akses ke rating sistem ini.'
        );
    }

    private function ratingPrompt(RecommendationLog $recommendationLog): array
    {
        return [
            'show' => true,
            'recommendation_log_id' => $recommendationLog->id,
            'title' => 'Bagaimana pengalaman menggunakan sistem rekomendasi TourHub?',
            'message' => 'Beri rating satu kali untuk menilai kualitas sistem rekomendasi TourHub secara keseluruhan.',
            'scale' => [
                'min' => 1,
                'max' => 5,
                'label_min' => 'Kurang membantu',
                'label_max' => 'Sangat membantu',
            ],
            'endpoint' => '/api/tourhub/system-ratings',
            'method' => 'POST',
        ];
    }
}
