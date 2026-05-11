<x-layouts.tourhub-auth title="Detail Riwayat - TourHub Bali">
    @php
        /*
         * Ambil semua rekomendasi dari response_payload.
         * Lalu urutkan berdasarkan final_score tertinggi.
         */
        $recommendations = collect(data_get($log->response_payload, 'recommendations', []))
            ->sortByDesc(fn ($item) => (float) data_get($item, 'final_score', 0))
            ->values();

        /*
         * Rekomendasi terbaik adalah item pertama setelah sorting final_score.
         */
        $bestRecommendation = $recommendations->first();

        $requestPayload = $log->request_payload ?? [];
        $responsePayload = $log->response_payload ?? [];

        $categories = data_get($requestPayload, 'kategori_preferensi', []);
        $keywords = data_get($requestPayload, 'keywords', []);
        $kabupatenKota = data_get($requestPayload, 'kabupaten_kota', '-');
        $kecamatan = data_get($requestPayload, 'kecamatan', '-');
        $minRating = data_get($requestPayload, 'min_rating', '-');
        $topN = data_get($requestPayload, 'top_n', '-');
        $visitDay = data_get($requestPayload, 'visit_day', '-');
        $useBmkg = data_get($requestPayload, 'use_bmkg') ? 'Ya' : 'Tidak';
        $isHighSeason = data_get($requestPayload, 'is_high_season') ? 'Ya' : 'Tidak';

        $bestName = data_get($bestRecommendation, 'nama_tempat_wisata', $log->top_destination_name ?? 'Request Rekomendasi');
        $bestFinalScore = data_get($bestRecommendation, 'final_score', '-');
    @endphp

    <div class="space-y-6">
        {{-- Header --}}
        <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 p-6 text-white shadow-2xl shadow-slate-900/10 md:p-8">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.40),_transparent_34%),radial-gradient(circle_at_bottom_right,_rgba(245,158,11,0.30),_transparent_34%)]"></div>

            <div
                class="absolute inset-0 opacity-20"
                style="background-image: linear-gradient(rgba(255,255,255,.08) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.08) 1px, transparent 1px); background-size: 32px 32px;"
            ></div>

            <div class="relative flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
                <div class="max-w-3xl">
                    <div class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black text-blue-100 ring-1 ring-white/10">
                        Detail Riwayat Rekomendasi
                    </div>

                    <h1 class="mt-4 text-3xl font-black tracking-tight md:text-4xl">
                        {{ $bestName }}
                    </h1>

                    <p class="mt-3 text-sm leading-6 text-slate-300">
                        Halaman ini menampilkan detail request rekomendasi, hasil response FastAPI,
                        dan destinasi yang paling direkomendasikan berdasarkan
                        <span class="font-bold text-white">final_score tertinggi</span>.
                    </p>

                    <div class="mt-5 flex flex-wrap gap-2 text-xs font-bold">
                        <span class="rounded-full bg-white/10 px-3 py-1 text-slate-100 ring-1 ring-white/10">
                            Dibuat: {{ $log->created_at?->format('d M Y H:i') }}
                        </span>

                        <span class="rounded-full bg-white/10 px-3 py-1 text-slate-100 ring-1 ring-white/10">
                            Status: {{ ucfirst($log->status) }}
                        </span>

                        <span class="rounded-full bg-white/10 px-3 py-1 text-slate-100 ring-1 ring-white/10">
                            Final Score Teratas: {{ $bestFinalScore }}
                        </span>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a
                        href="{{ route('user.dashboard') }}"
                        class="inline-flex rounded-2xl bg-white px-5 py-3 text-sm font-black text-slate-950 shadow-sm hover:bg-slate-100"
                    >
                        ← Kembali ke Dashboard
                    </a>

                    <a
                        href="{{ route('tourhub.recommendation.index') }}"
                        class="inline-flex rounded-2xl bg-blue-600 px-5 py-3 text-sm font-black text-white shadow-sm shadow-blue-600/20 hover:bg-blue-700"
                    >
                        Rekomendasi Baru
                    </a>
                </div>
            </div>
        </section>

        {{-- Summary Cards --}}
        <section class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Status</p>

                <div class="mt-3">
                    <span class="inline-flex rounded-full px-3 py-1 text-sm font-black {{ $log->status === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                        {{ ucfirst($log->status) }}
                    </span>
                </div>

                <p class="mt-4 text-xs text-slate-500">
                    Status request rekomendasi.
                </p>
            </div>

            <div class="rounded-3xl border border-blue-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Cuaca</p>
                <p class="mt-2 text-2xl font-black text-blue-600">{{ $log->weather_used ?? '-' }}</p>
                <p class="mt-1 text-xs text-slate-500">
                    Source: {{ $log->weather_source ?? 'manual' }}
                </p>
            </div>

            <div class="rounded-3xl border border-emerald-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Candidates</p>
                <p class="mt-2 text-3xl font-black text-emerald-600">{{ $log->total_candidates ?? '-' }}</p>
                <p class="mt-1 text-xs text-slate-500">
                    Jumlah kandidat dari FastAPI.
                </p>
            </div>

            <div class="rounded-3xl border border-amber-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Response Time</p>
                <p class="mt-2 text-3xl font-black text-amber-600">
                    {{ $log->response_time_ms ? $log->response_time_ms . ' ms' : '-' }}
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    Waktu respons rekomendasi.
                </p>
            </div>
        </section>

        {{-- Request Parameter Summary --}}
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-sm font-black uppercase tracking-wider text-slate-500">
                        Parameter Request
                    </p>

                    <h2 class="mt-1 text-2xl font-black text-slate-950">
                        Preferensi yang Digunakan
                    </h2>

                    <p class="mt-2 text-sm text-slate-600">
                        Ringkasan input user saat rekomendasi ini dibuat.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    @forelse ((array) $categories as $category)
                        <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-blue-700">
                            {{ $category }}
                        </span>
                    @empty
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">
                            Tidak ada kategori
                        </span>
                    @endforelse
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-3 md:grid-cols-4">
                <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Kabupaten/Kota</p>
                    <p class="mt-1 font-black text-slate-950">{{ $kabupatenKota ?: '-' }}</p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Kecamatan</p>
                    <p class="mt-1 font-black text-slate-950">{{ $kecamatan ?: '-' }}</p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Min Rating</p>
                    <p class="mt-1 font-black text-slate-950">{{ $minRating }}</p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Top N</p>
                    <p class="mt-1 font-black text-slate-950">{{ $topN }}</p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Hari Kunjungan</p>
                    <p class="mt-1 font-black text-slate-950">{{ ucfirst((string) $visitDay) }}</p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">BMKG</p>
                    <p class="mt-1 font-black text-slate-950">{{ $useBmkg }}</p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">High Season</p>
                    <p class="mt-1 font-black text-slate-950">{{ $isHighSeason }}</p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Keywords</p>
                    <p class="mt-1 font-black text-slate-950">
                        {{ count((array) $keywords) ? implode(', ', (array) $keywords) : '-' }}
                    </p>
                </div>
            </div>
        </section>

        @if ($log->status === 'failed')
            <section class="rounded-3xl border border-red-200 bg-red-50 p-6">
                <h2 class="text-lg font-black text-red-700">Error Message</h2>
                <p class="mt-2 text-sm leading-6 text-red-700">{{ $log->error_message }}</p>
            </section>
        @endif

        @if ($recommendations->isNotEmpty())
            {{-- Best Recommendation --}}
            <section class="overflow-hidden rounded-3xl border border-amber-200 bg-gradient-to-br from-amber-50 via-white to-blue-50 shadow-xl shadow-amber-900/5">
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

                                <h2 class="mt-4 text-3xl font-black tracking-tight text-slate-950">
                                    {{ data_get($bestRecommendation, 'nama_tempat_wisata') }}
                                </h2>

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
                                Destinasi ini memiliki <strong>final score tertinggi</strong> dibanding kandidat lain pada riwayat ini.
                                Final score digunakan sebagai skor ranking akhir dari kombinasi CBF, rating, jumlah ulasan,
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
            </section>

            {{-- Ranking List --}}
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-5 flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p class="text-sm font-black uppercase tracking-wider text-slate-500">
                            Ranking Rekomendasi
                        </p>

                        <h2 class="text-2xl font-black text-slate-950">
                            Diurutkan Berdasarkan Final Score
                        </h2>

                        <p class="mt-2 text-sm text-slate-600">
                            Item paling atas adalah destinasi yang paling direkomendasikan untuk request ini.
                        </p>
                    </div>

                    <span class="inline-flex rounded-2xl bg-slate-950 px-4 py-2 text-sm font-black text-white">
                        Total {{ $recommendations->count() }} hasil
                    </span>
                </div>

                <div class="space-y-4">
                    @foreach ($recommendations as $index => $item)
                        <article class="overflow-hidden rounded-3xl border {{ $index === 0 ? 'border-amber-300 bg-amber-50/40' : 'border-slate-200 bg-white' }}">
                            <div class="flex flex-col gap-4 p-4 md:flex-row">
                                <div class="relative shrink-0">
                                    @if (data_get($item, 'link_gambar'))
                                        <img
                                            src="{{ data_get($item, 'link_gambar') }}"
                                            alt="{{ data_get($item, 'nama_tempat_wisata') }}"
                                            class="h-40 w-full rounded-2xl object-cover md:w-52"
                                        >
                                    @else
                                        <div class="flex h-40 w-full items-center justify-center rounded-2xl bg-slate-100 text-sm font-bold text-slate-400 md:w-52">
                                            No Image
                                        </div>
                                    @endif

                                    <div class="absolute left-3 top-3 rounded-xl {{ $index === 0 ? 'bg-amber-400 text-slate-950' : 'bg-slate-950 text-white' }} px-3 py-1 text-sm font-black">
                                        #{{ $index + 1 }}
                                    </div>
                                </div>

                                <div class="flex-1">
                                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                        <div>
                                            <div class="flex flex-wrap gap-2">
                                                @if ($index === 0)
                                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-700">
                                                        🏆 Paling Direkomendasikan
                                                    </span>
                                                @endif

                                                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                                                    {{ data_get($item, 'kategori') }}
                                                </span>

                                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                                                    {{ data_get($item, 'tipe_wisata') }}
                                                </span>
                                            </div>

                                            <h3 class="mt-3 text-xl font-black text-slate-950">
                                                {{ data_get($item, 'nama_tempat_wisata') }}
                                            </h3>

                                            <p class="mt-1 text-sm font-medium text-slate-600">
                                                {{ data_get($item, 'kecamatan') }} - {{ data_get($item, 'kabupaten_kota') }}
                                            </p>
                                        </div>

                                        <div class="rounded-2xl bg-slate-950 px-4 py-3 text-white">
                                            <p class="text-xs font-bold text-slate-300">Final Score</p>
                                            <p class="text-2xl font-black">
                                                {{ data_get($item, 'final_score') }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-4 grid grid-cols-2 gap-2 md:grid-cols-4">
                                        <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Rating</p>
                                            <p class="font-black text-slate-950">{{ data_get($item, 'rating') }}</p>
                                        </div>

                                        <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">CBF</p>
                                            <p class="font-black text-slate-950">{{ data_get($item, 'cbf_score') }}</p>
                                        </div>

                                        <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Context</p>
                                            <p class="font-black text-slate-950">{{ data_get($item, 'context_multiplier') }}</p>
                                        </div>

                                        <div class="rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Jumlah Rating</p>
                                            <p class="font-black text-slate-950">
                                                {{ number_format((int) data_get($item, 'jumlah_rating', 0)) }}
                                            </p>
                                        </div>
                                    </div>

                                    @if (data_get($item, 'alasan'))
                                        <p class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm leading-6 text-slate-700">
                                            {{ data_get($item, 'alasan') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- JSON Payload --}}
        <section class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Request Payload</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Parameter yang dikirim Laravel ke FastAPI.
                </p>

                <pre class="mt-4 max-h-[520px] overflow-auto rounded-2xl bg-slate-950 p-4 text-xs leading-5 text-slate-100">{{ json_encode($log->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Response Payload</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Response lengkap dari FastAPI rekomendasi.
                </p>

                <pre class="mt-4 max-h-[520px] overflow-auto rounded-2xl bg-slate-950 p-4 text-xs leading-5 text-slate-100">{{ json_encode($log->response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </section>
    </div>
</x-layouts.tourhub-auth>
