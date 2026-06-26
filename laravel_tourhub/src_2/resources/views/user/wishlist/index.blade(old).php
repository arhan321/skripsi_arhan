<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Wishlist Saya - TourHub Bali</title>
        <script src="https://cdn.tailwindcss.com"></script>

        <style>
            html {
                scroll-behavior: smooth;
            }

            @media (prefers-reduced-motion: reduce) {
                html {
                    scroll-behavior: auto;
                }
            }

            .soft-grid {
                background-image:
                    linear-gradient(rgba(15, 23, 42, 0.035) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(15, 23, 42, 0.035) 1px, transparent 1px);
                background-size: 28px 28px;
            }

            .premium-shadow {
                box-shadow:
                    0 20px 60px rgba(15, 23, 42, 0.1),
                    0 1px 2px rgba(15, 23, 42, 0.06);
            }

            .tourhub-nav-glass {
                background: rgba(255, 255, 255, 0.9);
                backdrop-filter: blur(18px);
                -webkit-backdrop-filter: blur(18px);
            }

            .tourhub-mobile-menu-panel {
                max-height: 0;
                opacity: 0;
                transform: translateY(-10px) scale(0.98);
                overflow: hidden;
                pointer-events: none;
                transition:
                    max-height 420ms cubic-bezier(0.22, 1, 0.36, 1),
                    opacity 260ms ease,
                    transform 320ms cubic-bezier(0.22, 1, 0.36, 1);
            }

            .tourhub-mobile-menu-panel.is-open {
                max-height: 620px;
                opacity: 1;
                transform: translateY(0) scale(1);
                pointer-events: auto;
            }

            .tourhub-menu-line {
                transform-origin: center;
                transition:
                    transform 260ms cubic-bezier(0.22, 1, 0.36, 1),
                    opacity 200ms ease;
            }

            .tourhub-menu-button.is-open .tourhub-menu-line:nth-child(1) {
                transform: translateY(6px) rotate(45deg);
            }

            .tourhub-menu-button.is-open .tourhub-menu-line:nth-child(2) {
                opacity: 0;
                transform: scaleX(0.4);
            }

            .tourhub-menu-button.is-open .tourhub-menu-line:nth-child(3) {
                transform: translateY(-6px) rotate(-45deg);
            }

            .tourhub-primary-action {
                transition:
                    transform 220ms ease,
                    background-color 220ms ease,
                    box-shadow 220ms ease,
                    color 220ms ease;
            }

            .tourhub-primary-action:hover {
                transform: translateY(-2px);
            }

            .wishlist-card {
                transition:
                    transform 220ms ease,
                    box-shadow 220ms ease,
                    border-color 220ms ease;
            }

            .wishlist-card:hover {
                transform: translateY(-4px);
                box-shadow:
                    0 24px 70px rgba(15, 23, 42, 0.13),
                    0 2px 8px rgba(15, 23, 42, 0.07);
            }

            .line-clamp-3 {
                display: -webkit-box;
                -webkit-line-clamp: 3;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .line-clamp-4 {
                display: -webkit-box;
                -webkit-line-clamp: 4;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            @media (prefers-reduced-motion: reduce) {
                .tourhub-mobile-menu-panel,
                .tourhub-menu-line,
                .tourhub-menu-button,
                .tourhub-primary-action,
                .wishlist-card {
                    transition: none !important;
                }
            }
        </style>
    </head>

    <body class="min-h-screen bg-slate-100 text-slate-950 antialiased">
        @php
            /*
             |--------------------------------------------------------------------------
             | Inline logic halaman Wishlist
             |--------------------------------------------------------------------------
             | Logic sengaja ditaruh langsung di view supaya halaman ini tetap jalan
             | walaupun controller hanya me-return view('user.wishlist.index').
             | Kalau controller sudah mengirim $wishlists, data controller tetap dipakai.
             */

            use Illuminate\Support\Facades\Auth;
            use Illuminate\Support\Facades\Route;
            use App\Models\Wishlist;

            $authUser = Auth::user();

            $wishlists = $wishlists ?? Wishlist::query()
                ->where('user_id', Auth::id())
                ->latest()
                ->paginate(12);

            $wishlistCount = Wishlist::query()
                ->where('user_id', Auth::id())
                ->count();

            $safeRoute = function (string $routeName, string $fallback = '#', array $parameters = []) {
                return Route::has($routeName) ? route($routeName, $parameters) : $fallback;
            };

            $formatNumber = function ($number): string {
                if ($number === null || $number === '') {
                    return '-';
                }

                return number_format((int) $number);
            };

            $formatRating = function ($rating): string {
                if ($rating === null || $rating === '') {
                    return '-';
                }

                return rtrim(rtrim(number_format((float) $rating, 1), '0'), '.');
            };

            $getSnapshotValue = function ($wishlist, array $keys, $default = null) {
                $snapshot = is_array($wishlist->snapshot ?? null) ? $wishlist->snapshot : [];

                foreach ($keys as $key) {
                    $value = data_get($snapshot, $key);

                    if ($value !== null && $value !== '') {
                        return $value;
                    }
                }

                return $default;
            };

            $getImageUrl = function ($wishlist) use ($getSnapshotValue): ?string {
                $image = $wishlist->image_url
                    ?: $getSnapshotValue($wishlist, ['link_gambar', 'image_url', 'gambar', 'photo_url']);

                return $image ? (string) $image : null;
            };

            $getMapsUrl = function ($wishlist) use ($getSnapshotValue): ?string {
                $maps = $wishlist->google_maps_url
                    ?: $getSnapshotValue($wishlist, ['link_google_maps', 'google_maps_url', 'maps_url']);

                return $maps ? (string) $maps : null;
            };

            $getCategory = function ($wishlist) use ($getSnapshotValue): string {
                return (string) (
                    $wishlist->category
                    ?: $getSnapshotValue($wishlist, ['kategori', 'category'], '-')
                    ?: '-'
                );
            };

            $getTourismType = function ($wishlist) use ($getSnapshotValue): string {
                return (string) (
                    $wishlist->tourism_type
                    ?: $getSnapshotValue($wishlist, ['tipe_wisata', 'tourism_type'], '-')
                    ?: '-'
                );
            };

            $getLocation = function ($wishlist) use ($getSnapshotValue): string {
                $subdistrict = $wishlist->subdistrict
                    ?: $getSnapshotValue($wishlist, ['kecamatan', 'subdistrict']);

                $city = $wishlist->city
                    ?: $getSnapshotValue($wishlist, ['kabupaten_kota', 'city']);

                if ($subdistrict && $city) {
                    return $subdistrict . ' - ' . $city;
                }

                if ($subdistrict) {
                    return (string) $subdistrict;
                }

                if ($city) {
                    return (string) $city;
                }

                return 'Lokasi belum tersedia';
            };

            $getReason = function ($wishlist) use ($getSnapshotValue): ?string {
                $reason = $wishlist->reason
                    ?: $getSnapshotValue($wishlist, ['alasan', 'reason']);

                if (! $reason) {
                    return null;
                }

                $reason = trim((string) $reason);

                if ($reason === '') {
                    return null;
                }

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
                $reason = trim($reason, " ;.\t\n\r\0\x0B");

                return $reason !== '' ? ucfirst($reason) . '.' : null;
            };

            $dashboardUrl = $safeRoute('user.dashboard', '/user/dashboard');
            $recommendationUrl = $safeRoute('tourhub.recommendation.index', '/tourhub/rekomendasi');
            $profileUrl = $safeRoute('user.profile.edit', '/user/profile');
            $wishlistUrl = $safeRoute('user.wishlist.index', '/user/wishlist');
            $logoutUrl = $safeRoute('user.logout', '/user/logout');
        @endphp

        <div class="soft-grid min-h-screen">
            <header class="tourhub-nav-glass sticky top-0 z-50 border-b border-white/80 shadow-[0_12px_34px_rgba(15,23,42,0.08)]">
                <div class="mx-auto max-w-7xl px-4 sm:px-6">
                    <div class="flex min-h-[76px] items-center justify-between gap-3 py-3">
                        <a href="{{ $dashboardUrl }}" class="group flex min-w-0 items-center gap-3">
                            <div
                                class="relative flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-slate-950 via-blue-950 to-blue-700 text-xl font-black text-white shadow-lg shadow-blue-900/20 transition duration-300 group-hover:-translate-y-0.5 group-hover:shadow-xl group-hover:shadow-blue-900/25"
                            >
                                <span class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(255,255,255,0.34),transparent_35%)]"></span>
                                <span class="relative">T</span>
                            </div>

                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h1 class="truncate text-xl font-black tracking-tight text-slate-950 sm:text-2xl">
                                        TourHub
                                    </h1>
                                    <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-black text-blue-700 ring-1 ring-blue-200">
                                        Bali
                                    </span>
                                </div>

                                <p class="truncate text-xs font-semibold text-slate-500">
                                    Temukan destinasi wisata terbaik
                                </p>
                            </div>
                        </a>

                        <div class="hidden items-center gap-2 text-sm font-bold lg:flex">
                            <span
                                class="hidden items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-2.5 text-xs font-black text-emerald-700 ring-1 ring-emerald-200 xl:inline-flex"
                            >
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                Sistem Rekomendasi Aktif
                            </span>

                            @auth
                                <span
                                    class="hidden max-w-[180px] truncate rounded-2xl bg-slate-50 px-4 py-2.5 text-xs text-slate-600 ring-1 ring-slate-200 xl:inline-flex"
                                >
                                    {{ $authUser?->name }}
                                </span>
                            @endauth

                            <a
                                href="{{ $recommendationUrl }}"
                                class="tourhub-primary-action inline-flex items-center justify-center rounded-2xl bg-blue-100 px-4 py-2.5 text-blue-700 shadow-sm shadow-blue-900/5 transition hover:bg-blue-200"
                            >
                                Rekomendasi
                            </a>

                            <a
                                href="{{ $dashboardUrl }}#riwayat"
                                class="tourhub-primary-action inline-flex items-center justify-center rounded-2xl bg-blue-100 px-4 py-2.5 text-blue-700 shadow-sm shadow-blue-900/5 transition hover:bg-blue-200"
                            >
                                Riwayat Saya
                            </a>

                            <a
                                href="{{ $wishlistUrl }}"
                                class="tourhub-primary-action inline-flex items-center justify-center rounded-2xl bg-amber-200 px-4 py-2.5 text-amber-800 shadow-sm shadow-amber-900/5 ring-1 ring-amber-300 transition"
                            >
                                Wishlist
                            </a>

                            <a
                                href="{{ $dashboardUrl }}"
                                class="tourhub-primary-action inline-flex items-center justify-center rounded-2xl bg-slate-950 px-4 py-2.5 text-white shadow-sm shadow-slate-900/15 transition hover:bg-slate-800"
                            >
                                Dashboard
                            </a>

                            @auth
                                <a
                                    href="{{ $profileUrl }}"
                                    class="tourhub-primary-action inline-flex items-center justify-center rounded-2xl bg-slate-100 px-4 py-2.5 text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-200"
                                >
                                    Profile
                                </a>

                                <form method="POST" action="{{ $logoutUrl }}">
                                    @csrf

                                    <button
                                        type="submit"
                                        class="tourhub-primary-action inline-flex items-center justify-center rounded-2xl bg-red-100 px-4 py-2.5 text-red-700 transition hover:bg-red-200"
                                    >
                                        Logout
                                    </button>
                                </form>
                            @endauth
                        </div>

                        <button
                            type="button"
                            id="tourhub-wishlist-menu-button"
                            class="tourhub-menu-button inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-md shadow-slate-900/10 transition duration-300 hover:-translate-y-0.5 hover:text-slate-950 hover:shadow-lg lg:hidden"
                            aria-label="Buka menu navigasi"
                            aria-expanded="false"
                            aria-controls="tourhub-wishlist-mobile-menu"
                        >
                            <span class="flex h-5 w-5 flex-col items-center justify-center gap-1">
                                <span class="tourhub-menu-line block h-0.5 w-5 rounded-full bg-current"></span>
                                <span class="tourhub-menu-line block h-0.5 w-5 rounded-full bg-current"></span>
                                <span class="tourhub-menu-line block h-0.5 w-5 rounded-full bg-current"></span>
                            </span>
                        </button>
                    </div>

                    <div id="tourhub-wishlist-mobile-menu" class="tourhub-mobile-menu-panel lg:hidden">
                        <div class="pb-4">
                            <div class="rounded-3xl border border-white/80 bg-white/95 p-2 shadow-2xl shadow-slate-900/12 backdrop-blur-xl">
                                @auth
                                    <div class="mb-2 rounded-2xl bg-gradient-to-br from-slate-950 via-slate-900 to-blue-950 px-4 py-3 text-white">
                                        <p class="text-[11px] font-semibold text-white/60">Masuk sebagai</p>
                                        <p class="truncate text-sm font-black">{{ $authUser?->name }}</p>
                                    </div>
                                @endauth

                                <a
                                    href="{{ $recommendationUrl }}"
                                    class="group flex items-center justify-between rounded-2xl px-4 py-3 text-sm font-bold text-slate-700 transition duration-300 hover:bg-slate-50 hover:text-slate-950"
                                >
                                    <span>Rekomendasi</span>
                                    <span class="text-slate-300 transition duration-300 group-hover:translate-x-0.5 group-hover:text-blue-500">›</span>
                                </a>

                                <a
                                    href="{{ $dashboardUrl }}#riwayat"
                                    class="group mt-1 flex items-center justify-between rounded-2xl bg-blue-50 px-4 py-3 text-sm font-black text-blue-700 ring-1 ring-blue-100 transition duration-300 hover:bg-blue-100"
                                >
                                    <span>Riwayat Saya</span>
                                    <span class="text-blue-300 transition duration-300 group-hover:translate-x-0.5">›</span>
                                </a>

                                <a
                                    href="{{ $wishlistUrl }}"
                                    class="group mt-1 flex items-center justify-between rounded-2xl bg-amber-100 px-4 py-3 text-sm font-black text-amber-800 ring-1 ring-amber-200 transition duration-300"
                                >
                                    <span>Wishlist</span>
                                    <span class="text-amber-300 transition duration-300 group-hover:translate-x-0.5">›</span>
                                </a>

                                <a
                                    href="{{ $dashboardUrl }}"
                                    class="group mt-1 flex items-center justify-between rounded-2xl px-4 py-3 text-sm font-bold text-slate-700 transition duration-300 hover:bg-slate-50 hover:text-slate-950"
                                >
                                    <span>Dashboard</span>
                                    <span class="text-slate-300 transition duration-300 group-hover:translate-x-0.5 group-hover:text-blue-500">›</span>
                                </a>

                                @auth
                                    <a
                                        href="{{ $profileUrl }}"
                                        class="group mt-1 flex items-center justify-between rounded-2xl px-4 py-3 text-sm font-bold text-slate-700 transition duration-300 hover:bg-slate-50 hover:text-slate-950"
                                    >
                                        <span>Profile</span>
                                        <span class="text-slate-300 transition duration-300 group-hover:translate-x-0.5 group-hover:text-blue-500">›</span>
                                    </a>

                                    <div class="my-2 h-px bg-gradient-to-r from-transparent via-slate-200 to-transparent"></div>

                                    <form method="POST" action="{{ $logoutUrl }}">
                                        @csrf

                                        <button
                                            type="submit"
                                            class="flex w-full items-center justify-between rounded-2xl bg-red-50 px-4 py-3 text-left text-sm font-black text-red-600 ring-1 ring-red-100 transition duration-300 hover:bg-red-600 hover:text-white"
                                        >
                                            <span>Logout</span>
                                            <span>→</span>
                                        </button>
                                    </form>
                                @endauth
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
                @if (session('success'))
                    <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-black text-emerald-700 shadow-sm">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('status'))
                    <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-black text-emerald-700 shadow-sm">
                        {{ session('status') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-black text-red-700 shadow-sm">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 shadow-sm">
                        <p class="font-black">Terjadi error</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <section class="premium-shadow overflow-hidden rounded-[2rem] border border-slate-200 bg-white">
                    <div class="relative overflow-hidden bg-slate-950 px-6 py-10 text-white md:px-10 md:py-12">
                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(245,158,11,0.35),_transparent_35%),radial-gradient(circle_at_bottom_right,_rgba(37,99,235,0.3),_transparent_30%)]"></div>
                        <div class="soft-grid absolute inset-0 opacity-20"></div>

                        <div class="relative flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                            <div>
                                <div class="inline-flex rounded-full bg-amber-400 px-4 py-2 text-xs font-black text-slate-950 shadow-lg shadow-amber-900/20">
                                    ★ Destinasi Tersimpan
                                </div>

                                <h1 class="mt-5 text-3xl font-black tracking-tight md:text-5xl">
                                    Wishlist Saya
                                </h1>

                                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300 md:text-base">
                                    Semua tempat wisata yang kamu simpan dari hasil rekomendasi dan detail riwayat akan muncul di halaman ini.
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-3 sm:flex">
                                <div class="rounded-3xl bg-white/10 px-5 py-4 text-center ring-1 ring-white/10 backdrop-blur">
                                    <p class="text-xs font-bold text-slate-300">Total Wishlist</p>
                                    <p class="mt-1 text-3xl font-black text-white">{{ $wishlistCount }}</p>
                                </div>

                                <a
                                    href="{{ $recommendationUrl }}"
                                    class="tourhub-primary-action inline-flex items-center justify-center rounded-3xl bg-white px-5 py-4 text-sm font-black text-slate-950 shadow-lg shadow-slate-950/10 hover:bg-blue-50"
                                >
                                    Cari Wisata Lagi
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="border-b border-slate-100 bg-gradient-to-br from-white via-slate-50 to-blue-50 px-6 py-5 md:px-10">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <p class="text-xs font-black tracking-wider text-blue-600 uppercase">
                                    Daftar pilihan pribadi
                                </p>
                                <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950">
                                    Tempat Wisata Favoritmu
                                </h2>
                            </div>

                            <div class="flex flex-wrap gap-2 text-xs font-bold">
                                <span class="rounded-full bg-slate-950 px-3 py-1 text-white">
                                    {{ $wishlistCount }} tempat tersimpan
                                </span>
                                <span class="rounded-full bg-amber-100 px-3 py-1 text-amber-700">
                                    Bisa dibuka kembali kapan saja
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 md:p-10">
                        @if ($wishlists->isEmpty())
                            <div class="rounded-[2rem] border border-dashed border-slate-300 bg-slate-50 p-8 text-center md:p-12">
                                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white text-4xl shadow-sm">
                                    ☆
                                </div>

                                <h3 class="mt-5 text-2xl font-black text-slate-950">
                                    Wishlist kamu masih kosong
                                </h3>

                                <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-slate-500">
                                    Klik tombol Wishlist pada halaman hasil rekomendasi atau detail riwayat untuk menyimpan tempat wisata yang ingin kamu kunjungi.
                                </p>

                                <a
                                    href="{{ $recommendationUrl }}"
                                    class="tourhub-primary-action mt-6 inline-flex items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white shadow-lg shadow-slate-900/15 hover:bg-slate-800"
                                >
                                    Mulai Cari Rekomendasi
                                </a>
                            </div>
                        @else
                            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                                @foreach ($wishlists as $wishlist)
                                    @php
                                        $imageUrl = $getImageUrl($wishlist);
                                        $mapsUrl = $getMapsUrl($wishlist);
                                        $category = $getCategory($wishlist);
                                        $tourismType = $getTourismType($wishlist);
                                        $location = $getLocation($wishlist);
                                        $reason = $getReason($wishlist);
                                        $rating = $formatRating($wishlist->rating ?? $getSnapshotValue($wishlist, ['rating']));
                                        $reviewCount = $formatNumber($wishlist->review_count ?? $getSnapshotValue($wishlist, ['jumlah_rating', 'review_count']));
                                        $destroyUrl = $safeRoute('wishlist.destroy', '#', ['wishlist' => $wishlist]);
                                    @endphp

                                    <article class="wishlist-card group overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                                        <div class="relative h-56 overflow-hidden">
                                            @if ($imageUrl)
                                                <img
                                                    src="{{ $imageUrl }}"
                                                    alt="{{ $wishlist->destination_name }}"
                                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                                    loading="lazy"
                                                />
                                            @else
                                                <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200 text-sm font-bold text-slate-400">
                                                    No Image
                                                </div>
                                            @endif

                                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/10 to-transparent"></div>

                                            <div class="absolute left-4 top-4 rounded-2xl bg-amber-400 px-3 py-2 text-sm font-black text-slate-950 shadow-lg shadow-amber-900/20">
                                                ★ Tersimpan
                                            </div>

                                            <div class="absolute bottom-4 left-4 right-4 text-white">
                                                <p class="text-xs font-black tracking-wider text-blue-100 uppercase">
                                                    Wishlist
                                                </p>

                                                <h3 class="mt-1 line-clamp-3 text-2xl font-black tracking-tight">
                                                    {{ $wishlist->destination_name }}
                                                </h3>

                                                <p class="mt-2 text-sm font-semibold text-slate-200">
                                                    📍 {{ $location }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="p-5">
                                            <div class="flex flex-wrap gap-2">
                                                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                                                    {{ $category }}
                                                </span>

                                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                                                    {{ $tourismType }}
                                                </span>
                                            </div>

                                            <div class="mt-5 grid grid-cols-2 gap-3">
                                                <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                                    <p class="text-[0.64rem] font-black uppercase tracking-wide text-slate-500">
                                                        Rating
                                                    </p>
                                                    <p class="mt-1 text-lg font-black text-slate-950">
                                                        {{ $rating }}
                                                    </p>
                                                </div>

                                                <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                                    <p class="text-[0.64rem] font-black uppercase tracking-wide text-slate-500">
                                                        Ulasan
                                                    </p>
                                                    <p class="mt-1 text-lg font-black text-slate-950">
                                                        {{ $reviewCount }}
                                                    </p>
                                                </div>
                                            </div>

                                            @if ($reason)
                                                <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                                                    <p class="text-xs font-black tracking-wider text-amber-700 uppercase">
                                                        Alasan Rekomendasi
                                                    </p>

                                                    <p class="mt-2 line-clamp-4 text-sm leading-6 text-slate-700">
                                                        {{ $reason }}
                                                    </p>
                                                </div>
                                            @endif

                                            <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:items-center">
                                                @if ($mapsUrl)
                                                    <a
                                                        href="{{ $mapsUrl }}"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-100 px-4 py-3 text-sm font-black text-emerald-700 transition hover:bg-emerald-200"
                                                    >
                                                        Buka Maps
                                                    </a>
                                                @endif

                                                @if ($destroyUrl !== '#')
                                                    <form method="POST" action="{{ $destroyUrl }}" class="w-full">
                                                        @csrf
                                                        @method('DELETE')

                                                        <button
                                                            type="submit"
                                                            onclick="return confirm('Hapus destinasi ini dari wishlist?')"
                                                            class="inline-flex w-full items-center justify-center rounded-2xl bg-red-50 px-4 py-3 text-sm font-black text-red-600 ring-1 ring-red-100 transition hover:bg-red-600 hover:text-white"
                                                        >
                                                            Hapus
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>

                                            <p class="mt-4 text-xs font-semibold text-slate-400">
                                                Disimpan pada {{ optional($wishlist->created_at)->format('d M Y H:i') }}
                                            </p>
                                        </div>
                                    </article>
                                @endforeach
                            </div>

                            <div class="mt-8">
                                {{ $wishlists->links() }}
                            </div>
                        @endif
                    </div>
                </section>
            </main>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const menuButton = document.getElementById('tourhub-wishlist-menu-button');
                const mobileMenu = document.getElementById('tourhub-wishlist-mobile-menu');

                if (menuButton && mobileMenu) {
                    const openMenu = function () {
                        mobileMenu.classList.add('is-open');
                        menuButton.classList.add('is-open');
                        menuButton.setAttribute('aria-expanded', 'true');
                    };

                    const closeMenu = function () {
                        mobileMenu.classList.remove('is-open');
                        menuButton.classList.remove('is-open');
                        menuButton.setAttribute('aria-expanded', 'false');
                    };

                    const toggleMenu = function () {
                        const isOpen = mobileMenu.classList.contains('is-open');

                        if (isOpen) {
                            closeMenu();
                        } else {
                            openMenu();
                        }
                    };

                    menuButton.addEventListener('click', function (event) {
                        event.stopPropagation();
                        toggleMenu();
                    });

                    mobileMenu.addEventListener('click', function (event) {
                        event.stopPropagation();
                    });

                    document.addEventListener('click', function () {
                        closeMenu();
                    });

                    document.addEventListener('keydown', function (event) {
                        if (event.key === 'Escape') {
                            closeMenu();
                        }
                    });

                    window.addEventListener('resize', function () {
                        if (window.innerWidth >= 1024) {
                            closeMenu();
                        }
                    });
                }
            });
        </script>
    </body>
</html>
