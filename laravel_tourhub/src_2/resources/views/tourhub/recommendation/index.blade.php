<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>TourHub Bali - Rekomendasi Wisata</title>
        <script src="https://cdn.tailwindcss.com"></script>

        <style>
            html {
                scroll-behavior: smooth;
                scroll-padding-top: 150px;
            }

            @media (prefers-reduced-motion: reduce) {
                html {
                    scroll-behavior: auto;
                }
            }

            [x-cloak] {
                display: none !important;
            }

            input[type='text'],
            input[type='number'],
            select {
                outline: none;
            }

            input[type='text']:focus,
            input[type='number']:focus,
            select:focus {
                box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
            }

            .premium-shadow {
                box-shadow:
                    0 20px 60px rgba(15, 23, 42, 0.1),
                    0 1px 2px rgba(15, 23, 42, 0.06);
            }

            .soft-grid {
                background-image:
                    linear-gradient(rgba(15, 23, 42, 0.035) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(15, 23, 42, 0.035) 1px, transparent 1px);
                background-size: 28px 28px;
            }

            .hide-scrollbar::-webkit-scrollbar {
                display: none;
            }

            .hide-scrollbar {
                -ms-overflow-style: none;
                scrollbar-width: none;
            }

            .hero-search-shadow {
                box-shadow:
                    0 24px 70px rgba(15, 23, 42, 0.22),
                    0 2px 8px rgba(15, 23, 42, 0.1);
            }

            .section-anchor {
                scroll-margin-top: 150px;
            }

            .nav-scroll-link {
                position: relative;
                display: inline-flex;
                align-items: center;
                padding-bottom: 0.35rem;
                transition:
                    color 180ms ease,
                    transform 180ms ease;
            }

            .nav-scroll-link::after {
                content: '';
                position: absolute;
                left: 0;
                right: 0;
                bottom: 0;
                height: 3px;
                border-radius: 9999px;
                background: rgb(37, 99, 235);
                transform: scaleX(0);
                transform-origin: left;
                transition: transform 220ms ease;
            }

            .nav-scroll-link:hover,
            .nav-scroll-link.is-active {
                color: rgb(29, 78, 216);
            }

            .nav-scroll-link.is-active::after {
                transform: scaleX(1);
            }


            /* =========================================================
               Tambahan finishing navbar rekomendasi
               Tujuan:
               - tombol aksi mobile tidak lagi menumpuk
               - dropdown mobile lebih smooth
               - tampilan navbar tetap premium tanpa mengubah logic rekomendasi
            ========================================================= */
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
                max-height: 560px;
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

            .tourhub-reason-content {
                display: block;
                white-space: normal;
                overflow-wrap: anywhere;
                word-break: normal;
                transition:
                    max-height 360ms cubic-bezier(0.22, 1, 0.36, 1),
                    opacity 220ms ease;
            }

            .tourhub-reason-content.is-collapsible {
                position: relative;
                max-height: 5.65rem;
                overflow: hidden;
            }

            .tourhub-reason-content.is-collapsible::after {
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

            .tourhub-reason-content.is-expanded::after {
                opacity: 0;
            }

            .tourhub-reason-button {
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

            .tourhub-reason-button:hover {
                transform: translateY(-1px);
                background: rgb(191, 219, 254);
                box-shadow: 0 12px 28px rgba(37, 99, 235, 0.13);
            }

            .tourhub-reason-button span {
                transition: transform 220ms ease;
            }

            .tourhub-reason-button.is-expanded span {
                transform: rotate(180deg);
            }

            @media (prefers-reduced-motion: reduce) {
                .tourhub-mobile-menu-panel,
                .tourhub-menu-line,
                .tourhub-menu-button,
                .tourhub-primary-action,
                .tourhub-reason-content,
                .tourhub-reason-content::after,
                .tourhub-reason-button,
                .tourhub-reason-button span {
                    transition: none !important;
                }
            }
        </style>
    </head>

    <body class="min-h-screen bg-slate-100 text-slate-950 antialiased">
        <div class="soft-grid min-h-screen">
            @php
                $selectedKategori = old(
                    'kategori_preferensi',
                    data_get($payload ?? [], 'kategori_preferensi', ['Alam']),
                );

                $locationOptions = [
                    'Kabupaten Gianyar' => ['Ubud', 'Gianyar', 'Tegallalang', 'Blahbatuh', 'Tampaksiring', 'Sukawati', 'Payangan'],
                    'Kabupaten Badung' => ['Kuta', 'Kuta Selatan', 'Kuta Utara', 'Mengwi', 'Abiansemal', 'Petang'],
                    'Kabupaten Tabanan' => ['Tabanan', 'Kediri', 'Penebel', 'Baturiti', 'Pupuan', 'Selemadeg Timur', 'Selemadeg Barat', 'Kerambitan', 'Marga'],
                    'Kabupaten Buleleng' => ['Buleleng', 'Gerokgak', 'Seririt', 'Busungbiu', 'Banjar', 'Sukasada', 'Sawan', 'Kubutambahan', 'Tejakula'],
                    'Kabupaten Karangasem' => ['Karangasem', 'Rendang', 'Sidemen', 'Manggis', 'Abang', 'Bebandem', 'Selat', 'Kubu'],
                    'Kabupaten Bangli' => ['Kintamani', 'Bangli', 'Susut', 'Tembuku'],
                    'Kabupaten Klungkung' => ['Nusa Penida', 'Klungkung', 'Banjarangkan', 'Dawan'],
                    'Kabupaten Jembrana' => ['Negara', 'Jembrana', 'Mendoyo', 'Melaya', 'Pekutatan'],
                    'Kota Denpasar' => ['Denpasar Selatan', 'Denpasar Barat', 'Denpasar Timur', 'Denpasar Utara'],
                ];

                $selectedLokasi = old('lokasi_wisata');

                if ($selectedLokasi === null) {
                    $payloadKabupaten = data_get($payload ?? [], 'kabupaten_kota');
                    $payloadKecamatan = data_get($payload ?? [], 'kecamatan');

                    if (isset($payload) && $payloadKabupaten) {
                        $selectedLokasi = $payloadKabupaten . '|' . ($payloadKecamatan ?: '');
                    } elseif (isset($payload)) {
                        $selectedLokasi = '';
                    } else {
                        $selectedLokasi = 'Kabupaten Gianyar|Ubud';
                    }
                }

                // Cuaca tidak lagi dipilih manual oleh user.
                // Nilai ini hanya sebagai fallback jika Otomatis gagal/tidak tersedia.
                $selectedCuaca = 'cerah';

                $selectedVisitDay = old('visit_day', data_get($payload ?? [], 'visit_day', 'weekday'));

                $isHighSeason = old('is_high_season', data_get($payload ?? [], 'is_high_season', false));

                // Otomatis dibuat aktif otomatis agar user tidak perlu memilih cuaca manual.
                $useBmkg = true;

                $selectedKategoriArray = (array) $selectedKategori;

                /*
                 * Keterangan periode Otomatis untuk ditampilkan di UI.
                 * Otomatis data terbuka prakiraan cuaca tersedia dalam format 3 harian,
                 * dengan 8 data per hari atau interval sekitar 3 jam.
                 */
                $bmkgForecastPeriodText = 'Info cuaca beberapa hari ke depan';
                $bmkgForecastIntervalText = 'Cuaca diperbarui berkala';


                /*
                 * Label user-friendly untuk mengganti angka teknis.
                 * Angka internal tetap dipakai sistem untuk pengurutan,
                 * tetapi user cukup melihat status yang mudah dipahami.
                 */
                $labelRekomendasi = function ($item, int $index = 0): string {
                    if ($index === 0) {
                        return 'Paling Cocok';
                    }

                    $score = (float) data_get($item, 'final_score', 0);

                    if ($score >= 0.75) {
                        return 'Sangat Cocok';
                    }

                    if ($score >= 0.45) {
                        return 'Cocok';
                    }

                    return 'Cukup Cocok';
                };

                $labelKesesuaian = function ($item): string {
                    $score = (float) data_get($item, 'cbf_score', 0);

                    if ($score >= 0.70) {
                        return 'Sangat Sesuai';
                    }

                    if ($score >= 0.40) {
                        return 'Sesuai';
                    }

                    if ($score > 0) {
                        return 'Cukup Sesuai';
                    }

                    return 'Sesuai Pilihan';
                };

                $labelKondisi = function ($item): string {
                    $nilai = (float) data_get($item, 'context_multiplier', 1);

                    if ($nilai >= 1.08) {
                        return 'Sangat Mendukung';
                    }

                    if ($nilai >= 1.00) {
                        return 'Mendukung';
                    }

                    if ($nilai >= 0.90) {
                        return 'Cukup Mendukung';
                    }

                    return 'Perlu Dipertimbangkan';
                };

                $cleanReason = function ($reason): string {
                    $reason = trim((string) $reason);

                    if ($reason === '') {
                        return '';
                    }

                    // Bersihkan angka dan istilah teknis agar alasan nyaman dibaca user biasa.
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
            @endphp

            {{-- Jumlah Hasilavigation: Travel app style. Menu Profile ditambahkan agar konsisten dengan Dashboard User. --}}
            <header class="tourhub-nav-glass sticky top-0 z-50 border-b border-white/80 shadow-[0_12px_34px_rgba(15,23,42,0.08)]">
                <div class="mx-auto max-w-7xl px-4 sm:px-6">
                    <div class="flex min-h-[76px] items-center justify-between gap-3 py-3">
                        <a href="{{ route('user.dashboard') }}" class="group flex min-w-0 items-center gap-3">
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
                            {{--
                                CODE MATI - Informasi endpoint layanan rekomendasi disembunyikan dari user awam.
                                Alasan: URL service internal seperti layanan rekomendasi/Flask/layanan rekomendasi bersifat teknis
                                dan tidak perlu ditampilkan pada halaman pengguna.

                                <span
                                    class="hidden rounded-2xl bg-slate-50 px-4 py-2.5 text-xs text-slate-600 ring-1 ring-slate-200 lg:inline-flex"
                                >
                                    layanan rekomendasi:
                                    <span class="ml-1 text-slate-900">{{ $defaultBaseUrl ?? '-' }}</span>
                                </span>
                            --}}

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
                                    {{ auth()->user()->name }}
                                </span>
                            @endauth

                            <a
                                href="{{ route('user.dashboard') }}#riwayat"
                                class="tourhub-primary-action inline-flex items-center justify-center rounded-2xl bg-blue-100 px-4 py-2.5 text-blue-700 shadow-sm shadow-blue-900/5 transition hover:bg-blue-200"
                            >
                                Riwayat Saya
                            </a>

                            <a
                                href="{{ route('user.dashboard') }}"
                                class="tourhub-primary-action inline-flex items-center justify-center rounded-2xl bg-slate-950 px-4 py-2.5 text-white shadow-sm shadow-slate-900/15 transition hover:bg-slate-800"
                            >
                                Dashboard
                            </a>

                            @auth
                                <a
                                    href="{{ route('user.profile.edit') }}"
                                    class="tourhub-primary-action inline-flex items-center justify-center rounded-2xl bg-slate-100 px-4 py-2.5 text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-200"
                                >
                                    Profile
                                </a>

                                <form method="POST" action="{{ route('user.logout') }}">
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
                            id="tourhub-recommendation-menu-button"
                            class="tourhub-menu-button inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-md shadow-slate-900/10 transition duration-300 hover:-translate-y-0.5 hover:text-slate-950 hover:shadow-lg lg:hidden"
                            aria-label="Buka menu navigasi"
                            aria-expanded="false"
                            aria-controls="tourhub-recommendation-mobile-menu"
                        >
                            <span class="flex h-5 w-5 flex-col items-center justify-center gap-1">
                                <span class="tourhub-menu-line block h-0.5 w-5 rounded-full bg-current"></span>
                                <span class="tourhub-menu-line block h-0.5 w-5 rounded-full bg-current"></span>
                                <span class="tourhub-menu-line block h-0.5 w-5 rounded-full bg-current"></span>
                            </span>
                        </button>
                    </div>

                    <div id="tourhub-recommendation-mobile-menu" class="tourhub-mobile-menu-panel lg:hidden">
                        <div class="pb-4">
                            <div class="rounded-3xl border border-white/80 bg-white/95 p-2 shadow-2xl shadow-slate-900/12 backdrop-blur-xl">
                                @auth
                                    <div class="mb-2 rounded-2xl bg-gradient-to-br from-slate-950 via-slate-900 to-blue-950 px-4 py-3 text-white">
                                        <p class="text-[11px] font-semibold text-white/60">Masuk sebagai</p>
                                        <p class="truncate text-sm font-black">{{ auth()->user()->name }}</p>
                                    </div>
                                @endauth

                                <a
                                    href="{{ route('user.dashboard') }}#riwayat"
                                    class="group flex items-center justify-between rounded-2xl bg-blue-50 px-4 py-3 text-sm font-black text-blue-700 ring-1 ring-blue-100 transition duration-300 hover:bg-blue-100"
                                >
                                    <span>Riwayat Saya</span>
                                    <span class="text-blue-300 transition duration-300 group-hover:translate-x-0.5">›</span>
                                </a>

                                <a
                                    href="{{ route('user.dashboard') }}"
                                    class="group mt-1 flex items-center justify-between rounded-2xl px-4 py-3 text-sm font-bold text-slate-700 transition duration-300 hover:bg-slate-50 hover:text-slate-950"
                                >
                                    <span>Dashboard</span>
                                    <span class="text-slate-300 transition duration-300 group-hover:translate-x-0.5 group-hover:text-blue-500">›</span>
                                </a>

                                @auth
                                    <a
                                        href="{{ route('user.profile.edit') }}"
                                        class="group mt-1 flex items-center justify-between rounded-2xl px-4 py-3 text-sm font-bold text-slate-700 transition duration-300 hover:bg-slate-50 hover:text-slate-950"
                                    >
                                        <span>Profile</span>
                                        <span class="text-slate-300 transition duration-300 group-hover:translate-x-0.5 group-hover:text-blue-500">›</span>
                                    </a>

                                    <div class="my-2 h-px bg-gradient-to-r from-transparent via-slate-200 to-transparent"></div>

                                    <form method="POST" action="{{ route('user.logout') }}">
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

                    <nav
                        class="hide-scrollbar flex gap-3 overflow-x-auto border-t border-slate-100 py-3 text-sm font-black text-slate-600 sm:gap-5 md:gap-7"
                    >
                        <a href="#search" data-scroll-link class="nav-scroll-link is-active whitespace-nowrap">
                            Rekomendasi
                        </a>
                        <a href="#hasil" data-scroll-link class="nav-scroll-link whitespace-nowrap">Hasil Terbaik</a>
                        <a href="#kategori" data-scroll-link class="nav-scroll-link whitespace-nowrap">Kategori</a>
                        <a href="#bmkg" data-scroll-link class="nav-scroll-link whitespace-nowrap">Cuaca Terkini</a>
                        <a href="#log" data-scroll-link class="nav-scroll-link whitespace-nowrap">Riwayat Terbaru</a>
                    </nav>
                </div>
            </header>

            {{-- Hero + Search Panel --}}
            <section id="search" class="section-anchor relative overflow-hidden bg-slate-950">
                <div
                    class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.35),_transparent_34%),radial-gradient(circle_at_bottom_right,_rgba(16,185,129,0.25),_transparent_30%)]"
                ></div>
                <div class="soft-grid absolute inset-0 opacity-20"></div>

                <div class="relative mx-auto max-w-7xl px-6 pt-10 pb-12 md:pt-12 md:pb-20">
                    <div class="mx-auto max-w-4xl text-center">
                        <div
                            class="inline-flex rounded-full bg-white/10 px-4 py-2 text-xs font-black text-blue-100 ring-1 ring-white/10"
                        >
                            Panduan Wisata Cerdas TourHub
                        </div>

                        <h2 class="mt-5 text-3xl font-black tracking-tight text-white md:text-5xl">
                            Pilihan utama untuk jelajahi Bali
                        </h2>

                        <p class="mx-auto mt-4 max-w-2xl text-sm leading-6 text-slate-300 md:text-base">
                            Cari destinasi wisata berdasarkan preferensi, lokasi, rating, cuaca, hari kunjungan, dan
                            kondisi high season.
                        </p>
                    </div>

                    <div class="mx-auto mt-8 max-w-6xl">
                        <div
                            class="mb-4 grid grid-cols-4 gap-2 text-center text-xs font-black text-slate-200 md:grid-cols-8 md:gap-4"
                        >
                            <div class="rounded-3xl bg-white px-3 py-4 text-slate-950 shadow-lg shadow-slate-950/10">
                                <div class="text-2xl">🧭</div>
                                <div class="mt-2">Rekomendasi</div>
                            </div>

                            <div class="rounded-3xl bg-white/10 px-3 py-4 ring-1 ring-white/10 backdrop-blur">
                                <div class="text-2xl">🌿</div>
                                <div class="mt-2">Alam</div>
                            </div>

                            <div class="rounded-3xl bg-white/10 px-3 py-4 ring-1 ring-white/10 backdrop-blur">
                                <div class="text-2xl">🏛️</div>
                                <div class="mt-2">Budaya</div>
                            </div>

                            <div class="rounded-3xl bg-white/10 px-3 py-4 ring-1 ring-white/10 backdrop-blur">
                                <div class="text-2xl">🎡</div>
                                <div class="mt-2">Rekreasi</div>
                            </div>

                            <div class="rounded-3xl bg-white/10 px-3 py-4 ring-1 ring-white/10 backdrop-blur">
                                <div class="text-2xl">🌦️</div>
                                <div class="mt-2">Cuaca</div>
                            </div>

                            <div class="rounded-3xl bg-white/10 px-3 py-4 ring-1 ring-white/10 backdrop-blur">
                                <div class="text-2xl">⭐</div>
                                <div class="mt-2">Rating</div>
                            </div>

                            <div class="rounded-3xl bg-white/10 px-3 py-4 ring-1 ring-white/10 backdrop-blur">
                                <div class="text-2xl">📍</div>
                                <div class="mt-2">Lokasi</div>
                            </div>

                            <div class="rounded-3xl bg-white/10 px-3 py-4 ring-1 ring-white/10 backdrop-blur">
                                <div class="text-2xl">🏆</div>
                                <div class="mt-2">Pilihan</div>
                            </div>
                        </div>

                        <div class="hero-search-shadow overflow-hidden rounded-[2rem] border border-white/20 bg-white">
                            <div class="border-b border-slate-100 bg-gradient-to-br from-white to-slate-50 p-5 md:p-6">
                                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                    <div>
                                        <p class="text-xs font-black tracking-wider text-blue-600 uppercase">
                                            Rencana Wisata
                                        </p>
                                        <h3 class="mt-1 text-2xl font-black tracking-tight text-slate-950">
                                            Mau liburan ke mana?
                                        </h3>
                                        <p class="mt-1 text-sm text-slate-600">
                                            Isi pilihan wisatamu, lalu TourHub akan mencarikan destinasi yang paling sesuai.
                                        </p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2 text-xs font-black md:flex">
                                        <span class="rounded-2xl bg-slate-950 px-3 py-2 text-white">Rekomendasi Cerdas</span>
                                        <span class="rounded-2xl bg-blue-100 px-3 py-2 text-blue-700">
                                            Cuaca Otomatis
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="p-5 md:p-6">
                                @if ($errors->any())
                                    <div
                                        class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
                                    >
                                        <div class="flex items-start gap-3">
                                            <div
                                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-red-100"
                                            >
                                                ⚠️
                                            </div>

                                            <div>
                                                <p class="font-black">Terjadi error</p>

                                                <ul class="mt-2 list-disc space-y-1 pl-5">
                                                    @foreach ($errors->all() as $error)
                                                        <li>{{ $error }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <form
                                    method="POST"
                                    action="{{ route('tourhub.recommendation.store') }}"
                                    class="space-y-5"
                                >
                                    @csrf

                                    <div id="kategori" class="section-anchor">
                                        <div class="mb-3 flex items-center justify-between gap-4">
                                            <label class="block text-sm font-black text-slate-800">
                                                Kategori Preferensi
                                            </label>
                                            <span
                                                class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500"
                                            >
                                                Bisa pilih lebih dari satu
                                            </span>
                                        </div>

                                        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                                            @foreach ([
                                                    'Alam' => ['icon' => '🌿', 'desc' => 'Pantai, gunung, air terjun'],
                                                    'Budaya' => ['icon' => '🏛️', 'desc' => 'Pura, desa adat, sejarah'],
                                                    'Rekreasi' => ['icon' => '🎡', 'desc' => 'Wahana dan aktivitas'],
                                                    'Umum' => ['icon' => '✨', 'desc' => 'Destinasi populer']
                                                ]
                                                as $kategori => $meta)
                                                <label
                                                    class="group relative cursor-pointer overflow-hidden rounded-3xl border border-slate-200 bg-white p-4 transition hover:-translate-y-0.5 hover:border-blue-300 hover:bg-blue-50"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        name="kategori_preferensi[]"
                                                        value="{{ $kategori }}"
                                                        @checked(in_array($kategori, $selectedKategoriArray, true))
                                                        class="peer sr-only"
                                                    />

                                                    <div
                                                        class="absolute top-3 right-3 hidden h-6 w-6 items-center justify-center rounded-full bg-blue-600 text-xs font-black text-white peer-checked:flex"
                                                    >
                                                        ✓
                                                    </div>

                                                    <div class="text-3xl">{{ $meta['icon'] }}</div>
                                                    <p class="mt-3 text-sm font-black text-slate-950">
                                                        {{ $kategori }}
                                                    </p>
                                                    <p class="mt-1 text-xs leading-5 text-slate-500">
                                                        {{ $meta['desc'] }}
                                                    </p>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                                        <div class="lg:col-span-8">
                                            <label
                                                for="lokasi_wisata"
                                                class="mb-1 block text-xs font-bold tracking-wide text-slate-500 uppercase"
                                            >
                                                Pilih Daerah Wisata
                                            </label>

                                            <select
                                                id="lokasi_wisata"
                                                name="lokasi_wisata"
                                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-4 text-sm font-semibold text-slate-900"
                                            >
                                                <option value="" @selected($selectedLokasi === '')>Semua Bali / semua kabupaten</option>

                                                @foreach ($locationOptions as $kabupaten => $kecamatans)
                                                    @php
                                                        $kabupatenOnlyValue = $kabupaten . '|';
                                                    @endphp

                                                    <optgroup label="{{ $kabupaten }}">
                                                        <option
                                                            value="{{ $kabupatenOnlyValue }}"
                                                            @selected($selectedLokasi === $kabupatenOnlyValue)
                                                        >
                                                            Semua kecamatan di {{ $kabupaten }}
                                                        </option>

                                                        @foreach ($kecamatans as $kecamatanOption)
                                                            @php
                                                                $lokasiValue = $kabupaten . '|' . $kecamatanOption;
                                                            @endphp

                                                            <option
                                                                value="{{ $lokasiValue }}"
                                                                @selected($selectedLokasi === $lokasiValue)
                                                            >
                                                                {{ $kabupaten }} — {{ $kecamatanOption }}
                                                            </option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </select>

                                            <p class="mt-2 text-xs font-semibold text-slate-500">
                                                Lokasi sudah dipasangkan manual, jadi user tidak bisa memilih kecamatan yang tidak sesuai kabupaten.
                                            </p>
                                        </div>

                                        <div class="lg:col-span-4">
                                            <label
                                                for="keywords"
                                                class="mb-1 block text-xs font-bold tracking-wide text-slate-500 uppercase"
                                            >
                                                Kata Kunci
                                            </label>

                                            <input
                                                id="keywords"
                                                type="text"
                                                name="keywords"
                                                value="{{ old('keywords', isset($payload['keywords']) ? implode(', ', (array) $payload['keywords']) : '') }}"
                                                placeholder="Contoh: pantai, sunset"
                                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-4 text-sm font-semibold text-slate-900 placeholder:text-slate-400"
                                            />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-4">
                                        <div>
                                            <label
                                                for="min_rating"
                                                class="mb-1 block text-xs font-bold tracking-wide text-slate-500 uppercase"
                                            >
                                                Rating Minimal
                                            </label>

                                            <input
                                                id="min_rating"
                                                type="number"
                                                step="0.1"
                                                min="0"
                                                max="5"
                                                name="min_rating"
                                                value="{{ old('min_rating', data_get($payload ?? [], 'min_rating', 4.2)) }}"
                                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-4 text-sm font-semibold text-slate-900"
                                            />
                                        </div>

                                        <div>
                                            <label
                                                for="top_n"
                                                class="mb-1 block text-xs font-bold tracking-wide text-slate-500 uppercase"
                                            >
                                                Jumlah Hasil
                                            </label>

                                            <input
                                                id="top_n"
                                                type="number"
                                                min="1"
                                                max="50"
                                                name="top_n"
                                                value="{{ old('top_n', data_get($payload ?? [], 'top_n', 10)) }}"
                                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-4 text-sm font-semibold text-slate-900"
                                            />
                                        </div>

                                        <div>
                                            <label
                                                class="mb-1 block text-xs font-bold tracking-wide text-slate-500 uppercase"
                                            >
                                                Cuaca Otomatis
                                            </label>

                                            <div
                                                class="w-full rounded-2xl border border-blue-100 bg-blue-50 px-4 py-4 text-sm font-semibold text-slate-900"
                                            >
                                                <div class="flex items-start gap-3">
                                                    <span class="text-xl">🌤️</span>
                                                    <div>
                                                        <p class="font-black text-slate-950">Otomatis dari Otomatis</p>
                                                        <p class="mt-1 text-xs font-medium leading-5 text-slate-500">
                                                            Cuaca akan dibaca otomatis. Jika cuaca kurang mendukung, TourHub akan mengutamakan tempat wisata yang lebih nyaman.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <input type="hidden" name="weather" value="cerah" />
                                        </div>

                                        <div>
                                            <label
                                                for="visit_day"
                                                class="mb-1 block text-xs font-bold tracking-wide text-slate-500 uppercase"
                                            >
                                                Hari Kunjungan
                                            </label>

                                            <select
                                                id="visit_day"
                                                name="visit_day"
                                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-4 text-sm font-semibold text-slate-900"
                                            >
                                                @foreach (['weekday', 'weekend'] as $day)
                                                    <option value="{{ $day }}" @selected($selectedVisitDay === $day)>
                                                        {{ ucfirst($day) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div id="bmkg" class="section-anchor grid grid-cols-1 gap-3 lg:grid-cols-12">
                                        <label
                                            class="flex cursor-pointer items-center justify-between rounded-3xl border border-blue-100 bg-blue-50/70 px-4 py-4 lg:col-span-4"
                                        >
                                            <span class="flex items-start gap-3">
                                                <span
                                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-xl"
                                                >
                                                    🌦️
                                                </span>
                                                <span>
                                                    <span class="block text-sm font-black text-slate-900">
                                                        Cuaca Otomatis
                                                    </span>
                                                    <span class="text-xs leading-5 text-slate-500">
                                                        Sistem mengambil prakiraan cuaca otomatis berdasarkan wilayah
                                                        untuk periode ±3 hari ke depan.
                                                    </span>
                                                </span>
                                            </span>

                                            <span class="flex items-center gap-2">
                                                <input type="hidden" name="use_bmkg" value="1" />
                                                <span class="rounded-full bg-blue-600 px-3 py-1 text-xs font-black text-white">
                                                    Aktif
                                                </span>
                                            </span>
                                        </label>

                                        <label
                                            class="flex cursor-pointer items-center justify-between rounded-3xl border border-slate-200 bg-white px-4 py-4 lg:col-span-4"
                                        >
                                            <span class="flex items-start gap-3">
                                                <span
                                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-xl"
                                                >
                                                    🧳
                                                </span>
                                                <span>
                                                    <span class="block text-sm font-black text-slate-900">
                                                        Musim Ramai
                                                    </span>
                                                    <span class="text-xs leading-5 text-slate-500">
                                                        Tandai jika kamu berkunjung saat musim liburan atau akhir pekan panjang.
                                                    </span>
                                                </span>
                                            </span>

                                            <span class="flex items-center gap-2">
                                                <input type="hidden" name="is_high_season" value="0" />
                                                <input
                                                    type="checkbox"
                                                    name="is_high_season"
                                                    value="1"
                                                    @checked((bool) $isHighSeason)
                                                    class="h-5 w-5 rounded border-slate-300"
                                                />
                                            </span>
                                        </label>

                                        <div
                                            class="rounded-3xl border border-blue-200 bg-white p-4 text-xs leading-5 text-blue-800 lg:col-span-4"
                                        >
                                            <p class="font-black text-blue-900">Catatan Otomatis</p>
                                            <p class="mt-1">
                                                Cuaca akan disesuaikan otomatis berdasarkan daerah wisata yang kamu pilih.
                                            </p>

                                            <div class="mt-3 rounded-2xl bg-blue-50 px-3 py-2 ring-1 ring-blue-100">
                                                <p class="font-black text-blue-900">{{ $bmkgForecastPeriodText }}</p>
                                                <p class="mt-1 text-[11px] leading-5 text-blue-700">
                                                    Informasi cuaca membantu TourHub menampilkan tempat wisata yang lebih nyaman untuk dikunjungi.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <input
                                        type="hidden"
                                        name="bmkg_adm4"
                                        value="{{ old('bmkg_adm4', data_get($payload ?? [], 'bmkg_adm4')) }}"
                                    />

                                    <button
                                        type="submit"
                                        class="group relative flex w-full items-center justify-center overflow-hidden rounded-2xl bg-slate-950 px-5 py-4 text-sm font-black text-white shadow-lg shadow-slate-900/20 transition hover:-translate-y-0.5 hover:bg-slate-800"
                                    >
                                        <span class="relative z-10 flex items-center gap-2">
                                            🔎 Cari Rekomendasi Wisata
                                        </span>
                                        <span
                                            class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent transition duration-700 group-hover:translate-x-full"
                                        ></span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <main class="mx-auto max-w-7xl px-6 py-8">
                {{-- Result Content --}}
                <section id="hasil" class="section-anchor space-y-6">
                    @isset($result)
                        @php
                            /*
                             * Sorting utama:
                             * Semua rekomendasi diurutkan berdasarkan final_score tertinggi.
                             * Item pertama setelah sorting dianggap sebagai paling direkomendasikan.
                             */
                            $recommendations = collect(data_get($result, 'recommendations', []))
                                ->sortByDesc(fn ($item) => (float) data_get($item, 'final_score', 0))
                                ->values();

                            $bestRecommendation = $recommendations->first();
                        @endphp

                        <div class="premium-shadow overflow-hidden rounded-[2rem] border border-slate-200 bg-white">
                            <div
                                class="border-b border-slate-100 bg-gradient-to-br from-white via-slate-50 to-blue-50 p-6"
                            >
                                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <p class="text-xs font-black tracking-wider text-blue-600 uppercase">
                                            Pilihan terbaik untuk liburanmu
                                        </p>

                                        <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950 md:text-3xl">
                                            Rekomendasi Destinasi Terbaik Untukmu
                                        </h2>

                                        <p class="mt-2 text-sm leading-6 text-slate-600">
                                            Hasil ini sudah diurutkan dari destinasi yang paling sesuai dengan pilihanmu.
                                        </p>

                                        <div class="mt-4 flex flex-wrap gap-2 text-xs font-bold">
                                            <span class="rounded-full bg-slate-950 px-3 py-1 text-white">
                                                Cuaca: {{ data_get($result, 'weather_used') ?? '-' }}
                                            </span>
<span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-700">
                                                Pilihan tersedia
                                            </span>
</div>

                                        @if (strtolower((string) data_get($result, 'weather_used')) === 'hujan')
                                            <div class="mt-4 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-800">
                                                🌧️ Otomatis mendeteksi potensi hujan pada wilayah ini. Sistem otomatis
                                                memprioritaskan destinasi indoor atau mixed agar perjalanan lebih nyaman.
                                            </div>
                                        @endif
                                    </div>

                                    <div class="rounded-3xl bg-white p-4 text-center shadow-sm ring-1 ring-slate-200">
                                        <p class="text-xs font-bold tracking-wide text-slate-500 uppercase">
                                            Pilihan Wisata
                                        </p>
                                        <p class="mt-1 text-3xl font-black text-slate-950">
                                            {{ $recommendations->count() }}
                                        </p>
                                        <p class="text-xs font-bold text-blue-700">Pilihan Wisata</p>
                                    </div>
                                </div>
                            </div>

                            <div class="p-6">
                                @if ($bestRecommendation)
                                    <div
                                        class="mb-8 overflow-hidden rounded-[2rem] border border-amber-200 bg-gradient-to-br from-amber-50 via-white to-blue-50 shadow-lg shadow-amber-900/5"
                                    >
                                        <div class="grid grid-cols-1 lg:grid-cols-12">
                                            <div class="relative min-h-[340px] lg:col-span-6">
                                                @if (data_get($bestRecommendation, 'link_gambar'))
                                                    <img
                                                        src="{{ data_get($bestRecommendation, 'link_gambar') }}"
                                                        alt="{{ data_get($bestRecommendation, 'nama_tempat_wisata') }}"
                                                        class="absolute inset-0 h-full w-full object-cover"
                                                    />
                                                @else
                                                    <div
                                                        class="absolute inset-0 flex h-full w-full items-center justify-center bg-gradient-to-br from-amber-100 to-blue-100 text-sm font-bold text-slate-500"
                                                    >
                                                        No Image
                                                    </div>
                                                @endif

                                                <div
                                                    class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-slate-950/10 to-transparent"
                                                ></div>

                                                <div
                                                    class="absolute top-5 left-5 rounded-2xl bg-amber-400 px-4 py-2 text-sm font-black text-slate-950 shadow-lg shadow-amber-900/20"
                                                >
                                                    🏆 Paling Direkomendasikan
                                                </div>

                                                <div class="absolute right-5 bottom-5 left-5 text-white">
                                                    <p
                                                        class="text-xs font-black tracking-wider text-blue-100 uppercase"
                                                    >
                                                        Paling Sesuai
                                                    </p>
                                                    <div class="mt-2 flex items-end justify-between gap-4">
                                                        <div>
                                                            <h3 class="text-3xl font-black tracking-tight md:text-4xl">
                                                                {{ data_get($bestRecommendation, 'nama_tempat_wisata') }}
                                                            </h3>
                                                            <p class="mt-2 text-sm font-semibold text-slate-200">
                                                                {{ data_get($bestRecommendation, 'kecamatan') }} -
                                                                {{ data_get($bestRecommendation, 'kabupaten_kota') }}
                                                            </p>
                                                        </div>
                                                        <div
                                                            class="rounded-3xl bg-white/90 px-5 py-3 text-right text-slate-950 backdrop-blur-xl"
                                                        >
                                                            <p class="text-xs font-bold text-slate-500">Status</p>
                                                            <p class="text-lg font-black leading-tight">
                                                                {{ $labelRekomendasi($bestRecommendation, 0) }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="p-6 md:p-8 lg:col-span-6">
                                                <div
                                                    class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between"
                                                >
                                                    <div>
                                                        <div class="flex flex-wrap gap-2">
                                                            <span
                                                                class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-700"
                                                            >
                                                                Pilihan Utama
                                                            </span>

                                                            <span
                                                                class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700"
                                                            >
                                                                {{ data_get($bestRecommendation, 'kategori') ?? '-' }}
                                                            </span>

                                                            <span
                                                                class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600"
                                                            >
                                                                {{ data_get($bestRecommendation, 'tipe_wisata') ?? '-' }}
                                                            </span>
                                                        </div>

                                                        <h3
                                                            class="mt-4 text-2xl font-black tracking-tight text-slate-950"
                                                        >
                                                            Kenapa destinasi ini cocok?
                                                        </h3>
                                                    </div>

                                                    @if (data_get($bestRecommendation, 'link_google_maps'))
                                                        <a
                                                            href="{{ data_get($bestRecommendation, 'link_google_maps') }}"
                                                            target="_blank"
                                                            class="inline-flex shrink-0 items-center justify-center rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-black text-white shadow-sm shadow-emerald-600/25 transition hover:bg-emerald-700"
                                                        >
                                                            Buka Maps
                                                        </a>
                                                    @endif
                                                </div>

                                                <div class="mt-6 grid grid-cols-2 gap-3 md:grid-cols-4">
                                                    <div class="rounded-2xl bg-white p-4 ring-1 ring-slate-200">
                                                        <p
                                                            class="text-xs font-bold tracking-wide text-slate-500 uppercase"
                                                        >
                                                            Rating
                                                        </p>
                                                        <p class="mt-1 text-xl font-black text-slate-950">
                                                            {{ data_get($bestRecommendation, 'rating') }}
                                                        </p>
                                                    </div>

                                                    <div class="rounded-2xl bg-white p-4 ring-1 ring-slate-200">
                                                        <p
                                                            class="text-xs font-bold tracking-wide text-slate-500 uppercase"
                                                        >
                                                            Ulasan
                                                        </p>
                                                        <p class="mt-1 text-xl font-black text-slate-950">
                                                            {{ number_format((int) data_get($bestRecommendation, 'jumlah_rating', 0)) }}
                                                        </p>
                                                    </div>

                                                    <div class="rounded-2xl bg-white p-4 ring-1 ring-slate-200">
                                                        <p
                                                            class="text-xs font-bold tracking-wide text-slate-500 uppercase"
                                                        >
                                                            Kesesuaian
                                                        </p>
                                                        <p class="mt-1 text-xl font-black text-slate-950">
                                                            {{ $labelKesesuaian($bestRecommendation) }}
                                                        </p>
                                                    </div>

                                                    <div class="rounded-2xl bg-white p-4 ring-1 ring-slate-200">
                                                        <p
                                                            class="text-xs font-bold tracking-wide text-slate-500 uppercase"
                                                        >
                                                            Kondisi Kunjungan
                                                        </p>
                                                        <p class="mt-1 text-xl font-black text-slate-950">
                                                            {{ $labelKondisi($bestRecommendation) }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="mt-6 rounded-2xl border border-amber-200 bg-white/80 p-4">
                                                    <p
                                                        class="text-xs font-black tracking-wider text-amber-700 uppercase"
                                                    >
                                                        Alasan Rekomendasi
                                                    </p>

                                                    <p class="mt-2 text-sm leading-6 text-slate-700">
                                                        Destinasi ini memiliki
                                                        <strong>paling sesuai</strong>
                                                        dibanding pilihan lainnya. Nilai ini dihitung dari kesesuaian preferensi,
                                                        rating, jumlah ulasan, dan kondisi kunjungan.
                                                    </p>

                                                    @php
                                                        $bestReason = $cleanReason(data_get($bestRecommendation, 'alasan'));
                                                    @endphp

                                                    @if ($bestReason)
                                                        <p class="mt-3 text-sm leading-6 text-slate-700">
                                                            {{ $bestReason }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                                    <div>
                                        <p class="text-xs font-black tracking-wider text-slate-500 uppercase">
                                            Aktivitas Trending
                                        </p>
                                        <h3 class="text-2xl font-black text-slate-950">Semua Pilihan Wisata</h3>
                                    </div>

                                    <p class="text-sm text-slate-500">Diurutkan dari paling sesuai.</p>
                                </div>

                                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                                    @forelse ($recommendations as $index => $item)
                                        <article
                                            class="group {{ $index === 0 ? 'border-amber-300 bg-amber-50/40' : 'border-slate-200 bg-white' }} overflow-hidden rounded-3xl border transition hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-900/10"
                                        >
                                            <div class="relative h-56 overflow-hidden">
                                                @if (data_get($item, 'link_gambar'))
                                                    <img
                                                        src="{{ data_get($item, 'link_gambar') }}"
                                                        alt="{{ data_get($item, 'nama_tempat_wisata') }}"
                                                        class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                                    />
                                                @else
                                                    <div
                                                        class="flex h-full w-full items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200 text-sm font-bold text-slate-400"
                                                    >
                                                        No Image
                                                    </div>
                                                @endif

                                                <div
                                                    class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-transparent to-transparent"
                                                ></div>

                                                <div
                                                    class="{{ $index === 0 ? 'bg-amber-400 text-slate-950' : 'bg-slate-950/90 text-white' }} absolute top-4 left-4 rounded-2xl px-3 py-2 text-sm font-black backdrop-blur"
                                                >
                                                    {{ $index === 0 ? 'Pilihan Utama' : 'Pilihan Lain' }}
                                                </div>

                                                @if ($index === 0)
                                                    <div
                                                        class="absolute top-4 right-4 rounded-2xl bg-white/90 px-3 py-2 text-xs font-black text-amber-700 backdrop-blur"
                                                    >
                                                        Tertinggi
                                                    </div>
                                                @endif

                                                <div class="absolute right-4 bottom-4 left-4">
                                                    <p class="text-xs font-bold text-blue-100">Status</p>
                                                    <p class="text-2xl font-black text-white">
                                                        {{ $labelRekomendasi($item, $index) }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="p-5">
                                                <div class="flex flex-wrap gap-2">
                                                    @if ($index === 0)
                                                        <span
                                                            class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-700"
                                                        >
                                                            🏆 Paling Direkomendasikan
                                                        </span>
                                                    @endif

                                                    <span
                                                        class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700"
                                                    >
                                                        {{ data_get($item, 'kategori') ?? '-' }}
                                                    </span>

                                                    <span
                                                        class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600"
                                                    >
                                                        {{ data_get($item, 'tipe_wisata') ?? '-' }}
                                                    </span>
                                                </div>

                                                <h3
                                                    class="mt-3 line-clamp-2 text-xl font-black tracking-tight text-slate-950"
                                                >
                                                    {{ data_get($item, 'nama_tempat_wisata') }}
                                                </h3>

                                                <p class="mt-2 text-sm font-medium text-slate-600">
                                                    📍 {{ data_get($item, 'kecamatan') }} -
                                                    {{ data_get($item, 'kabupaten_kota') }}
                                                </p>

                                                <div class="mt-5 grid grid-cols-2 gap-3">
                                                    <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                                        <p
                                                            class="text-xs font-bold tracking-wide text-slate-500 uppercase"
                                                        >
                                                            Rating
                                                        </p>
                                                        <p class="mt-1 text-lg font-black text-slate-950">
                                                            {{ data_get($item, 'rating') }}
                                                        </p>
                                                    </div>

                                                    <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                                        <p
                                                            class="text-xs font-bold tracking-wide text-slate-500 uppercase"
                                                        >
                                                            Ulasan
                                                        </p>
                                                        <p class="mt-1 text-lg font-black text-slate-950">
                                                            {{ number_format((int) data_get($item, 'jumlah_rating', 0)) }}
                                                        </p>
                                                    </div>

                                                    <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                                        <p
                                                            class="text-xs font-bold tracking-wide text-slate-500 uppercase"
                                                        >
                                                            Kesesuaian
                                                        </p>
                                                        <p class="mt-1 text-lg font-black text-slate-950">
                                                            {{ $labelKesesuaian($item) }}
                                                        </p>
                                                    </div>

                                                    <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                                        <p
                                                            class="text-xs font-bold tracking-wide text-slate-500 uppercase"
                                                        >
                                                            Kondisi Kunjungan
                                                        </p>
                                                        <p class="mt-1 text-lg font-black text-slate-950">
                                                            {{ $labelKondisi($item) }}
                                                        </p>
                                                    </div>
                                                </div>

                                                @php
                                                    $itemReason = $cleanReason(data_get($item, 'alasan'));
                                                    $needsToggle = $shouldShowReasonToggle($itemReason);
                                                @endphp

                                                @if ($itemReason)
                                                    <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                                        <p
                                                            class="text-xs font-black tracking-wider text-slate-500 uppercase"
                                                        >
                                                            Alasan
                                                        </p>

                                                        <p
                                                            class="tourhub-reason-content mt-2 text-sm leading-6 text-slate-700 {{ $needsToggle ? 'is-collapsible' : '' }}"
                                                            data-recommendation-reason-content
                                                            @if ($needsToggle) data-collapsible-reason="true" @endif
                                                        >
                                                            {{ $itemReason }}
                                                        </p>

                                                        @if ($needsToggle)
                                                            <button
                                                                type="button"
                                                                class="tourhub-reason-button"
                                                                data-recommendation-reason-button
                                                                aria-expanded="false"
                                                            >
                                                                <span>⌄</span>
                                                                Baca selengkapnya
                                                            </button>
                                                        @endif
                                                    </div>
                                                @endif

                                                @if (data_get($item, 'link_google_maps'))
                                                    <a
                                                        href="{{ data_get($item, 'link_google_maps') }}"
                                                        target="_blank"
                                                        class="mt-4 inline-flex w-full items-center justify-center rounded-2xl bg-emerald-100 px-4 py-3 text-sm font-black text-emerald-700 transition hover:bg-emerald-200"
                                                    >
                                                        Buka Maps
                                                    </a>
                                                @endif
                                            </div>
                                        </article>
                                    @empty
                                        <div
                                            class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center md:col-span-2 xl:col-span-3"
                                        >
                                            <div
                                                class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white text-3xl shadow-sm"
                                            >
                                                🧭
                                            </div>

                                            <h3 class="mt-4 text-xl font-black text-slate-950">
                                                Belum ada pilihan wisata
                                            </h3>

                                            <p class="mt-2 text-sm text-slate-500">
                                                Coba kosongkan kata kunci, turunkan rating minimal, atau pilih beberapa kategori.
                                            </p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        {{-- Area teknis pengembang disembunyikan dari user biasa. --}}
                    @else
                        {{-- Empty State: Traveloka-like content rows --}}
                        <div class="premium-shadow overflow-hidden rounded-[2rem] border border-slate-200 bg-white">
                            <div class="grid grid-cols-1 md:grid-cols-2">
                                <div class="p-8 md:p-10">
                                    <div
                                        class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-blue-700"
                                    >
                                        Belum Ada Hasil
                                    </div>

                                    <h2 class="mt-4 text-3xl font-black tracking-tight text-slate-950">
                                        Mulai rekomendasi pertamamu.
                                    </h2>

                                    <p class="mt-3 text-sm leading-6 text-slate-600">
                                        Isi pilihan wisata pada kotak pencarian di atas untuk mendapatkan rekomendasi Bali. Hasilnya akan langsung tersimpan di riwayat akunmu.
                                    </p>

                                    <div class="mt-6 grid grid-cols-1 gap-3">
                                        <div class="rounded-2xl bg-slate-50 p-4">
                                            <p class="font-black text-slate-900">1. Pilih kategori</p>
                                            <p class="mt-1 text-sm text-slate-500">Contoh: Alam dan Budaya.</p>
                                        </div>

                                        <div class="rounded-2xl bg-slate-50 p-4">
                                            <p class="font-black text-slate-900">2. Isi lokasi</p>
                                            <p class="mt-1 text-sm text-slate-500">
                                                Contoh: Kabupaten Gianyar, Kecamatan Ubud.
                                            </p>
                                        </div>

                                        <div class="rounded-2xl bg-slate-50 p-4">
                                            <p class="font-black text-slate-900">3. Klik cari</p>
                                            <p class="mt-1 text-sm text-slate-500">
                                                Sistem akan menghitung Rekomendasi Pintar.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="relative min-h-[360px] bg-gradient-to-br from-slate-950 via-blue-950 to-slate-900 p-8 text-white"
                                >
                                    <div class="soft-grid absolute inset-0 opacity-20"></div>
                                    <div class="relative flex h-full flex-col justify-between">
                                        <div>
                                            <p class="text-sm font-bold text-blue-200">Panduan TourHub</p>
                                            <h3 class="mt-3 text-4xl font-black">Rekomendasi Pintar</h3>
                                            <p class="mt-3 text-sm leading-6 text-slate-300">
                                                Rekomendasi dibuat berdasarkan pilihan wisata, rating, popularitas, lokasi, dan kondisi cuaca.
                                            </p>
                                        </div>

                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="rounded-2xl bg-white/10 p-4 backdrop-blur">
                                                <p class="text-xs text-slate-300">Kesesuaian</p>
                                                <p class="mt-1 font-black">Kesesuaian</p>
                                            </div>

                                            <div class="rounded-2xl bg-white/10 p-4 backdrop-blur">
                                                <p class="text-xs text-slate-300">Kondisi Perjalanan</p>
                                                <p class="mt-1 font-black">Kondisi Kunjungan</p>
                                            </div>

                                            <div class="rounded-2xl bg-white/10 p-4 backdrop-blur">
                                                <p class="text-xs text-slate-300">Cuaca</p>
                                                <p class="mt-1 font-black">Otomatis</p>
                                            </div>

                                            <div class="rounded-2xl bg-white/10 p-4 backdrop-blur">
                                                <p class="text-xs text-slate-300">Hasil</p>
                                                <p class="mt-1 font-black">Pilihan</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                            {{--
                            <div class="premium-shadow overflow-hidden rounded-3xl border border-slate-200 bg-white">
                                <div class="h-32 bg-gradient-to-br from-slate-950 via-blue-950 to-slate-900"></div>
                                <div class="p-5">
                                    <p class="text-xs font-black text-blue-700">Kategori</p>
                                    <h3 class="mt-1 text-xl font-black text-slate-950">Alam Bali</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-500">
                                        Cocok untuk pantai, air terjun, dan pegunungan.
                                    </p>
                                </div>
                            </div>

                            <div class="premium-shadow overflow-hidden rounded-3xl border border-slate-200 bg-white">
                                <div class="h-32 bg-gradient-to-br from-slate-950 via-blue-950 to-slate-900"></div>
                                <div class="p-5">
                                    <p class="text-xs font-black text-blue-700">Kategori</p>
                                    <h3 class="mt-1 text-xl font-black text-slate-950">Budaya Bali</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-500">
                                        Cocok untuk pura, desa adat, dan wisata sejarah.
                                    </p>
                                </div>
                            </div>

                            <div class="premium-shadow overflow-hidden rounded-3xl border border-slate-200 bg-white">
                                <div class="h-32 bg-gradient-to-br from-slate-950 via-blue-950 to-slate-900"></div>
                                <div class="p-5">
                                    <p class="text-xs font-black text-blue-700">Kategori</p>
                                    <h3 class="mt-1 text-xl font-black text-slate-950">Rekreasi</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-500">
                                        Cocok untuk aktivitas keluarga dan tempat populer.
                                    </p>
                                </div>
                            </div>
                            --}}

                            <div class="premium-shadow overflow-hidden rounded-3xl border border-slate-200 bg-white">
                                <div class="relative h-48 overflow-hidden">
                                    <img
                                        src="https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?auto=format&fit=crop&w=1200&q=80"
                                        alt="Pemandangan alam Bali"
                                        class="h-full w-full object-cover"
                                    />
                                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-900/20 to-transparent"></div>
                                    <div class="absolute right-4 bottom-4 left-4 flex items-end justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-black tracking-wider text-blue-200 uppercase">Kategori</p>
                                            <h3 class="mt-1 text-2xl font-black text-white">Alam Bali</h3>
                                        </div>
                                        <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold text-white backdrop-blur">Pantai • Gunung</span>
                                    </div>
                                </div>
                                <div class="p-5">
                                    <p class="text-sm leading-6 text-slate-600">
                                        Cocok untuk wisata pantai, air terjun, sawah terasering, dan panorama pegunungan.
                                    </p>
                                </div>
                            </div>

                            <div class="premium-shadow overflow-hidden rounded-3xl border border-slate-200 bg-white">
                                <div class="relative h-48 overflow-hidden">
                                    <img
                                        src="https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=1200&q=80"
                                        alt="Wisata budaya Bali"
                                        class="h-full w-full object-cover"
                                    />
                                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-900/20 to-transparent"></div>
                                    <div class="absolute right-4 bottom-4 left-4 flex items-end justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-black tracking-wider text-blue-200 uppercase">Kategori</p>
                                            <h3 class="mt-1 text-2xl font-black text-white">Budaya Bali</h3>
                                        </div>
                                        <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold text-white backdrop-blur">Pura • Sejarah</span>
                                    </div>
                                </div>
                                <div class="p-5">
                                    <p class="text-sm leading-6 text-slate-600">
                                        Cocok untuk pura, desa adat, pertunjukan seni, dan destinasi yang kaya nilai sejarah.
                                    </p>
                                </div>
                            </div>

                            <div class="premium-shadow overflow-hidden rounded-3xl border border-slate-200 bg-white">
                                <div class="relative h-48 overflow-hidden">
                                    <img
                                        src="https://images.unsplash.com/photo-1544644181-1484b3fdfc62?auto=format&fit=crop&w=1200&q=80"
                                        alt="Wisata rekreasi Bali"
                                        class="h-full w-full object-cover"
                                    />
                                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-900/20 to-transparent"></div>
                                    <div class="absolute right-4 bottom-4 left-4 flex items-end justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-black tracking-wider text-blue-200 uppercase">Kategori</p>
                                            <h3 class="mt-1 text-2xl font-black text-white">Rekreasi</h3>
                                        </div>
                                        <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold text-white backdrop-blur">Keluarga • Populer</span>
                                    </div>
                                </div>
                                <div class="p-5">
                                    <p class="text-sm leading-6 text-slate-600">
                                        Cocok untuk aktivitas keluarga, tempat populer, wisata santai, dan pengalaman hiburan.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endisset
                </section>

                {{-- Latest Logs --}}
                <section
                    id="log"
                    class="section-anchor premium-shadow mt-8 overflow-hidden rounded-[2rem] border border-slate-200 bg-white"
                >
                    <div class="border-b border-slate-100 bg-gradient-to-br from-white to-slate-50 p-6">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <p class="text-xs font-black tracking-wider text-slate-500 uppercase">
                                    Riwayat Terbaru
                                </p>

                                <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950">
                                    Riwayat Pencarian Terbaru
                                </h2>

                                <p class="mt-2 text-sm text-slate-600">
                                    Pencarian yang kamu lakukan akan tersimpan di riwayat akunmu.
                                </p>
                            </div>

                            <a
                                href="{{ route('user.dashboard') }}#riwayat"
                                class="inline-flex rounded-2xl bg-slate-100 px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-200"
                            >
                                Lihat Semua
                            </a>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr
                                    class="border-b border-slate-200 bg-slate-50 text-left text-xs tracking-wide text-slate-500 uppercase"
                                >
                                    <th class="px-6 py-4">Waktu</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4">Cuaca</th>
                                    <th class="px-6 py-4">Pilihan</th>
                                    <th class="px-6 py-4">Destinasi Teratas</th>
                                    
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                @forelse ($latestLogs as $log)
                                    <tr class="transition hover:bg-slate-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-bold text-slate-900">
                                                {{ $log->created_at?->format('d M Y') }}
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                {{ $log->created_at?->format('H:i') }}
                                            </div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span
                                                class="{{ $log->status === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }} inline-flex rounded-full px-3 py-1 text-xs font-black"
                                            >
                                                {{ $log->status === 'success' ? 'Berhasil' : 'Belum Berhasil' }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <div class="font-bold text-slate-900">
                                                {{ $log->weather_used ?? '-' }}
                                            </div>
</td>

                                        <td class="px-6 py-4 font-bold text-slate-900">
                                            {{ $log->total_candidates ?? '-' }}
                                        </td>

                                        <td class="px-6 py-4">
                                            <div class="max-w-[260px] font-bold text-slate-900">
                                                {{ $log->top_destination_name ?? '-' }}
                                            </div>
                                        </td>

                                        
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center">
                                            <div
                                                class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl"
                                            >
                                                📄
                                            </div>

                                            <h3 class="mt-4 font-black text-slate-950">Belum ada log rekomendasi</h3>

                                            <p class="mt-1 text-sm text-slate-500">
                                                Setelah mencari rekomendasi, riwayat akan tampil di sini.
                                            </p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const header = document.querySelector('header')
                const navLinks = Array.from(document.querySelectorAll('a[data-scroll-link][href^="#"]'))
                const sections = navLinks
                    .map((link) => document.querySelector(link.getAttribute('href')))
                    .filter(Boolean)

                document.querySelectorAll('[data-recommendation-reason-button]').forEach((button) => {
                    const reasonBox = button.closest('div')?.querySelector('[data-recommendation-reason-content]')

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


                // Logic tambahan khusus navbar mobile halaman rekomendasi.
                // Tidak mengubah logic rekomendasi, hanya mengatur buka/tutup dropdown.
                const recommendationMenuButton = document.getElementById('tourhub-recommendation-menu-button')
                const recommendationMobileMenu = document.getElementById('tourhub-recommendation-mobile-menu')

                if (recommendationMenuButton && recommendationMobileMenu) {
                    const closeRecommendationMenu = () => {
                        recommendationMobileMenu.classList.remove('is-open')
                        recommendationMenuButton.classList.remove('is-open')
                        recommendationMenuButton.setAttribute('aria-expanded', 'false')
                    }

                    const openRecommendationMenu = () => {
                        recommendationMobileMenu.classList.add('is-open')
                        recommendationMenuButton.classList.add('is-open')
                        recommendationMenuButton.setAttribute('aria-expanded', 'true')
                    }

                    recommendationMenuButton.addEventListener('click', (event) => {
                        event.stopPropagation()

                        if (recommendationMobileMenu.classList.contains('is-open')) {
                            closeRecommendationMenu()
                        } else {
                            openRecommendationMenu()
                        }
                    })

                    recommendationMobileMenu.addEventListener('click', (event) => {
                        event.stopPropagation()
                    })

                    document.addEventListener('click', closeRecommendationMenu)

                    document.addEventListener('keydown', (event) => {
                        if (event.key === 'Escape') {
                            closeRecommendationMenu()
                        }
                    })

                    window.addEventListener('resize', () => {
                        if (window.innerWidth >= 1024) {
                            closeRecommendationMenu()
                        }
                    })
                }

                const getHeaderOffset = () => {
                    const headerHeight = header ? header.offsetHeight : 120
                    return headerHeight + 18
                }

                const setActiveLink = (targetId) => {
                    navLinks.forEach((link) => {
                        const isActive = link.getAttribute('href') === `#${targetId}`
                        link.classList.toggle('is-active', isActive)
                    })
                }

                const scrollToSection = (target, updateHash = true) => {
                    const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - getHeaderOffset()

                    window.scrollTo({
                        top: Math.max(targetPosition, 0),
                        behavior: 'smooth',
                    })

                    setActiveLink(target.id)

                    if (updateHash && window.history && window.history.pushState) {
                        window.history.pushState(null, '', `#${target.id}`)
                    }
                }

                navLinks.forEach((link) => {
                    link.addEventListener('click', (event) => {
                        const hash = link.getAttribute('href')
                        const target = document.querySelector(hash)

                        if (!target) {
                            return
                        }

                        event.preventDefault()
                        scrollToSection(target)
                    })
                })

                const updateActiveOnScroll = () => {
                    const offset = getHeaderOffset() + 24
                    let currentSectionId = sections[0]?.id

                    sections.forEach((section) => {
                        const top = section.getBoundingClientRect().top + window.pageYOffset

                        if (window.pageYOffset + offset >= top) {
                            currentSectionId = section.id
                        }
                    })

                    if (currentSectionId) {
                        setActiveLink(currentSectionId)
                    }
                }

                let ticking = false
                window.addEventListener(
                    'scroll',
                    () => {
                        if (!ticking) {
                            window.requestAnimationFrame(() => {
                                updateActiveOnScroll()
                                ticking = false
                            })
                            ticking = true
                        }
                    },
                    { passive: true }
                )

                if (window.location.hash) {
                    const initialTarget = document.querySelector(window.location.hash)

                    if (initialTarget) {
                        window.setTimeout(() => scrollToSection(initialTarget, false), 120)
                    }
                } else {
                    updateActiveOnScroll()
                }
            })
        </script>
    </body>
</html>
