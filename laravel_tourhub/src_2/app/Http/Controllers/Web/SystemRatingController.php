<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RecommendationLog;
use App\Models\SystemRating;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class SystemRatingController extends Controller
{
    /**
     * Simpan atau perbarui rating sistem dari halaman web/Blade.
     *
     * Catatan:
     * - Rating ini adalah rating kualitas sistem rekomendasi TourHub.
     * - Bukan rating tempat wisata.
     * - Satu user hanya punya satu rating untuk satu recommendation_log_id.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recommendation_log_id' => ['required', 'integer', 'exists:recommendation_logs,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'source' => ['nullable', 'string', 'max:100'],
            'platform' => ['nullable', 'string', Rule::in(['web', 'mobile', 'api'])],
        ], [
            'recommendation_log_id.required' => 'Riwayat rekomendasi tidak ditemukan.',
            'recommendation_log_id.exists' => 'Riwayat rekomendasi tidak valid.',
            'rating.required' => 'Silakan pilih rating terlebih dahulu.',
            'rating.integer' => 'Rating harus berupa angka.',
            'rating.min' => 'Rating minimal 1 bintang.',
            'rating.max' => 'Rating maksimal 5 bintang.',
            'comment.max' => 'Komentar maksimal 1000 karakter.',
            'platform.in' => 'Platform rating tidak valid.',
        ]);

        if (! Schema::hasTable('system_ratings')) {
            return back()
                ->withInput()
                ->with('error', 'Tabel system_ratings belum tersedia. Jalankan migration rating system terlebih dahulu.');
        }

        $user = $request->user();

        if (! $user) {
            return redirect()
                ->route('user.login')
                ->with('error', 'Silakan login terlebih dahulu untuk memberikan rating sistem.');
        }

        $recommendationLog = RecommendationLog::query()
            ->where('id', $validated['recommendation_log_id'])
            ->where('user_id', $user->id)
            ->first();

        if (! $recommendationLog) {
            return back()
                ->withInput()
                ->with('error', 'Riwayat rekomendasi tidak ditemukan atau bukan milik akun kamu.');
        }

        if ($recommendationLog->status !== 'success') {
            return back()
                ->withInput()
                ->with('error', 'Rating hanya bisa diberikan pada rekomendasi yang berhasil.');
        }

        $systemRating = SystemRating::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'recommendation_log_id' => $recommendationLog->id,
            ],
            [
                'rating' => (int) $validated['rating'],
                'comment' => $validated['comment'] ?? null,
                'source' => $validated['source'] ?? 'web_page',
                'platform' => $validated['platform'] ?? 'web',
                'rated_at' => now(),
            ]
        );

        $message = $systemRating->wasRecentlyCreated
            ? 'Terima kasih, rating sistem berhasil dikirim.'
            : 'Rating sistem berhasil diperbarui.';

        return back()->with('success', $message);
    }

    /**
     * Perbarui rating sistem dari halaman web/Blade.
     */
    public function update(Request $request, SystemRating $systemRating): RedirectResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'source' => ['nullable', 'string', 'max:100'],
            'platform' => ['nullable', 'string', Rule::in(['web', 'mobile', 'api'])],
        ], [
            'rating.required' => 'Silakan pilih rating terlebih dahulu.',
            'rating.integer' => 'Rating harus berupa angka.',
            'rating.min' => 'Rating minimal 1 bintang.',
            'rating.max' => 'Rating maksimal 5 bintang.',
            'comment.max' => 'Komentar maksimal 1000 karakter.',
            'platform.in' => 'Platform rating tidak valid.',
        ]);

        $user = $request->user();

        if (! $user || (int) $systemRating->user_id !== (int) $user->id) {
            abort(403, 'Kamu tidak memiliki akses untuk mengubah rating ini.');
        }

        $systemRating->update([
            'rating' => (int) $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'source' => $validated['source'] ?? $systemRating->source ?? 'web_page',
            'platform' => $validated['platform'] ?? $systemRating->platform ?? 'web',
            'rated_at' => now(),
        ]);

        return back()->with('success', 'Rating sistem berhasil diperbarui.');
    }

    /**
     * Hapus rating sistem milik user dari halaman web/Blade.
     */
    public function destroy(Request $request, SystemRating $systemRating): RedirectResponse
    {
        $user = $request->user();

        if (! $user || (int) $systemRating->user_id !== (int) $user->id) {
            abort(403, 'Kamu tidak memiliki akses untuk menghapus rating ini.');
        }

        $systemRating->delete();

        return back()->with('success', 'Rating sistem berhasil dihapus.');
    }
}
