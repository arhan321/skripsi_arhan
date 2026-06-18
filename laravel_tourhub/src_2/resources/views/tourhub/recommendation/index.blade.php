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
                // Nilai ini hanya sebagai fallback jika BMKG gagal/tidak tersedia.
                $selectedWeather = 'cerah';

                $selectedVisitDay = old('visit_day', data_get($payload ?? [], 'visit_day', 'weekday'));

                $isHighSeason = old('is_high_season', data_get($payload ?? [], 'is_high_season', false));

                // BMKG dibuat aktif otomatis agar user tidak perlu memilih cuaca manual.
                $useBmkg = true;

                $selectedKategoriArray = (array) $selectedKategori;

                /*
                 * Keterangan periode BMKG untuk ditampilkan di UI.
                 * BMKG data terbuka prakiraan cuaca tersedia dalam format 3 harian,
                 * dengan 8 data per hari atau interval sekitar 3 jam.
                 */
                $bmkgForecastPeriodText = 'Prakiraan BMKG ±3 hari ke depan';
                $bmkgForecastIntervalText = 'Update prakiraan per 3 jam';
            @endphp

            {{-- Top Navigation: Travel app style --}}
            <header class="sticky top-0 z-50 border-b border-slate-200 bg-white/90 backdrop-blur-xl">
                <div class="mx-auto max-w-7xl px-6">
                    <div class="flex min-h-[72px] flex-col gap-4 py-4 md:flex-row md:items-center md:justify-between">
                        <a href="{{ route('user.dashboard') }}" class="flex items-center gap-3">
                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-slate-950 to-blue-700 text-xl font-black text-white shadow-lg shadow-blue-900/20"
                            >
                                T
                            </div>

                            <div>
                                <div class="flex items-center gap-2">
                                    <h1 class="text-2xl font-black tracking-tight text-slate-950">TourHub</h1>
                                    <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-black text-blue-700">
                                        Bali
                                    </span>
                                </div>

                                <p class="text-xs font-semibold text-slate-500">Temukan destinasi wisata terbaik</p>
                            </div>
                        </a>

                        <div class="flex flex-wrap items-center gap-2 text-sm font-bold">
                            {{--
                                CODE MATI - Informasi endpoint ML API disembunyikan dari user awam.
                                Alasan: URL service internal seperti FastAPI/Flask/ML API bersifat teknis
                                dan tidak perlu ditampilkan pada halaman pengguna.

                                <span
                                    class="hidden rounded-2xl bg-slate-50 px-4 py-2.5 text-xs text-slate-600 ring-1 ring-slate-200 lg:inline-flex"
                                >
                                    ML API:
                                    <span class="ml-1 text-slate-900">{{ $defaultBaseUrl ?? '-' }}</span>
                                </span>
                            --}}

                            <span
                                class="hidden items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-2.5 text-xs font-black text-emerald-700 ring-1 ring-emerald-200 lg:inline-flex"
                            >
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                Sistem Rekomendasi Aktif
                            </span>

                            @auth
                                <span
                                    class="hidden rounded-2xl bg-slate-50 px-4 py-2.5 text-xs text-slate-600 ring-1 ring-slate-200 xl:inline-flex"
                                >
                                    {{ auth()->user()->name }}
                                </span>
                            @endauth

                            <a
                                href="{{ route('user.dashboard') }}#riwayat"
                                class="inline-flex items-center justify-center rounded-2xl bg-blue-100 px-4 py-2.5 text-blue-700 transition hover:-translate-y-0.5 hover:bg-blue-200"
                            >
                                Riwayat Saya
                            </a>

                            <a
                                href="{{ route('user.dashboard') }}"
                                class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-4 py-2.5 text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-800"
                            >
                                Dashboard
                            </a>

                            @auth
                                <form method="POST" action="{{ route('user.logout') }}">
                                    @csrf

                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center rounded-2xl bg-red-100 px-4 py-2.5 text-red-700 transition hover:-translate-y-0.5 hover:bg-red-200"
                                    >
                                        Logout
                                    </button>
                                </form>
                            @endauth
                        </div>
                    </div>

                    <nav
                        class="hide-scrollbar flex gap-7 overflow-x-auto border-t border-slate-100 py-3 text-sm font-black text-slate-600"
                    >
                        <a href="#search" data-scroll-link class="nav-scroll-link is-active whitespace-nowrap">
                            Rekomendasi
                        </a>
                        <a href="#hasil" data-scroll-link class="nav-scroll-link whitespace-nowrap">Hasil Ranking</a>
                        <a href="#kategori" data-scroll-link class="nav-scroll-link whitespace-nowrap">Kategori</a>
                        <a href="#bmkg" data-scroll-link class="nav-scroll-link whitespace-nowrap">Cuaca BMKG</a>
                        <a href="#log" data-scroll-link class="nav-scroll-link whitespace-nowrap">Aktivitas Terbaru</a>
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
                                <div class="mt-2">Top-N</div>
                            </div>
                        </div>

                        <div class="hero-search-shadow overflow-hidden rounded-[2rem] border border-white/20 bg-white">
                            <div class="border-b border-slate-100 bg-gradient-to-br from-white to-slate-50 p-5 md:p-6">
                                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                    <div>
                                        <p class="text-xs font-black tracking-wider text-blue-600 uppercase">
                                            Travel Planner
                                        </p>
                                        <h3 class="mt-1 text-2xl font-black tracking-tight text-slate-950">
                                            Mau liburan ke mana?
                                        </h3>
                                        <p class="mt-1 text-sm text-slate-600">
                                            Isi parameter rekomendasi, lalu sistem akan menghitung ranking destinasi
                                            terbaik.
                                        </p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2 text-xs font-black md:flex">
                                        <span class="rounded-2xl bg-slate-950 px-3 py-2 text-white">Rekomendasi Cerdas</span>
                                        <span class="rounded-2xl bg-blue-100 px-3 py-2 text-blue-700">
                                            BMKG Otomatis
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
                                                Lokasi Wisata
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
                                                Keywords
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
                                                Min Rating
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
                                                Top N
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
                                                        <p class="font-black text-slate-950">Otomatis dari BMKG</p>
                                                        <p class="mt-1 text-xs font-medium leading-5 text-slate-500">
                                                            Default sistem adalah cerah. Jika BMKG mendeteksi hujan,
                                                            CARS otomatis memprioritaskan destinasi indoor atau mixed.
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
                                                        BMKG Otomatis
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
                                                    Aktif Otomatis
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
                                                        High Season
                                                    </span>
                                                    <span class="text-xs leading-5 text-slate-500">
                                                        Simulasi kondisi ramai wisatawan.
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
                                            <p class="font-black text-blue-900">Catatan BMKG</p>
                                            <p class="mt-1">
                                                User tidak perlu mengisi kode ADM4. Sistem akan menentukan ADM4 secara
                                                otomatis dari lokasi yang dipilih.
                                            </p>

                                            <div class="mt-3 rounded-2xl bg-blue-50 px-3 py-2 ring-1 ring-blue-100">
                                                <p class="font-black text-blue-900">{{ $bmkgForecastPeriodText }}</p>
                                                <p class="mt-1 text-[11px] leading-5 text-blue-700">
                                                    Data prakiraan cuaca BMKG bersifat 3 harian dengan interval sekitar
                                                    3 jam. Di sistem ini, cuaca dipakai sebagai konteks CARS. Jika
                                                    hujan terdeteksi, sistem memprioritaskan destinasi indoor atau mixed.
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
                                            Promo terbaik buat liburan irit!
                                        </p>

                                        <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950 md:text-3xl">
                                            Rekomendasi Destinasi Terbaik Untukmu
                                        </h2>

                                        <p class="mt-2 text-sm leading-6 text-slate-600">
                                            Hasil ini sudah diurutkan berdasarkan final score tertinggi. Ranking #1
                                            adalah destinasi paling direkomendasikan.
                                        </p>

                                        <div class="mt-4 flex flex-wrap gap-2 text-xs font-bold">
                                            <span class="rounded-full bg-slate-950 px-3 py-1 text-white">
                                                Cuaca: {{ data_get($result, 'weather_used') ?? '-' }}
                                            </span>

                                            <span class="rounded-full bg-blue-100 px-3 py-1 text-blue-700">
                                                Sumber Cuaca: {{ data_get($result, 'weather_source') ?? '-' }}
                                            </span>

                                            @if (str_contains(strtolower((string) data_get($result, 'weather_source', '')), 'bmkg'))
                                                <span class="rounded-full bg-cyan-100 px-3 py-1 text-cyan-700">
                                                    {{ $bmkgForecastPeriodText }} · {{ $bmkgForecastIntervalText }}
                                                </span>
                                            @endif

                                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-700">
                                                Candidates: {{ data_get($result, 'total_candidates') ?? '-' }}
                                            </span>

                                            <span class="rounded-full bg-amber-100 px-3 py-1 text-amber-700">
                                                Response: {{ $responseTimeMs ?? '-' }} ms
                                            </span>
                                        </div>

                                        @if (strtolower((string) data_get($result, 'weather_used')) === 'hujan')
                                            <div class="mt-4 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-800">
                                                🌧️ BMKG mendeteksi potensi hujan pada wilayah ini. Sistem otomatis
                                                memprioritaskan destinasi indoor atau mixed agar perjalanan lebih nyaman.
                                            </div>
                                        @endif
                                    </div>

                                    <div class="rounded-3xl bg-white p-4 text-center shadow-sm ring-1 ring-slate-200">
                                        <p class="text-xs font-bold tracking-wide text-slate-500 uppercase">
                                            Total Output
                                        </p>
                                        <p class="mt-1 text-3xl font-black text-slate-950">
                                            {{ $recommendations->count() }}
                                        </p>
                                        <p class="text-xs font-bold text-blue-700">Top Destinasi</p>
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
                                                        Final Score Tertinggi
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
                                                            <p class="text-xs font-bold text-slate-500">Score</p>
                                                            <p class="text-3xl font-black">
                                                                {{ data_get($bestRecommendation, 'final_score') }}
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
                                                                Rank #1
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
                                                            CBF
                                                        </p>
                                                        <p class="mt-1 text-xl font-black text-slate-950">
                                                            {{ data_get($bestRecommendation, 'cbf_score') }}
                                                        </p>
                                                    </div>

                                                    <div class="rounded-2xl bg-white p-4 ring-1 ring-slate-200">
                                                        <p
                                                            class="text-xs font-bold tracking-wide text-slate-500 uppercase"
                                                        >
                                                            Context
                                                        </p>
                                                        <p class="mt-1 text-xl font-black text-slate-950">
                                                            {{ data_get($bestRecommendation, 'context_multiplier') }}
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
                                                        <strong>final score tertinggi</strong>
                                                        dibanding kandidat lainnya. Final score dihitung dari kecocokan
                                                        CBF, kualitas rating, jumlah ulasan, dan penyesuaian konteks
                                                        CARS.
                                                    </p>

                                                    @if (data_get($bestRecommendation, 'alasan'))
                                                        <p class="mt-3 text-sm leading-6 text-slate-700">
                                                            {{ data_get($bestRecommendation, 'alasan') }}
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
                                        <h3 class="text-2xl font-black text-slate-950">Semua Kandidat Rekomendasi</h3>
                                    </div>

                                    <p class="text-sm text-slate-500">Diurutkan dari final score tertinggi.</p>
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
                                                    #{{ $index + 1 }}
                                                </div>

                                                @if ($index === 0)
                                                    <div
                                                        class="absolute top-4 right-4 rounded-2xl bg-white/90 px-3 py-2 text-xs font-black text-amber-700 backdrop-blur"
                                                    >
                                                        Tertinggi
                                                    </div>
                                                @endif

                                                <div class="absolute right-4 bottom-4 left-4">
                                                    <p class="text-xs font-bold text-blue-100">Final Score</p>
                                                    <p class="text-3xl font-black text-white">
                                                        {{ data_get($item, 'final_score') }}
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
                                                            CBF
                                                        </p>
                                                        <p class="mt-1 text-lg font-black text-slate-950">
                                                            {{ data_get($item, 'cbf_score') }}
                                                        </p>
                                                    </div>

                                                    <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                                        <p
                                                            class="text-xs font-bold tracking-wide text-slate-500 uppercase"
                                                        >
                                                            Context
                                                        </p>
                                                        <p class="mt-1 text-lg font-black text-slate-950">
                                                            {{ data_get($item, 'context_multiplier') }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                                    <p
                                                        class="text-xs font-black tracking-wider text-slate-500 uppercase"
                                                    >
                                                        Alasan
                                                    </p>

                                                    <p class="mt-2 line-clamp-4 text-sm leading-6 text-slate-700">
                                                        {{ data_get($item, 'alasan') }}
                                                    </p>
                                                </div>

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
                                                Tidak ada rekomendasi
                                            </h3>

                                            <p class="mt-2 text-sm text-slate-500">
                                                Coba kosongkan keyword, turunkan min rating, atau pilih semua kategori.
                                            </p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        {{-- JSON Debug --}}
                        <details class="premium-shadow overflow-hidden rounded-3xl border border-slate-200 bg-white">
                            <summary
                                class="cursor-pointer border-b border-slate-100 bg-slate-50 px-6 py-4 text-sm font-black text-slate-950"
                            >
                                Lihat JSON Request & Response
                            </summary>

                            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2">
                                <div>
                                    <p class="mb-2 text-sm font-black text-slate-900">Request ke FastAPI</p>

                                    <pre
                                        class="max-h-[480px] overflow-auto rounded-2xl bg-slate-950 p-4 text-xs leading-5 text-slate-100"
                                    >
{{ json_encode($payload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre
                                    >
                                </div>

                                <div>
                                    <p class="mb-2 text-sm font-black text-slate-900">Response FastAPI</p>

                                    <pre
                                        class="max-h-[480px] overflow-auto rounded-2xl bg-slate-950 p-4 text-xs leading-5 text-slate-100"
                                    >
{{ json_encode($result ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre
                                    >
                                </div>
                            </div>
                        </details>
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
                                        Isi parameter pada kotak pencarian di atas untuk mendapatkan rekomendasi wisata
                                        Bali. Hasilnya akan langsung disimpan ke riwayat user kamu.
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
                                                Sistem akan menghitung CBF + CARS.
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
                                            <p class="text-sm font-bold text-blue-200">TourHub Insight</p>
                                            <h3 class="mt-3 text-4xl font-black">CBF + CARS</h3>
                                            <p class="mt-3 text-sm leading-6 text-slate-300">
                                                Ranking destinasi dipengaruhi oleh kecocokan preferensi, rating,
                                                popularitas, dan konteks seperti cuaca.
                                            </p>
                                        </div>

                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="rounded-2xl bg-white/10 p-4 backdrop-blur">
                                                <p class="text-xs text-slate-300">CBF</p>
                                                <p class="mt-1 font-black">Similarity</p>
                                            </div>

                                            <div class="rounded-2xl bg-white/10 p-4 backdrop-blur">
                                                <p class="text-xs text-slate-300">CARS</p>
                                                <p class="mt-1 font-black">Context</p>
                                            </div>

                                            <div class="rounded-2xl bg-white/10 p-4 backdrop-blur">
                                                <p class="text-xs text-slate-300">Weather</p>
                                                <p class="mt-1 font-black">BMKG</p>
                                            </div>

                                            <div class="rounded-2xl bg-white/10 p-4 backdrop-blur">
                                                <p class="text-xs text-slate-300">Output</p>
                                                <p class="mt-1 font-black">Top-N</p>
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
                                    Aktivitas Terbaru
                                </p>

                                <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950">
                                    Log Rekomendasi Terbaru
                                </h2>

                                <p class="mt-2 text-sm text-slate-600">
                                    Data ini tersimpan sebagai riwayat rekomendasi milik user yang sedang login.
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
                                    <th class="px-6 py-4">Candidates</th>
                                    <th class="px-6 py-4">Top Destination</th>
                                    <th class="px-6 py-4">Response</th>
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
                                                {{ ucfirst($log->status) }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <div class="font-bold text-slate-900">
                                                {{ $log->weather_used ?? '-' }}
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                {{ $log->weather_source ?? 'manual' }}
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

                                        <td class="px-6 py-4 font-medium text-slate-600">
                                            {{ $log->response_time_ms ? $log->response_time_ms . ' ms' : '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center">
                                            <div
                                                class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl"
                                            >
                                                📄
                                            </div>

                                            <h3 class="mt-4 font-black text-slate-950">Belum ada log rekomendasi</h3>

                                            <p class="mt-1 text-sm text-slate-500">
                                                Setelah submit form, log akan tampil di sini.
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
