<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TourHub Bali - Rekomendasi Wisata</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-100 text-slate-900">
    <div class="min-h-screen">
<header class="bg-white border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-6 py-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">TourHub Bali</h1>

            <p class="text-sm text-slate-600">
                Simulasi Web Rekomendasi Wisata CBF + CARS
            </p>

            <p class="text-xs text-slate-500 mt-1">
                ML API: {{ $defaultBaseUrl ?? '-' }}
            </p>

            @auth
                <p class="text-xs text-slate-500 mt-1">
                    Login sebagai:
                    <span class="font-semibold text-slate-700">
                        {{ auth()->user()->name }}
                    </span>
                </p>
            @endauth
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a
                href="{{ route('user.dashboard') }}"
                class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700"
            >
                ← Kembali ke Dashboard
            </a>

            <a
                href="{{ route('user.dashboard') }}"
                class="inline-flex items-center rounded-lg bg-blue-100 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-200"
            >
                Riwayat Rekomendasi Saya
            </a>

            @auth
                <form method="POST" action="{{ route('user.logout') }}">
                    @csrf

                    <button
                        type="submit"
                        class="inline-flex items-center rounded-lg bg-red-100 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-200"
                    >
                        Logout
                    </button>
                </form>
            @endauth
        </div>
    </div>
</header>

        <main class="max-w-7xl mx-auto px-6 py-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
            <section class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold mb-1">
                        Parameter Rekomendasi
                    </h2>

                    <p class="text-sm text-slate-500 mb-5">
                        Isi preferensi wisata, lalu Laravel akan memanggil FastAPI Machine Learning.
                    </p>

                    @if ($errors->any())
                        <div class="mb-5 rounded-lg bg-red-100 text-red-700 p-4 text-sm">
                            <p class="font-semibold mb-2">Terjadi error:</p>

                            <ul class="list-disc pl-5 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('tourhub.recommendation.store') }}" class="space-y-4">
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

                        <div>
                            <label class="block text-sm font-medium mb-2">
                                Kategori Preferensi
                            </label>

                            <div class="grid grid-cols-2 gap-2">
                                @foreach (['Alam', 'Budaya', 'Rekreasi', 'Umum'] as $kategori)
                                    <label class="flex items-center gap-2 border border-slate-200 rounded-lg px-3 py-2 text-sm cursor-pointer hover:bg-slate-50">
                                        <input
                                            type="checkbox"
                                            name="kategori_preferensi[]"
                                            value="{{ $kategori }}"
                                            @checked(in_array($kategori, (array) $selectedKategori))
                                            class="rounded border-slate-300"
                                        >

                                        <span>{{ $kategori }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label for="kabupaten_kota" class="block text-sm font-medium mb-1">
                                Kabupaten/Kota
                            </label>

                            <select
                                id="kabupaten_kota"
                                name="kabupaten_kota"
                                class="w-full rounded-lg border-slate-300"
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
                            <label for="kecamatan" class="block text-sm font-medium mb-1">
                                Kecamatan
                            </label>

                            <input
                                id="kecamatan"
                                type="text"
                                name="kecamatan"
                                value="{{ old('kecamatan', data_get($payload ?? [], 'kecamatan')) }}"
                                placeholder="Contoh: Ubud"
                                class="w-full rounded-lg border-slate-300"
                            >

                            <p class="text-xs text-slate-500 mt-1">
                                Untuk BMKG otomatis saat ini gunakan contoh: <strong>Ubud</strong>.
                            </p>
                        </div>

                        <div>
                            <label for="keywords" class="block text-sm font-medium mb-1">
                                Keywords
                            </label>

                            <input
                                id="keywords"
                                type="text"
                                name="keywords"
                                value="{{ old('keywords', isset($payload['keywords']) ? implode(', ', $payload['keywords']) : '') }}"
                                placeholder="Contoh: pantai, sunset"
                                class="w-full rounded-lg border-slate-300"
                            >

                            <p class="text-xs text-slate-500 mt-1">
                                Pisahkan dengan koma.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="min_rating" class="block text-sm font-medium mb-1">
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
                                    class="w-full rounded-lg border-slate-300"
                                >
                            </div>

                            <div>
                                <label for="top_n" class="block text-sm font-medium mb-1">
                                    Top N
                                </label>

                                <input
                                    id="top_n"
                                    type="number"
                                    min="1"
                                    max="50"
                                    name="top_n"
                                    value="{{ old('top_n', data_get($payload ?? [], 'top_n', 10)) }}"
                                    class="w-full rounded-lg border-slate-300"
                                >
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="weather" class="block text-sm font-medium mb-1">
                                    Cuaca Manual
                                </label>

                                <select
                                    id="weather"
                                    name="weather"
                                    class="w-full rounded-lg border-slate-300"
                                >
                                    @foreach (['cerah', 'hujan', 'mendung', 'berawan', 'unknown'] as $weather)
                                        <option value="{{ $weather }}" @selected($selectedWeather === $weather)>
                                            {{ ucfirst($weather) }}
                                        </option>
                                    @endforeach
                                </select>

                                <p class="text-xs text-slate-500 mt-1">
                                    Dipakai jika BMKG tidak aktif.
                                </p>
                            </div>

                            <div>
                                <label for="visit_day" class="block text-sm font-medium mb-1">
                                    Hari Kunjungan
                                </label>

                                <select
                                    id="visit_day"
                                    name="visit_day"
                                    class="w-full rounded-lg border-slate-300"
                                >
                                    @foreach (['weekday', 'weekend'] as $day)
                                        <option value="{{ $day }}" @selected($selectedVisitDay === $day)>
                                            {{ ucfirst($day) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="hidden" name="is_high_season" value="0">

                                <input
                                    type="checkbox"
                                    name="is_high_season"
                                    value="1"
                                    @checked((bool) $isHighSeason)
                                    class="rounded border-slate-300"
                                >

                                <span>High Season</span>
                            </label>

                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="hidden" name="use_bmkg" value="0">

                                <input
                                    type="checkbox"
                                    name="use_bmkg"
                                    value="1"
                                    @checked((bool) $useBmkg)
                                    class="rounded border-slate-300"
                                >

                                <span>Gunakan BMKG</span>
                            </label>

                            <div class="rounded-lg bg-blue-50 border border-blue-200 p-3 text-xs text-blue-700">
                                Jika BMKG aktif, kode ADM4 akan diisi otomatis oleh sistem berdasarkan
                                <strong>Kabupaten/Kota</strong> dan <strong>Kecamatan</strong>.
                                User tidak perlu mengisi ADM4.
                            </div>
                        </div>

                        {{-- ADM4 disembunyikan dari user.
                             Controller akan mengisi otomatis jika use_bmkg = true. --}}
                        <input
                            type="hidden"
                            name="bmkg_adm4"
                            value="{{ old('bmkg_adm4', data_get($payload ?? [], 'bmkg_adm4')) }}"
                        >

                        <button
                            type="submit"
                            class="w-full rounded-lg bg-slate-900 text-white py-3 font-semibold hover:bg-slate-700"
                        >
                            Cari Rekomendasi
                        </button>
                    </form>
                </div>
            </section>

            <section class="lg:col-span-2 space-y-6">
                @isset($result)
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <div class="flex justify-between items-start mb-5 gap-4">
                            <div>
                                <h2 class="text-lg font-semibold">
                                    Hasil Rekomendasi
                                </h2>

                                <p class="text-sm text-slate-500 mt-1">
                                    Cuaca:
                                    <span class="font-semibold">{{ data_get($result, 'weather_used') ?? '-' }}</span>
                                    |
                                    Source:
                                    <span class="font-semibold">{{ data_get($result, 'weather_source') ?? '-' }}</span>
                                    |
                                    Candidates:
                                    <span class="font-semibold">{{ data_get($result, 'total_candidates') ?? '-' }}</span>
                                    |
                                    Response:
                                    <span class="font-semibold">{{ $responseTimeMs ?? '-' }} ms</span>
                                </p>
                            </div>

                            <div class="shrink-0">
                                <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                    Top {{ count(data_get($result, 'recommendations', [])) }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-4">
                            @forelse (data_get($result, 'recommendations', []) as $index => $item)
                                <article class="border border-slate-200 rounded-xl bg-white overflow-hidden">
                                    <div class="grid grid-cols-1 md:grid-cols-4">
                                        <div class="md:col-span-1 bg-slate-100">
                                            @if (data_get($item, 'link_gambar'))
                                                <img
                                                    src="{{ data_get($item, 'link_gambar') }}"
                                                    alt="{{ data_get($item, 'nama_tempat_wisata') }}"
                                                    class="w-full h-48 md:h-full object-cover"
                                                >
                                            @else
                                                <div class="h-48 md:h-full flex items-center justify-center text-slate-400 text-sm">
                                                    No Image
                                                </div>
                                            @endif
                                        </div>

                                        <div class="md:col-span-3 p-5">
                                            <div class="flex justify-between gap-4">
                                                <div>
                                                    <p class="text-xs text-slate-500">
                                                        #{{ $index + 1 }}
                                                        |
                                                        {{ data_get($item, 'kategori') }}
                                                        |
                                                        {{ data_get($item, 'tipe_wisata') }}
                                                    </p>

                                                    <h3 class="text-xl font-bold mt-1">
                                                        {{ data_get($item, 'nama_tempat_wisata') }}
                                                    </h3>

                                                    <p class="text-sm text-slate-600 mt-1">
                                                        {{ data_get($item, 'kecamatan') }}
                                                        -
                                                        {{ data_get($item, 'kabupaten_kota') }}
                                                    </p>
                                                </div>

                                                <div class="text-right">
                                                    <p class="text-xs text-slate-500">
                                                        Final Score
                                                    </p>

                                                    <p class="text-xl font-bold">
                                                        {{ data_get($item, 'final_score') }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-4 text-sm">
                                                <div class="bg-slate-100 rounded-lg p-3">
                                                    <p class="text-xs text-slate-500">Rating</p>
                                                    <p class="font-semibold">{{ data_get($item, 'rating') }}</p>
                                                </div>

                                                <div class="bg-slate-100 rounded-lg p-3">
                                                    <p class="text-xs text-slate-500">Jumlah Rating</p>
                                                    <p class="font-semibold">
                                                        {{ number_format((int) data_get($item, 'jumlah_rating', 0)) }}
                                                    </p>
                                                </div>

                                                <div class="bg-slate-100 rounded-lg p-3">
                                                    <p class="text-xs text-slate-500">CBF</p>
                                                    <p class="font-semibold">{{ data_get($item, 'cbf_score') }}</p>
                                                </div>

                                                <div class="bg-slate-100 rounded-lg p-3">
                                                    <p class="text-xs text-slate-500">Context</p>
                                                    <p class="font-semibold">{{ data_get($item, 'context_multiplier') }}</p>
                                                </div>
                                            </div>

                                            <p class="text-sm mt-4 text-slate-700">
                                                {{ data_get($item, 'alasan') }}
                                            </p>

                                            @if (data_get($item, 'link_google_maps'))
                                                <a
                                                    href="{{ data_get($item, 'link_google_maps') }}"
                                                    target="_blank"
                                                    class="inline-flex mt-4 rounded-lg bg-emerald-100 text-emerald-700 px-4 py-2 text-sm font-semibold hover:bg-emerald-200"
                                                >
                                                    Buka Google Maps
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-slate-500">
                                    Tidak ada rekomendasi.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <details class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <summary class="font-semibold cursor-pointer">
                            Lihat JSON Request & Response
                        </summary>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div>
                                <p class="font-semibold text-sm mb-2">Request ke FastAPI</p>

                                <pre class="bg-slate-950 text-slate-100 text-xs p-4 rounded-lg overflow-auto">{{ json_encode($payload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>

                            <div>
                                <p class="font-semibold text-sm mb-2">Response FastAPI</p>

                                <pre class="bg-slate-950 text-slate-100 text-xs p-4 rounded-lg overflow-auto">{{ json_encode($result ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                        </div>
                    </details>
                @else
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-10 text-center">
                        <h2 class="text-xl font-bold mb-2">
                            Belum ada hasil rekomendasi
                        </h2>

                        <p class="text-slate-500">
                            Isi form di sebelah kiri untuk mengetes rekomendasi TourHub Bali.
                        </p>
                    </div>
                @endisset

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold mb-4">
                        Log Rekomendasi Terbaru
                    </h2>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-slate-500">
                                    <th class="py-2 pr-4">Waktu</th>
                                    <th class="py-2 pr-4">Status</th>
                                    <th class="py-2 pr-4">Cuaca</th>
                                    <th class="py-2 pr-4">Candidates</th>
                                    <th class="py-2 pr-4">Top Destination</th>
                                    <th class="py-2 pr-4">Response</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($latestLogs as $log)
                                    <tr class="border-b last:border-b-0">
                                        <td class="py-2 pr-4">
                                            {{ $log->created_at?->format('d M Y H:i') }}
                                        </td>

                                        <td class="py-2 pr-4">
                                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $log->status === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                                {{ $log->status }}
                                            </span>
                                        </td>

                                        <td class="py-2 pr-4">
                                            {{ $log->weather_used ?? '-' }}
                                        </td>

                                        <td class="py-2 pr-4">
                                            {{ $log->total_candidates ?? '-' }}
                                        </td>

                                        <td class="py-2 pr-4">
                                            {{ $log->top_destination_name ?? '-' }}
                                        </td>

                                        <td class="py-2 pr-4">
                                            {{ $log->response_time_ms ? $log->response_time_ms . ' ms' : '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-5 text-center text-slate-500">
                                            Belum ada log.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>