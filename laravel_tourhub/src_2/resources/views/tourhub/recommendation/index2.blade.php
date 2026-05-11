<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TourHub Bali - Rekomendasi Wisata</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        [x-cloak] { display: none !important; }

        input[type="text"],
        input[type="number"],
        select {
            outline: none;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        select:focus {
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
        }

        .premium-shadow {
            box-shadow:
                0 20px 60px rgba(15, 23, 42, 0.10),
                0 1px 2px rgba(15, 23, 42, 0.06);
        }

        .soft-grid {
            background-image:
                linear-gradient(rgba(15, 23, 42, 0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(15, 23, 42, 0.035) 1px, transparent 1px);
            background-size: 28px 28px;
        }
    </style>
</head>

<body class="min-h-screen bg-slate-100 text-slate-950 antialiased">
    <div class="min-h-screen soft-grid">
        {{-- Top Navigation --}}
        <header class="sticky top-0 z-40 border-b border-white/20 bg-white/80 backdrop-blur-xl">
            <div class="mx-auto flex max-w-7xl flex-col gap-4 px-6 py-4 md:flex-row md:items-center md:justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-slate-950 to-blue-700 text-xl font-black text-white shadow-lg shadow-blue-900/20">
                        T
                    </div>

                    <div>
                        <h1 class="text-2xl font-black tracking-tight text-slate-950">
                            TourHub Bali
                        </h1>

                        <p class="text-sm font-medium text-slate-600">
                            Simulasi Web Rekomendasi Wisata CBF + CARS
                        </p>

                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                            <span>
                                ML API:
                                <span class="font-semibold text-slate-700">
                                    {{ $defaultBaseUrl ?? '-' }}
                                </span>
                            </span>

                            @auth
                                <span class="hidden h-1 w-1 rounded-full bg-slate-300 md:inline-block"></span>

                                <span>
                                    Login sebagai:
                                    <span class="font-bold text-slate-800">
                                        {{ auth()->user()->name }}
                                    </span>
                                </span>
                            @endauth
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a
                        href="{{ route('user.dashboard') }}"
                        class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-800"
                    >
                        ← Kembali ke Dashboard
                    </a>

                    <a
                        href="{{ route('user.dashboard') }}#riwayat"
                        class="inline-flex items-center justify-center rounded-2xl bg-blue-100 px-4 py-2.5 text-sm font-bold text-blue-700 transition hover:-translate-y-0.5 hover:bg-blue-200"
                    >
                        Riwayat Saya
                    </a>

                    @auth
                        <form method="POST" action="{{ route('user.logout') }}">
                            @csrf

                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-2xl bg-red-100 px-4 py-2.5 text-sm font-bold text-red-700 transition hover:-translate-y-0.5 hover:bg-red-200"
                            >
                                Logout
                            </button>
                        </form>
                    @endauth
                </div>
            </div>
        </header>

        {{-- Hero --}}
        <section class="relative overflow-hidden bg-slate-950">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.35),_transparent_34%),radial-gradient(circle_at_bottom_right,_rgba(16,185,129,0.25),_transparent_30%)]"></div>
            <div class="absolute inset-0 opacity-20 soft-grid"></div>

            <div class="relative mx-auto max-w-7xl px-6 py-8 md:py-10">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 lg:items-end">
                    <div class="lg:col-span-2">
                        <div class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-bold text-blue-100 ring-1 ring-white/10">
                            Machine Learning Recommendation System
                        </div>

                        <h2 class="mt-4 max-w-3xl text-3xl font-black tracking-tight text-white md:text-5xl">
                            Cari destinasi Bali berdasarkan preferensi dan konteks cuaca.
                        </h2>

                        <p class="mt-4 max-w-2xl text-sm leading-6 text-slate-300 md:text-base">
                            Sistem ini menggabungkan Content-Based Filtering untuk kecocokan preferensi
                            dan Context-Aware Recommender System untuk menyesuaikan ranking berdasarkan
                            cuaca, hari kunjungan, dan high season.
                        </p>
                    </div>

                    <div class="rounded-3xl border border-white/10 bg-white/10 p-5 text-white backdrop-blur-xl">
                        <p class="text-sm font-bold text-slate-200">Status Sistem</p>

                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-2xl bg-white/10 p-3">
                                <p class="text-xs text-slate-300">Algorithm</p>
                                <p class="mt-1 font-black">CBF + CARS</p>
                            </div>

                            <div class="rounded-2xl bg-white/10 p-3">
                                <p class="text-xs text-slate-300">Weather</p>
                                <p class="mt-1 font-black">BMKG / Manual</p>
                            </div>

                            <div class="rounded-2xl bg-white/10 p-3">
                                <p class="text-xs text-slate-300">Ranking</p>
                                <p class="mt-1 font-black">Final Score</p>
                            </div>

                            <div class="rounded-2xl bg-white/10 p-3">
                                <p class="text-xs text-slate-300">History</p>
                                <p class="mt-1 font-black">Per User</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <main class="mx-auto max-w-7xl px-6 py-8">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                {{-- Left Form --}}
                <aside class="lg:col-span-4 xl:col-span-4">
                    <div class="sticky top-28 overflow-hidden rounded-3xl border border-slate-200 bg-white premium-shadow">
                        <div class="border-b border-slate-100 bg-gradient-to-br from-white to-slate-50 p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wider text-blue-600">
                                        Input Parameter
                                    </p>

                                    <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950">
                                        Rekomendasi Wisata
                                    </h2>

                                    <p class="mt-2 text-sm leading-6 text-slate-600">
                                        Isi preferensi wisata, lalu Laravel akan memanggil FastAPI Machine Learning.
                                    </p>
                                </div>

                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-2xl">
                                    🧭
                                </div>
                            </div>
                        </div>

                        <div class="p-6">
                            @if ($errors->any())
                                <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-red-100">
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

                            <form method="POST" action="{{ route('tourhub.recommendation.store') }}" class="space-y-5">
                                @csrf

                                @php
                                    $selectedKategori = old(
                                        'kategori_preferensi',
                                        data_get($payload ?? [], 'kategori_preferensi', ['Alam'])
                                    );

                                    $selectedKabupaten = old(
                                        'kabupaten_kota',
                                        data_get($payload ?? [], 'kabupaten_kota', 'Kabupaten Gianyar')
                                    );

                                    $selectedWeather = old(
                                        'weather',
                                        data_get($payload ?? [], 'weather', 'cerah')
                                    );

                                    $selectedVisitDay = old(
                                        'visit_day',
                                        data_get($payload ?? [], 'visit_day', 'weekday')
                                    );

                                    $isHighSeason = old(
                                        'is_high_season',
                                        data_get($payload ?? [], 'is_high_season', false)
                                    );

                                    $useBmkg = old(
                                        'use_bmkg',
                                        data_get($payload ?? [], 'use_bmkg', false)
                                    );
                                @endphp

                                {{-- Category --}}
                                <div>
                                    <label class="mb-2 block text-sm font-black text-slate-800">
                                        Kategori Preferensi
                                    </label>

                                    <div class="grid grid-cols-2 gap-2">
                                        @foreach (['Alam' => '🌿', 'Budaya' => '🏛️', 'Rekreasi' => '🎡', 'Umum' => '✨'] as $kategori => $icon)
                                            <label class="group flex cursor-pointer items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-3 text-sm font-semibold transition hover:border-blue-300 hover:bg-blue-50">
                                                <input
                                                    type="checkbox"
                                                    name="kategori_preferensi[]"
                                                    value="{{ $kategori }}"
                                                    @checked(in_array($kategori, (array) $selectedKategori))
                                                    class="h-4 w-4 rounded border-slate-300 text-blue-600"
                                                >

                                                <span>{{ $icon }}</span>
                                                <span>{{ $kategori }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Location --}}
                                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="mb-3 flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-black text-slate-900">Lokasi Wisata</p>
                                            <p class="text-xs text-slate-500">Digunakan untuk filter dataset dan ADM4 BMKG.</p>
                                        </div>

                                        <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-600 ring-1 ring-slate-200">
                                            Bali
                                        </span>
                                    </div>

                                    <div class="space-y-3">
                                        <div>
                                            <label for="kabupaten_kota" class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">
                                                Kabupaten/Kota
                                            </label>

                                            <select
                                                id="kabupaten_kota"
                                                name="kabupaten_kota"
                                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900"
                                            >
                                                @foreach ([
                                                    '',
                                                    'Kabupaten Gianyar',
                                                    'Kabupaten Badung',
                                                    'Kabupaten Tabanan',
                                                    'Kabupaten Buleleng',
                                                    'Kabupaten Karangasem',
                                                    'Kabupaten Bangli',
                                                    'Kabupaten Klungkung',
                                                    'Kabupaten Jembrana',
                                                    'Kota Denpasar',
                                                ] as $kabupaten)
                                                    <option value="{{ $kabupaten }}" @selected($selectedKabupaten === $kabupaten)>
                                                        {{ $kabupaten ?: 'Semua Kabupaten/Kota' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label for="kecamatan" class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">
                                                Kecamatan
                                            </label>

                                            <input
                                                id="kecamatan"
                                                type="text"
                                                name="kecamatan"
                                                value="{{ old('kecamatan', data_get($payload ?? [], 'kecamatan')) }}"
                                                placeholder="Contoh: Ubud"
                                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 placeholder:text-slate-400"
                                            >

                                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                                Contoh aman untuk BMKG otomatis: <strong>Ubud</strong>, <strong>Kuta</strong>, <strong>Kintamani</strong>, <strong>Denpasar Selatan</strong>.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Keywords --}}
                                <div>
                                    <label for="keywords" class="mb-1 block text-sm font-black text-slate-800">
                                        Keywords
                                    </label>

                                    <input
                                        id="keywords"
                                        type="text"
                                        name="keywords"
                                        value="{{ old('keywords', isset($payload['keywords']) ? implode(', ', $payload['keywords']) : '') }}"
                                        placeholder="Contoh: pantai, sunset"
                                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 placeholder:text-slate-400"
                                    >

                                    <p class="mt-2 text-xs text-slate-500">
                                        Pisahkan dengan koma. Kosongkan saat test awal agar kandidat lebih banyak.
                                    </p>
                                </div>

                                {{-- Rating and Top N --}}
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label for="min_rating" class="mb-1 block text-sm font-black text-slate-800">
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
                                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900"
                                        >
                                    </div>

                                    <div>
                                        <label for="top_n" class="mb-1 block text-sm font-black text-slate-800">
                                            Top N
                                        </label>

                                        <input
                                            id="top_n"
                                            type="number"
                                            min="1"
                                            max="50"
                                            name="top_n"
                                            value="{{ old('top_n', data_get($payload ?? [], 'top_n', 10)) }}"
                                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900"
                                        >
                                    </div>
                                </div>

                                {{-- Context --}}
                                <div class="rounded-3xl border border-blue-100 bg-blue-50/70 p-4">
                                    <div class="mb-3 flex items-center gap-2">
                                        <span class="flex h-9 w-9 items-center justify-center rounded-2xl bg-blue-100">
                                            🌦️
                                        </span>

                                        <div>
                                            <p class="text-sm font-black text-slate-900">Konteks CARS</p>
                                            <p class="text-xs text-slate-500">Cuaca, hari kunjungan, dan high season.</p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label for="weather" class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">
                                                Cuaca Manual
                                            </label>

                                            <select
                                                id="weather"
                                                name="weather"
                                                class="w-full rounded-2xl border border-blue-100 bg-white px-4 py-3 text-sm font-semibold text-slate-900"
                                            >
                                                @foreach (['cerah', 'hujan', 'mendung', 'berawan', 'unknown'] as $weather)
                                                    <option value="{{ $weather }}" @selected($selectedWeather === $weather)>
                                                        {{ ucfirst($weather) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label for="visit_day" class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">
                                                Hari Kunjungan
                                            </label>

                                            <select
                                                id="visit_day"
                                                name="visit_day"
                                                class="w-full rounded-2xl border border-blue-100 bg-white px-4 py-3 text-sm font-semibold text-slate-900"
                                            >
                                                @foreach (['weekday', 'weekend'] as $day)
                                                    <option value="{{ $day }}" @selected($selectedVisitDay === $day)>
                                                        {{ ucfirst($day) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mt-4 space-y-3">
                                        <label class="flex cursor-pointer items-center justify-between rounded-2xl bg-white px-4 py-3 ring-1 ring-blue-100">
                                            <span>
                                                <span class="block text-sm font-bold text-slate-900">High Season</span>
                                                <span class="text-xs text-slate-500">Simulasi kondisi ramai wisatawan.</span>
                                            </span>

                                            <span class="flex items-center gap-2">
                                                <input type="hidden" name="is_high_season" value="0">
                                                <input
                                                    type="checkbox"
                                                    name="is_high_season"
                                                    value="1"
                                                    @checked((bool) $isHighSeason)
                                                    class="h-5 w-5 rounded border-slate-300"
                                                >
                                            </span>
                                        </label>

<label class="flex cursor-pointer items-center justify-between rounded-2xl bg-white px-4 py-3 ring-1 ring-blue-100">
    <span>
        <span class="block text-sm font-bold text-slate-900">Gunakan BMKG</span>
        <span class="text-xs text-slate-500">
            Mengambil prakiraan cuaca BMKG berdasarkan wilayah yang dipilih.
        </span>
    </span>

    <span class="flex items-center gap-2">
        <input type="hidden" name="use_bmkg" value="0">
        <input
            type="checkbox"
            name="use_bmkg"
            value="1"
            @checked((bool) $useBmkg)
            class="h-5 w-5 rounded border-slate-300"
        >
    </span>
</label>

<div class="rounded-2xl border border-blue-200 bg-white p-4 text-xs leading-5 text-blue-800">
    <div class="flex items-start gap-3">
        <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-blue-100">
            ℹ️
        </div>

        <div>
            <p class="font-black text-blue-900">
                Catatan penggunaan cuaca BMKG
            </p>

            <p class="mt-1">
                Jika opsi <strong>Gunakan BMKG</strong> aktif, sistem mengambil
                <strong>prakiraan cuaca BMKG</strong> berdasarkan wilayah
                kabupaten/kota dan kecamatan yang dipilih. Data BMKG merupakan
                prakiraan cuaca beberapa hari ke depan, sehingga hasil rekomendasi
                akan menyesuaikan kondisi cuaca dari data prakiraan tersebut.
            </p>

            <p class="mt-2">
                User tidak perlu mengisi kode ADM4. Sistem akan menentukan ADM4
                secara otomatis dari lokasi yang dipilih.
            </p>
        </div>
    </div>
</div>
                                    </div>
                                </div>

                                <input
                                    type="hidden"
                                    name="bmkg_adm4"
                                    value="{{ old('bmkg_adm4', data_get($payload ?? [], 'bmkg_adm4')) }}"
                                >

                                <button
                                    type="submit"
                                    class="group relative w-full overflow-hidden rounded-2xl bg-slate-950 px-5 py-4 text-sm font-black text-white shadow-lg shadow-slate-900/20 transition hover:-translate-y-0.5 hover:bg-slate-800"
                                >
                                    <span class="relative z-10">Cari Rekomendasi</span>
                                    <span class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent transition duration-700 group-hover:translate-x-full"></span>
                                </button>
                            </form>
                        </div>
                    </div>
                </aside>

                {{-- Right Content --}}
                <section class="space-y-6 lg:col-span-8 xl:col-span-8">
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

                        {{-- Result Summary --}}
                        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white premium-shadow">
                            <div class="border-b border-slate-100 bg-gradient-to-br from-white via-slate-50 to-blue-50 p-6">
                                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <p class="text-xs font-black uppercase tracking-wider text-blue-600">
                                            Hasil Rekomendasi
                                        </p>

                                        <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950">
                                            Ranking Berdasarkan Final Score Tertinggi
                                        </h2>

                                        <p class="mt-2 text-sm text-slate-600">
                                            Destinasi dengan final score paling tinggi ditampilkan sebagai
                                            <span class="font-bold text-slate-900">paling direkomendasikan</span>.
                                        </p>

                                        <div class="mt-3 flex flex-wrap gap-2 text-xs font-bold">
                                            <span class="rounded-full bg-slate-950 px-3 py-1 text-white">
                                                Cuaca: {{ data_get($result, 'weather_used') ?? '-' }}
                                            </span>

                                            <span class="rounded-full bg-blue-100 px-3 py-1 text-blue-700">
                                                Source: {{ data_get($result, 'weather_source') ?? '-' }}
                                            </span>

                                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-700">
                                                Candidates: {{ data_get($result, 'total_candidates') ?? '-' }}
                                            </span>

                                            <span class="rounded-full bg-amber-100 px-3 py-1 text-amber-700">
                                                Response: {{ $responseTimeMs ?? '-' }} ms
                                            </span>
                                        </div>
                                    </div>

                                    <span class="inline-flex rounded-2xl bg-blue-600 px-4 py-2 text-sm font-black text-white shadow-sm shadow-blue-600/30">
                                        Top {{ $recommendations->count() }}
                                    </span>
                                </div>
                            </div>

                            <div class="p-6">
                                @if ($bestRecommendation)
                                    {{-- Best Recommendation Highlight --}}
                                    <div class="mb-6 overflow-hidden rounded-3xl border border-amber-200 bg-gradient-to-br from-amber-50 via-white to-blue-50 shadow-lg shadow-amber-900/5">
                                        <div class="grid grid-cols-1 lg:grid-cols-12">
                                            <div class="relative lg:col-span-5">
                                                @if (data_get($bestRecommendation, 'link_gambar'))
                                                    <img
                                                        src="{{ data_get($bestRecommendation, 'link_gambar') }}"
                                                        alt="{{ data_get($bestRecommendation, 'nama_tempat_wisata') }}"
                                                        class="h-80 w-full object-cover lg:h-full"
                                                    >
                                                @else
                                                    <div class="flex h-80 w-full items-center justify-center bg-gradient-to-br from-amber-100 to-blue-100 text-sm font-bold text-slate-500 lg:h-full">
                                                        No Image
                                                    </div>
                                                @endif

                                                <div class="absolute left-4 top-4 rounded-2xl bg-amber-400 px-4 py-2 text-sm font-black text-slate-950 shadow-lg shadow-amber-900/20">
                                                    🏆 Paling Direkomendasikan
                                                </div>

                                                <div class="absolute bottom-4 left-4 right-4 rounded-3xl bg-white/90 p-4 backdrop-blur-xl">
                                                    <p class="text-xs font-black uppercase tracking-wider text-slate-500">
                                                        Final Score Tertinggi
                                                    </p>

                                                    <p class="mt-1 text-4xl font-black tracking-tight text-slate-950">
                                                        {{ data_get($bestRecommendation, 'final_score') }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="lg:col-span-7 p-6 md:p-8">
                                                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                                    <div>
                                                        <div class="flex flex-wrap gap-2">
                                                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-700">
                                                                Rank #1
                                                            </span>

                                                            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                                                                {{ data_get($bestRecommendation, 'kategori') ?? '-' }}
                                                            </span>

                                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                                                                {{ data_get($bestRecommendation, 'tipe_wisata') ?? '-' }}
                                                            </span>
                                                        </div>

                                                        <h3 class="mt-4 text-3xl font-black tracking-tight text-slate-950">
                                                            {{ data_get($bestRecommendation, 'nama_tempat_wisata') }}
                                                        </h3>

                                                        <p class="mt-2 text-sm font-semibold text-slate-600">
                                                            {{ data_get($bestRecommendation, 'kecamatan') }}
                                                            -
                                                            {{ data_get($bestRecommendation, 'kabupaten_kota') }}
                                                        </p>
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
                                                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Rating</p>
                                                        <p class="mt-1 text-xl font-black text-slate-950">{{ data_get($bestRecommendation, 'rating') }}</p>
                                                    </div>

                                                    <div class="rounded-2xl bg-white p-4 ring-1 ring-slate-200">
                                                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Jumlah Rating</p>
                                                        <p class="mt-1 text-xl font-black text-slate-950">
                                                            {{ number_format((int) data_get($bestRecommendation, 'jumlah_rating', 0)) }}
                                                        </p>
                                                    </div>

                                                    <div class="rounded-2xl bg-white p-4 ring-1 ring-slate-200">
                                                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">CBF</p>
                                                        <p class="mt-1 text-xl font-black text-slate-950">{{ data_get($bestRecommendation, 'cbf_score') }}</p>
                                                    </div>

                                                    <div class="rounded-2xl bg-white p-4 ring-1 ring-slate-200">
                                                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Context</p>
                                                        <p class="mt-1 text-xl font-black text-slate-950">{{ data_get($bestRecommendation, 'context_multiplier') }}</p>
                                                    </div>
                                                </div>

                                                <div class="mt-6 rounded-2xl border border-amber-200 bg-white/80 p-4">
                                                    <p class="text-xs font-black uppercase tracking-wider text-amber-700">
                                                        Kenapa ini paling direkomendasikan?
                                                    </p>

                                                    <p class="mt-2 text-sm leading-6 text-slate-700">
                                                        Destinasi ini memiliki <strong>final score tertinggi</strong> dibanding kandidat lainnya.
                                                        Final score dihitung dari kecocokan CBF, kualitas rating, jumlah ulasan,
                                                        dan penyesuaian konteks CARS.
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

                                    {{-- Sorted Ranking List --}}
                                    <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                                        <div>
                                            <p class="text-xs font-black uppercase tracking-wider text-slate-500">
                                                Daftar Ranking
                                            </p>
                                            <h3 class="text-xl font-black text-slate-950">
                                                Semua Kandidat Diurutkan dari Final Score Tertinggi
                                            </h3>
                                        </div>

                                        <p class="text-sm text-slate-500">
                                            Ranking #1 adalah destinasi paling direkomendasikan.
                                        </p>
                                    </div>
                                @endif

                                <div class="space-y-5">
                                    @forelse ($recommendations as $index => $item)
                                        <article class="group overflow-hidden rounded-3xl border {{ $index === 0 ? 'border-amber-300 bg-amber-50/40' : 'border-slate-200 bg-white' }} transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-slate-900/10">
                                            <div class="grid grid-cols-1 md:grid-cols-12">
                                                <div class="relative md:col-span-4">
                                                    @if (data_get($item, 'link_gambar'))
                                                        <img
                                                            src="{{ data_get($item, 'link_gambar') }}"
                                                            alt="{{ data_get($item, 'nama_tempat_wisata') }}"
                                                            class="h-64 w-full object-cover md:h-full"
                                                        >
                                                    @else
                                                        <div class="flex h-64 w-full items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200 text-sm font-bold text-slate-400 md:h-full">
                                                            No Image
                                                        </div>
                                                    @endif

                                                    <div class="absolute left-4 top-4 rounded-2xl {{ $index === 0 ? 'bg-amber-400 text-slate-950' : 'bg-slate-950/90 text-white' }} px-3 py-2 text-sm font-black backdrop-blur">
                                                        #{{ $index + 1 }}
                                                    </div>

                                                    @if ($index === 0)
                                                        <div class="absolute right-4 top-4 rounded-2xl bg-white/90 px-3 py-2 text-xs font-black text-amber-700 backdrop-blur">
                                                            Tertinggi
                                                        </div>
                                                    @endif

                                                    <div class="absolute bottom-4 left-4 right-4 rounded-2xl bg-white/90 p-3 backdrop-blur">
                                                        <p class="text-xs font-bold text-slate-500">Final Score</p>
                                                        <p class="text-2xl font-black text-slate-950">
                                                            {{ data_get($item, 'final_score') }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="md:col-span-8 p-5 md:p-6">
                                                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                                        <div>
                                                            <div class="flex flex-wrap gap-2">
                                                                @if ($index === 0)
                                                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-700">
                                                                        🏆 Paling Direkomendasikan
                                                                    </span>
                                                                @endif

                                                                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                                                                    {{ data_get($item, 'kategori') ?? '-' }}
                                                                </span>

                                                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                                                                    {{ data_get($item, 'tipe_wisata') ?? '-' }}
                                                                </span>
                                                            </div>

                                                            <h3 class="mt-3 text-2xl font-black tracking-tight text-slate-950">
                                                                {{ data_get($item, 'nama_tempat_wisata') }}
                                                            </h3>

                                                            <p class="mt-2 text-sm font-medium text-slate-600">
                                                                {{ data_get($item, 'kecamatan') }}
                                                                -
                                                                {{ data_get($item, 'kabupaten_kota') }}
                                                            </p>
                                                        </div>

                                                        @if (data_get($item, 'link_google_maps'))
                                                            <a
                                                                href="{{ data_get($item, 'link_google_maps') }}"
                                                                target="_blank"
                                                                class="inline-flex shrink-0 items-center justify-center rounded-2xl bg-emerald-100 px-4 py-2 text-sm font-black text-emerald-700 transition hover:bg-emerald-200"
                                                            >
                                                                Maps
                                                            </a>
                                                        @endif
                                                    </div>

                                                    <div class="mt-5 grid grid-cols-2 gap-3 md:grid-cols-4">
                                                        <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Rating</p>
                                                            <p class="mt-1 text-lg font-black text-slate-950">{{ data_get($item, 'rating') }}</p>
                                                        </div>

                                                        <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Jumlah Rating</p>
                                                            <p class="mt-1 text-lg font-black text-slate-950">
                                                                {{ number_format((int) data_get($item, 'jumlah_rating', 0)) }}
                                                            </p>
                                                        </div>

                                                        <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">CBF</p>
                                                            <p class="mt-1 text-lg font-black text-slate-950">{{ data_get($item, 'cbf_score') }}</p>
                                                        </div>

                                                        <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Context</p>
                                                            <p class="mt-1 text-lg font-black text-slate-950">{{ data_get($item, 'context_multiplier') }}</p>
                                                        </div>
                                                    </div>

                                                    <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                                        <p class="text-xs font-black uppercase tracking-wider text-slate-500">
                                                            Alasan Rekomendasi
                                                        </p>

                                                        <p class="mt-2 text-sm leading-6 text-slate-700">
                                                            {{ data_get($item, 'alasan') }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </article>
                                    @empty
                                        <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center">
                                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white text-3xl shadow-sm">
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
                        <details class="overflow-hidden rounded-3xl border border-slate-200 bg-white premium-shadow">
                            <summary class="cursor-pointer border-b border-slate-100 bg-slate-50 px-6 py-4 text-sm font-black text-slate-950">
                                Lihat JSON Request & Response
                            </summary>

                            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2">
                                <div>
                                    <p class="mb-2 text-sm font-black text-slate-900">Request ke FastAPI</p>

                                    <pre class="max-h-[480px] overflow-auto rounded-2xl bg-slate-950 p-4 text-xs leading-5 text-slate-100">{{ json_encode($payload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>

                                <div>
                                    <p class="mb-2 text-sm font-black text-slate-900">Response FastAPI</p>

                                    <pre class="max-h-[480px] overflow-auto rounded-2xl bg-slate-950 p-4 text-xs leading-5 text-slate-100">{{ json_encode($result ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>
                            </div>
                        </details>
                    @else
                        {{-- Empty State --}}
                        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white premium-shadow">
                            <div class="grid grid-cols-1 md:grid-cols-2">
                                <div class="p-8 md:p-10">
                                    <div class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-blue-700">
                                        Belum Ada Hasil
                                    </div>

                                    <h2 class="mt-4 text-3xl font-black tracking-tight text-slate-950">
                                        Mulai rekomendasi pertamamu.
                                    </h2>

                                    <p class="mt-3 text-sm leading-6 text-slate-600">
                                        Isi parameter di sebelah kiri untuk mendapatkan rekomendasi wisata Bali.
                                        Hasilnya akan langsung disimpan ke riwayat user kamu.
                                    </p>

                                    <div class="mt-6 grid grid-cols-1 gap-3">
                                        <div class="rounded-2xl bg-slate-50 p-4">
                                            <p class="font-black text-slate-900">1. Pilih kategori</p>
                                            <p class="mt-1 text-sm text-slate-500">Contoh: Alam dan Budaya.</p>
                                        </div>

                                        <div class="rounded-2xl bg-slate-50 p-4">
                                            <p class="font-black text-slate-900">2. Isi lokasi</p>
                                            <p class="mt-1 text-sm text-slate-500">Contoh: Kabupaten Gianyar, Kecamatan Ubud.</p>
                                        </div>

                                        <div class="rounded-2xl bg-slate-50 p-4">
                                            <p class="font-black text-slate-900">3. Klik cari</p>
                                            <p class="mt-1 text-sm text-slate-500">Sistem akan menghitung CBF + CARS.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="relative min-h-[360px] bg-gradient-to-br from-slate-950 via-blue-950 to-slate-900 p-8 text-white">
                                    <div class="absolute inset-0 opacity-20 soft-grid"></div>
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
                    @endisset

                    {{-- Latest Logs --}}
                    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white premium-shadow">
                        <div class="border-b border-slate-100 bg-gradient-to-br from-white to-slate-50 p-6">
                            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wider text-slate-500">
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
                                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
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
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-black {{ $log->status === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
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
                                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl">
                                                    📄
                                                </div>

                                                <h3 class="mt-4 font-black text-slate-950">
                                                    Belum ada log rekomendasi
                                                </h3>

                                                <p class="mt-1 text-sm text-slate-500">
                                                    Setelah submit form, log akan tampil di sini.
                                                </p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
