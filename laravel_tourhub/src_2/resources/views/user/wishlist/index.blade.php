@php
    /*
     |--------------------------------------------------------------------------
     | Logic halaman Wishlist langsung di view
     |--------------------------------------------------------------------------
     | Navbar tidak dibuat di halaman ini.
     | Halaman ini memakai <x-layouts.tourhub-auth>, jadi navbar akan mengikuti
     | component resources/views/components/layouts/tourhub-auth.blade.php.
     */

    $authUser = auth()->user();

    $wishlists = $wishlists ?? \App\Models\Wishlist::query()
        ->where('user_id', auth()->id())
        ->latest()
        ->paginate(12);

    $wishlistCount = \App\Models\Wishlist::query()
        ->where('user_id', auth()->id())
        ->count();

    $safeRoute = function (string $routeName, string $fallback = '#', array $parameters = []) {
        return \Illuminate\Support\Facades\Route::has($routeName)
            ? route($routeName, $parameters)
            : $fallback;
    };

    $formatNumber = function ($number): string {
        if ($number === null || $number === '') {
            return '-';
        }

        return number_format((int) $number);
    };

    $formatRating = function ($rating): string {
        if ($rating === null || $rating === '') {
            return '-';
        }

        return rtrim(rtrim(number_format((float) $rating, 1), '0'), '.');
    };

    $getSnapshotValue = function ($wishlist, array $keys, $default = null) {
        $snapshot = is_array($wishlist->snapshot ?? null)
            ? $wishlist->snapshot
            : [];

        foreach ($keys as $key) {
            $value = data_get($snapshot, $key);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $default;
    };

    $getImageUrl = function ($wishlist) use ($getSnapshotValue): ?string {
        $image = $wishlist->image_url
            ?: $getSnapshotValue($wishlist, ['link_gambar', 'image_url', 'gambar', 'photo_url']);

        return $image ? (string) $image : null;
    };

    $getMapsUrl = function ($wishlist) use ($getSnapshotValue): ?string {
        $maps = $wishlist->google_maps_url
            ?: $getSnapshotValue($wishlist, ['link_google_maps', 'google_maps_url', 'maps_url']);

        return $maps ? (string) $maps : null;
    };

    $getCategory = function ($wishlist) use ($getSnapshotValue): string {
        return (string) (
            $wishlist->category
            ?: $getSnapshotValue($wishlist, ['kategori', 'category'], '-')
            ?: '-'
        );
    };

    $getTourismType = function ($wishlist) use ($getSnapshotValue): string {
        return (string) (
            $wishlist->tourism_type
            ?: $getSnapshotValue($wishlist, ['tipe_wisata', 'tourism_type'], '-')
            ?: '-'
        );
    };

    $getLocation = function ($wishlist) use ($getSnapshotValue): string {
        $subdistrict = $wishlist->subdistrict
            ?: $getSnapshotValue($wishlist, ['kecamatan', 'subdistrict']);

        $city = $wishlist->city
            ?: $getSnapshotValue($wishlist, ['kabupaten_kota', 'city']);

        if ($subdistrict && $city) {
            return $subdistrict . ' - ' . $city;
        }

        if ($subdistrict) {
            return (string) $subdistrict;
        }

        if ($city) {
            return (string) $city;
        }

        return 'Lokasi belum tersedia';
    };

    $getReason = function ($wishlist) use ($getSnapshotValue): ?string {
        $reason = $wishlist->reason
            ?: $getSnapshotValue($wishlist, ['alasan', 'reason']);

        if (! $reason) {
            return null;
        }

        $reason = trim((string) $reason);

        if ($reason === '') {
            return null;
        }

        // Bersihkan istilah teknis agar nyaman dibaca user.
        $reason = preg_replace('/\s*\(\s*CBF\s*=\s*[^\)]*\)/i', '', $reason);
        $reason = preg_replace('/\s*CBF\s*=\s*[0-9\.]+\s*;?/i', '', $reason);
        $reason = preg_replace('/\s*context\s*=\s*[0-9\.]+\s*;?/i', '', $reason);
        $reason = preg_replace('/\s*final score\s*[^;\.]*[;\.]?/i', '', $reason);

        $reason = str_ireplace('cocok dengan fitur/preferensi user', 'Cocok dengan preferensi pencarianmu', $reason);
        $reason = str_ireplace('fitur/preferensi user', 'preferensi pencarianmu', $reason);
        $reason = str_ireplace('user', 'kamu', $reason);
        $reason = str_ireplace('outdoor', 'luar ruangan', $reason);
        $reason = str_ireplace('indoor', 'dalam ruangan', $reason);
        $reason = str_ireplace('mixed', 'fleksibel', $reason);
        $reason = str_ireplace('weekend', 'akhir pekan', $reason);
        $reason = str_ireplace('weekday', 'hari biasa', $reason);

        $reason = preg_replace('/\s+/', ' ', $reason);
        $reason = trim($reason, " ;.\t\n\r\0\x0B");

        return $reason !== '' ? ucfirst($reason) . '.' : null;
    };

    $recommendationUrl = $safeRoute('tourhub.recommendation.index', '/tourhub/rekomendasi');
@endphp

<x-layouts.tourhub-auth title="Wishlist Saya - TourHub Bali">
    <style>
        .wishlist-premium-shadow {
            box-shadow:
                0 20px 60px rgba(15, 23, 42, 0.10),
                0 1px 2px rgba(15, 23, 42, 0.06);
        }

        .wishlist-soft-grid {
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.055) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.055) 1px, transparent 1px);
            background-size: 28px 28px;
        }

        .wishlist-card {
            transition:
                transform 220ms ease,
                box-shadow 220ms ease,
                border-color 220ms ease;
        }

        .wishlist-card:hover {
            transform: translateY(-4px);
            box-shadow:
                0 24px 70px rgba(15, 23, 42, 0.13),
                0 2px 8px rgba(15, 23, 42, 0.07);
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .line-clamp-4 {
            display: -webkit-box;
            -webkit-line-clamp: 4;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        @media (prefers-reduced-motion: reduce) {
            .wishlist-card {
                transition: none !important;
            }
        }
    </style>

    <section class="wishlist-premium-shadow overflow-hidden rounded-[2rem] border border-slate-200 bg-white">
        <div class="relative overflow-hidden bg-slate-950 px-6 py-10 text-white md:px-10 md:py-12">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(245,158,11,0.35),_transparent_35%),radial-gradient(circle_at_bottom_right,_rgba(37,99,235,0.3),_transparent_30%)]"></div>
            <div class="wishlist-soft-grid absolute inset-0 opacity-30"></div>

            <div class="relative flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                <div>
                    <div class="inline-flex rounded-full bg-amber-400 px-4 py-2 text-xs font-black text-slate-950 shadow-lg shadow-amber-900/20">
                        ★ Destinasi Tersimpan
                    </div>

                    <h1 class="mt-5 text-3xl font-black tracking-tight md:text-5xl">
                        Wishlist Saya
                    </h1>

                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300 md:text-base">
                        Semua tempat wisata yang kamu simpan dari hasil rekomendasi dan detail riwayat akan muncul di halaman ini.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:flex">
                    <div class="rounded-3xl bg-white/10 px-5 py-4 text-center ring-1 ring-white/10 backdrop-blur">
                        <p class="text-xs font-bold text-slate-300">Total Wishlist</p>
                        <p class="mt-1 text-3xl font-black text-white">{{ $wishlistCount }}</p>
                    </div>

                    <a
                        href="{{ $recommendationUrl }}"
                        class="inline-flex items-center justify-center rounded-3xl bg-white px-5 py-4 text-sm font-black text-slate-950 shadow-lg shadow-slate-950/10 transition hover:-translate-y-0.5 hover:bg-blue-50"
                    >
                        Cari Wisata Lagi
                    </a>
                </div>
            </div>
        </div>

        <div class="border-b border-slate-100 bg-gradient-to-br from-white via-slate-50 to-blue-50 px-6 py-5 md:px-10">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-wider text-blue-600">
                        Daftar pilihan pribadi
                    </p>

                    <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950">
                        Tempat Wisata Favoritmu
                    </h2>
                </div>

                <div class="flex flex-wrap gap-2 text-xs font-bold">
                    <span class="rounded-full bg-slate-950 px-3 py-1 text-white">
                        {{ $wishlistCount }} tempat tersimpan
                    </span>

                    <span class="rounded-full bg-amber-100 px-3 py-1 text-amber-700">
                        Bisa dibuka kembali kapan saja
                    </span>
                </div>
            </div>
        </div>

        <div class="p-6 md:p-8">
            @if ($wishlists->isEmpty())
                <div class="rounded-[2rem] border border-dashed border-slate-300 bg-slate-50 p-8 text-center md:p-12">
                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white text-4xl shadow-sm">
                        ☆
                    </div>

                    <h3 class="mt-5 text-2xl font-black text-slate-950">
                        Wishlist kamu masih kosong
                    </h3>

                    <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-slate-500">
                        Klik tombol Wishlist pada halaman hasil rekomendasi atau detail riwayat untuk menyimpan tempat wisata yang ingin kamu kunjungi.
                    </p>

                    <a
                        href="{{ $recommendationUrl }}"
                        class="mt-6 inline-flex items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white shadow-lg shadow-slate-900/15 transition hover:-translate-y-0.5 hover:bg-slate-800"
                    >
                        Mulai Cari Rekomendasi
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($wishlists as $wishlist)
                        @php
                            $imageUrl = $getImageUrl($wishlist);
                            $mapsUrl = $getMapsUrl($wishlist);
                            $category = $getCategory($wishlist);
                            $tourismType = $getTourismType($wishlist);
                            $location = $getLocation($wishlist);
                            $reason = $getReason($wishlist);
                            $rating = $formatRating($wishlist->rating ?? $getSnapshotValue($wishlist, ['rating']));
                            $reviewCount = $formatNumber($wishlist->review_count ?? $getSnapshotValue($wishlist, ['jumlah_rating', 'review_count']));
                            $destroyUrl = $safeRoute('wishlist.destroy', '#', ['wishlist' => $wishlist]);
                        @endphp

                        <article class="wishlist-card group overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                            <div class="relative h-56 overflow-hidden">
                                @if ($imageUrl)
                                    <img
                                        src="{{ $imageUrl }}"
                                        alt="{{ $wishlist->destination_name }}"
                                        class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                        loading="lazy"
                                    />
                                @else
                                    <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200 text-sm font-bold text-slate-400">
                                        No Image
                                    </div>
                                @endif

                                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/10 to-transparent"></div>

                                <div class="absolute left-4 top-4 rounded-2xl bg-amber-400 px-3 py-2 text-sm font-black text-slate-950 shadow-lg shadow-amber-900/20">
                                    ★ Tersimpan
                                </div>

                                <div class="absolute bottom-4 left-4 right-4 text-white">
                                    <p class="text-xs font-black uppercase tracking-wider text-blue-100">
                                        Wishlist
                                    </p>

                                    <h3 class="mt-1 line-clamp-3 text-2xl font-black tracking-tight">
                                        {{ $wishlist->destination_name }}
                                    </h3>

                                    <p class="mt-2 text-sm font-semibold text-slate-200">
                                        📍 {{ $location }}
                                    </p>
                                </div>
                            </div>

                            <div class="p-5">
                                <div class="flex flex-wrap gap-2">
                                    <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                                        {{ $category }}
                                    </span>

                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                                        {{ $tourismType }}
                                    </span>
                                </div>

                                <div class="mt-5 grid grid-cols-2 gap-3">
                                    <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                        <p class="text-[0.64rem] font-black uppercase tracking-wide text-slate-500">
                                            Rating
                                        </p>

                                        <p class="mt-1 text-lg font-black text-slate-950">
                                            {{ $rating }}
                                        </p>
                                    </div>

                                    <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                        <p class="text-[0.64rem] font-black uppercase tracking-wide text-slate-500">
                                            Ulasan
                                        </p>

                                        <p class="mt-1 text-lg font-black text-slate-950">
                                            {{ $reviewCount }}
                                        </p>
                                    </div>
                                </div>

                                @if ($reason)
                                    <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                                        <p class="text-xs font-black uppercase tracking-wider text-amber-700">
                                            Alasan Rekomendasi
                                        </p>

                                        <p class="mt-2 line-clamp-4 text-sm leading-6 text-slate-700">
                                            {{ $reason }}
                                        </p>
                                    </div>
                                @endif

                                <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:items-center">
                                    @if ($mapsUrl)
                                        <a
                                            href="{{ $mapsUrl }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-100 px-4 py-3 text-sm font-black text-emerald-700 transition hover:bg-emerald-200"
                                        >
                                            Buka Maps
                                        </a>
                                    @endif

                                    @if ($destroyUrl !== '#')
                                        <form method="POST" action="{{ $destroyUrl }}" class="w-full">
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                onclick="return confirm('Hapus destinasi ini dari wishlist?')"
                                                class="inline-flex w-full items-center justify-center rounded-2xl bg-red-50 px-4 py-3 text-sm font-black text-red-600 ring-1 ring-red-100 transition hover:bg-red-600 hover:text-white"
                                            >
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                <p class="mt-4 text-xs font-semibold text-slate-400">
                                    Disimpan pada {{ optional($wishlist->created_at)->format('d M Y H:i') }}
                                </p>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $wishlists->links() }}
                </div>
            @endif
        </div>
    </section>
</x-layouts.tourhub-auth>
