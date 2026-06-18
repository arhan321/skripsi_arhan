<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TourHub Bali - Platform Rekomendasi Wisata</title>

    {{-- Standalone Tailwind CDN agar landing page langsung rapi tanpa perlu rebuild asset. --}}
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    boxShadow: {
                        luxury: '0 30px 80px rgba(15, 23, 42, 0.18)',
                        soft: '0 18px 45px rgba(15, 23, 42, 0.08)',
                    },
                }
            }
        }
    </script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: Inter, ui-sans-serif, system-ui, sans-serif;
        }

        .luxury-grid {
            background-image:
                linear-gradient(rgba(15, 23, 42, 0.055) 1px, transparent 1px),
                linear-gradient(90deg, rgba(15, 23, 42, 0.055) 1px, transparent 1px);
            background-size: 34px 34px;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
        }

        .dark-glass {
            background: rgba(2, 6, 23, 0.72);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
        }

        .hero-mask {
            mask-image: linear-gradient(to bottom, black 72%, transparent 100%);
            -webkit-mask-image: linear-gradient(to bottom, black 72%, transparent 100%);
        }

        .hero-stat-card {
            background: linear-gradient(
                180deg,
                rgba(255, 255, 255, 0.34) 0%,
                rgba(255, 255, 255, 0.20) 46%,
                rgba(15, 23, 42, 0.18) 100%
            );
            border: 1px solid rgba(255, 255, 255, 0.28);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.34),
                0 18px 38px rgba(2, 6, 23, 0.12);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .hero-stat-title {
            color: rgba(255, 255, 255, 0.98);
            text-shadow: 0 2px 14px rgba(2, 6, 23, 0.34);
        }

        .hero-stat-subtitle {
            color: rgba(241, 245, 249, 0.96);
            text-shadow: 0 1px 10px rgba(2, 6, 23, 0.38);
        }

        .text-balance {
            text-wrap: balance;
        }
    </style>
</head>

<body class="min-h-screen bg-[#f3f7fb] text-slate-950 antialiased">
    {{-- Page background --}}
    <div class="fixed inset-0 -z-10 luxury-grid bg-slate-50"></div>
    <div class="fixed inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,_rgba(37,99,235,0.16),_transparent_36%),radial-gradient(circle_at_80%_10%,_rgba(20,184,166,0.14),_transparent_30%),radial-gradient(circle_at_bottom_right,_rgba(15,23,42,0.10),_transparent_36%)]"></div>

    {{-- Floating Navbar --}}
    <header class="fixed left-0 right-0 top-0 z-50">
        <div class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
            <div class="glass-card flex items-center justify-between rounded-[1.6rem] border border-white/70 px-4 py-3 shadow-soft ring-1 ring-slate-900/5">
                <a href="{{ route('landing') }}" class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-950 text-xl font-black text-white shadow-lg shadow-slate-900/20">
                        T
                    </div>

                    <div>
                        <p class="text-lg font-black leading-none tracking-tight text-slate-950">TourHub Bali</p>
                        <p class="mt-1 text-xs font-bold text-slate-500">Smart Travel Recommendation</p>
                    </div>
                </a>

                <nav class="hidden items-center gap-7 text-sm font-extrabold text-slate-600 lg:flex">
                    <a href="#destinasi" class="transition hover:text-blue-700">Destinasi</a>
                    <a href="#fitur" class="transition hover:text-blue-700">Fitur</a>
                    <a href="#cara-kerja" class="transition hover:text-blue-700">Cara Kerja</a>
                    <a href="#mulai" class="transition hover:text-blue-700">Mulai</a>
                </nav>

                <div class="flex items-center gap-2">
                    @auth
                        <a
                            href="{{ route('user.dashboard') }}"
                            class="hidden rounded-2xl px-4 py-2 text-sm font-black text-slate-700 transition hover:bg-white sm:inline-flex"
                        >
                            Dashboard
                        </a>

                        <a
                            href="{{ route('tourhub.recommendation.index') }}"
                            class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white shadow-lg shadow-slate-900/20 transition hover:-translate-y-0.5 hover:bg-blue-700"
                        >
                            Cari Rekomendasi
                        </a>
                    @else
                        <a
                            href="{{ route('user.login') }}"
                            class="hidden rounded-2xl px-4 py-2 text-sm font-black text-slate-700 transition hover:bg-white sm:inline-flex"
                        >
                            Login
                        </a>

                        <a
                            href="{{ route('user.register') }}"
                            class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white shadow-lg shadow-slate-900/20 transition hover:-translate-y-0.5 hover:bg-blue-700"
                        >
                            Register
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <main>
        {{-- Hero --}}
        <section class="relative overflow-hidden pt-28">
            <div class="absolute inset-x-0 top-0 -z-10 h-[820px] overflow-hidden hero-mask">
                <img
                    src="https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=2600&q=90"
                    alt="Bali travel destination"
                    class="h-full w-full object-cover"
                />
                <div class="absolute inset-0 bg-gradient-to-br from-slate-950/95 via-slate-950/76 to-blue-950/40"></div>
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_10%,_rgba(59,130,246,0.38),_transparent_34%),radial-gradient(circle_at_85%_70%,_rgba(45,212,191,0.24),_transparent_30%)]"></div>
            </div>

            <div class="mx-auto max-w-7xl px-4 pb-20 pt-16 sm:px-6 lg:px-8 lg:pb-28 lg:pt-24">
                <div class="grid items-center gap-12 lg:grid-cols-[1.05fr_0.95fr]">
                    <div class="max-w-3xl">
                        <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-blue-100 backdrop-blur">
                            <span>✨</span>
                            Platform rekomendasi wisata Bali
                        </div>

                        <h1 class="mt-7 text-balance text-5xl font-black leading-[0.98] tracking-tight text-white sm:text-6xl lg:text-7xl">
                            Liburan ke Bali jadi lebih terarah.
                        </h1>

                        <p class="mt-7 max-w-2xl text-base font-medium leading-8 text-slate-200 sm:text-lg">
                            Temukan destinasi wisata yang sesuai dengan minat, lokasi, rating, hari kunjungan,
                            dan kondisi cuaca secara otomatis.
                        </p>

                        <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                            @auth
                                <a
                                    href="{{ route('tourhub.recommendation.index') }}"
                                    class="inline-flex items-center justify-center rounded-2xl bg-white px-7 py-4 text-sm font-black text-slate-950 shadow-2xl shadow-white/10 transition hover:-translate-y-0.5 hover:bg-blue-50"
                                >
                                    🔎 Cari Rekomendasi Wisata
                                </a>

                                <a
                                    href="{{ route('user.dashboard') }}"
                                    class="inline-flex items-center justify-center rounded-2xl border border-white/15 bg-white/10 px-7 py-4 text-sm font-black text-white backdrop-blur transition hover:bg-white/15"
                                >
                                    Lihat Dashboard
                                </a>
                            @else
                                <a
                                    href="{{ route('user.login') }}"
                                    class="inline-flex items-center justify-center rounded-2xl bg-white px-7 py-4 text-sm font-black text-slate-950 shadow-2xl shadow-white/10 transition hover:-translate-y-0.5 hover:bg-blue-50"
                                >
                                    🔎 Mulai Cari Rekomendasi
                                </a>

                                <a
                                    href="{{ route('user.register') }}"
                                    class="inline-flex items-center justify-center rounded-2xl border border-white/15 bg-white/10 px-7 py-4 text-sm font-black text-white backdrop-blur transition hover:bg-white/15"
                                >
                                    Buat Akun Gratis
                                </a>
                            @endauth
                        </div>

                        <div class="mt-9 grid max-w-2xl grid-cols-2 gap-3 sm:grid-cols-4">
                            {{--
                                Tampilan lama:
                                <div class="rounded-3xl border border-white/10 bg-white/10 p-4 text-white backdrop-blur">
                                    <p class="text-2xl font-black">Bali</p>
                                    <p class="mt-1 text-xs font-semibold text-slate-300">Fokus wilayah</p>
                                </div>

                                Catatan:
                                Tampilan lama tidak dihapus, hanya diganti style aktif di bawah agar teks subtitle
                                tidak terlihat buyar saat berada di area gradasi putih hero.
                            --}}

                            <div class="hero-stat-card rounded-3xl p-4">
                                <p class="hero-stat-title text-2xl font-black tracking-tight">Bali</p>
                                <p class="hero-stat-subtitle mt-1 text-xs font-extrabold">Fokus wilayah</p>
                            </div>

                            <div class="hero-stat-card rounded-3xl p-4">
                                <p class="hero-stat-title text-2xl font-black tracking-tight">CBF</p>
                                <p class="hero-stat-subtitle mt-1 text-xs font-extrabold">Preferensi</p>
                            </div>

                            <div class="hero-stat-card rounded-3xl p-4">
                                <p class="hero-stat-title text-2xl font-black tracking-tight">CARS</p>
                                <p class="hero-stat-subtitle mt-1 text-xs font-extrabold">Konteks</p>
                            </div>

                            <div class="hero-stat-card rounded-3xl p-4">
                                <p class="hero-stat-title text-2xl font-black tracking-tight">BMKG</p>
                                <p class="hero-stat-subtitle mt-1 text-xs font-extrabold">Cuaca</p>
                            </div>
                        </div>
                    </div>

                    {{-- Luxury Planner Preview --}}
                    <div class="relative">
                        <div class="absolute -left-8 -top-8 hidden h-36 w-36 rounded-full bg-blue-500/30 blur-3xl lg:block"></div>
                        <div class="absolute -bottom-8 -right-8 hidden h-40 w-40 rounded-full bg-emerald-400/20 blur-3xl lg:block"></div>

                        <div class="relative rounded-[2.3rem] border border-white/20 bg-white/92 p-5 shadow-luxury backdrop-blur-2xl">
                            <div class="rounded-[1.8rem] bg-slate-950 p-6 text-white">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-black uppercase tracking-[0.16em] text-blue-200">Travel Planner</p>
                                        <h2 class="mt-2 text-3xl font-black tracking-tight">Cari wisata Bali</h2>
                                    </div>

                                    <span class="rounded-full bg-blue-600 px-3 py-1 text-xs font-black">Otomatis</span>
                                </div>

                                <div class="mt-7 grid gap-4">
                                    <div class="rounded-3xl bg-white p-5 text-slate-950">
                                        <p class="text-xs font-black uppercase tracking-wider text-slate-500">Preferensi</p>
                                        <p class="mt-2 text-xl font-black">Alam • Budaya • Rekreasi</p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="rounded-3xl bg-white/10 p-5 ring-1 ring-white/10">
                                            <p class="text-xs text-slate-300">Lokasi</p>
                                            <p class="mt-2 text-xl font-black">Ubud</p>
                                        </div>

                                        <div class="rounded-3xl bg-white/10 p-5 ring-1 ring-white/10">
                                            <p class="text-xs text-slate-300">Cuaca</p>
                                            <p class="mt-2 text-xl font-black">BMKG</p>
                                        </div>
                                    </div>

                                    <div class="overflow-hidden rounded-3xl bg-blue-600">
                                        <div class="p-5">
                                            <p class="text-base font-black">Hasil rekomendasi dipersonalisasi setelah login.</p>
                                            <p class="mt-2 text-sm leading-6 text-blue-100">
                                                Riwayat pencarian dan hasil ranking tersimpan di dashboard pengguna.
                                            </p>
                                        </div>

                                        <div class="grid grid-cols-3 border-t border-white/10">
                                            <div class="p-4">
                                                <p class="text-xs text-blue-100">Rating</p>
                                                <p class="font-black">4.5+</p>
                                            </div>
                                            <div class="border-x border-white/10 p-4">
                                                <p class="text-xs text-blue-100">Output</p>
                                                <p class="font-black">Top-N</p>
                                            </div>
                                            <div class="p-4">
                                                <p class="text-xs text-blue-100">Maps</p>
                                                <p class="font-black">Ready</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 grid grid-cols-3 gap-3 text-center">
                                <div class="rounded-3xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                    <p class="text-lg font-black">🎯</p>
                                    <p class="mt-1 text-xs font-bold text-slate-500">Personal</p>
                                </div>
                                <div class="rounded-3xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                    <p class="text-lg font-black">🌦️</p>
                                    <p class="mt-1 text-xs font-bold text-slate-500">Cuaca</p>
                                </div>
                                <div class="rounded-3xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                    <p class="text-lg font-black">⭐</p>
                                    <p class="mt-1 text-xs font-bold text-slate-500">Rating</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Destination Showcase --}}
        <section id="destinasi" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-sm font-black uppercase tracking-[0.18em] text-blue-700">Inspirasi Wisata</p>
                    <h2 class="mt-3 text-balance text-4xl font-black tracking-tight text-slate-950 sm:text-5xl">
                        Pilihan kategori destinasi
                    </h2>
                    <p class="mt-4 max-w-2xl text-sm font-medium leading-7 text-slate-600">
                        Lihat gambaran kategori wisata. Untuk mendapatkan rekomendasi yang sesuai dengan kebutuhan,
                        masuk ke fitur rekomendasi setelah login.
                    </p>
                </div>

                @auth
                    <a href="{{ route('tourhub.recommendation.index') }}" class="rounded-2xl bg-slate-950 px-6 py-4 text-sm font-black text-white shadow-lg shadow-slate-900/20 transition hover:-translate-y-0.5 hover:bg-blue-700">
                        Cari Sekarang
                    </a>
                @else
                    <a href="{{ route('user.login') }}" class="rounded-2xl bg-slate-950 px-6 py-4 text-sm font-black text-white shadow-lg shadow-slate-900/20 transition hover:-translate-y-0.5 hover:bg-blue-700">
                        Login untuk Rekomendasi
                    </a>
                @endauth
            </div>

            <div class="mt-10 grid gap-6 lg:grid-cols-3">
                <article class="group overflow-hidden rounded-[2.2rem] border border-white bg-white shadow-soft ring-1 ring-slate-900/5 transition duration-500 hover:-translate-y-2 hover:shadow-luxury">
                    <div class="relative h-72 overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?auto=format&fit=crop&w=1400&q=88" alt="Alam Bali" class="h-full w-full object-cover transition duration-700 group-hover:scale-110">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/10 to-transparent"></div>
                        <div class="absolute bottom-6 left-6 right-6">
                            <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-black text-white backdrop-blur">Pantai • Air Terjun • Gunung</span>
                            <h3 class="mt-4 text-3xl font-black tracking-tight text-white">Alam Bali</h3>
                        </div>
                    </div>
                    <div class="p-6">
                        <p class="text-sm font-medium leading-7 text-slate-600">
                            Cocok untuk wisatawan yang ingin menikmati panorama alam, pantai, air terjun, sawah terasering, dan pegunungan.
                        </p>
                    </div>
                </article>

                <article class="group overflow-hidden rounded-[2.2rem] border border-white bg-white shadow-soft ring-1 ring-slate-900/5 transition duration-500 hover:-translate-y-2 hover:shadow-luxury">
                    <div class="relative h-72 overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1555400038-63f5ba517a47?auto=format&fit=crop&w=1400&q=88" alt="Budaya Bali" class="h-full w-full object-cover transition duration-700 group-hover:scale-110">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/10 to-transparent"></div>
                        <div class="absolute bottom-6 left-6 right-6">
                            <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-black text-white backdrop-blur">Pura • Desa Adat • Sejarah</span>
                            <h3 class="mt-4 text-3xl font-black tracking-tight text-white">Budaya Bali</h3>
                        </div>
                    </div>
                    <div class="p-6">
                        <p class="text-sm font-medium leading-7 text-slate-600">
                            Cocok untuk mengenal pura, desa adat, tradisi lokal, pertunjukan seni, dan wisata sejarah khas Bali.
                        </p>
                    </div>
                </article>

                <article class="group overflow-hidden rounded-[2.2rem] border border-white bg-white shadow-soft ring-1 ring-slate-900/5 transition duration-500 hover:-translate-y-2 hover:shadow-luxury">
                    <div class="relative h-72 overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1544644181-1484b3fdfc62?auto=format&fit=crop&w=1400&q=88" alt="Rekreasi Bali" class="h-full w-full object-cover transition duration-700 group-hover:scale-110">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/10 to-transparent"></div>
                        <div class="absolute bottom-6 left-6 right-6">
                            <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-black text-white backdrop-blur">Keluarga • Populer • Aktivitas</span>
                            <h3 class="mt-4 text-3xl font-black tracking-tight text-white">Rekreasi</h3>
                        </div>
                    </div>
                    <div class="p-6">
                        <p class="text-sm font-medium leading-7 text-slate-600">
                            Cocok untuk wisata keluarga, aktivitas santai, destinasi populer, dan pengalaman hiburan.
                        </p>
                    </div>
                </article>
            </div>
        </section>

        {{-- Feature Section --}}
        <section id="fitur" class="relative overflow-hidden bg-white py-20">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_15%_20%,_rgba(37,99,235,0.08),_transparent_30%),radial-gradient(circle_at_85%_20%,_rgba(20,184,166,0.08),_transparent_32%)]"></div>

            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-3xl text-center">
                    <p class="text-sm font-black uppercase tracking-[0.18em] text-blue-700">Fitur Utama</p>
                    <h2 class="mt-3 text-balance text-4xl font-black tracking-tight text-slate-950 sm:text-5xl">
                        Dibuat untuk pengalaman wisata yang lebih praktis
                    </h2>
                </div>

                <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-[2rem] border border-slate-200 bg-slate-50 p-7 shadow-soft transition hover:-translate-y-1 hover:bg-white">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-100 text-2xl">🎯</div>
                        <h3 class="mt-5 text-lg font-black">Sesuai Preferensi</h3>
                        <p class="mt-3 text-sm font-medium leading-7 text-slate-600">
                            Rekomendasi disesuaikan dengan kategori dan kata kunci yang dipilih wisatawan.
                        </p>
                    </div>

                    <div class="rounded-[2rem] border border-slate-200 bg-slate-50 p-7 shadow-soft transition hover:-translate-y-1 hover:bg-white">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-100 text-2xl">🌦️</div>
                        <h3 class="mt-5 text-lg font-black">Cuaca Otomatis</h3>
                        <p class="mt-3 text-sm font-medium leading-7 text-slate-600">
                            Kondisi cuaca digunakan untuk membantu menyesuaikan pilihan destinasi.
                        </p>
                    </div>

                    <div class="rounded-[2rem] border border-slate-200 bg-slate-50 p-7 shadow-soft transition hover:-translate-y-1 hover:bg-white">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-100 text-2xl">⭐</div>
                        <h3 class="mt-5 text-lg font-black">Rating Destinasi</h3>
                        <p class="mt-3 text-sm font-medium leading-7 text-slate-600">
                            Hasil rekomendasi mempertimbangkan rating dan popularitas destinasi.
                        </p>
                    </div>

                    <div class="rounded-[2rem] border border-slate-200 bg-slate-50 p-7 shadow-soft transition hover:-translate-y-1 hover:bg-white">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-100 text-2xl">🕘</div>
                        <h3 class="mt-5 text-lg font-black">Riwayat Pencarian</h3>
                        <p class="mt-3 text-sm font-medium leading-7 text-slate-600">
                            Setiap pencarian rekomendasi tersimpan pada dashboard pengguna.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- How it works --}}
        <section id="cara-kerja" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div>
                    <p class="text-sm font-black uppercase tracking-[0.18em] text-blue-700">Cara Kerja</p>
                    <h2 class="mt-3 text-balance text-4xl font-black tracking-tight text-slate-950 sm:text-5xl">
                        Dari preferensi menjadi rekomendasi
                    </h2>
                    <p class="mt-5 max-w-xl text-sm font-medium leading-7 text-slate-600">
                        Pengguna cukup memilih kategori, lokasi, rating, dan rencana kunjungan. Sistem akan mengolah
                        data destinasi dan menampilkan pilihan wisata yang paling relevan.
                    </p>

                    <div class="mt-8 rounded-[2rem] bg-slate-950 p-6 text-white shadow-luxury">
                        <p class="text-sm font-bold text-slate-300">Platform ini dirancang untuk membantu pengguna awam memilih destinasi secara mudah tanpa perlu memahami proses teknis di belakangnya.</p>
                    </div>
                </div>

                <div class="grid gap-4">
                    <div class="flex gap-4 rounded-[2rem] border border-white bg-white p-6 shadow-soft ring-1 ring-slate-900/5">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-lg font-black text-blue-700">1</div>
                        <div>
                            <h3 class="text-lg font-black">Pilih preferensi</h3>
                            <p class="mt-2 text-sm font-medium leading-6 text-slate-600">Tentukan kategori, lokasi, rating minimal, dan kata kunci wisata.</p>
                        </div>
                    </div>

                    <div class="flex gap-4 rounded-[2rem] border border-white bg-white p-6 shadow-soft ring-1 ring-slate-900/5">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-lg font-black text-blue-700">2</div>
                        <div>
                            <h3 class="text-lg font-black">Sistem menyesuaikan konteks</h3>
                            <p class="mt-2 text-sm font-medium leading-6 text-slate-600">Cuaca, hari kunjungan, dan kondisi high season ikut dipertimbangkan.</p>
                        </div>
                    </div>

                    <div class="flex gap-4 rounded-[2rem] border border-white bg-white p-6 shadow-soft ring-1 ring-slate-900/5">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-lg font-black text-blue-700">3</div>
                        <div>
                            <h3 class="text-lg font-black">Dapatkan rekomendasi</h3>
                            <p class="mt-2 text-sm font-medium leading-6 text-slate-600">Hasil destinasi ditampilkan dalam bentuk ranking agar mudah dipilih.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <section id="mulai" class="mx-auto max-w-7xl px-4 pb-20 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-[2.7rem] bg-slate-950 p-8 text-white shadow-luxury md:p-12">
                <div class="absolute inset-0">
                    <img src="https://images.unsplash.com/photo-1570789210967-2cac24afeb00?auto=format&fit=crop&w=2200&q=85" alt="Bali beach" class="h-full w-full object-cover opacity-25">
                    <div class="absolute inset-0 bg-gradient-to-r from-slate-950 via-slate-950/90 to-slate-950/50"></div>
                </div>

                <div class="relative grid items-center gap-8 md:grid-cols-[1fr_auto]">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-blue-200">Mulai Sekarang</p>
                        <h2 class="mt-3 text-balance text-4xl font-black tracking-tight sm:text-5xl">
                            Siap mencari destinasi yang cocok?
                        </h2>
                        <p class="mt-4 max-w-2xl text-sm font-medium leading-7 text-slate-300">
                            Masuk ke akun TourHub untuk menggunakan fitur rekomendasi wisata dan menyimpan riwayat pencarian.
                        </p>
                    </div>

                    @auth
                        <a href="{{ route('tourhub.recommendation.index') }}" class="rounded-2xl bg-white px-7 py-4 text-sm font-black text-slate-950 shadow-2xl shadow-white/10 transition hover:-translate-y-0.5 hover:bg-blue-50">
                            Cari Rekomendasi
                        </a>
                    @else
                        <a href="{{ route('user.login') }}" class="rounded-2xl bg-white px-7 py-4 text-sm font-black text-slate-950 shadow-2xl shadow-white/10 transition hover:-translate-y-0.5 hover:bg-blue-50">
                            Login dan Mulai
                        </a>
                    @endauth
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white/80 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-8 text-sm font-semibold text-slate-500 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8">
            <p>© {{ date('Y') }} TourHub Bali. Platform Rekomendasi Wisata.</p>
            <p>CBF + CARS • Cuaca BMKG • Riwayat Pengguna</p>
        </div>
    </footer>
</body>
</html>
