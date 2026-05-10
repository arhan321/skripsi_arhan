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
        <div class="bg-white border-b">
            <div class="max-w-7xl mx-auto px-6 py-5">
                <h1 class="text-2xl font-bold">TourHub Bali</h1>
                <p class="text-sm text-slate-600">
                    Simulasi Web Rekomendasi Wisata CBF + CARS
                </p>
                <p class="text-xs text-slate-500 mt-1">
                    ML API: {{ $defaultBaseUrl ?? '-' }}
                </p>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-6 py-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow p-6">
                    <h2 class="text-lg font-semibold mb-4">Parameter Rekomendasi</h2>

                    @if ($errors->any())
                        <div class="mb-4 rounded-lg bg-red-100 text-red-700 p-3 text-sm">
                            <ul class="list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('tourhub.recommendation.store') }}" class="space-y-4">
                        @csrf

                        @php
                            $selectedKategori = old('kategori_preferensi', data_get($payload ?? [], 'kategori_preferensi', ['Alam']));
                        @endphp

                        <div>
                            <label class="block text-sm font-medium mb-2">Kategori Preferensi</label>

                            <div class="grid grid-cols-2 gap-2">
                                @foreach (['Alam', 'Budaya', 'Rekreasi', 'Umum'] as $kategori)
                                    <label class="flex items-center gap-2 border rounded-lg px-3 py-2 text-sm">
                                        <input
                                            type="checkbox"
                                            name="kategori_preferensi[]"
                                            value="{{ $kategori }}"
                                            @checked(in_array($kategori, (array) $selectedKategori))
                                        >
                                        {{ $kategori }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Kabupaten/Kota</label>

                            @php
                                $selectedKabupaten = old('kabupaten_kota', data_get($payload ?? [], 'kabupaten_kota', 'Kabupaten Gianyar'));
                            @endphp

                            <select name="kabupaten_kota" class="w-full rounded-lg border-slate-300">
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
                            <label class="block text-sm font-medium mb-1">Kecamatan</label>
                            <input
                                type="text"
                                name="kecamatan"
                                value="{{ old('kecamatan', data_get($payload ?? [], 'kecamatan')) }}"
                                placeholder="Opsional, contoh: Ubud"
                                class="w-full rounded-lg border-slate-300"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Keywords</label>
                            <input
                                type="text"
                                name="keywords"
                                value="{{ old('keywords', isset($payload['keywords']) ? implode(', ', $payload['keywords']) : '') }}"
                                placeholder="Contoh: pantai, sunset"
                                class="w-full rounded-lg border-slate-300"
                            >
                            <p class="text-xs text-slate-500 mt-1">Pisahkan dengan koma.</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium mb-1">Min Rating</label>
                                <input
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
                                <label class="block text-sm font-medium mb-1">Top N</label>
                                <input
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
                                <label class="block text-sm font-medium mb-1">Cuaca</label>

                                @php
                                    $selectedWeather = old('weather', data_get($payload ?? [], 'weather', 'cerah'));
                                @endphp

                                <select name="weather" class="w-full rounded-lg border-slate-300">
                                    @foreach (['cerah', 'hujan', 'mendung', 'berawan', 'unknown'] as $weather)
                                        <option value="{{ $weather }}" @selected($selectedWeather === $weather)>
                                            {{ ucfirst($weather) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Hari Kunjungan</label>

                                @php
                                    $selectedVisitDay = old('visit_day', data_get($payload ?? [], 'visit_day', 'weekday'));
                                @endphp

                                <select name="visit_day" class="w-full rounded-lg border-slate-300">
                                    @foreach (['weekday', 'weekend'] as $day)
                                        <option value="{{ $day }}" @selected($selectedVisitDay === $day)>
                                            {{ ucfirst($day) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <label class="flex items-center gap-2 text-sm">
                            <input type="hidden" name="is_high_season" value="0">
                            <input
                                type="checkbox"
                                name="is_high_season"
                                value="1"
                                @checked(old('is_high_season', data_get($payload ?? [], 'is_high_season', false)))
                            >
                            High Season
                        </label>

                        <label class="flex items-center gap-2 text-sm">
                            <input type="hidden" name="use_bmkg" value="0">
                            <input
                                type="checkbox"
                                name="use_bmkg"
                                value="1"
                                @checked(old('use_bmkg', data_get($payload ?? [], 'use_bmkg', false)))
                            >
                            Gunakan BMKG
                        </label>

                        <div>
                            <label class="block text-sm font-medium mb-1">BMKG ADM4</label>
                            <input
                                type="text"
                                name="bmkg_adm4"
                                value="{{ old('bmkg_adm4', data_get($payload ?? [], 'bmkg_adm4')) }}"
                                placeholder="Opsional"
                                class="w-full rounded-lg border-slate-300"
                            >
                        </div>

                        <button
                            type="submit"
                            class="w-full rounded-lg bg-slate-900 text-white py-3 font-semibold hover:bg-slate-700"
                        >
                            Cari Rekomendasi
                        </button>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-6">
                @isset($result)
                    <div class="bg-white rounded-xl shadow p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h2 class="text-lg font-semibold">Hasil Rekomendasi</h2>
                                <p class="text-sm text-slate-500">
                                    Cuaca: {{ data_get($result, 'weather_used') ?? '-' }}
                                    |
                                    Source: {{ data_get($result, 'weather_source') ?? '-' }}
                                    |
                                    Candidates: {{ data_get($result, 'total_candidates') ?? '-' }}
                                    |
                                    Response: {{ $responseTimeMs ?? '-' }} ms
                                </p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            @forelse (data_get($result, 'recommendations', []) as $index => $item)
                                <div class="border rounded-xl p-4 bg-white">
                                    <div class="flex gap-4">
                                        @if (data_get($item, 'link_gambar'))
                                            <img
                                                src="{{ data_get($item, 'link_gambar') }}"
                                                alt="{{ data_get($item, 'nama_tempat_wisata') }}"
                                                class="w-32 h-24 object-cover rounded-lg"
                                            >
                                        @endif

                                        <div class="flex-1">
                                            <p class="text-xs text-slate-500">
                                                #{{ $index + 1 }}
                                                |
                                                {{ data_get($item, 'kategori') }}
                                                |
                                                {{ data_get($item, 'tipe_wisata') }}
                                            </p>

                                            <h3 class="text-lg font-bold">
                                                {{ data_get($item, 'nama_tempat_wisata') }}
                                            </h3>

                                            <p class="text-sm text-slate-600">
                                                {{ data_get($item, 'kecamatan') }}
                                                -
                                                {{ data_get($item, 'kabupaten_kota') }}
                                            </p>

                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-3 text-sm">
                                                <div class="bg-slate-100 rounded-lg p-2">
                                                    <p class="text-xs text-slate-500">Rating</p>
                                                    <p class="font-semibold">{{ data_get($item, 'rating') }}</p>
                                                </div>

                                                <div class="bg-slate-100 rounded-lg p-2">
                                                    <p class="text-xs text-slate-500">CBF</p>
                                                    <p class="font-semibold">{{ data_get($item, 'cbf_score') }}</p>
                                                </div>

                                                <div class="bg-slate-100 rounded-lg p-2">
                                                    <p class="text-xs text-slate-500">Context</p>
                                                    <p class="font-semibold">{{ data_get($item, 'context_multiplier') }}</p>
                                                </div>

                                                <div class="bg-slate-100 rounded-lg p-2">
                                                    <p class="text-xs text-slate-500">Final</p>
                                                    <p class="font-semibold">{{ data_get($item, 'final_score') }}</p>
                                                </div>
                                            </div>

                                            <p class="text-sm mt-3 text-slate-700">
                                                {{ data_get($item, 'alasan') }}
                                            </p>

                                            @if (data_get($item, 'link_google_maps'))
                                                <a
                                                    href="{{ data_get($item, 'link_google_maps') }}"
                                                    target="_blank"
                                                    class="inline-block mt-3 text-sm text-blue-600 hover:underline"
                                                >
                                                    Buka Google Maps
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-slate-500">Tidak ada rekomendasi.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow p-6">
                        <h2 class="text-lg font-semibold mb-3">JSON Debug</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="font-semibold text-sm mb-2">Request</p>
                                <pre class="bg-slate-950 text-slate-100 text-xs p-4 rounded-lg overflow-auto">{{ json_encode($payload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>

                            <div>
                                <p class="font-semibold text-sm mb-2">Response</p>
                                <pre class="bg-slate-950 text-slate-100 text-xs p-4 rounded-lg overflow-auto">{{ json_encode($result ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-white rounded-xl shadow p-10 text-center">
                        <h2 class="text-xl font-bold mb-2">Belum ada hasil rekomendasi</h2>
                        <p class="text-slate-500">
                            Isi form di sebelah kiri untuk mengetes rekomendasi TourHub Bali.
                        </p>
                    </div>
                @endisset

                <div class="bg-white rounded-xl shadow p-6">
                    <h2 class="text-lg font-semibold mb-4">Log Rekomendasi Terbaru</h2>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-slate-500">
                                    <th class="py-2">Waktu</th>
                                    <th class="py-2">Status</th>
                                    <th class="py-2">Cuaca</th>
                                    <th class="py-2">Candidates</th>
                                    <th class="py-2">Top Destination</th>
                                    <th class="py-2">Response</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($latestLogs as $log)
                                    <tr class="border-b">
                                        <td class="py-2">
                                            {{ $log->created_at?->format('d M Y H:i') }}
                                        </td>

                                        <td class="py-2">
                                            {{ $log->status }}
                                        </td>

                                        <td class="py-2">
                                            {{ $log->weather_used ?? '-' }}
                                        </td>

                                        <td class="py-2">
                                            {{ $log->total_candidates ?? '-' }}
                                        </td>

                                        <td class="py-2">
                                            {{ $log->top_destination_name ?? '-' }}
                                        </td>

                                        <td class="py-2">
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
            </div>
        </div>
    </div>
</body>
</html>