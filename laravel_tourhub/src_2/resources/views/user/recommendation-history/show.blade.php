@php
    /*
     * Halaman detail riwayat rekomendasi versi user-friendly.
     * Catatan:
     * - Nilai teknis seperti final_score, cbf_score, context_multiplier,
     *   request payload, response payload, FastAPI, dan ADM4 tidak ditampilkan ke user.
     * - Nilai teknis tetap boleh dipakai di belakang layar untuk menentukan urutan rekomendasi.
     */
    $recommendations = collect(data_get($log->response_payload, 'recommendations', []))
        ->sortByDesc(fn ($item) => (float) data_get($item, 'final_score', 0))
        ->values();

    $bestRecommendation = $recommendations->first();

    $requestPayload = $log->request_payload ?? [];

    $categories = data_get($requestPayload, 'kategori_preferensi', []);
    if (is_string($categories)) {
        $categories = array_filter(array_map('trim', explode(',', $categories)));
    }
    $categories = (array) $categories;

    $keywords = data_get($requestPayload, 'keywords', []);
    if (is_string($keywords)) {
        $keywords = array_filter(array_map('trim', explode(',', $keywords)));
    }
    $keywords = (array) $keywords;

    $kabupatenKota = data_get($requestPayload, 'kabupaten_kota', '-');
    $kecamatan = data_get($requestPayload, 'kecamatan', '-');
    $minRating = data_get($requestPayload, 'min_rating', '-');
    $topN = data_get($requestPayload, 'top_n', '-');
    $visitDay = data_get($requestPayload, 'visit_day', '-');
    $useBmkg = data_get($requestPayload, 'use_bmkg') ? 'Aktif' : 'Tidak aktif';
    $isHighSeason = data_get($requestPayload, 'is_high_season') ? 'Ya' : 'Tidak';
    $bestName = data_get($bestRecommendation, 'nama_tempat_wisata', $log->top_destination_name ?? 'Detail Rekomendasi');

    $statusLabel = function (?string $status): string {
        return $status === 'success' ? 'Berhasil' : 'Belum Berhasil';
    };

    $statusBadgeClass = function (?string $status): string {
        return $status === 'success'
            ? 'bg-emerald-100 text-emerald-700 ring-emerald-200'
            : 'bg-amber-100 text-amber-700 ring-amber-200';
    };

    $formatVisitDay = function ($day): string {
        return match (strtolower((string) $day)) {
            'weekday' => 'Hari Biasa',
            'weekend' => 'Akhir Pekan',
            default => $day ? ucfirst((string) $day) : '-',
        };
    };

    $formatWeather = function ($weather): string {
        $weather = trim((string) $weather);

        return $weather !== '' && $weather !== '-'
            ? ucfirst($weather)
            : '-';
    };

    $conditionLabel = function ($item): string {
        $value = (float) data_get($item, 'context_multiplier', 1);

        if ($value >= 1.08) {
            return 'Sangat Mendukung';
        }

        if ($value >= 1.0) {
            return 'Mendukung';
        }

        return 'Perlu Dipertimbangkan';
    };

    $matchLabel = function ($item): string {
        $value = (float) data_get($item, 'cbf_score', 0);

        if ($value >= 0.55) {
            return 'Sangat Sesuai';
        }

        if ($value >= 0.25) {
            return 'Sesuai';
        }

        return 'Cukup Sesuai';
    };

    $weatherNote = function (?string $weather): string {
        return strtolower((string) $weather) === 'hujan'
            ? 'Saat cuaca hujan, sistem membantu menampilkan pilihan wisata yang lebih nyaman untuk dikunjungi.'
            : 'Cuaca saat pencarian mendukung untuk menjelajahi destinasi wisata pilihan.';
    };

    $cleanReason = function ($reason): string {
        $reason = trim((string) $reason);

        if ($reason === '') {
            return '';
        }

        // Bersihkan angka dan istilah teknis agar alasan lebih nyaman dibaca user biasa.
        $reason = preg_replace('/\s*\(\s*CBF\s*=\s*[^\)]*\)/i', '', $reason);
        $reason = preg_replace('/\s*CBF\s*=\s*[0-9\.]+\s*;?/i', '', $reason);
        $reason = preg_replace('/\s*context\s*=\s*[0-9\.]+\s*;?/i', '', $reason);
        $reason = preg_replace('/\s*final score\s*[^;\.]*[;\.]?/i', '', $reason);

        $reason = str_ireplace('cocok dengan fitur/preferensi user', 'Cocok dengan preferensi pencarianmu', $reason);
        $reason = str_ireplace('fitur/preferensi user', 'preferensi pencarianmu', $reason);
        $reason = str_ireplace('user', 'kamu', $reason);
        $reason = str_ireplace('outdoor', 'luar ruangan', $reason);
        $reason = str_ireplace('indoor', 'dalam ruangan', $reason);
        $reason = str_ireplace('mixed', 'fleksibel', $reason);
        $reason = str_ireplace('weekend', 'akhir pekan', $reason);
        $reason = str_ireplace('weekday', 'hari biasa', $reason);

        $reason = preg_replace('/\s+/', ' ', $reason);
        $reason = preg_replace('/\s*;\s*/', '; ', $reason);
        $reason = preg_replace('/;\s*;/', ';', $reason);
        $reason = trim($reason, " ;.\t\n\r\0\x0B");

        return $reason !== '' ? ucfirst($reason) . '.' : '';
    };

    $shouldShowReasonToggle = function (?string $reason): bool {
        return mb_strlen(strip_tags((string) $reason)) > 135;
    };


    $formatTourismType = function ($type): string {
        $type = strtolower(trim((string) $type));

        return match ($type) {
            'outdoor' => 'Luar ruangan',
            'indoor' => 'Dalam ruangan',
            'mixed' => 'Fleksibel',
            default => $type !== '' && $type !== '-' ? ucfirst($type) : '-',
        };
    };

    $buildFriendlyReason = function ($item, int $index = 0) use ($matchLabel, $conditionLabel, $formatTourismType): string {
        $name = trim((string) (
            data_get($item, 'nama_tempat_wisata')
            ?? data_get($item, 'destination_name')
            ?? data_get($item, 'name')
            ?? 'Destinasi ini'
        ));

        $category = trim((string) (data_get($item, 'kategori') ?? data_get($item, 'category') ?? ''));
        $type = $formatTourismType(data_get($item, 'tipe_wisata') ?? data_get($item, 'tourism_type') ?? '');
        $subdistrict = trim((string) (data_get($item, 'kecamatan') ?? data_get($item, 'subdistrict') ?? ''));
        $city = trim((string) (data_get($item, 'kabupaten_kota') ?? data_get($item, 'city') ?? ''));
        $rating = data_get($item, 'rating');
        $reviewCount = (int) (data_get($item, 'jumlah_rating') ?? data_get($item, 'review_count') ?? 0);
        $match = strtolower($matchLabel($item));
        $condition = strtolower($conditionLabel($item));

        $locationParts = array_filter([$subdistrict, $city], fn ($value) => $value !== '' && $value !== '-');
        $locationText = count($locationParts) ? implode(', ', $locationParts) : 'wilayah yang kamu pilih';

        $sentences = [];

        if ($index === 0) {
            $sentences[] = $name . ' menjadi pilihan utama karena paling mendekati preferensi dan rencana kunjunganmu.';
        } else {
            $sentences[] = $name . ' direkomendasikan karena cukup dekat dengan preferensi dan rencana kunjunganmu.';
        }

        $profileParts = [];

        if ($category !== '' && $category !== '-') {
            $profileParts[] = 'kategori ' . $category;
        }

        if ($type !== '-') {
            $profileParts[] = 'tipe kunjungan ' . strtolower($type);
        }

        if (count($profileParts)) {
            $sentences[] = 'Destinasi ini termasuk ' . implode(' dengan ', $profileParts) . ' di ' . $locationText . '.';
        } else {
            $sentences[] = 'Destinasi ini berada di ' . $locationText . ' dan masuk dalam daftar pilihan yang relevan untuk pencarianmu.';
        }

        if ($rating !== null && $rating !== '') {
            $ratingText = 'Penilaian pengunjungnya baik, dengan rating ' . $rating;

            if ($reviewCount > 0) {
                $ratingText .= ' dari ' . number_format($reviewCount) . ' ulasan';
            }

            $sentences[] = $ratingText . '.';
        } elseif ($reviewCount > 0) {
            $sentences[] = 'Destinasi ini memiliki ' . number_format($reviewCount) . ' ulasan dari pengunjung.';
        }

        $sentences[] = 'Tingkat kesesuaiannya ' . $match . ', dengan kondisi kunjungan yang ' . $condition . '.';

        return implode(' ', array_filter($sentences));
    };

    /*
     |--------------------------------------------------------------------------
     | Logic Wishlist langsung di halaman detail history
     |--------------------------------------------------------------------------
     | Sengaja ditaruh di view agar tombol wishlist di halaman history tidak
     | bergantung pada component terpisah. Controller tetap memakai route
     | wishlist.toggle yang sudah kamu buat.
     */
    $currentUserId = (int) (auth()->id() ?? 0);

    $wishlistToggleUrl = \Illuminate\Support\Facades\Route::has('wishlist.toggle')
        ? route('wishlist.toggle')
        : null;

    $wishlistIndexUrl = \Illuminate\Support\Facades\Route::has('user.wishlist.index')
        ? route('user.wishlist.index')
        : null;

    $wishlistDestinationKeys = [];

    if ($currentUserId > 0 && class_exists(\App\Models\Wishlist::class)) {
        $wishlistDestinationKeys = \App\Models\Wishlist::query()
            ->where('user_id', $currentUserId)
            ->pluck('destination_key')
            ->filter()
            ->values()
            ->all();

        $wishlistDestinationKeys = array_flip($wishlistDestinationKeys);
    }

    $normalizeTextForWishlist = function ($value): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    };

    $lowerTextForWishlist = function (?string $value): string {
        $value = (string) $value;

        return function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);
    };

    $makeWishlistDestinationKey = function ($item) use ($normalizeTextForWishlist, $lowerTextForWishlist): string {
        $destinationId = $normalizeTextForWishlist(
            data_get($item, 'id_tempat')
            ?? data_get($item, 'id')
            ?? data_get($item, 'destination_id')
        );

        if ($destinationId) {
            return sha1('id:' . $destinationId);
        }

        $name = $normalizeTextForWishlist(
            data_get($item, 'nama_tempat_wisata')
            ?? data_get($item, 'destination_name')
            ?? data_get($item, 'name')
        );

        $latitude = $normalizeTextForWishlist(data_get($item, 'latitude'));
        $longitude = $normalizeTextForWishlist(data_get($item, 'longitude'));
        $subdistrict = $normalizeTextForWishlist(data_get($item, 'kecamatan') ?? data_get($item, 'subdistrict'));
        $city = $normalizeTextForWishlist(data_get($item, 'kabupaten_kota') ?? data_get($item, 'city'));

        return sha1($lowerTextForWishlist(implode('|', [
            $name,
            $subdistrict,
            $city,
            $latitude,
            $longitude,
        ])));
    };

    $makeWishlistPayload = function ($item) use ($normalizeTextForWishlist, $makeWishlistDestinationKey): string {
        $payload = [
            'destination_key' => $makeWishlistDestinationKey($item),

            'id_tempat' => $normalizeTextForWishlist(
                data_get($item, 'id_tempat')
                ?? data_get($item, 'id')
                ?? data_get($item, 'destination_id')
            ),

            'nama_tempat_wisata' => $normalizeTextForWishlist(
                data_get($item, 'nama_tempat_wisata')
                ?? data_get($item, 'destination_name')
                ?? data_get($item, 'name')
            ),

            'kategori' => $normalizeTextForWishlist(
                data_get($item, 'kategori')
                ?? data_get($item, 'category')
            ),

            'tipe_wisata' => $normalizeTextForWishlist(
                data_get($item, 'tipe_wisata')
                ?? data_get($item, 'tourism_type')
            ),

            'kecamatan' => $normalizeTextForWishlist(
                data_get($item, 'kecamatan')
                ?? data_get($item, 'subdistrict')
            ),

            'kabupaten_kota' => $normalizeTextForWishlist(
                data_get($item, 'kabupaten_kota')
                ?? data_get($item, 'city')
            ),

            'rating' => data_get($item, 'rating'),
            'jumlah_rating' => data_get($item, 'jumlah_rating') ?? data_get($item, 'review_count'),
            'latitude' => data_get($item, 'latitude'),
            'longitude' => data_get($item, 'longitude'),

            'link_google_maps' => $normalizeTextForWishlist(
                data_get($item, 'link_google_maps')
                ?? data_get($item, 'google_maps_url')
                ?? data_get($item, 'maps_url')
            ),

            'link_gambar' => $normalizeTextForWishlist(
                data_get($item, 'link_gambar')
                ?? data_get($item, 'image_url')
            ),

            'alasan' => $normalizeTextForWishlist(
                data_get($item, 'alasan')
                ?? data_get($item, 'reason')
            ),

            'final_score' => data_get($item, 'final_score'),
            'cbf_score' => data_get($item, 'cbf_score'),
            'rating_score' => data_get($item, 'rating_score'),
            'popularity_score' => data_get($item, 'popularity_score'),
            'context_multiplier' => data_get($item, 'context_multiplier'),
        ];

        $payload = array_filter($payload, function ($value) {
            return $value !== null && $value !== '';
        });

        return base64_encode(json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        ));
    };

    $isDestinationWishlisted = function ($item) use ($makeWishlistDestinationKey, $wishlistDestinationKeys): bool {
        return isset($wishlistDestinationKeys[$makeWishlistDestinationKey($item)]);
    };

@endphp


<style>
    .history-premium-shadow {
        box-shadow:
            0 20px 60px rgba(15, 23, 42, 0.10),
            0 1px 2px rgba(15, 23, 42, 0.06);
    }

    .history-soft-grid {
        background-image:
            linear-gradient(rgba(255, 255, 255, 0.045) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.045) 1px, transparent 1px);
        background-size: 28px 28px;
    }

    .tourhub-reason-text {
        display: block;
        max-height: none;
        overflow: visible;
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: normal;
    }

    /*
     * Rapihin khusus container "Pilihan Utama" pada detail history.
     * Scope class ini hanya dipakai pada section pilihan utama agar bagian lain tidak ikut berubah.
     */
    .history-featured-main-card {
        overflow: hidden;
    }

    .history-featured-content {
        min-width: 0;
    }

    .history-featured-heading {
        max-width: 28rem;
        line-height: 1.15;
        overflow-wrap: normal;
        word-break: normal;
    }

    .history-featured-actions {
        display: flex;
        width: 100%;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.6rem;
    }

    .history-featured-actions > * {
        min-width: 0;
    }

    .history-featured-action-button {
        white-space: nowrap;
    }

    .history-featured-stat-card {
        min-width: 0;
        min-height: 5.85rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .history-featured-stat-value {
        overflow-wrap: anywhere;
        word-break: normal;
        line-height: 1.22;
    }

    @media (min-width: 1024px) {
        .history-featured-heading {
            max-width: none;
        }
    }

    @media (max-width: 1279px) {
        .history-featured-actions form,
        .history-featured-actions a {
            flex: 1 1 auto;
        }

        .history-featured-action-button {
            width: 100%;
        }
    }

    .tourhub-card-reason-content {
        display: block;
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: normal;
        transition:
            max-height 360ms cubic-bezier(0.22, 1, 0.36, 1),
            opacity 220ms ease;
    }

    .tourhub-card-reason-content.is-collapsible {
        position: relative;
        max-height: 5.65rem;
        overflow: hidden;
    }

    .tourhub-card-reason-content.is-collapsible::after {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        height: 2.4rem;
        background: linear-gradient(to bottom, rgba(248, 250, 252, 0), rgb(248, 250, 252));
        pointer-events: none;
        transition: opacity 240ms ease;
    }

    .tourhub-card-reason-content.is-expanded::after {
        opacity: 0;
    }

    .tourhub-card-reason-button {
        margin-top: 0.75rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        border-radius: 9999px;
        background: rgb(219, 234, 254);
        padding: 0.5rem 0.85rem;
        font-size: 0.75rem;
        font-weight: 900;
        color: rgb(29, 78, 216);
        box-shadow: 0 8px 22px rgba(37, 99, 235, 0.08);
        transition:
            transform 200ms ease,
            background-color 200ms ease,
            box-shadow 200ms ease;
    }

    .tourhub-card-reason-button:hover {
        transform: translateY(-1px);
        background: rgb(191, 219, 254);
        box-shadow: 0 12px 28px rgba(37, 99, 235, 0.13);
    }

    .tourhub-card-reason-button span {
        transition: transform 220ms ease;
    }

    .tourhub-card-reason-button.is-expanded span {
        transform: rotate(180deg);
    }

    @media (prefers-reduced-motion: reduce) {
        .tourhub-card-reason-content,
        .tourhub-card-reason-content::after,
        .tourhub-card-reason-button,
        .tourhub-card-reason-button span {
            transition: none !important;
        }
    }
</style>

<x-layouts.tourhub-auth title="Detail Riwayat - TourHub Bali">
    {{-- Hero Detail --}}
    <section class="relative overflow-hidden rounded-[2rem] bg-slate-950 p-6 text-white shadow-2xl shadow-slate-900/10 md:p-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.35),_transparent_34%),radial-gradient(circle_at_bottom_right,_rgba(245,158,11,0.28),_transparent_32%)]"></div>
        <div class="history-soft-grid absolute inset-0 opacity-40"></div>

        <div class="relative grid grid-cols-1 gap-6 lg:grid-cols-12 lg:items-center">
            <div class="lg:col-span-8">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-xs font-black text-white ring-1 ring-white/15">
                    <span>🧭</span>
                    Detail Riwayat Rekomendasi
                </span>

                <h1 class="mt-5 text-3xl font-black tracking-tight md:text-4xl">
                    {{ $bestName }}
                </h1>

                <p class="mt-4 max-w-3xl text-sm leading-7 text-slate-200 md:text-base">
                    Ini adalah ringkasan hasil rekomendasi wisata yang pernah kamu cari. Informasi di bawah dibuat sederhana agar mudah dibaca dan dipahami.
                </p>

                <div class="mt-5 flex flex-wrap gap-2">
                    <span class="rounded-full bg-white/10 px-3 py-1.5 text-xs font-bold text-slate-100 ring-1 ring-white/10">
                        Dicari pada {{ $log->created_at?->format('d M Y H:i') }}
                    </span>

                    <span class="rounded-full px-3 py-1.5 text-xs font-black ring-1 {{ $statusBadgeClass($log->status) }}">
                        {{ $statusLabel($log->status) }}
                    </span>

                    @if ($recommendations->isNotEmpty())
                        <span class="rounded-full bg-blue-100 px-3 py-1.5 text-xs font-black text-blue-700 ring-1 ring-blue-200">
                            {{ $recommendations->count() }} pilihan tersedia
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex flex-col gap-3 lg:col-span-4">
                <a
                    href="{{ route('user.dashboard') }}#riwayat"
                    class="inline-flex items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-black text-slate-950 shadow-lg shadow-white/10 transition hover:-translate-y-0.5 hover:bg-slate-100"
                >
                    ← Kembali ke Dashboard
                </a>

                <a
                    href="{{ route('tourhub.recommendation.index') }}"
                    class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-blue-900/20 transition hover:-translate-y-0.5 hover:bg-blue-700"
                >
                    Cari Rekomendasi Baru
                </a>
            </div>
        </div>
    </section>

    {{-- Summary Cards --}}
    <section class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="history-premium-shadow rounded-3xl border border-slate-200 bg-white p-5">
            <p class="text-sm font-semibold text-slate-500">Status Pencarian</p>
            <p class="mt-3 text-2xl font-black text-slate-950">{{ $statusLabel($log->status) }}</p>
            <p class="mt-3 text-sm leading-6 text-slate-500">
                Menunjukkan apakah pencarian rekomendasi berhasil menampilkan hasil.
            </p>
        </article>

        <article class="history-premium-shadow rounded-3xl border border-blue-200 bg-white p-5">
            <p class="text-sm font-semibold text-slate-500">Cuaca Saat Itu</p>
            <p class="mt-3 text-2xl font-black text-blue-700">{{ $formatWeather($log->weather_used ?? '-') }}</p>
            <p class="mt-3 text-sm leading-6 text-slate-500">
                {{ $weatherNote($log->weather_used ?? '-') }}
            </p>
        </article>

        <article class="history-premium-shadow rounded-3xl border border-emerald-200 bg-white p-5">
            <p class="text-sm font-semibold text-slate-500">Pilihan Tersedia</p>
            <p class="mt-3 text-2xl font-black text-emerald-600">{{ (int) ($log->total_candidates ?? $recommendations->count()) }}</p>
            <p class="mt-3 text-sm leading-6 text-slate-500">
                Jumlah destinasi yang berhasil ditemukan dari pencarian ini.
            </p>
        </article>

        <article class="history-premium-shadow rounded-3xl border border-amber-200 bg-white p-5">
            <p class="text-sm font-semibold text-slate-500">Destinasi Teratas</p>
            <p class="mt-3 line-clamp-2 text-2xl font-black text-amber-600">{{ $bestName }}</p>
            <p class="mt-3 text-sm leading-6 text-slate-500">
                Pilihan yang paling sesuai dari riwayat pencarian ini.
            </p>
        </article>
    </section>

    {{-- Preferensi yang Digunakan --}}
    <section class="mt-6 history-premium-shadow rounded-[2rem] border border-slate-200 bg-white p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-500">Preferensi Pencarian</p>
                <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950">
                    Pilihan yang Kamu Gunakan
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Ringkasan pilihan yang kamu masukkan saat mencari rekomendasi wisata.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                @forelse ($categories as $category)
                    <span class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700">
                        {{ $category }}
                    </span>
                @empty
                    <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-500">
                        Semua kategori
                    </span>
                @endforelse
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl bg-slate-50 p-4">
                <p class="text-xs font-bold text-slate-500">Kabupaten/Kota</p>
                <p class="mt-2 font-black text-slate-950">{{ $kabupatenKota ?: '-' }}</p>
            </div>

            <div class="rounded-2xl bg-slate-50 p-4">
                <p class="text-xs font-bold text-slate-500">Kecamatan</p>
                <p class="mt-2 font-black text-slate-950">{{ $kecamatan ?: '-' }}</p>
            </div>

            <div class="rounded-2xl bg-slate-50 p-4">
                <p class="text-xs font-bold text-slate-500">Rating Minimal</p>
                <p class="mt-2 font-black text-slate-950">{{ $minRating }}</p>
            </div>

            <div class="rounded-2xl bg-slate-50 p-4">
                <p class="text-xs font-bold text-slate-500">Jumlah Pilihan</p>
                <p class="mt-2 font-black text-slate-950">{{ $topN }}</p>
            </div>

            <div class="rounded-2xl bg-slate-50 p-4">
                <p class="text-xs font-bold text-slate-500">Hari Kunjungan</p>
                <p class="mt-2 font-black text-slate-950">{{ $formatVisitDay($visitDay) }}</p>
            </div>

            <div class="rounded-2xl bg-slate-50 p-4">
                <p class="text-xs font-bold text-slate-500">Cuaca Otomatis</p>
                <p class="mt-2 font-black text-slate-950">{{ $useBmkg }}</p>
            </div>

            <div class="rounded-2xl bg-slate-50 p-4">
                <p class="text-xs font-bold text-slate-500">Musim Ramai</p>
                <p class="mt-2 font-black text-slate-950">{{ $isHighSeason }}</p>
            </div>

            <div class="rounded-2xl bg-slate-50 p-4">
                <p class="text-xs font-bold text-slate-500">Kata Kunci</p>
                <p class="mt-2 line-clamp-2 font-black text-slate-950">
                    {{ count($keywords) ? implode(', ', $keywords) : '-' }}
                </p>
            </div>
        </div>
    </section>

    @if ($log->status === 'failed')
        <section class="mt-6 rounded-[1.8rem] border border-amber-200 bg-amber-50 p-6 text-amber-900 shadow-sm">
            <h2 class="text-xl font-black">Pencarian belum berhasil</h2>
            <p class="mt-2 text-sm leading-6">
                Pencarian ini belum menemukan hasil yang sesuai. Coba ubah kata kunci, turunkan rating minimal, atau pilih wilayah yang lebih luas.
            </p>
        </section>
    @endif

    @if ($recommendations->isNotEmpty())
        {{-- Pilihan Utama --}}
        <section class="history-featured-main-card mt-6 overflow-hidden history-premium-shadow rounded-[2rem] border border-amber-200 bg-gradient-to-br from-amber-50 via-white to-blue-50">
            <div class="grid grid-cols-1 lg:grid-cols-12">
                <div class="relative min-h-[340px] lg:col-span-5">
                    @if (data_get($bestRecommendation, 'link_gambar'))
                        <img
                            src="{{ data_get($bestRecommendation, 'link_gambar') }}"
                            alt="{{ data_get($bestRecommendation, 'nama_tempat_wisata') }}"
                            class="absolute inset-0 h-full w-full object-cover"
                            loading="lazy"
                        />
                    @else
                        <div class="absolute inset-0 flex h-full w-full items-center justify-center bg-gradient-to-br from-amber-100 to-blue-100 text-sm font-bold text-slate-500">
                            No Image
                        </div>
                    @endif

                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/75 via-slate-950/10 to-transparent"></div>

                    <div class="absolute left-5 top-5 rounded-2xl bg-amber-400 px-4 py-2 text-sm font-black text-slate-950 shadow-lg shadow-amber-900/20">
                        🏆 Pilihan Utama
                    </div>

                    <div class="absolute bottom-5 left-5 right-5 text-white">
                        <p class="text-xs font-black tracking-wider text-blue-100 uppercase">
                            Paling Direkomendasikan
                        </p>

                        <h3 class="mt-2 text-3xl font-black tracking-tight md:text-4xl">
                            {{ data_get($bestRecommendation, 'nama_tempat_wisata') }}
                        </h3>

                        <p class="mt-2 text-sm font-semibold text-slate-200">
                            {{ data_get($bestRecommendation, 'kecamatan') }} - {{ data_get($bestRecommendation, 'kabupaten_kota') }}
                        </p>
                    </div>
                </div>

                <div class="history-featured-content p-6 md:p-8 lg:col-span-7">
                    <div class="flex flex-col gap-5">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap gap-2">
                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-700">
                                        Pilihan Utama
                                    </span>

                                    <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                                        {{ data_get($bestRecommendation, 'kategori') ?? '-' }}
                                    </span>

                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                                        {{ $formatTourismType(data_get($bestRecommendation, 'tipe_wisata', '-')) }}
                                    </span>
                                </div>

                                <h2 class="history-featured-heading mt-4 text-2xl font-black tracking-tight text-slate-950 md:text-3xl">
                                    Kenapa destinasi ini cocok?
                                </h2>
                            </div>

                            @php
                                $bestIsWishlisted = $isDestinationWishlisted($bestRecommendation);
                                $bestWishlistPayload = $makeWishlistPayload($bestRecommendation);
                            @endphp

                            <div class="history-featured-actions xl:w-auto xl:justify-end">
                                @if ($wishlistToggleUrl)
                                    <form method="POST" action="{{ $wishlistToggleUrl }}">
                                        @csrf

                                        <input type="hidden" name="recommendation_log_id" value="{{ $log->id }}">
                                        <input type="hidden" name="destination_payload_encoding" value="base64">
                                        <input type="hidden" name="destination_payload" value="{{ $bestWishlistPayload }}">

                                        <button
                                            type="submit"
                                            class="{{ $bestIsWishlisted ? 'bg-amber-400 text-slate-950 hover:bg-amber-500' : 'bg-white text-slate-800 ring-1 ring-slate-200 hover:bg-amber-50 hover:text-amber-700 hover:ring-amber-200' }} history-featured-action-button inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-black shadow-sm transition"
                                        >
                                            <span>{{ $bestIsWishlisted ? '★' : '☆' }}</span>
                                            <span>{{ $bestIsWishlisted ? 'Tersimpan' : 'Wishlist' }}</span>
                                        </button>
                                    </form>
                                @endif

                                @if ($wishlistIndexUrl)
                                    <a
                                        href="{{ $wishlistIndexUrl }}"
                                        class="history-featured-action-button inline-flex shrink-0 items-center justify-center rounded-2xl bg-amber-100 px-4 py-2.5 text-sm font-black text-amber-700 transition hover:bg-amber-200"
                                    >
                                        Lihat Wishlist
                                    </a>
                                @endif

                                @if (data_get($bestRecommendation, 'link_google_maps'))
                                    <a
                                        href="{{ data_get($bestRecommendation, 'link_google_maps') }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="history-featured-action-button inline-flex shrink-0 items-center justify-center rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-black text-white shadow-sm shadow-emerald-600/25 transition hover:bg-emerald-700"
                                    >
                                        📍 Buka Maps
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-3 xl:grid-cols-4">
                        <div class="history-featured-stat-card rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
                            <p class="text-xs font-bold tracking-wide text-slate-500 uppercase">Rating</p>
                            <p class="history-featured-stat-value mt-1 text-xl font-black text-slate-950">
                                {{ data_get($bestRecommendation, 'rating') }}
                            </p>
                        </div>

                        <div class="history-featured-stat-card rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
                            <p class="text-xs font-bold tracking-wide text-slate-500 uppercase">Ulasan</p>
                            <p class="history-featured-stat-value mt-1 text-xl font-black text-slate-950">
                                {{ number_format((int) data_get($bestRecommendation, 'jumlah_rating', 0)) }}
                            </p>
                        </div>

                        <div class="history-featured-stat-card rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
                            <p class="text-xs font-bold tracking-wide text-slate-500 uppercase">Kesesuaian</p>
                            <p class="history-featured-stat-value mt-1 text-sm font-black text-slate-950 md:text-base">
                                {{ $matchLabel($bestRecommendation) }}
                            </p>
                        </div>

                        <div class="history-featured-stat-card rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
                            <p class="text-xs font-bold tracking-wide text-slate-500 uppercase">Kondisi</p>
                            <p class="history-featured-stat-value mt-1 text-sm font-black text-slate-950 md:text-base">
                                {{ $conditionLabel($bestRecommendation) }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                        <p class="text-xs font-black tracking-wider text-amber-700 uppercase">
                            Alasan Rekomendasi
                        </p>

                        <p class="mt-2 text-sm leading-6 text-slate-700">
                            Destinasi ini menjadi pilihan utama karena paling sesuai dengan preferensi pencarianmu, memiliki kualitas penilaian yang baik, dan cocok dengan kondisi kunjungan saat pencarian dibuat.
                        </p>

                        @php
                            $bestReason = $buildFriendlyReason($bestRecommendation, 0);
                        @endphp

                        @if ($bestReason)
                            <p class="tourhub-reason-text mt-3 text-sm leading-6 text-slate-700">
                                {{ $bestReason }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        {{-- Daftar Rekomendasi --}}
        <section class="mt-6 history-premium-shadow rounded-[2rem] border border-slate-200 bg-white p-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-500">Daftar Rekomendasi</p>
                    <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950">
                        Pilihan Wisata yang Cocok untuk Kamu
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Destinasi paling atas adalah pilihan yang paling direkomendasikan dari pencarian ini.
                    </p>
                </div>

                <span class="rounded-full bg-blue-50 px-4 py-2 text-sm font-black text-blue-700">
                    {{ $recommendations->count() }} pilihan
                </span>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($recommendations as $index => $item)
                    <article class="group overflow-hidden rounded-3xl border {{ $index === 0 ? 'border-amber-300 bg-amber-50/40' : 'border-slate-200 bg-white' }} transition hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-900/10">
                        <div class="relative h-52 overflow-hidden">
                            @if (data_get($item, 'link_gambar'))
                                <img
                                    src="{{ data_get($item, 'link_gambar') }}"
                                    alt="{{ data_get($item, 'nama_tempat_wisata') }}"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                    loading="lazy"
                                />
                            @else
                                <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200 text-sm font-bold text-slate-400">
                                    No Image
                                </div>
                            @endif

                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-transparent to-transparent"></div>

                            <div class="absolute left-4 top-4 rounded-2xl {{ $index === 0 ? 'bg-amber-400 text-slate-950' : 'bg-white/90 text-slate-950' }} px-3 py-2 text-xs font-black shadow backdrop-blur">
                                {{ $index === 0 ? 'Pilihan Utama' : 'Rekomendasi' }}
                            </div>

                            <div class="absolute bottom-4 left-4 right-4">
                                <p class="text-xs font-bold text-blue-100">
                                    {{ $matchLabel($item) }} untuk preferensimu
                                </p>
                                <h3 class="mt-1 line-clamp-2 text-2xl font-black tracking-tight text-white">
                                    {{ data_get($item, 'nama_tempat_wisata') }}
                                </h3>
                            </div>
                        </div>

                        <div class="p-4">
                            <div class="flex flex-wrap gap-2">
                                @if ($index === 0)
                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-700">
                                        🏆 Paling Cocok
                                    </span>
                                @endif

                                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                                    {{ data_get($item, 'kategori') ?? '-' }}
                                </span>

                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                                    {{ $formatTourismType(data_get($item, 'tipe_wisata', '-')) }}
                                </span>
                            </div>

                            <p class="mt-3 text-sm font-medium text-slate-600">
                                📍 {{ data_get($item, 'kecamatan') }} - {{ data_get($item, 'kabupaten_kota') }}
                            </p>

                            <div class="mt-4 grid grid-cols-2 gap-2.5">
                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                    <p class="text-xs font-bold tracking-wide text-slate-500 uppercase">Rating</p>
                                    <p class="mt-1 text-lg font-black text-slate-950">
                                        {{ data_get($item, 'rating') }}
                                    </p>
                                </div>

                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                    <p class="text-xs font-bold tracking-wide text-slate-500 uppercase">Ulasan</p>
                                    <p class="mt-1 text-lg font-black text-slate-950">
                                        {{ number_format((int) data_get($item, 'jumlah_rating', 0)) }}
                                    </p>
                                </div>

                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                    <p class="text-xs font-bold tracking-wide text-slate-500 uppercase">Kesesuaian</p>
                                    <p class="mt-1 text-sm font-black text-slate-950">
                                        {{ $matchLabel($item) }}
                                    </p>
                                </div>

                                <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                    <p class="text-xs font-bold tracking-wide text-slate-500 uppercase">Kondisi</p>
                                    <p class="mt-1 text-sm font-black text-slate-950">
                                        {{ $conditionLabel($item) }}
                                    </p>
                                </div>
                            </div>

                            @php
                                $itemReason = $buildFriendlyReason($item, $index);
                                $needsToggle = $shouldShowReasonToggle($itemReason);
                            @endphp

                            @if ($itemReason)
                                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-xs font-black tracking-wider text-slate-500 uppercase">
                                        Alasan
                                    </p>

                                    <p
                                        class="tourhub-card-reason-content mt-2 text-sm leading-6 text-slate-700 {{ $needsToggle ? 'is-collapsible' : '' }}"
                                        data-card-reason-content
                                        @if ($needsToggle) data-collapsible-reason="true" @endif
                                    >
                                        {{ $itemReason }}
                                    </p>

                                    @if ($needsToggle)
                                        <button
                                            type="button"
                                            class="tourhub-card-reason-button"
                                            data-card-reason-button
                                            aria-expanded="false"
                                        >
                                            <span>⌄</span>
                                            Baca selengkapnya
                                        </button>
                                    @endif
                                </div>
                            @endif

                            @php
                                $itemIsWishlisted = $isDestinationWishlisted($item);
                                $itemWishlistPayload = $makeWishlistPayload($item);
                            @endphp

                            <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center">
                                @if ($wishlistToggleUrl)
                                    <form method="POST" action="{{ $wishlistToggleUrl }}" class="w-full">
                                        @csrf

                                        <input type="hidden" name="recommendation_log_id" value="{{ $log->id }}">
                                        <input type="hidden" name="destination_payload_encoding" value="base64">
                                        <input type="hidden" name="destination_payload" value="{{ $itemWishlistPayload }}">

                                        <button
                                            type="submit"
                                            class="{{ $itemIsWishlisted ? 'bg-amber-400 text-slate-950 hover:bg-amber-500' : 'bg-white text-slate-800 ring-1 ring-slate-200 hover:bg-amber-50 hover:text-amber-700 hover:ring-amber-200' }} inline-flex w-full items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-black shadow-sm transition"
                                        >
                                            <span>{{ $itemIsWishlisted ? '★' : '☆' }}</span>
                                            <span>{{ $itemIsWishlisted ? 'Tersimpan' : 'Wishlist' }}</span>
                                        </button>
                                    </form>
                                @endif

                                @if (data_get($item, 'link_google_maps'))
                                    <a
                                        href="{{ data_get($item, 'link_google_maps') }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-100 px-4 py-3 text-sm font-black text-emerald-700 transition hover:bg-emerald-200"
                                    >
                                        📍 Buka Maps
                                    </a>
                                @else
                                    <div class="w-full rounded-2xl bg-slate-100 px-4 py-3 text-center text-sm font-bold text-slate-500">
                                        Maps belum tersedia
                                    </div>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-card-reason-button]').forEach((button) => {
                const reasonBox = button.closest('div')?.querySelector('[data-card-reason-content]')

                if (!reasonBox) {
                    return
                }

                const collapsedHeight = reasonBox.offsetHeight

                button.addEventListener('click', () => {
                    const isExpanded = reasonBox.classList.contains('is-expanded')

                    if (isExpanded) {
                        reasonBox.style.maxHeight = `${reasonBox.scrollHeight}px`

                        requestAnimationFrame(() => {
                            reasonBox.classList.remove('is-expanded')
                            reasonBox.style.maxHeight = `${collapsedHeight}px`
                        })

                        window.setTimeout(() => {
                            reasonBox.style.maxHeight = ''
                        }, 380)

                        button.classList.remove('is-expanded')
                        button.setAttribute('aria-expanded', 'false')
                        button.innerHTML = '<span>⌄</span> Baca selengkapnya'
                    } else {
                        reasonBox.style.maxHeight = `${collapsedHeight}px`

                        requestAnimationFrame(() => {
                            reasonBox.classList.add('is-expanded')
                            reasonBox.style.maxHeight = `${reasonBox.scrollHeight}px`
                        })

                        button.classList.add('is-expanded')
                        button.setAttribute('aria-expanded', 'true')
                        button.innerHTML = '<span>⌄</span> Tutup'
                    }
                })
            })
        })
    </script>
</x-layouts.tourhub-auth>
