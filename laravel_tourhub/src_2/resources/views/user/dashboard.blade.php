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
@endphp

<x-layouts.tourhub-auth title="Dashboard User - TourHub Bali">
    {{-- Hero Section --}}
    <section class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-slate-900 to-sky-950 p-6 text-white shadow-2xl shadow-slate-900/10 md:p-8">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-12 md:items-center">
            <div class="md:col-span-8">
                <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-xs font-bold text-white ring-1 ring-white/15">
                    <span>👋</span>
                    <span>Panel User TourHub Bali</span>
                </div>

                <h1 class="mt-5 text-3xl font-black tracking-tight md:text-4xl">
                    Halo, {{ auth()->user()->name }}
                </h1>

                <p class="mt-4 max-w-3xl text-sm leading-7 text-slate-200 md:text-base">
                    Di dashboard ini kamu bisa melihat ringkasan pencarian wisata, rekomendasi terakhir,
                    dan riwayat pencarian yang sudah tersimpan di akun kamu.
                </p>

                <div class="mt-5 flex flex-wrap gap-2">
                    <span class="rounded-full bg-white/10 px-3 py-1.5 text-xs font-bold text-slate-100 ring-1 ring-white/10">
                        Rekomendasi Pintar
                    </span>
                    <span class="rounded-full bg-white/10 px-3 py-1.5 text-xs font-bold text-slate-100 ring-1 ring-white/10">
                        Cuaca Terkini
                    </span>
                    <span class="rounded-full bg-white/10 px-3 py-1.5 text-xs font-bold text-slate-100 ring-1 ring-white/10">
                        Riwayat Tersimpan
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

    {{-- Statistic Cards --}}
    <section class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-[1.6rem] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-slate-500">Total Pencarian</p>
                    <p class="mt-3 text-4xl font-black text-slate-950">{{ $totalRecommendations }}</p>
                </div>

                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-2xl">
                    📌
                </div>
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

                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-2xl">
                    ✅
                </div>
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

                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-100 text-2xl">
                    ⚠️
                </div>
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

                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-100 text-2xl">
                    📈
                </div>
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
                    <a
                        href="{{ route('user.recommendation-history.show', $latestSuccess) }}"
                        class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white transition hover:-translate-y-0.5 hover:bg-slate-800"
                    >
                        Lihat Detail
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
                <li class="flex gap-3">
                    <span class="font-black text-slate-950">1.</span>
                    <span>Gunakan kata kunci yang sederhana, misalnya pantai, sunset, pura, atau air terjun.</span>
                </li>
                <li class="flex gap-3">
                    <span class="font-black text-slate-950">2.</span>
                    <span>Turunkan rating minimum jika pilihan destinasi yang muncul terlalu sedikit.</span>
                </li>
                <li class="flex gap-3">
                    <span class="font-black text-slate-950">3.</span>
                    <span>Pilih beberapa kategori sekaligus agar pilihan wisata lebih beragam.</span>
                </li>
                <li class="flex gap-3">
                    <span class="font-black text-slate-950">4.</span>
                    <span>Coba wilayah populer seperti Gianyar, Badung, Denpasar, atau Tabanan.</span>
                </li>
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
                        Semua rekomendasi yang kamu cari akan tersimpan di sini.
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
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-3xl">
                                    🧭
                                </div>

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
</x-layouts.tourhub-auth>
