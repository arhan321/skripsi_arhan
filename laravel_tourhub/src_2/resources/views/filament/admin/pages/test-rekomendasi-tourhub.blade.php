<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Simulasi Rekomendasi TourHub Bali</x-slot>

            <x-slot name="description">
                Halaman ini digunakan untuk membuka simulasi web rekomendasi wisata yang terhubung ke FastAPI Machine
                Learning.
            </x-slot>

            <div class="space-y-4">
                <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-900">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Sistem rekomendasi TourHub Bali menggunakan
                        <strong>Content-Based Filtering</strong>
                        untuk menghitung kecocokan preferensi pengguna dan
                        <strong>Context-Aware Recommender System</strong>
                        untuk menyesuaikan rekomendasi berdasarkan konteks seperti cuaca, hari kunjungan, dan high
                        season.
                    </p>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div
                        class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                    >
                        <p class="text-sm text-gray-500 dark:text-gray-400">Web Simulasi</p>
                        <p class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">Form Rekomendasi</p>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                            Buka halaman input preferensi wisata dan tampilkan hasil rekomendasi dari FastAPI.
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                    >
                        <p class="text-sm text-gray-500 dark:text-gray-400">ML Service</p>
                        <p class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">FastAPI CBF + CARS</p>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                            Mengecek apakah service machine learning yang sudah di-hosting sedang aktif.
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                    >
                        <p class="text-sm text-gray-500 dark:text-gray-400">Log</p>
                        <p class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">Recommendation Logs</p>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                            Semua request dan response rekomendasi disimpan untuk dokumentasi dan pengujian.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <x-filament::button
                        tag="a"
                        href="{{ route('tourhub.recommendation.index') }}"
                        target="_blank"
                        icon="heroicon-o-arrow-top-right-on-square"
                    >
                        Buka Simulasi Rekomendasi
                    </x-filament::button>

                    <x-filament::button
                        tag="a"
                        href="{{ route('tourhub.ml.health') }}"
                        target="_blank"
                        color="gray"
                        icon="heroicon-o-signal"
                    >
                        Cek ML API Health
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Sample Parameter Pengujian</x-slot>

            <x-slot name="description">
                Parameter berikut bisa digunakan saat membuka halaman simulasi rekomendasi.
            </x-slot>

            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Skenario</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                                Parameter
                            </th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                                Ekspektasi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-950">
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">Alam + Cerah</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                Kategori: Alam, Kabupaten: Gianyar, Cuaca: cerah, Visit day: weekday
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                Destinasi outdoor seperti waterfall, beach, dan rice terrace naik.
                            </td>
                        </tr>

                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">Alam + Hujan</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                Kategori: Alam, Kabupaten: Gianyar, Cuaca: hujan, Visit day: weekday
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                Destinasi indoor atau mixed naik sebagai alternatif karena hujan.
                            </td>
                        </tr>

                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">Alam + Budaya + Weekend</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                Kategori: Alam dan Budaya, Keyword: pantai, sunset, Cuaca: hujan, Visit day: weekend
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                CARS menyesuaikan ranking dan bisa memprioritaskan indoor saat cuaca tidak mendukung.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
