@php
    $latestPayload = $latestSuccess?->request_payload ?? [];
    $latestCategories = data_get($latestPayload, 'kategori_preferensi', []);

    if (is_string($latestCategories)) {
        $latestCategories = array_filter(array_map('trim', explode(',', $latestCategories)));
    }

    $latestCategories = (array) $latestCategories;
    $latestKabupaten = data_get($latestPayload, 'kabupaten_kota', '-');
    $latestKecamatan = data_get($latestPayload, 'kecamatan', '-');
    $latestWeather = $latestSuccess?->weather_used ?? '-';
    $latestCandidates = (int) ($latestSuccess?->total_candidates ?? 0);
    $latestTopDestination = $latestSuccess?->top_destination_name ?? '-';
    $successRate = $totalRecommendations > 0
        ? round(($successRecommendations / $totalRecommendations) * 100)
        : 0;

    $statusLabel = function (?string $status): string {
        return $status === 'success' ? 'Berhasil' : 'Belum Berhasil';
    };

    $statusClass = function (?string $status): string {
        return $status === 'success'
            ? 'bg-emerald-100 text-emerald-700 ring-emerald-200'
            : 'bg-amber-100 text-amber-700 ring-amber-200';
    };

    $formatWeather = function (?string $weather): string {
        $weather = trim((string) $weather);

        return $weather !== '' && $weather !== '-'
            ? ucfirst($weather)
            : '-';
    };

    $starText = function (?int $rating): string {
        if (! $rating) {
            return 'Belum dinilai';
        }

        return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
    };

    /*
     * =========================================================
     * Logic Rating Sistem TourHub untuk dashboard user
     * =========================================================
     * Konsep terbaru:
     * - Rating sistem adalah penilaian kualitas TourHub secara keseluruhan.
     * - Satu user cukup memberi rating satu kali saja.
     * - Rating tidak lagi menempel pada setiap riwayat rekomendasi.
     */
    $systemRatingTableAvailable = false;

    try {
        $systemRatingTableAvailable = class_exists(\App\Models\SystemRating::class)
            && \Illuminate\Support\Facades\Schema::hasTable('system_ratings');
    } catch (\Throwable $exception) {
        $systemRatingTableAvailable = false;
    }

    $pendingRatingLog = null;
    $latestSystemRating = null;
    $systemRatingStoreRoute = \Illuminate\Support\Facades\Route::has('system-ratings.store')
        ? route('system-ratings.store')
        : null;

    if (auth()->check() && $systemRatingTableAvailable) {
        try {
            $latestSystemRating = \App\Models\SystemRating::query()
                ->where('user_id', auth()->id())
                ->latest('rated_at')
                ->first();

            if (! $latestSystemRating) {
                $pendingRatingLog = \App\Models\RecommendationLog::query()
                    ->where('user_id', auth()->id())
                    ->where('status', 'success')
                    ->latest()
                    ->first();
            }
        } catch (\Throwable $exception) {
            $pendingRatingLog = null;
            $latestSystemRating = null;
        }
    }

    $selectedDashboardSystemRating = (int) old('rating', 0);
    $dashboardSystemRatingComment = old('comment', '');

    $dashboardSystemRatingLabel = function (int $rating): string {
        return match ($rating) {
            1 => 'Kurang membantu',
            2 => 'Cukup kurang',
            3 => 'Cukup membantu',
            4 => 'Membantu',
            5 => 'Sangat membantu',
            default => 'Belum memilih rating',
        };
    };

    $dashboardSystemRatingDescription = function (int $rating): string {
        return match ($rating) {
            1 => 'Rekomendasi belum sesuai dengan preferensi dan perlu banyak diperbaiki.',
            2 => 'Rekomendasi masih kurang pas, tetapi ada beberapa bagian yang bisa dipakai.',
            3 => 'Rekomendasi cukup membantu, namun masih bisa dibuat lebih akurat.',
            4 => 'Rekomendasi sudah membantu memilih destinasi wisata yang sesuai.',
            5 => 'Rekomendasi sangat membantu dan terasa sesuai dengan rencana wisata.',
            default => 'Klik bintang dari kiri ke kanan. Bintang 1 berarti kurang membantu, bintang 5 berarti sangat membantu.',
        };
    };

    $dashboardSystemRatingEmoji = function (int $rating): string {
        return match ($rating) {
            1 => '😕',
            2 => '🙂',
            3 => '😊',
            4 => '🤩',
            5 => '🏆',
            default => '⭐',
        };
    };
@endphp


<style>
    /* =========================================================
       Rating System Dashboard TourHub
       Scope class khusus dashboard-system-rating agar tidak
       mengganggu komponen dashboard lain yang sudah ada.
    ========================================================= */
    .dashboard-system-rating-shell {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        border-radius: 2rem;
        border: 1px solid rgb(252, 211, 77);
        background:
            radial-gradient(circle at top left, rgba(250, 204, 21, 0.20), transparent 34%),
            radial-gradient(circle at bottom right, rgba(59, 130, 246, 0.14), transparent 30%),
            linear-gradient(135deg, rgba(255, 251, 235, 0.94), rgb(255, 255, 255) 48%, rgba(239, 246, 255, 0.92));
        box-shadow:
            0 24px 65px rgba(15, 23, 42, 0.08),
            0 1px 2px rgba(15, 23, 42, 0.05);
    }

    .dashboard-system-rating-shell::before,
    .dashboard-system-rating-shell::after {
        content: '';
        position: absolute;
        border-radius: 9999px;
        pointer-events: none;
        z-index: -1;
    }

    .dashboard-system-rating-shell::before {
        top: -5rem;
        right: 8%;
        width: 15rem;
        height: 15rem;
        background: rgba(250, 204, 21, 0.16);
        filter: blur(12px);
    }

    .dashboard-system-rating-shell::after {
        bottom: -6rem;
        left: 7%;
        width: 18rem;
        height: 18rem;
        background: rgba(59, 130, 246, 0.10);
        filter: blur(16px);
    }

    .dashboard-system-rating-float-star {
        position: absolute;
        color: rgba(251, 191, 36, 0.18);
        filter: drop-shadow(0 14px 26px rgba(245, 158, 11, 0.10));
        animation: dashboardSystemFloat 5.6s ease-in-out infinite;
        pointer-events: none;
        user-select: none;
    }

    .dashboard-system-rating-float-star--one {
        top: 1.4rem;
        right: 18%;
        font-size: 4.5rem;
    }

    .dashboard-system-rating-float-star--two {
        bottom: 1.8rem;
        left: 3.5%;
        font-size: 3.4rem;
        animation-delay: 1.1s;
    }

    @keyframes dashboardSystemFloat {
        0%, 100% {
            transform: translateY(0) rotate(-8deg) scale(1);
        }
        50% {
            transform: translateY(-12px) rotate(8deg) scale(1.08);
        }
    }

    .dashboard-system-rating-card {
        position: relative;
        border-radius: 1.7rem;
        border: 1px solid rgb(226, 232, 240);
        background: rgba(255, 255, 255, 0.86);
        box-shadow:
            0 16px 44px rgba(15, 23, 42, 0.08),
            inset 0 1px 0 rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
    }

    .dashboard-system-rating-card::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: inherit;
        padding: 1px;
        background: linear-gradient(135deg, rgba(250, 204, 21, 0.42), rgba(59, 130, 246, 0.20), rgba(255, 255, 255, 0));
        -webkit-mask:
            linear-gradient(#fff 0 0) content-box,
            linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask-composite: exclude;
        pointer-events: none;
    }

    .dashboard-system-rating-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 0.7rem;
    }

    .dashboard-system-rating-star-card {
        position: relative;
        min-height: 7.15rem;
        cursor: pointer;
        overflow: hidden;
        border-radius: 1.25rem;
        border: 1px solid rgb(226, 232, 240);
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.94));
        color: rgb(148, 163, 184);
        box-shadow:
            0 9px 24px rgba(15, 23, 42, 0.06),
            inset 0 1px 0 rgba(255, 255, 255, 0.9);
        transition:
            transform 220ms cubic-bezier(0.22, 1, 0.36, 1),
            border-color 220ms ease,
            background 220ms ease,
            color 220ms ease,
            box-shadow 220ms ease;
    }

    .dashboard-system-rating-star-card::before {
        content: '';
        position: absolute;
        inset: -60% -20%;
        background: linear-gradient(115deg, transparent 35%, rgba(255, 255, 255, 0.72) 50%, transparent 65%);
        transform: translateX(-82%) rotate(8deg);
        transition: transform 520ms cubic-bezier(0.22, 1, 0.36, 1);
    }

    .dashboard-system-rating-star-card::after {
        content: '';
        position: absolute;
        left: 50%;
        top: 50%;
        width: 0.5rem;
        height: 0.5rem;
        border-radius: 9999px;
        background: rgba(250, 204, 21, 0.7);
        opacity: 0;
        transform: translate(-50%, -50%) scale(0.5);
        box-shadow:
            0 -2.5rem 0 rgba(250, 204, 21, 0.45),
            2.2rem -1.25rem 0 rgba(59, 130, 246, 0.30),
            2.3rem 1.25rem 0 rgba(250, 204, 21, 0.40),
            0 2.5rem 0 rgba(59, 130, 246, 0.26),
            -2.25rem 1.2rem 0 rgba(250, 204, 21, 0.34),
            -2.15rem -1.25rem 0 rgba(59, 130, 246, 0.28);
        pointer-events: none;
    }

    .dashboard-system-rating-star-card:hover,
    .dashboard-system-rating-star-card.is-preview,
    .dashboard-system-rating-star-card.is-active {
        transform: translateY(-6px) scale(1.025);
        border-color: rgb(245, 158, 11);
        background:
            radial-gradient(circle at 50% 15%, rgba(255, 255, 255, 0.86), transparent 34%),
            linear-gradient(180deg, rgb(254, 243, 199), rgb(250, 204, 21));
        color: rgb(15, 23, 42);
        box-shadow:
            0 18px 38px rgba(245, 158, 11, 0.22),
            inset 0 1px 0 rgba(255, 255, 255, 0.84);
    }

    .dashboard-system-rating-star-card:hover::before,
    .dashboard-system-rating-star-card.is-preview::before,
    .dashboard-system-rating-star-card.is-active::before {
        transform: translateX(82%) rotate(8deg);
    }

    .dashboard-system-rating-star-card.is-selected {
        animation: dashboardSystemSelectedPop 520ms cubic-bezier(0.22, 1, 0.36, 1);
    }

    .dashboard-system-rating-star-card.is-selected::after {
        animation: dashboardSystemSpark 620ms ease-out;
    }

    @keyframes dashboardSystemSelectedPop {
        0% { transform: translateY(-6px) scale(1); }
        35% { transform: translateY(-9px) scale(1.15) rotate(-2deg); }
        70% { transform: translateY(-4px) scale(0.98) rotate(1deg); }
        100% { transform: translateY(-6px) scale(1.025); }
    }

    @keyframes dashboardSystemSpark {
        0% { opacity: 0; transform: translate(-50%, -50%) scale(0.45); }
        34% { opacity: 1; }
        100% { opacity: 0; transform: translate(-50%, -50%) scale(1.8); }
    }

    .dashboard-system-rating-star-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        font-size: 2.2rem;
        text-shadow: 0 10px 20px rgba(15, 23, 42, 0.12);
        transition:
            transform 220ms cubic-bezier(0.22, 1, 0.36, 1),
            filter 220ms ease;
    }

    .dashboard-system-rating-star-card:hover .dashboard-system-rating-star-icon,
    .dashboard-system-rating-star-card.is-preview .dashboard-system-rating-star-icon,
    .dashboard-system-rating-star-card.is-active .dashboard-system-rating-star-icon {
        transform: rotate(-8deg) scale(1.18);
        filter: drop-shadow(0 10px 16px rgba(245, 158, 11, 0.28));
    }

    .dashboard-system-rating-summary {
        position: relative;
        overflow: hidden;
        border-radius: 1.5rem;
        background:
            radial-gradient(circle at top left, rgba(250, 204, 21, 0.14), transparent 32%),
            linear-gradient(135deg, rgb(15, 23, 42), rgb(30, 41, 59));
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.12);
    }

    .dashboard-system-rating-summary::after {
        content: '';
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(255, 255, 255, 0.035) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.035) 1px, transparent 1px);
        background-size: 22px 22px;
        pointer-events: none;
    }

    .dashboard-system-rating-meter {
        position: relative;
        height: 0.72rem;
        overflow: hidden;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.16);
    }

    .dashboard-system-rating-meter-fill {
        height: 100%;
        width: 0%;
        border-radius: inherit;
        background: linear-gradient(90deg, rgb(250, 204, 21), rgb(251, 146, 60), rgb(59, 130, 246));
        box-shadow: 0 0 24px rgba(250, 204, 21, 0.28);
        transition: width 520ms cubic-bezier(0.22, 1, 0.36, 1);
    }

    .dashboard-system-rating-feedback {
        transform: translateY(0);
        transition:
            transform 260ms cubic-bezier(0.22, 1, 0.36, 1),
            opacity 260ms ease;
    }

    .dashboard-system-rating-feedback.is-changing {
        opacity: 0;
        transform: translateY(8px);
    }

    .dashboard-system-rating-shake {
        animation: dashboardSystemShake 440ms ease;
    }

    @keyframes dashboardSystemShake {
        0%, 100% { transform: translateX(0); }
        20% { transform: translateX(-8px); }
        40% { transform: translateX(8px); }
        60% { transform: translateX(-5px); }
        80% { transform: translateX(5px); }
    }

    @media (max-width: 640px) {
        .dashboard-system-rating-grid {
            grid-template-columns: repeat(5, minmax(3.6rem, 1fr));
            gap: 0.45rem;
            overflow-x: auto;
            padding-bottom: 0.2rem;
        }

        .dashboard-system-rating-star-card {
            min-height: 6.45rem;
            min-width: 4.35rem;
        }

        .dashboard-system-rating-star-icon {
            font-size: 1.85rem;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .dashboard-system-rating-float-star,
        .dashboard-system-rating-star-card,
        .dashboard-system-rating-star-card::before,
        .dashboard-system-rating-star-card::after,
        .dashboard-system-rating-star-icon,
        .dashboard-system-rating-meter-fill,
        .dashboard-system-rating-feedback,
        .dashboard-system-rating-shake {
            animation: none !important;
            transition: none !important;
        }
    }
</style>

<x-layouts.tourhub-auth title="Dashboard User - TourHub Bali">
    {{-- Hero Section --}}
    <section class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-slate-900 to-sky-950 p-6 text-white shadow-2xl shadow-slate-900/10 md:p-8">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-12 md:items-center">
            <div class="md:col-span-8">
                <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-xs font-bold text-white ring-1 ring-white/15">
                    <span>👤</span>
                    <span>Panel User TourHub Bali</span>
                </div>

                <h1 class="mt-5 text-3xl font-black tracking-tight md:text-4xl">
                    Halo, {{ auth()->user()->name }}
                </h1>

                <p class="mt-4 max-w-3xl text-sm leading-7 text-slate-200 md:text-base">
                    Di dashboard ini kamu bisa melihat ringkasan pencarian wisata, rekomendasi terakhir,
                    riwayat pencarian, dan status rating sistem rekomendasi TourHub.
                </p>

                <div class="mt-5 flex flex-wrap gap-2">
                    <span class="rounded-full bg-white/10 px-3 py-1.5 text-xs font-bold text-slate-100 ring-1 ring-white/10">
                        Rekomendasi Pintar
                    </span>
                    <span class="rounded-full bg-white/10 px-3 py-1.5 text-xs font-bold text-slate-100 ring-1 ring-white/10">
                        Cuaca Terkini
                    </span>
                    <span class="rounded-full bg-white/10 px-3 py-1.5 text-xs font-bold text-slate-100 ring-1 ring-white/10">
                        Rating Sistem
                    </span>
                </div>
            </div>

            <div class="flex flex-col gap-3 md:col-span-4">
                <a
                    href="{{ route('tourhub.recommendation.index') }}"
                    class="inline-flex items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-black text-slate-950 shadow-lg shadow-white/10 transition hover:-translate-y-0.5 hover:bg-slate-100"
                >
                    Cari Rekomendasi Baru
                </a>

                <a
                    href="#riwayat"
                    class="inline-flex items-center justify-center rounded-2xl bg-white/10 px-5 py-3 text-sm font-black text-white ring-1 ring-white/15 transition hover:-translate-y-0.5 hover:bg-white/15"
                >
                    Lihat Riwayat Saya
                </a>
            </div>
        </div>
    </section>

    {{-- Flash Message --}}
    @if (session('success'))
        <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session('status'))
        <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">
            {{ session('error') }}
        </div>
    @endif


{{-- Rating System Dashboard Status --}}
@if ($latestSystemRating)
    <section id="rating-system-dashboard" class="dashboard-system-rating-shell mt-6 overflow-hidden">
        <span class="dashboard-system-rating-float-star dashboard-system-rating-float-star--one">★</span>
        <span class="dashboard-system-rating-float-star dashboard-system-rating-float-star--two">★</span>

        <div class="border-b border-emerald-100/80 p-6 md:p-7">
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-12 lg:items-start">
                <div class="lg:col-span-8">
                    <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-4 py-2 text-xs font-black text-emerald-700 ring-1 ring-emerald-200">
                        <span>💚</span>
                        Rating Sistem TourHub
                    </span>

                    <h2 class="mt-4 text-2xl font-black tracking-tight text-slate-950 md:text-3xl">
                        Terima kasih sudah menilai sistem TourHub
                    </h2>

                    <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-600">
                        Rating kamu sudah tersimpan sebagai penilaian kualitas sistem rekomendasi TourHub secara keseluruhan.
                        Karena konsepnya satu user cukup memberi satu rating, form permintaan rating tidak akan muncul lagi di dashboard.
                    </p>

                    <div class="mt-4 flex flex-wrap gap-2 text-xs font-bold">
                        <span class="rounded-full bg-white px-3 py-1.5 text-slate-700 ring-1 ring-slate-200">
                            Diberikan pada {{ $latestSystemRating->rated_at?->format('d M Y H:i') ?? $latestSystemRating->created_at?->format('d M Y H:i') ?? '-' }}
                        </span>

                        <span class="rounded-full bg-white px-3 py-1.5 text-slate-700 ring-1 ring-slate-200">
                            {{ $dashboardSystemRatingLabel((int) $latestSystemRating->rating) }}
                        </span>

                        @if ($latestSystemRating->recommendation_log_id)
                            <span class="rounded-full bg-white px-3 py-1.5 text-slate-700 ring-1 ring-slate-200">
                                Konteks riwayat #{{ $latestSystemRating->recommendation_log_id }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="lg:col-span-4">
                    <div class="rounded-[1.55rem] border border-emerald-200 bg-white/90 p-5 text-center shadow-sm backdrop-blur">
                        <p class="text-xs font-bold tracking-wide text-slate-500 uppercase">Rating Kamu</p>
                        <p class="mt-1 text-4xl font-black text-emerald-600">
                            {{ (int) $latestSystemRating->rating }}/5
                        </p>
                        <p class="mt-2 text-lg font-black leading-none text-amber-500">
                            {{ $starText((int) $latestSystemRating->rating) }}
                        </p>
                        <p class="mt-2 text-xs font-black text-emerald-700">
                            {{ $dashboardSystemRatingLabel((int) $latestSystemRating->rating) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 md:p-7">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <div class="dashboard-system-rating-card p-5 md:p-6 lg:col-span-8">
                    <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                        <div class="flex items-start gap-4">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-3xl text-emerald-700 ring-1 ring-emerald-200">
                                ✅
                            </div>

                            <div>
                                <p class="text-sm font-black text-slate-950">
                                    Penilaianmu membantu evaluasi sistem rekomendasi.
                                </p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    Masukan ini bisa dipakai sebagai data pendukung untuk melihat apakah sistem rekomendasi TourHub sudah membantu user dalam memilih destinasi wisata Bali.
                                </p>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-center ring-1 ring-emerald-100">
                            <p class="text-xs font-bold tracking-wide text-emerald-700 uppercase">Status</p>
                            <p class="mt-1 text-sm font-black text-emerald-800">Sudah memberi rating</p>
                        </div>
                    </div>

                    <div class="mt-5 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div>
                                <p class="text-xs font-black tracking-wide text-slate-500 uppercase">
                                    Komentar dari kamu
                                </p>

                                @if (trim((string) $latestSystemRating->comment) !== '')
                                    <p class="mt-3 text-sm font-semibold leading-7 text-slate-700">
                                        “{{ $latestSystemRating->comment }}”
                                    </p>
                                @else
                                    <p class="mt-3 text-sm font-semibold leading-7 text-slate-500">
                                        Kamu belum menambahkan komentar tambahan. Rating bintang kamu tetap sudah tersimpan.
                                    </p>
                                @endif
                            </div>

                            {{-- <div class="shrink-0 rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-100">
                                <p class="text-xs font-bold text-slate-500">Platform</p>
                                <p class="mt-1 text-sm font-black text-slate-950">
                                    {{ ucfirst((string) ($latestSystemRating->platform ?? 'web')) }}
                                </p>
                            </div> --}}
                        </div>
                    </div>
                </div>

                <aside class="dashboard-system-rating-card p-5 md:p-6 lg:col-span-4">
                    <p class="text-sm font-black text-slate-950">
                        Ringkasan rating sistem
                    </p>

                    <div class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                        <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                            <p class="text-xs font-bold tracking-wide text-slate-500 uppercase">Nilai</p>
                            <p class="mt-1 text-xl font-black text-slate-950">
                                {{ (int) $latestSystemRating->rating }}/5 - {{ $dashboardSystemRatingLabel((int) $latestSystemRating->rating) }}
                            </p>
                        </div>

                        <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                            <p class="text-xs font-bold tracking-wide text-slate-500 uppercase">Makna rating</p>
                            <p class="mt-1 text-sm font-semibold text-slate-600">
                                {{ $dashboardSystemRatingDescription((int) $latestSystemRating->rating) }}
                            </p>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>
@elseif ($pendingRatingLog)
    <section id="rating-system-dashboard" class="dashboard-system-rating-shell mt-6">
        <span class="dashboard-system-rating-float-star dashboard-system-rating-float-star--one">★</span>
        <span class="dashboard-system-rating-float-star dashboard-system-rating-float-star--two">★</span>

        <div class="border-b border-amber-100/80 p-6 md:p-7">
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-12 lg:items-start">
                <div class="lg:col-span-8">
                    <span class="inline-flex items-center gap-2 rounded-full bg-amber-100 px-4 py-2 text-xs font-black text-amber-700 ring-1 ring-amber-200">
                        <span>⭐</span>
                        Rating Sistem TourHub
                    </span>

                    <h2 class="mt-4 text-2xl font-black tracking-tight text-slate-950 md:text-3xl">
                        Bantu nilai sistem rekomendasi TourHub
                    </h2>

                    <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-600">
                        Rating ini cukup diberikan satu kali untuk menilai kualitas sistem rekomendasi TourHub secara keseluruhan, bukan rating tempat wisatanya. Setelah kamu mengirim rating, form ini otomatis tidak akan muncul lagi di dashboard.
                    </p>

                    <div class="mt-4 flex flex-wrap gap-2 text-xs font-bold">
                        <span class="rounded-full bg-white px-3 py-1.5 text-slate-700 ring-1 ring-slate-200">
                            Riwayat #{{ $pendingRatingLog->id }}
                        </span>
                        <span class="rounded-full bg-white px-3 py-1.5 text-slate-700 ring-1 ring-slate-200">
                            {{ $pendingRatingLog->created_at?->format('d M Y H:i') }}
                        </span>
                        <span class="rounded-full bg-white px-3 py-1.5 text-slate-700 ring-1 ring-slate-200">
                            {{ $pendingRatingLog->top_destination_name ?? 'Rekomendasi TourHub' }}
                        </span>
                    </div>
                </div>

                <div class="lg:col-span-4">
                    <div class="rounded-[1.55rem] border border-amber-200 bg-white/88 p-5 text-center shadow-sm backdrop-blur">
                        <p class="text-xs font-bold tracking-wide text-slate-500 uppercase">Status Rating</p>
                        <p class="mt-1 text-4xl font-black text-slate-950" data-dashboard-rating-status-main>
                            Belum
                        </p>
                        <p class="mt-1 text-xs font-black text-slate-500" data-dashboard-rating-status-sub>
                            Belum diberi rating
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 md:p-7">
            @if (! $systemRatingStoreRoute)
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
            @else
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                    <div class="dashboard-system-rating-card p-5 md:p-6 lg:col-span-8">
                        <form method="POST" action="{{ $systemRatingStoreRoute }}" class="space-y-5" data-dashboard-rating-form>
                            @csrf

                            <input type="hidden" name="recommendation_log_id" value="{{ $pendingRatingLog->id }}">
                            <input type="hidden" name="source" value="dashboard_page">
                            <input type="hidden" name="platform" value="web">

                            <div>
                                <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                                    <div>
                                        <label class="block text-sm font-black text-slate-800">
                                            Seberapa membantu sistem rekomendasi TourHub?
                                        </label>
                                        <p class="mt-1 text-xs font-semibold leading-5 text-slate-500">
                                            Klik bintang dari kiri ke kanan. Sistem akan membaca rating 1 sampai 5 secara otomatis.
                                        </p>
                                    </div>

                                    <span class="inline-flex w-fit items-center gap-2 rounded-full bg-amber-50 px-4 py-2 text-xs font-black text-amber-700 ring-1 ring-amber-200" data-dashboard-rating-pill>
                                        <span>⭐</span>
                                        <span>Pilih rating</span>
                                    </span>
                                </div>

                                <div class="dashboard-system-rating-grid mt-4" data-dashboard-rating-group>
                                    @for ($ratingValue = 1; $ratingValue <= 5; $ratingValue++)
                                        <label
                                            class="dashboard-system-rating-star-card {{ $selectedDashboardSystemRating >= $ratingValue ? 'is-active' : '' }} {{ $selectedDashboardSystemRating === $ratingValue ? 'is-selected' : '' }} flex flex-col items-center justify-center px-2 py-4 text-center"
                                            data-dashboard-rating-star
                                            data-rating-value="{{ $ratingValue }}"
                                            aria-label="Beri rating {{ $ratingValue }} dari 5"
                                        >
                                            <input
                                                type="radio"
                                                name="rating"
                                                value="{{ $ratingValue }}"
                                                class="sr-only"
                                                @checked($selectedDashboardSystemRating === $ratingValue)
                                            >

                                            <span class="dashboard-system-rating-star-icon relative z-10">★</span>
                                            <span class="relative z-10 mt-2 text-xs font-black">{{ $ratingValue }}/5</span>
                                            <span class="relative z-10 mt-1 text-[10px] font-black leading-4 opacity-70">
                                                {{ $dashboardSystemRatingLabel($ratingValue) }}
                                            </span>
                                        </label>
                                    @endfor
                                </div>

                                <div class="dashboard-system-rating-summary mt-4 p-4 text-white">
                                    <div class="relative z-10 flex items-start gap-4">
                                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/12 text-3xl ring-1 ring-white/15" data-dashboard-rating-emoji>
                                            {{ $dashboardSystemRatingEmoji($selectedDashboardSystemRating) }}
                                        </div>

                                        <div class="min-w-0 flex-1 dashboard-system-rating-feedback" data-dashboard-rating-feedback>
                                            <p class="text-base font-black" data-dashboard-rating-label>
                                                {{ $dashboardSystemRatingLabel($selectedDashboardSystemRating) }}
                                            </p>
                                            <p class="mt-1 text-xs leading-5 text-slate-300" data-dashboard-rating-description>
                                                {{ $dashboardSystemRatingDescription($selectedDashboardSystemRating) }}
                                            </p>

                                            <div class="mt-3 dashboard-system-rating-meter">
                                                <div class="dashboard-system-rating-meter-fill" data-dashboard-rating-meter style="width: {{ $selectedDashboardSystemRating > 0 ? ($selectedDashboardSystemRating / 5) * 100 : 0 }}%"></div>
                                            </div>

                                            <div class="mt-2 flex justify-between text-[11px] font-black text-slate-400">
                                                <span>1 BINTANG</span>
                                                <span>5 BINTANG</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @error('rating')
                                    <p class="mt-2 text-sm font-bold text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="dashboard_system_rating_comment" class="block text-sm font-black text-slate-800">
                                    Komentar atau masukan
                                    <span class="font-semibold text-slate-400">(opsional)</span>
                                </label>

                                <textarea
                                    id="dashboard_system_rating_comment"
                                    name="comment"
                                    rows="4"
                                    maxlength="1000"
                                    placeholder="Contoh: rekomendasinya sudah sesuai, tapi saya ingin hasil yang lebih dekat dengan lokasi saya."
                                    class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-blue-300 focus:ring-4 focus:ring-blue-500/10"
                                >{{ $dashboardSystemRatingComment }}</textarea>

                                @error('comment')
                                    <p class="mt-2 text-sm font-bold text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <button
                                    type="submit"
                                    class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white shadow-lg shadow-slate-900/15 transition hover:-translate-y-0.5 hover:bg-slate-800 sm:w-auto"
                                >
                                    Kirim Rating Sistem
                                </button>

                                {{-- <a
                                    href="{{ route('user.recommendation-history.show', $pendingRatingLog) }}#rating-system"
                                    class="inline-flex w-full items-center justify-center rounded-2xl bg-blue-50 px-5 py-3 text-sm font-black text-blue-700 ring-1 ring-blue-100 transition hover:bg-blue-100 sm:w-auto"
                                >
                                    Lihat Detail Riwayat
                                </a> --}}
                            </div>
                        </form>
                    </div>

                    <aside class="dashboard-system-rating-card p-5 md:p-6 lg:col-span-4">
                        <p class="text-sm font-black text-slate-950">
                            Kenapa rating ini penting?
                        </p>

                        <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                            <li class="flex gap-2">
                                <span class="mt-0.5">✅</span>
                                <span>Membantu mengevaluasi apakah rekomendasi sudah sesuai preferensi user.</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="mt-0.5">📊</span>
                                <span>Bisa menjadi data pendukung pengujian kualitas sistem pada skripsi.</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="mt-0.5">🧭</span>
                                <span>Membedakan rating sistem dari rating asli tempat wisata.</span>
                            </li>
                        </ul>

                        <div class="mt-5 rounded-2xl border border-blue-200 bg-blue-50 p-4">
                            <p class="text-xs font-black tracking-wide text-blue-700 uppercase">
                                Cara Membaca Rating
                            </p>
                            <div class="mt-3 space-y-2 text-xs font-bold leading-5 text-blue-800">
                                <p>⭐ 1 bintang: rekomendasi kurang membantu.</p>
                                <p>⭐⭐⭐ 3 bintang: cukup membantu.</p>
                                <p>⭐⭐⭐⭐⭐ 5 bintang: sangat membantu.</p>
                            </div>
                        </div>
                    </aside>
                </div>
            @endif
        </div>
    </section>
@endif

    {{-- Statistic Cards --}}
    <section class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-[1.6rem] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-slate-500">Total Pencarian</p>
                    <p class="mt-3 text-4xl font-black text-slate-950">{{ $totalRecommendations }}</p>
                </div>
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-2xl">🔎</div>
            </div>
            <p class="mt-4 text-sm leading-6 text-slate-500">
                Jumlah semua pencarian rekomendasi yang pernah kamu lakukan.
            </p>
        </article>

        <article class="rounded-[1.6rem] border border-emerald-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-slate-500">Pencarian Berhasil</p>
                    <p class="mt-3 text-4xl font-black text-emerald-600">{{ $successRecommendations }}</p>
                </div>
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-2xl">✅</div>
            </div>
            <p class="mt-4 text-sm leading-6 text-slate-500">
                Pencarian yang berhasil menampilkan pilihan destinasi wisata.
            </p>
        </article>

        <article class="rounded-[1.6rem] border border-amber-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-slate-500">Belum Berhasil</p>
                    <p class="mt-3 text-4xl font-black text-amber-600">{{ $failedRecommendations }}</p>
                </div>
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-100 text-2xl">⚠️</div>
            </div>
            <p class="mt-4 text-sm leading-6 text-slate-500">
                Pencarian yang belum menemukan hasil sesuai pilihan yang dimasukkan.
            </p>
        </article>

        <article class="rounded-[1.6rem] border border-blue-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-slate-500">Hasil Tersedia</p>
                    <p class="mt-3 text-4xl font-black text-blue-600">{{ $successRate }}%</p>
                </div>
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-100 text-2xl">📊</div>
            </div>
            <p class="mt-4 text-sm leading-6 text-slate-500">
                Persentase pencarian yang berhasil memberikan rekomendasi wisata.
            </p>
        </article>
    </section>

    {{-- Latest Recommendation Summary --}}
    <section class="mt-6 grid grid-cols-1 gap-5 lg:grid-cols-12">
        <article class="rounded-[1.8rem] border border-slate-200 bg-white p-6 shadow-sm lg:col-span-8">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-500">Rekomendasi Terakhir</p>
                    <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950">
                        {{ $latestTopDestination }}
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Destinasi teratas dari pencarian rekomendasi terakhir kamu.
                    </p>
                </div>

                @if ($latestSuccess)
                    @php
                        $latestRecommendationDetailUrl = route('user.recommendation-history.show', $latestSuccess);
                        $latestRecommendationButtonLabel = 'Lihat Detail';
                    @endphp

                    <a
                        href="{{ $latestRecommendationDetailUrl }}"
                        class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white transition hover:-translate-y-0.5 hover:bg-slate-800"
                    >
                        {{ $latestRecommendationButtonLabel }}
                    </a>
                @endif
            </div>

            <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-bold text-slate-500">Kabupaten/Kota</p>
                    <p class="mt-2 font-black text-slate-950">{{ $latestKabupaten ?: '-' }}</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-bold text-slate-500">Kecamatan</p>
                    <p class="mt-2 font-black text-slate-950">{{ $latestKecamatan ?: '-' }}</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-bold text-slate-500">Cuaca Saat Itu</p>
                    <p class="mt-2 font-black text-slate-950">{{ $formatWeather($latestWeather) }}</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-bold text-slate-500">Pilihan Ditemukan</p>
                    <p class="mt-2 font-black text-slate-950">{{ $latestCandidates }}</p>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap gap-2">
                @forelse ($latestCategories as $category)
                    <span class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700">
                        {{ $category }}
                    </span>
                @empty
                    <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-500">
                        Belum ada kategori
                    </span>
                @endforelse
            </div>
        </article>

        <article class="rounded-[1.8rem] border border-slate-200 bg-white p-6 shadow-sm lg:col-span-4">
            <p class="text-sm font-semibold text-slate-500">Tips Pencarian</p>
            <h2 class="mt-2 text-xl font-black tracking-tight text-slate-950">
                Agar hasil lebih sesuai
            </h2>
            <ol class="mt-5 space-y-3 text-sm leading-6 text-slate-600">
                <li class="flex gap-3"><span class="font-black text-slate-950">1.</span><span>Gunakan kata kunci yang sederhana, misalnya pantai, sunset, pura, atau air terjun.</span></li>
                <li class="flex gap-3"><span class="font-black text-slate-950">2.</span><span>Turunkan rating minimum jika pilihan destinasi yang muncul terlalu sedikit.</span></li>
                <li class="flex gap-3"><span class="font-black text-slate-950">3.</span><span>Pilih beberapa kategori sekaligus agar pilihan wisata lebih beragam.</span></li>
                <li class="flex gap-3"><span class="font-black text-slate-950">4.</span><span>Coba wilayah populer seperti Gianyar, Badung, Denpasar, atau Tabanan.</span></li>
            </ol>
        </article>
    </section>

    {{-- History Table --}}
    <section id="riwayat" class="mt-6 overflow-hidden rounded-[1.8rem] border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-500">Riwayat</p>
                    <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950">
                        Riwayat Rekomendasi Saya
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Semua rekomendasi yang kamu cari akan tersimpan di sini sebagai riwayat pencarian wisata.
                    </p>
                </div>
                <a
                    href="{{ route('tourhub.recommendation.index') }}"
                    class="inline-flex items-center justify-center rounded-2xl bg-blue-50 px-5 py-3 text-sm font-black text-blue-700 transition hover:-translate-y-0.5 hover:bg-blue-100"
                >
                    + Rekomendasi Baru
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-black uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Waktu</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Pencarian</th>
                        <th class="px-6 py-4">Cuaca</th>
                        <th class="px-6 py-4">Pilihan</th>
                        <th class="px-6 py-4">Destinasi Teratas</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logs as $log)
                        @php
                            $payload = $log->request_payload ?? [];
                            $categories = data_get($payload, 'kategori_preferensi', []);

                            if (is_string($categories)) {
                                $categories = array_filter(array_map('trim', explode(',', $categories)));
                            }

                            $categories = (array) $categories;
                            $kabupaten = data_get($payload, 'kabupaten_kota', '-');
                            $kecamatan = data_get($payload, 'kecamatan', '-');
                            $topDestination = $log->top_destination_name ?? '-';
                            $choiceCount = (int) ($log->total_candidates ?? 0);
                        @endphp

                        <tr class="align-top transition hover:bg-slate-50">
                            <td class="whitespace-nowrap px-6 py-5">
                                <p class="font-black text-slate-950">{{ $log->created_at?->format('d M Y') }}</p>
                                <p class="mt-1 text-xs font-semibold text-slate-500">{{ $log->created_at?->format('H:i') }}</p>
                            </td>
                            <td class="px-6 py-5">
                                <span class="inline-flex rounded-full px-3 py-1.5 text-xs font-black ring-1 {{ $statusClass($log->status) }}">
                                    {{ $statusLabel($log->status) }}
                                </span>
                            </td>
                            <td class="min-w-[220px] px-6 py-5">
                                <p class="font-black text-slate-950">{{ $kabupaten ?: '-' }}</p>
                                <p class="mt-1 text-sm font-semibold text-slate-500">{{ $kecamatan ?: '-' }}</p>
                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    @forelse ($categories as $category)
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">
                                            {{ $category }}
                                        </span>
                                    @empty
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-500">
                                            Semua kategori
                                        </span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <span class="font-black text-slate-950">{{ $formatWeather($log->weather_used ?? '-') }}</span>
                            </td>
                            <td class="px-6 py-5">
                                <span class="font-black text-slate-950">{{ $choiceCount }}</span>
                            </td>
                            <td class="min-w-[200px] px-6 py-5">
                                <p class="font-black text-slate-950">{{ $topDestination }}</p>
                            </td>
                            <td class="px-6 py-5 text-right">
                                <a
                                    href="{{ route('user.recommendation-history.show', $log) }}"
                                    class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-4 py-2.5 text-xs font-black text-white transition hover:bg-slate-800"
                                >
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-14 text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-3xl">🧭</div>
                                <h3 class="mt-4 text-xl font-black text-slate-950">
                                    Belum ada riwayat rekomendasi
                                </h3>
                                <p class="mt-2 text-sm text-slate-500">
                                    Mulai cari rekomendasi wisata pertamamu.
                                </p>
                                <a
                                    href="{{ route('tourhub.recommendation.index') }}"
                                    class="mt-5 inline-flex items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white transition hover:bg-slate-800"
                                >
                                    Cari Rekomendasi
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="border-t border-slate-200 p-5">
                {{ $logs->links() }}
            </div>
        @endif
    </section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const ratingLabels = {
            1: {
                label: 'Kurang membantu',
                description: 'Rekomendasi belum sesuai dengan preferensi dan perlu banyak diperbaiki.',
                emoji: '😕',
            },
            2: {
                label: 'Cukup kurang',
                description: 'Rekomendasi masih kurang pas, tetapi ada beberapa bagian yang bisa dipakai.',
                emoji: '🙂',
            },
            3: {
                label: 'Cukup membantu',
                description: 'Rekomendasi cukup membantu, namun masih bisa dibuat lebih akurat.',
                emoji: '😊',
            },
            4: {
                label: 'Membantu',
                description: 'Rekomendasi sudah membantu memilih destinasi wisata yang sesuai.',
                emoji: '🤩',
            },
            5: {
                label: 'Sangat membantu',
                description: 'Rekomendasi sangat membantu dan terasa sesuai dengan rencana wisata.',
                emoji: '🏆',
            },
        }

        const defaultRatingState = {
            label: 'Belum memilih rating',
            description: 'Klik bintang dari kiri ke kanan. Bintang 1 berarti kurang membantu, bintang 5 berarti sangat membantu.',
            emoji: '⭐',
        }

        document.querySelectorAll('[data-dashboard-rating-group]').forEach((group) => {
            const form = group.closest('form')
            const stars = Array.from(group.querySelectorAll('[data-dashboard-rating-star]'))
            const labelTarget = document.querySelector('[data-dashboard-rating-label]')
            const descriptionTarget = document.querySelector('[data-dashboard-rating-description]')
            const emojiTarget = document.querySelector('[data-dashboard-rating-emoji]')
            const meterTarget = document.querySelector('[data-dashboard-rating-meter]')
            const feedbackTarget = document.querySelector('[data-dashboard-rating-feedback]')
            const pillTarget = document.querySelector('[data-dashboard-rating-pill] span:last-child')
            const statusMainTarget = document.querySelector('[data-dashboard-rating-status-main]')
            const statusSubTarget = document.querySelector('[data-dashboard-rating-status-sub]')

            let selectedValue = Number(group.querySelector('input[type="radio"]:checked')?.value || 0)

            const animateFeedback = () => {
                if (!feedbackTarget) {
                    return
                }

                feedbackTarget.classList.add('is-changing')

                window.setTimeout(() => {
                    feedbackTarget.classList.remove('is-changing')
                }, 120)
            }

            const syncStars = (value, persist = false) => {
                const ratingInfo = ratingLabels[value] || defaultRatingState

                stars.forEach((star) => {
                    const starValue = Number(star.dataset.ratingValue || 0)
                    const isActive = starValue <= value
                    const isSelected = persist && starValue === value

                    star.classList.toggle('is-active', isActive)
                    star.classList.toggle('is-preview', !persist && isActive)
                    star.classList.toggle('is-selected', isSelected)
                })

                if (labelTarget) {
                    labelTarget.textContent = ratingInfo.label
                }

                if (descriptionTarget) {
                    descriptionTarget.textContent = ratingInfo.description
                }

                if (emojiTarget) {
                    emojiTarget.textContent = ratingInfo.emoji
                }

                if (meterTarget) {
                    meterTarget.style.width = value > 0 ? `${(value / 5) * 100}%` : '0%'
                }

                if (pillTarget) {
                    pillTarget.textContent = value > 0 ? `${value}/5 - ${ratingInfo.label}` : 'Pilih rating'
                }

                if (statusMainTarget) {
                    statusMainTarget.textContent = value > 0 ? `${value}/5` : 'Belum'
                }

                if (statusSubTarget) {
                    statusSubTarget.textContent = value > 0 ? ratingInfo.label : 'Belum diberi rating'
                }
            }

            stars.forEach((star) => {
                const input = star.querySelector('input[type="radio"]')
                const value = Number(star.dataset.ratingValue || 0)

                star.addEventListener('mouseenter', () => {
                    syncStars(value, false)
                })

                star.addEventListener('mouseleave', () => {
                    syncStars(selectedValue, true)
                })

                star.addEventListener('click', () => {
                    selectedValue = value

                    if (input) {
                        input.checked = true
                    }

                    animateFeedback()
                    syncStars(selectedValue, true)

                    window.setTimeout(() => {
                        star.classList.remove('is-selected')
                        window.requestAnimationFrame(() => star.classList.add('is-selected'))
                    }, 10)
                })

                star.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault()
                        star.click()
                    }
                })
            })

            if (form) {
                form.addEventListener('submit', (event) => {
                    const checkedRating = Number(form.querySelector('input[name="rating"]:checked')?.value || 0)

                    if (checkedRating < 1) {
                        event.preventDefault()
                        group.classList.remove('dashboard-system-rating-shake')
                        void group.offsetWidth
                        group.classList.add('dashboard-system-rating-shake')
                        syncStars(0, true)
                    }
                })
            }

            syncStars(selectedValue, true)
        })
    })
</script>

</x-layouts.tourhub-auth>
