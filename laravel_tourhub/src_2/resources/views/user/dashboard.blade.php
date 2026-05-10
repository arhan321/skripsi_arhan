<x-layouts.tourhub-auth title="Dashboard User - TourHub Bali">
    <div class="space-y-6">
        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <p class="text-sm text-slate-500">Panel User</p>
                    <h1 class="text-2xl font-bold">Halo, {{ auth()->user()->name }}</h1>
                    <p class="text-sm text-slate-600 mt-1">
                        Di sini kamu bisa melihat riwayat rekomendasi wisata yang pernah kamu cari.
                    </p>
                </div>

                <a href="{{ route('tourhub.recommendation.index') }}"
                   class="inline-flex rounded-xl bg-slate-900 text-white px-5 py-3 text-sm font-semibold hover:bg-slate-700">
                    Cari Rekomendasi Baru
                </a>
            </div>
        </section>

        <section class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-sm text-slate-500">Total Request</p>
                <p class="text-3xl font-bold mt-2">{{ $totalRecommendations }}</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-sm text-slate-500">Berhasil</p>
                <p class="text-3xl font-bold mt-2 text-emerald-600">{{ $successRecommendations }}</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-sm text-slate-500">Gagal</p>
                <p class="text-3xl font-bold mt-2 text-red-600">{{ $failedRecommendations }}</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-sm text-slate-500">Rekomendasi Terakhir</p>
                <p class="text-lg font-bold mt-2">
                    {{ $latestSuccess?->top_destination_name ?? '-' }}
                </p>
            </div>
        </section>

        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-bold mb-4">Riwayat Rekomendasi Saya</h2>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-slate-500">
                            <th class="py-3 pr-4">Waktu</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 pr-4">Cuaca</th>
                            <th class="py-3 pr-4">Candidates</th>
                            <th class="py-3 pr-4">Top Destination</th>
                            <th class="py-3 pr-4">Response</th>
                            <th class="py-3 pr-4">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($logs as $log)
                            <tr class="border-b last:border-b-0">
                                <td class="py-3 pr-4">
                                    {{ $log->created_at?->format('d M Y H:i') }}
                                </td>

                                <td class="py-3 pr-4">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $log->status === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $log->status }}
                                    </span>
                                </td>

                                <td class="py-3 pr-4">
                                    {{ $log->weather_used ?? '-' }}
                                </td>

                                <td class="py-3 pr-4">
                                    {{ $log->total_candidates ?? '-' }}
                                </td>

                                <td class="py-3 pr-4">
                                    {{ $log->top_destination_name ?? '-' }}
                                </td>

                                <td class="py-3 pr-4">
                                    {{ $log->response_time_ms ? $log->response_time_ms . ' ms' : '-' }}
                                </td>

                                <td class="py-3 pr-4">
                                    <a href="{{ route('user.recommendation-history.show', $log) }}"
                                       class="text-blue-600 font-semibold hover:underline">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-500">
                                    Belum ada riwayat rekomendasi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                {{ $logs->links() }}
            </div>
        </section>
    </div>
</x-layouts.tourhub-auth>
