<x-layouts.tourhub-auth title="Dashboard User - TourHub Bali">
    @php
        $latestPayload = $latestSuccess?->request_payload ?? [];
        $latestResponse = $latestSuccess?->response_payload ?? [];

        $latestCategories = data_get($latestPayload, 'kategori_preferensi', []);
        $latestKabupaten = data_get($latestPayload, 'kabupaten_kota', '-');
        $latestKecamatan = data_get($latestPayload, 'kecamatan', '-');
        $latestWeather = $latestSuccess?->weather_used ?? '-';
        $latestWeatherSource = $latestSuccess?->weather_source ?? '-';
        $latestCandidates = $latestSuccess?->total_candidates ?? 0;
        $latestTopDestination = $latestSuccess?->top_destination_name ?? '-';

        $successRate =
            $totalRecommendations > 0 ? round(($successRecommendations / $totalRecommendations) * 100) : 0;
    @endphp

    <div class="space-y-6">
        {{-- Hero Section --}}
        <section
            class="relative overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800 p-6 shadow-sm md:p-8"
        >
            <div class="absolute top-0 right-0 h-40 w-40 rounded-full bg-blue-500/20 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 h-32 w-32 rounded-full bg-emerald-500/20 blur-3xl"></div>

            <div class="relative flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                <div class="max-w-2xl">
                    <div
                        class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-slate-200 ring-1 ring-white/10"
                    >
                        <span>👋</span>
                        <span>Panel User TourHub Bali</span>
                    </div>

                    <h1 class="mt-4 text-3xl font-bold tracking-tight text-white md:text-4xl">
                        Halo, {{ auth()->user()->name }}
                    </h1>

                    <p class="mt-3 text-sm leading-6 text-slate-300 md:text-base">
                        Di dashboard ini kamu bisa melihat ringkasan pencarian wisata, riwayat rekomendasi, serta detail
                        hasil rekomendasi yang sudah disimpan berdasarkan akun kamu.
                    </p>

                    <div class="mt-5 flex flex-wrap gap-2 text-xs text-slate-300">
                        <span class="rounded-full bg-white/10 px-3 py-1 ring-1 ring-white/10">CBF + CARS</span>
                        <span class="rounded-full bg-white/10 px-3 py-1 ring-1 ring-white/10">BMKG Context</span>
                        <span class="rounded-full bg-white/10 px-3 py-1 ring-1 ring-white/10">Riwayat Per User</span>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row md:flex-col">
                    <a
                        href="{{ route('tourhub.recommendation.index') }}"
                        class="inline-flex items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-bold text-slate-950 shadow-sm hover:bg-slate-100"
                    >
                        Cari Rekomendasi Baru
                    </a>

                    <a
                        href="#riwayat"
                        class="inline-flex items-center justify-center rounded-2xl bg-white/10 px-5 py-3 text-sm font-bold text-white ring-1 ring-white/15 hover:bg-white/15"
                    >
                        Lihat Riwayat Saya
                    </a>
                </div>
            </div>
        </section>

        {{-- Flash Message --}}
        @if (session('success'))
            <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
                {{ session('success') }}
            </section>
        @endif

        {{-- Statistic Cards --}}
        <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Total Request</p>
                        <p class="mt-2 text-4xl font-bold text-slate-950">{{ $totalRecommendations }}</p>
                    </div>

                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-2xl">📌</div>
                </div>

                <p class="mt-4 text-xs text-slate-500">Total semua percobaan rekomendasi yang pernah kamu lakukan.</p>
            </div>

            <div class="rounded-3xl border border-emerald-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Berhasil</p>
                        <p class="mt-2 text-4xl font-bold text-emerald-600">{{ $successRecommendations }}</p>
                    </div>

                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-2xl">✅</div>
                </div>

                <p class="mt-4 text-xs text-slate-500">Request yang berhasil mendapatkan response dari FastAPI ML.</p>
            </div>

            <div class="rounded-3xl border border-red-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Gagal</p>
                        <p class="mt-2 text-4xl font-bold text-red-600">{{ $failedRecommendations }}</p>
                    </div>

                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-red-100 text-2xl">⚠️</div>
                </div>

                <p class="mt-4 text-xs text-slate-500">
                    Request yang gagal, biasanya karena API, parameter, atau koneksi.
                </p>
            </div>

            <div class="rounded-3xl border border-blue-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Success Rate</p>
                        <p class="mt-2 text-4xl font-bold text-blue-600">{{ $successRate }}%</p>
                    </div>

                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 text-2xl">📈</div>
                </div>

                <p class="mt-4 text-xs text-slate-500">Persentase request berhasil dari total pencarian rekomendasi.</p>
            </div>
        </section>

        {{-- Latest Recommendation Summary --}}
        <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-2">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Rekomendasi Terakhir</p>

                        <h2 class="mt-2 text-2xl font-bold text-slate-950">
                            {{ $latestTopDestination }}
                        </h2>

                        <p class="mt-2 text-sm text-slate-600">
                            Destinasi teratas dari pencarian rekomendasi terakhir kamu.
                        </p>
                    </div>

                    @if ($latestSuccess)
                        <a
                            href="{{ route('user.recommendation-history.show', $latestSuccess) }}"
                            class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700"
                        >
                            Lihat Detail
                        </a>
                    @endif
                </div>

                <div class="mt-6 grid grid-cols-1 gap-3 md:grid-cols-4">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-medium text-slate-500">Kabupaten/Kota</p>
                        <p class="mt-1 font-bold text-slate-900">{{ $latestKabupaten ?: '-' }}</p>
                    </div>

                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-medium text-slate-500">Kecamatan</p>
                        <p class="mt-1 font-bold text-slate-900">{{ $latestKecamatan ?: '-' }}</p>
                    </div>

                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-medium text-slate-500">Cuaca</p>
                        <p class="mt-1 font-bold text-slate-900">{{ $latestWeather }}</p>
                    </div>

                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-medium text-slate-500">Candidates</p>
                        <p class="mt-1 font-bold text-slate-900">{{ $latestCandidates }}</p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    @forelse ((array) $latestCategories as $category)
                        <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                            {{ $category }}
                        </span>
                    @empty
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                            Belum ada kategori
                        </span>
                    @endforelse

                    @if ($latestWeatherSource !== '-')
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                            Source: {{ $latestWeatherSource }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Tips Pengujian</p>

                <h2 class="mt-2 text-xl font-bold text-slate-950">Agar hasil tidak 0 candidates</h2>

                <ul class="mt-4 space-y-3 text-sm text-slate-600">
                    <li class="flex gap-2">
                        <span>1.</span>
                        <span>Kosongkan keyword saat test awal.</span>
                    </li>
                    <li class="flex gap-2">
                        <span>2.</span>
                        <span>Gunakan min rating 0 atau 3.5.</span>
                    </li>
                    <li class="flex gap-2">
                        <span>3.</span>
                        <span>Centang beberapa kategori sekaligus.</span>
                    </li>
                    <li class="flex gap-2">
                        <span>4.</span>
                        <span>Gunakan wilayah yang datanya banyak seperti Gianyar atau Badung.</span>
                    </li>
                </ul>
            </div>
        </section>

        {{-- History Table --}}
        <section id="riwayat" class="rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Riwayat</p>
                        <h2 class="text-xl font-bold text-slate-950">Riwayat Rekomendasi Saya</h2>
                        <p class="mt-1 text-sm text-slate-600">
                            Semua rekomendasi yang kamu cari akan tersimpan di sini.
                        </p>
                    </div>

                    <a
                        href="{{ route('tourhub.recommendation.index') }}"
                        class="inline-flex items-center justify-center rounded-xl bg-blue-100 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-200"
                    >
                        + Rekomendasi Baru
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
                            <th class="px-6 py-4">Parameter</th>
                            <th class="px-6 py-4">Cuaca</th>
                            <th class="px-6 py-4">Candidates</th>
                            <th class="px-6 py-4">Top Destination</th>
                            <th class="px-6 py-4">Response</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($logs as $log)
                            @php
                                $payload = $log->request_payload ?? [];
                                $categories = data_get($payload, 'kategori_preferensi', []);
                                $kabupaten = data_get($payload, 'kabupaten_kota', '-');
                                $kecamatan = data_get($payload, 'kecamatan', '-');
                                $topDestination = $log->top_destination_name ?? '-';
                            @endphp

                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-semibold text-slate-900">
                                        {{ $log->created_at?->format('d M Y') }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ $log->created_at?->format('H:i') }}
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        class="{{ $log->status === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }} inline-flex rounded-full px-3 py-1 text-xs font-bold"
                                    >
                                        {{ ucfirst($log->status) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-900">
                                        {{ $kabupaten ?: '-' }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ $kecamatan ?: '-' }}
                                    </div>

                                    <div class="mt-2 flex flex-wrap gap-1">
                                        @foreach ((array) $categories as $category)
                                            <span
                                                class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600"
                                            >
                                                {{ $category }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-900">
                                        {{ $log->weather_used ?? '-' }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ $log->weather_source ?? 'manual' }}
                                    </div>
                                </td>

                                <td class="px-6 py-4 font-semibold text-slate-900">
                                    {{ $log->total_candidates ?? '-' }}
                                </td>

                                <td class="px-6 py-4">
                                    <div class="max-w-[220px] font-semibold text-slate-900">
                                        {{ $topDestination }}
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-slate-600">
                                    {{ $log->response_time_ms ? $log->response_time_ms . ' ms' : '-' }}
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <a
                                        href="{{ route('user.recommendation-history.show', $log) }}"
                                        class="inline-flex rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white hover:bg-slate-700"
                                    >
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <div
                                        class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-3xl"
                                    >
                                        🧭
                                    </div>

                                    <h3 class="mt-4 text-lg font-bold text-slate-950">Belum ada riwayat rekomendasi</h3>

                                    <p class="mt-2 text-sm text-slate-500">Mulai cari rekomendasi wisata pertamamu.</p>

                                    <a
                                        href="{{ route('tourhub.recommendation.index') }}"
                                        class="mt-5 inline-flex rounded-xl bg-slate-900 px-5 py-3 text-sm font-bold text-white hover:bg-slate-700"
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
                <div class="border-t border-slate-200 p-6">
                    {{ $logs->links() }}
                </div>
            @endif
        </section>
    </div>
</x-layouts.tourhub-auth>
