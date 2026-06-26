<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use JsonException;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use App\Models\RecommendationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

final class WishlistController extends Controller
{
    public function index(): View
    {
        $wishlists = Wishlist::query()
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(12);

        return view('user.wishlist.index', [
            'wishlists' => $wishlists,
        ]);
    }

    public function toggle(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'destination_payload' => ['required', 'string'],
            'destination_payload_encoding' => ['nullable', 'string', 'in:base64,json'],
            'recommendation_log_id' => ['nullable', 'integer', 'exists:recommendation_logs,id'],
        ]);

        $destination = $this->decodeDestinationPayload(
            (string) $validated['destination_payload'],
            $validated['destination_payload_encoding'] ?? null
        );

        if ($destination === []) {
            throw ValidationException::withMessages([
                'destination_payload' => 'Data destinasi tidak valid.',
            ]);
        }

        $userId = (int) Auth::id();

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

            return $this->respond($request, false, 'Destinasi berhasil dihapus dari wishlist.');
        }

        Wishlist::create(array_merge($attributes, [
            'user_id' => $userId,
            'recommendation_log_id' => $recommendationLogId,
        ]));

        return $this->respond($request, true, 'Destinasi berhasil ditambahkan ke wishlist.');
    }

    public function destroy(Request $request, Wishlist $wishlist): RedirectResponse|JsonResponse
    {
        abort_if((int) $wishlist->user_id !== (int) Auth::id(), 403);

        $wishlist->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Wishlist berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Wishlist berhasil dihapus.');
    }

    /**
     * Decode payload destinasi dari tombol wishlist.
     *
     * Perbaikan penting:
     * - Mendukung payload base64 agar JSON tidak rusak saat masuk ke HTML attribute.
     * - Tetap mendukung payload JSON lama agar kompatibel dengan tombol lama.
     *
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

    private function respond(Request $request, bool $wished, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'wished' => $wished,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }
}
