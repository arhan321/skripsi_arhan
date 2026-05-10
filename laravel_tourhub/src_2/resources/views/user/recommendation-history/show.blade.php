<x-layouts.tourhub-auth title="Detail Riwayat - TourHub Bali">
    <div class="space-y-6">
        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                <div>
                    <p class="text-sm text-slate-500">Detail Riwayat Rekomendasi</p>
                    <h1 class="text-2xl font-bold">
                        {{ $log->top_destination_name ?? 'Request Rekomendasi' }}
                    </h1>
                    <p class="text-sm text-slate-600 mt-1">
                        Dibuat pada {{ $log->created_at?->format('d M Y H:i') }}
                    </p>
                </div>

                <a href="{{ route('user.dashboard') }}"
                   class="inline-flex rounded-xl bg-slate-900 text-white px-5 py-3 text-sm font-semibold hover:bg-slate-700">
                    Kembali ke Dashboard
                </a>
            </div>
        </section>

        <section class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-sm text-slate-500">Status</p>
                <p class="text-xl font-bold mt-2">{{ $log->status }}</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-sm text-slate-500">Cuaca</p>
                <p class="text-xl font-bold mt-2">{{ $log->weather_used ?? '-' }}</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-sm text-slate-500">Candidates</p>
                <p class="text-xl font-bold mt-2">{{ $log->total_candidates ?? '-' }}</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-sm text-slate-500">Response</p>
                <p class="text-xl font-bold mt-2">
                    {{ $log->response_time_ms ? $log->response_time_ms . ' ms' : '-' }}
                </p>
            </div>
        </section>

        @if ($log->status === 'failed')
            <section class="bg-red-50 rounded-2xl border border-red-200 p-6">
                <h2 class="text-lg font-bold text-red-700 mb-2">Error Message</h2>
                <p class="text-sm text-red-700">{{ $log->error_message }}</p>
            </section>
        @endif

        @if (data_get($log->response_payload, 'recommendations'))
            <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="text-lg font-bold mb-4">Hasil Rekomendasi</h2>

                <div class="space-y-4">
                    @foreach (data_get($log->response_payload, 'recommendations', []) as $index => $item)
                        <article class="rounded-xl border border-slate-200 p-4">
                            <div class="flex flex-col md:flex-row gap-4">
                                @if (data_get($item, 'link_gambar'))
                                    <img src="{{ data_get($item, 'link_gambar') }}"
                                         alt="{{ data_get($item, 'nama_tempat_wisata') }}"
                                         class="w-full md:w-40 h-32 object-cover rounded-xl bg-slate-100">
                                @endif

                                <div class="flex-1">
                                    <p class="text-xs text-slate-500">
                                        #{{ $index + 1 }} · {{ data_get($item, 'kategori') }} · {{ data_get($item, 'tipe_wisata') }}
                                    </p>

                                    <h3 class="text-lg font-bold mt-1">
                                        {{ data_get($item, 'nama_tempat_wisata') }}
                                    </h3>

                                    <p class="text-sm text-slate-600">
                                        {{ data_get($item, 'kecamatan') }} - {{ data_get($item, 'kabupaten_kota') }}
                                    </p>

                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-3 text-sm">
                                        <div class="rounded-lg bg-slate-100 p-2">
                                            <p class="text-xs text-slate-500">Rating</p>
                                            <p class="font-bold">{{ data_get($item, 'rating') }}</p>
                                        </div>

                                        <div class="rounded-lg bg-slate-100 p-2">
                                            <p class="text-xs text-slate-500">CBF</p>
                                            <p class="font-bold">{{ data_get($item, 'cbf_score') }}</p>
                                        </div>

                                        <div class="rounded-lg bg-slate-100 p-2">
                                            <p class="text-xs text-slate-500">Context</p>
                                            <p class="font-bold">{{ data_get($item, 'context_multiplier') }}</p>
                                        </div>

                                        <div class="rounded-lg bg-slate-100 p-2">
                                            <p class="text-xs text-slate-500">Final</p>
                                            <p class="font-bold">{{ data_get($item, 'final_score') }}</p>
                                        </div>
                                    </div>

                                    <p class="text-sm text-slate-700 mt-3">
                                        {{ data_get($item, 'alasan') }}
                                    </p>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="text-lg font-bold mb-3">Request Payload</h2>
                <pre class="bg-slate-950 text-slate-100 rounded-xl p-4 text-xs overflow-auto">{{ json_encode($log->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="text-lg font-bold mb-3">Response Payload</h2>
                <pre class="bg-slate-950 text-slate-100 rounded-xl p-4 text-xs overflow-auto">{{ json_encode($log->response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </section>
    </div>
</x-layouts.tourhub-auth>
