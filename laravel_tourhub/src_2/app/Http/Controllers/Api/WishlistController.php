<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use JsonException;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use App\Models\RecommendationLog;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

final class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $wishlists = Wishlist::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(12);

        return response()->json([
            'success' => true,
            'message' => 'Daftar wishlist berhasil diambil.',
            'data' => $wishlists,
        ]);
    }

    public function toggle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'destination' => ['nullable', 'array'],
            'destination_payload' => ['nullable', 'string'],
            'destination_payload_encoding' => ['nullable', 'string', 'in:base64,json'],
            'recommendation_log_id' => ['nullable', 'integer', 'exists:recommendation_logs,id'],
        ]);

        $destination = $this->resolveDestinationData($validated);

        if ($destination === []) {
            throw ValidationException::withMessages([
                'destination' => 'Data destinasi wajib dikirim.',
            ]);
        }

        $userId = (int) $request->user()->id;

        $recommendationLogId = $this->resolveRecommendationLogId(
            isset($validated['recommendation_log_id']) ? (int) $validated['recommendation_log_id'] : null,
            $userId
        );

        $attributes = Wishlist::fromRecommendationItem($destination);

        $wishlist = Wishlist::query()
            ->where('user_id', $userId)
            ->where('destination_key', $attributes['destination_key'])
            ->first();

        if ($wishlist) {
            $wishlist->delete();

            return response()->json([
                'success' => true,
                'wished' => false,
                'message' => 'Destinasi berhasil dihapus dari wishlist.',
            ]);
        }

        $wishlist = Wishlist::create(array_merge($attributes, [
            'user_id' => $userId,
            'recommendation_log_id' => $recommendationLogId,
        ]));

        return response()->json([
            'success' => true,
            'wished' => true,
            'message' => 'Destinasi berhasil ditambahkan ke wishlist.',
            'data' => $wishlist,
        ], 201);
    }

    public function destroy(Request $request, Wishlist $wishlist): JsonResponse
    {
        abort_if(
            (int) $wishlist->user_id !== (int) $request->user()->id,
            403,
            'Kamu tidak punya akses ke wishlist ini.'
        );

        $wishlist->delete();

        return response()->json([
            'success' => true,
            'message' => 'Wishlist berhasil dihapus.',
        ]);
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function resolveDestinationData(array $validated): array
    {
        if (isset($validated['destination']) && is_array($validated['destination'])) {
            return $validated['destination'];
        }

        if (! isset($validated['destination_payload'])) {
            return [];
        }

        return $this->decodeDestinationPayload(
            (string) $validated['destination_payload'],
            $validated['destination_payload_encoding'] ?? null
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeDestinationPayload(string $payload, ?string $encoding = null): array
    {
        $payload = trim($payload);

        if ($payload === '') {
            throw ValidationException::withMessages([
                'destination_payload' => 'Format data destinasi tidak valid.',
            ]);
        }

        $jsonPayload = $payload;

        if ($encoding === 'base64') {
            $decodedBase64 = base64_decode($payload, true);

            if ($decodedBase64 === false || trim($decodedBase64) === '') {
                throw ValidationException::withMessages([
                    'destination_payload' => 'Format data destinasi tidak valid.',
                ]);
            }

            $jsonPayload = $decodedBase64;
        } elseif ($encoding !== 'json') {
            $decodedBase64 = base64_decode($payload, true);

            if ($decodedBase64 !== false && $this->looksLikeJson($decodedBase64)) {
                $jsonPayload = $decodedBase64;
            }
        }

        try {
            $decoded = json_decode($jsonPayload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages([
                'destination_payload' => 'Format data destinasi tidak valid.',
            ]);
        }

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'destination_payload' => 'Data destinasi tidak valid.',
            ]);
        }

        return $decoded;
    }

    private function looksLikeJson(string $value): bool
    {
        $value = trim($value);

        return str_starts_with($value, '{') || str_starts_with($value, '[');
    }

    private function resolveRecommendationLogId(?int $recommendationLogId, int $userId): ?int
    {
        if (! $recommendationLogId) {
            return null;
        }

        $exists = RecommendationLog::query()
            ->where('id', $recommendationLogId)
            ->where('user_id', $userId)
            ->exists();

        abort_if(! $exists, 403, 'Kamu tidak punya akses ke riwayat rekomendasi ini.');

        return $recommendationLogId;
    }
}
