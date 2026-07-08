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

    /*
     |--------------------------------------------------------------------------
     | Rating Sistem TourHub pada Detail Riwayat
     |--------------------------------------------------------------------------
     | Fitur ini hanya menambahkan form penilaian kualitas sistem rekomendasi.
     | Catatan penting:
     | - Ini bukan rating tempat wisata.
     | - Fitur lama di halaman history tetap dipertahankan.
     | - Query dibuat defensif agar halaman tidak langsung error jika migration
     |   atau route rating belum dipasang.
     */
    $systemRatingRouteAvailable = \Illuminate\Support\Facades\Route::has('system-ratings.store');
    $systemRatingDestroyRouteAvailable = \Illuminate\Support\Facades\Route::has('system-ratings.destroy');

    $systemRatingTableAvailable = false;

    try {
        $systemRatingTableAvailable = class_exists(\App\Models\SystemRating::class)
            && \Illuminate\Support\Facades\Schema::hasTable('system_ratings');
    } catch (\Throwable $exception) {
        $systemRatingTableAvailable = false;
    }

    $existingSystemRating = null;

    if (
        $currentUserId > 0
        && $systemRatingTableAvailable
        && class_exists(\App\Models\SystemRating::class)
    ) {
        try {
            $existingSystemRating = \App\Models\SystemRating::query()
                ->where('user_id', $currentUserId)
                ->latest('rated_at')
                ->first();
        } catch (\Throwable $exception) {
            $existingSystemRating = null;
        }
    }

    $selectedSystemRating = (int) old('rating', (int) ($existingSystemRating?->rating ?? 0));
    $systemRatingComment = old('comment', (string) ($existingSystemRating?->comment ?? ''));

    $systemRatingLabel = function (int $rating): string {
        return match ($rating) {
            1 => 'Kurang membantu',
            2 => 'Cukup kurang',
            3 => 'Cukup membantu',
            4 => 'Membantu',
            5 => 'Sangat membantu',
            default => 'Belum diberi rating',
        };
    };

    $systemRatingDescription = function (int $rating): string {
        return match ($rating) {
            1 => 'Hasil rekomendasi belum sesuai dengan kebutuhanmu.',
            2 => 'Hasil rekomendasi masih perlu banyak diperbaiki.',
            3 => 'Hasil rekomendasi cukup membantu, tetapi masih bisa ditingkatkan.',
            4 => 'Hasil rekomendasi sudah membantu memilih destinasi wisata.',
            5 => 'Hasil rekomendasi sangat membantu dan sesuai dengan preferensimu.',
            default => 'Berikan penilaian setelah kamu melihat hasil rekomendasi pada riwayat ini.',
        };
    };

    $systemRatingEmoji = function (int $rating): string {
        return match ($rating) {
            1 => '😕',
            2 => '🙂',
            3 => '😊',
            4 => '🤩',
            5 => '🏆',
            default => '⭐',
        };
    };

    $systemRatingProgress = min(100, max(0, $selectedSystemRating * 20));


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

    /*
     * Tambahan khusus Rating Sistem TourHub.
     * Scope class diawali history-system-rating agar tidak mengganggu fitur lama.
     * Bagian ini dibuat lebih ekspresif karena rating sistem adalah feedback utama user.
     */
    .history-system-rating-shell {
        position: relative;
        overflow: hidden;
        isolation: isolate;
        background:
            radial-gradient(circle at 12% 8%, rgba(250, 204, 21, 0.26), transparent 30%),
            radial-gradient(circle at 92% 18%, rgba(59, 130, 246, 0.18), transparent 30%),
            radial-gradient(circle at 50% 110%, rgba(245, 158, 11, 0.18), transparent 32%),
            rgb(255, 255, 255);
    }

    .history-system-rating-shell::before {
        content: '';
        position: absolute;
        inset: -2px;
        z-index: -2;
        background:
            linear-gradient(120deg, rgba(245, 158, 11, 0.60), rgba(59, 130, 246, 0.30), rgba(251, 191, 36, 0.50));
        opacity: 0.50;
    }

    .history-system-rating-shell::after {
        content: '';
        position: absolute;
        inset: 1px;
        z-index: -1;
        border-radius: 1.9rem;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
    }

    .history-system-rating-note {
        position: relative;
        overflow: hidden;
        background-image:
            radial-gradient(circle at top left, rgba(59, 130, 246, 0.16), transparent 34%),
            radial-gradient(circle at bottom right, rgba(245, 158, 11, 0.22), transparent 34%),
            linear-gradient(135deg, rgba(255, 251, 235, 0.92), rgba(239, 246, 255, 0.88));
    }

    .history-system-rating-note::before {
        content: '★ ★ ★';
        position: absolute;
        right: 1.5rem;
        top: -1rem;
        color: rgba(245, 158, 11, 0.12);
        font-size: 5rem;
        font-weight: 900;
        letter-spacing: 0.35rem;
        transform: rotate(-10deg);
        pointer-events: none;
    }

    .history-system-rating-status-card {
        position: relative;
        overflow: hidden;
    }

    .history-system-rating-status-card::before {
        content: '';
        position: absolute;
        inset: auto -25% -45% -25%;
        height: 5rem;
        background: radial-gradient(circle, rgba(245, 158, 11, 0.20), transparent 68%);
        pointer-events: none;
    }

    .history-system-rating-form-card {
        position: relative;
        overflow: hidden;
        border-radius: 1.5rem;
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.86)),
            radial-gradient(circle at top right, rgba(250, 204, 21, 0.14), transparent 34%);
        box-shadow:
            0 20px 45px rgba(15, 23, 42, 0.08),
            inset 0 0 0 1px rgba(226, 232, 240, 0.90);
    }

    .history-system-rating-group {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 0.7rem;
    }

    .history-system-rating-star {
        --star-bg: rgba(255, 255, 255, 0.96);
        --star-border: rgb(226, 232, 240);
        --star-text: rgb(148, 163, 184);
        --star-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
        position: relative;
        min-height: 6.3rem;
        transform-style: preserve-3d;
        border-color: var(--star-border);
        background:
            radial-gradient(circle at 50% 18%, rgba(255, 255, 255, 0.90), transparent 28%),
            var(--star-bg);
        color: var(--star-text);
        box-shadow: var(--star-shadow);
        user-select: none;
        transition:
            transform 220ms cubic-bezier(0.22, 1, 0.36, 1),
            background-color 220ms ease,
            color 220ms ease,
            box-shadow 220ms ease,
            border-color 220ms ease,
            filter 220ms ease;
    }

    .history-system-rating-star::before {
        content: '';
        position: absolute;
        inset: 0.25rem;
        border-radius: 1.1rem;
        background:
            linear-gradient(135deg, rgba(255, 255, 255, 0.70), transparent 42%),
            radial-gradient(circle at 50% 100%, rgba(250, 204, 21, 0.0), transparent 52%);
        opacity: 0;
        transition: opacity 220ms ease;
        pointer-events: none;
    }

    .history-system-rating-star::after {
        content: '';
        position: absolute;
        left: 50%;
        top: 50%;
        width: 0.7rem;
        height: 0.7rem;
        border-radius: 9999px;
        background: rgba(250, 204, 21, 0.55);
        opacity: 0;
        transform: translate(-50%, -50%) scale(0.4);
        box-shadow:
            0 -2.35rem 0 rgba(250, 204, 21, 0.38),
            1.75rem -1.55rem 0 rgba(251, 191, 36, 0.30),
            2.2rem 0.25rem 0 rgba(245, 158, 11, 0.24),
            1.0rem 2.0rem 0 rgba(251, 191, 36, 0.25),
            -1.0rem 2.0rem 0 rgba(250, 204, 21, 0.25),
            -2.2rem 0.25rem 0 rgba(245, 158, 11, 0.24),
            -1.75rem -1.55rem 0 rgba(251, 191, 36, 0.30);
        pointer-events: none;
    }

    .history-system-rating-star:hover,
    .history-system-rating-star.is-preview,
    .history-system-rating-star.is-active {
        --star-bg: rgb(255, 251, 235);
        --star-border: rgb(245, 158, 11);
        --star-text: rgb(15, 23, 42);
        --star-shadow: 0 18px 38px rgba(245, 158, 11, 0.20);
        transform: translateY(-5px) scale(1.035);
        filter: saturate(1.08);
    }

    .history-system-rating-star.is-active {
        background:
            radial-gradient(circle at 50% 10%, rgba(255, 255, 255, 0.92), transparent 24%),
            linear-gradient(180deg, rgb(254, 240, 138), rgb(251, 191, 36));
        animation: historyRatingPop 420ms cubic-bezier(0.22, 1, 0.36, 1);
    }

    .history-system-rating-star.is-preview:not(.is-active) {
        background:
            radial-gradient(circle at 50% 10%, rgba(255, 255, 255, 0.92), transparent 24%),
            linear-gradient(180deg, rgb(254, 249, 195), rgb(253, 230, 138));
    }

    .history-system-rating-star.is-just-selected {
        animation: historyRatingBounce 520ms cubic-bezier(0.22, 1, 0.36, 1);
    }

    .history-system-rating-star.is-just-selected::after {
        animation: historyRatingSpark 680ms ease-out;
    }

    .history-system-rating-star:hover::before,
    .history-system-rating-star.is-preview::before,
    .history-system-rating-star.is-active::before {
        opacity: 1;
    }

    .history-system-rating-star:has(input:focus-visible) {
        outline: 4px solid rgba(59, 130, 246, 0.20);
        outline-offset: 4px;
    }

    .history-system-rating-star-visual {
        position: relative;
        z-index: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.8rem;
        height: 2.8rem;
        border-radius: 9999px;
        font-size: 2rem;
        line-height: 1;
        text-shadow:
            0 1px 0 rgba(255, 255, 255, 0.65),
            0 8px 16px rgba(245, 158, 11, 0.15);
        transform: translateZ(14px);
        transition:
            transform 220ms cubic-bezier(0.22, 1, 0.36, 1),
            text-shadow 220ms ease;
    }

    .history-system-rating-star:hover .history-system-rating-star-visual,
    .history-system-rating-star.is-preview .history-system-rating-star-visual,
    .history-system-rating-star.is-active .history-system-rating-star-visual {
        transform: translateZ(20px) rotate(-5deg) scale(1.14);
        text-shadow:
            0 1px 0 rgba(255, 255, 255, 0.72),
            0 12px 22px rgba(146, 64, 14, 0.20);
    }

    .history-system-rating-star-score {
        position: relative;
        z-index: 1;
        margin-top: 0.35rem;
        font-size: 0.78rem;
        font-weight: 950;
        letter-spacing: 0.04em;
    }

    .history-system-rating-star-caption {
        position: relative;
        z-index: 1;
        margin-top: 0.22rem;
        max-width: 5.4rem;
        font-size: 0.66rem;
        font-weight: 850;
        line-height: 0.92rem;
        color: currentColor;
        opacity: 0.78;
    }

    .history-system-rating-live-card {
        position: relative;
        overflow: hidden;
        border-radius: 1.35rem;
        background:
            linear-gradient(135deg, rgba(15, 23, 42, 0.96), rgba(30, 41, 59, 0.94)),
            radial-gradient(circle at top right, rgba(250, 204, 21, 0.22), transparent 36%);
        color: white;
        box-shadow: 0 22px 42px rgba(15, 23, 42, 0.18);
    }

    .history-system-rating-live-card::before {
        content: '';
        position: absolute;
        inset: -35%;
        background:
            conic-gradient(from 180deg, transparent, rgba(250, 204, 21, 0.18), transparent, rgba(59, 130, 246, 0.14), transparent);
        animation: historyRatingRotate 9s linear infinite;
        opacity: 0.75;
        pointer-events: none;
    }

    .history-system-rating-live-card > * {
        position: relative;
        z-index: 1;
    }

    .history-system-rating-emoji {
        display: flex;
        width: 3.7rem;
        height: 3.7rem;
        align-items: center;
        justify-content: center;
        border-radius: 1.25rem;
        background: rgba(255, 255, 255, 0.12);
        font-size: 2rem;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.14);
        transition: transform 280ms cubic-bezier(0.22, 1, 0.36, 1);
    }

    .history-system-rating-live-card.is-updated .history-system-rating-emoji {
        animation: historyRatingWiggle 560ms ease;
    }

    .history-system-rating-meter {
        position: relative;
        height: 0.85rem;
        overflow: hidden;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.14);
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.10);
    }

    .history-system-rating-meter-fill {
        display: block;
        height: 100%;
        width: 0%;
        border-radius: inherit;
        background:
            linear-gradient(90deg, rgb(251, 191, 36), rgb(245, 158, 11), rgb(250, 204, 21));
        box-shadow:
            0 0 18px rgba(250, 204, 21, 0.55),
            inset 0 1px 0 rgba(255, 255, 255, 0.45);
        transition: width 420ms cubic-bezier(0.22, 1, 0.36, 1);
    }

    .history-system-rating-submit {
        position: relative;
        overflow: hidden;
    }

    .history-system-rating-submit::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.18), transparent);
        transform: translateX(-120%);
        transition: transform 620ms ease;
    }

    .history-system-rating-submit:hover::after {
        transform: translateX(120%);
    }

    .history-system-rating-side-card {
        position: relative;
        overflow: hidden;
        background:
            linear-gradient(180deg, rgba(248, 250, 252, 0.96), rgba(255, 255, 255, 0.96)),
            radial-gradient(circle at top right, rgba(59, 130, 246, 0.14), transparent 34%);
    }

    .history-system-rating-error-shake {
        animation: historyRatingShake 420ms ease;
    }

    @keyframes historyRatingPop {
        0% {
            transform: translateY(0) scale(0.96);
        }
        55% {
            transform: translateY(-7px) scale(1.08);
        }
        100% {
            transform: translateY(-5px) scale(1.035);
        }
    }

    @keyframes historyRatingBounce {
        0% {
            transform: translateY(-5px) scale(1.02) rotate(0deg);
        }
        35% {
            transform: translateY(-9px) scale(1.10) rotate(-3deg);
        }
        70% {
            transform: translateY(-4px) scale(1.03) rotate(2deg);
        }
        100% {
            transform: translateY(-5px) scale(1.035) rotate(0deg);
        }
    }

    @keyframes historyRatingSpark {
        0% {
            opacity: 0;
            transform: translate(-50%, -50%) scale(0.15);
        }
        25% {
            opacity: 1;
        }
        100% {
            opacity: 0;
            transform: translate(-50%, -50%) scale(1.85);
        }
    }

    @keyframes historyRatingRotate {
        to {
            transform: rotate(360deg);
        }
    }

    @keyframes historyRatingWiggle {
        0%, 100% {
            transform: rotate(0deg) scale(1);
        }
        30% {
            transform: rotate(-8deg) scale(1.08);
        }
        65% {
            transform: rotate(6deg) scale(1.04);
        }
    }

    @keyframes historyRatingShake {
        0%, 100% {
            transform: translateX(0);
        }
        20% {
            transform: translateX(-7px);
        }
        40% {
            transform: translateX(7px);
        }
        60% {
            transform: translateX(-5px);
        }
        80% {
            transform: translateX(5px);
        }
    }

    @media (max-width: 640px) {
        .history-system-rating-group {
            gap: 0.45rem;
        }

        .history-system-rating-star {
            min-height: 5.55rem;
            border-radius: 1rem;
            padding-left: 0.35rem;
            padding-right: 0.35rem;
        }

        .history-system-rating-star-visual {
            width: 2.35rem;
            height: 2.35rem;
            font-size: 1.65rem;
        }

        .history-system-rating-star-caption {
            display: none;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .history-system-rating-star,
        .history-system-rating-star::before,
        .history-system-rating-star::after,
        .history-system-rating-star-visual,
        .history-system-rating-meter-fill,
        .history-system-rating-live-card::before,
        .history-system-rating-submit::after,
        .history-system-rating-emoji {
            animation: none !important;
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


    @if ($log->status === 'success')
        {{-- Rating Sistem TourHub --}}
        <section id="rating-system" class="history-system-rating-shell mt-6 history-premium-shadow rounded-[2rem] border border-amber-200">
            <div class="history-system-rating-note border-b border-amber-100 p-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="max-w-3xl">
                        <span class="inline-flex items-center gap-2 rounded-full bg-amber-100 px-4 py-2 text-xs font-black text-amber-700 ring-1 ring-amber-200">
                            <span>⭐</span>
                            Rating Sistem TourHub
                        </span>

                        <h2 class="mt-4 text-2xl font-black tracking-tight text-slate-950 md:text-3xl">
                            {{ $existingSystemRating ? 'Rating sistem sudah kamu kirim' : 'Bantu nilai sistem rekomendasi TourHub' }}
                        </h2>

                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Rating ini cukup diberikan satu kali untuk menilai kualitas sistem rekomendasi TourHub secara keseluruhan, bukan untuk menilai destinasi wisata.
                            Masukanmu membantu evaluasi sistem pada penelitian skripsi ini.
                        </p>

                        <div class="mt-4 flex flex-wrap gap-2 text-xs font-black">
                            <span class="rounded-full bg-white/80 px-3 py-1.5 text-slate-700 ring-1 ring-amber-100">
                                Bintang 1 = kurang membantu
                            </span>
                            <span class="rounded-full bg-white/80 px-3 py-1.5 text-slate-700 ring-1 ring-amber-100">
                                Bintang 5 = sangat membantu
                            </span>
                        </div>
                    </div>

                    <div class="history-system-rating-status-card rounded-3xl bg-white px-5 py-4 text-center shadow-sm ring-1 ring-amber-200">
                        <p class="text-xs font-bold tracking-wide text-slate-500 uppercase">Status Rating</p>

                        @if ($existingSystemRating)
                            <p class="mt-1 text-3xl font-black text-amber-500">
                                {{ $existingSystemRating->rating }}/5
                            </p>
                            <p class="mt-1 text-xs font-black text-amber-700">
                                {{ $systemRatingLabel((int) $existingSystemRating->rating) }}
                            </p>
                        @else
                            <p class="mt-1 text-3xl font-black text-slate-950">Belum</p>
                            <p class="mt-1 text-xs font-black text-slate-500">Belum diberi rating</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="p-6">
                @if (session('success'))
                    <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-black text-emerald-700">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-black text-red-700">
                        {{ session('error') }}
                    </div>
                @endif

                @if (! $systemRatingRouteAvailable)
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-800">
                        <p class="font-black">Route rating belum tersedia.</p>
                        <p class="mt-1">
                            Tambahkan route web <span class="font-black">system-ratings.store</span> terlebih dahulu agar form rating bisa digunakan.
                        </p>
                    </div>
                @elseif (! $systemRatingTableAvailable)
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-800">
                        <p class="font-black">Tabel rating belum tersedia.</p>
                        <p class="mt-1">
                            Jalankan migration <span class="font-black">system_ratings</span> terlebih dahulu agar user bisa menyimpan rating sistem.
                        </p>
                    </div>
                @elseif ($existingSystemRating)
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                        <div class="history-system-rating-form-card p-5 md:p-6 lg:col-span-8">
                            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p class="text-sm font-black text-slate-950">Terima kasih, kamu sudah menilai sistem TourHub.</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">
                                        Karena konsep rating sistem sekarang cukup satu kali per user, form permintaan rating tidak ditampilkan lagi.
                                    </p>
                                </div>
                                <div class="rounded-3xl bg-amber-50 px-5 py-4 text-center ring-1 ring-amber-200">
                                    <p class="text-3xl font-black text-amber-500">{{ $existingSystemRating->rating }}/5</p>
                                    <p class="mt-1 text-xs font-black text-amber-700">{{ $systemRatingLabel((int) $existingSystemRating->rating) }}</p>
                                </div>
                            </div>

                            @if ($existingSystemRating->comment)
                                <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-4">
                                    <p class="text-xs font-black tracking-wide text-slate-500 uppercase">Komentar kamu</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-700">{{ $existingSystemRating->comment }}</p>
                                </div>
                            @endif
                        </div>

                        <aside class="lg:col-span-4">
                            <div class="history-system-rating-side-card rounded-3xl border border-slate-200 p-5">
                                <p class="text-sm font-black text-slate-950">Status rating sistem</p>
                                <p class="mt-3 text-sm leading-6 text-slate-600">
                                    Rating ini berlaku untuk akun kamu secara keseluruhan, bukan hanya untuk riwayat rekomendasi ini.
                                </p>

                                <div class="mt-5 rounded-2xl border border-amber-200 bg-white p-4">
                                    <p class="text-xs font-bold tracking-wide text-slate-500 uppercase">Rating terakhir</p>
                                    <p class="mt-2 text-sm font-black text-slate-950">{{ $systemRatingLabel((int) $existingSystemRating->rating) }}</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">
                                        Diberikan pada {{ $existingSystemRating->rated_at?->format('d M Y H:i') ?? $existingSystemRating->updated_at?->format('d M Y H:i') }}
                                    </p>
                                </div>
                            </div>
                        </aside>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                        <div class="lg:col-span-8">
                            <form method="POST" action="{{ route('system-ratings.store') }}" class="history-system-rating-form-card space-y-5 p-5 md:p-6" data-system-rating-form>
                                @csrf

                                <input type="hidden" name="recommendation_log_id" value="{{ $log->id }}">
                                <input type="hidden" name="source" value="history_detail_page">
                                <input type="hidden" name="platform" value="web">

                                <div>
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                                        <div>
                                            <label class="block text-sm font-black text-slate-800">
                                                Seberapa membantu sistem rekomendasi TourHub?
                                            </label>
                                            <p class="mt-1 text-xs font-semibold leading-5 text-slate-500">
                                                Klik bintang dari kiri ke kanan. Sistem akan membaca rating 1 sampai 5 secara otomatis.
                                            </p>
                                        </div>

                                        <span class="inline-flex w-max items-center gap-2 rounded-full bg-amber-50 px-3 py-1.5 text-xs font-black text-amber-700 ring-1 ring-amber-100">
                                            <span data-system-rating-mini-score>{{ $selectedSystemRating > 0 ? $selectedSystemRating . '/5' : 'Pilih rating' }}</span>
                                        </span>
                                    </div>

                                    <div class="history-system-rating-group mt-4" data-system-rating-group>
                                        @for ($ratingValue = 1; $ratingValue <= 5; $ratingValue++)
                                            <label
                                                class="history-system-rating-star {{ $selectedSystemRating >= $ratingValue ? 'is-active' : '' }} flex cursor-pointer flex-col items-center justify-center rounded-2xl border px-2 py-3 text-center"
                                                data-system-rating-star
                                                data-rating-value="{{ $ratingValue }}"
                                                data-rating-label="{{ $systemRatingLabel($ratingValue) }}"
                                                data-rating-description="{{ $systemRatingDescription($ratingValue) }}"
                                                data-rating-emoji="{{ $systemRatingEmoji($ratingValue) }}"
                                            >
                                                <input
                                                    type="radio"
                                                    name="rating"
                                                    value="{{ $ratingValue }}"
                                                    class="sr-only"
                                                    @checked($selectedSystemRating === $ratingValue)
                                                >

                                                <span class="history-system-rating-star-visual">★</span>
                                                <span class="history-system-rating-star-score">{{ $ratingValue }}/5</span>
                                                <span class="history-system-rating-star-caption">{{ $systemRatingLabel($ratingValue) }}</span>
                                            </label>
                                        @endfor
                                    </div>

                                    <div class="history-system-rating-live-card mt-4 p-4" data-system-rating-live-card>
                                        <div class="flex items-start gap-4">
                                            <span class="history-system-rating-emoji shrink-0" data-system-rating-emoji>
                                                {{ $systemRatingEmoji($selectedSystemRating) }}
                                            </span>

                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm font-black text-white" data-system-rating-label>
                                                    {{ $systemRatingLabel($selectedSystemRating) }}
                                                </p>

                                                <p class="mt-1 text-xs leading-5 text-white/70" data-system-rating-description>
                                                    {{ $systemRatingDescription($selectedSystemRating) }}
                                                </p>

                                                <div class="mt-3 history-system-rating-meter">
                                                    <span
                                                        class="history-system-rating-meter-fill"
                                                        data-system-rating-progress
                                                        style="width: {{ $systemRatingProgress }}%"
                                                    ></span>
                                                </div>

                                                <div class="mt-2 flex justify-between text-[10px] font-black uppercase tracking-wide text-white/45">
                                                    <span>1 Bintang</span>
                                                    <span>5 Bintang</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @error('rating')
                                        <p class="mt-2 text-sm font-bold text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="system_rating_comment" class="block text-sm font-black text-slate-800">
                                        Komentar atau masukan
                                        <span class="font-semibold text-slate-400">(opsional)</span>
                                    </label>

                                    <textarea
                                        id="system_rating_comment"
                                        name="comment"
                                        rows="4"
                                        maxlength="1000"
                                        placeholder="Contoh: rekomendasinya sudah sesuai, tapi saya ingin hasil yang lebih dekat dengan lokasi saya."
                                        class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-blue-300 focus:ring-4 focus:ring-blue-500/10"
                                    >{{ $systemRatingComment }}</textarea>

                                    @error('comment')
                                        <p class="mt-2 text-sm font-bold text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                    <button
                                        type="submit"
                                        class="history-system-rating-submit inline-flex w-full items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white shadow-lg shadow-slate-900/15 transition hover:-translate-y-0.5 hover:bg-slate-800 sm:w-auto"
                                    >
                                        <span class="relative z-10">Kirim Rating Sistem</span>
                                    </button>

                                    <a
                                        href="#hasil-rekomendasi-history"
                                        class="inline-flex w-full items-center justify-center rounded-2xl bg-blue-50 px-5 py-3 text-sm font-black text-blue-700 ring-1 ring-blue-100 transition hover:bg-blue-100 sm:w-auto"
                                    >
                                        Lihat Lagi Rekomendasi
                                    </a>
                                </div>
                            </form>
                        </div>

                        <aside class="lg:col-span-4">
                            <div class="history-system-rating-side-card rounded-3xl border border-slate-200 p-5">
                                <p class="text-sm font-black text-slate-950">Kenapa rating ini penting?</p>

                                <ul class="mt-3 space-y-3 text-sm leading-6 text-slate-600">
                                    <li class="flex gap-2"><span class="mt-0.5">✅</span><span>Membantu mengevaluasi apakah sistem rekomendasi sudah sesuai preferensi user.</span></li>
                                    <li class="flex gap-2"><span class="mt-0.5">📊</span><span>Bisa menjadi data pendukung pengujian kualitas sistem pada skripsi.</span></li>
                                    <li class="flex gap-2"><span class="mt-0.5">🧭</span><span>Membedakan rating sistem dari rating asli tempat wisata.</span></li>
                                </ul>

                                <div class="mt-5 rounded-2xl border border-blue-100 bg-blue-50 p-4">
                                    <p class="text-xs font-black tracking-wide text-blue-700 uppercase">Cara membaca rating</p>
                                    <div class="mt-3 space-y-2 text-xs font-semibold leading-5 text-blue-900">
                                        <p>⭐ 1 bintang: sistem kurang membantu.</p>
                                        <p>⭐⭐⭐ 3 bintang: cukup membantu.</p>
                                        <p>⭐⭐⭐⭐⭐ 5 bintang: sangat membantu.</p>
                                    </div>
                                </div>
                            </div>
                        </aside>
                    </div>
                @endif
            </div>
        </section>
    @endif

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
        <section id="hasil-rekomendasi-history" class="mt-6 history-premium-shadow rounded-[2rem] border border-slate-200 bg-white p-6">
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const emptyRatingState = {
                label: 'Belum diberi rating',
                description: 'Berikan penilaian setelah kamu melihat hasil rekomendasi pada riwayat ini.',
                emoji: '⭐',
            }

            document.querySelectorAll('[data-system-rating-form]').forEach((form) => {
                const group = form.querySelector('[data-system-rating-group]')
                const stars = group ? Array.from(group.querySelectorAll('[data-system-rating-star]')) : []
                const labelTarget = form.querySelector('[data-system-rating-label]')
                const descriptionTarget = form.querySelector('[data-system-rating-description]')
                const emojiTarget = form.querySelector('[data-system-rating-emoji]')
                const progressTarget = form.querySelector('[data-system-rating-progress]')
                const miniScoreTarget = form.querySelector('[data-system-rating-mini-score]')
                const liveCard = form.querySelector('[data-system-rating-live-card]')

                let selectedValue = Number(form.querySelector('input[name="rating"]:checked')?.value || 0)
                let previewValue = 0

                const getStarMeta = (value) => {
                    const star = stars.find((item) => Number(item.dataset.ratingValue || 0) === Number(value))

                    if (!star) {
                        return emptyRatingState
                    }

                    return {
                        label: star.dataset.ratingLabel || emptyRatingState.label,
                        description: star.dataset.ratingDescription || emptyRatingState.description,
                        emoji: star.dataset.ratingEmoji || emptyRatingState.emoji,
                    }
                }

                const animateLiveCard = () => {
                    if (!liveCard) {
                        return
                    }

                    liveCard.classList.remove('is-updated')
                    void liveCard.offsetWidth
                    liveCard.classList.add('is-updated')
                }

                const paintStars = (value, mode = 'selected') => {
                    stars.forEach((star) => {
                        const starValue = Number(star.dataset.ratingValue || 0)
                        const isFilled = starValue <= value

                        star.classList.toggle('is-active', isFilled && mode === 'selected')
                        star.classList.toggle('is-preview', isFilled && mode === 'preview')
                    })
                }

                const syncDisplay = (value, mode = 'selected') => {
                    const meta = Number(value) > 0 ? getStarMeta(value) : emptyRatingState
                    const progress = Math.max(0, Math.min(100, Number(value || 0) * 20))

                    if (labelTarget) {
                        labelTarget.textContent = meta.label
                    }

                    if (descriptionTarget) {
                        descriptionTarget.textContent = meta.description
                    }

                    if (emojiTarget) {
                        emojiTarget.textContent = meta.emoji
                    }

                    if (progressTarget) {
                        progressTarget.style.width = `${progress}%`
                    }

                    if (miniScoreTarget) {
                        miniScoreTarget.textContent = Number(value) > 0 ? `${value}/5` : 'Pilih rating'
                    }

                    paintStars(value, mode)
                }

                const selectRating = (value, sourceStar = null) => {
                    selectedValue = Number(value || 0)
                    previewValue = 0

                    const input = form.querySelector(`input[name="rating"][value="${selectedValue}"]`)

                    if (input) {
                        input.checked = true
                    }

                    stars.forEach((star) => {
                        star.classList.remove('is-just-selected')
                    })

                    if (sourceStar) {
                        sourceStar.classList.add('is-just-selected')
                        window.setTimeout(() => {
                            sourceStar.classList.remove('is-just-selected')
                        }, 620)
                    }

                    syncDisplay(selectedValue, 'selected')
                    animateLiveCard()
                }

                stars.forEach((star) => {
                    const input = star.querySelector('input[type="radio"]')
                    const value = Number(star.dataset.ratingValue || 0)

                    star.addEventListener('mouseenter', () => {
                        previewValue = value
                        syncDisplay(previewValue, 'preview')
                    })

                    star.addEventListener('mouseleave', () => {
                        previewValue = 0
                        syncDisplay(selectedValue, 'selected')
                    })

                    star.addEventListener('click', () => {
                        selectRating(value, star)
                    })

                    star.addEventListener('keydown', (event) => {
                        if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault()
                            selectRating(value, star)
                        }
                    })

                    input?.addEventListener('change', () => {
                        selectRating(value, star)
                    })
                })

                form.addEventListener('submit', (event) => {
                    const checkedInput = form.querySelector('input[name="rating"]:checked')

                    if (!checkedInput) {
                        event.preventDefault()

                        group?.classList.remove('history-system-rating-error-shake')
                        void group?.offsetWidth
                        group?.classList.add('history-system-rating-error-shake')

                        syncDisplay(0, 'selected')

                        if (descriptionTarget) {
                            descriptionTarget.textContent = 'Pilih minimal 1 bintang terlebih dahulu sebelum mengirim rating sistem.'
                        }

                        stars[0]?.focus?.()
                    }
                })

                syncDisplay(selectedValue, 'selected')
            })
        })
    </script>

</x-layouts.tourhub-auth>
