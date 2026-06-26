<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title ?? 'TourHub Bali' }}</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        html {
            scroll-behavior: smooth;
        }

        body {
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        .tourhub-glass {
            background: rgba(255, 255, 255, 0.86);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .tourhub-mobile-panel {
            max-height: 0;
            opacity: 0;
            transform: translateY(-10px) scale(0.98);
            overflow: hidden;
            pointer-events: none;
            transition:
                max-height 420ms cubic-bezier(0.22, 1, 0.36, 1),
                opacity 280ms ease,
                transform 320ms cubic-bezier(0.22, 1, 0.36, 1),
                padding 320ms ease;
        }

        .tourhub-mobile-panel.is-open {
            max-height: 640px;
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }

        .tourhub-menu-line {
            transform-origin: center;
            transition:
                transform 260ms cubic-bezier(0.22, 1, 0.36, 1),
                opacity 200ms ease;
        }

        .tourhub-menu-button.is-open .tourhub-menu-line:nth-child(1) {
            transform: translateY(6px) rotate(45deg);
        }

        .tourhub-menu-button.is-open .tourhub-menu-line:nth-child(2) {
            opacity: 0;
            transform: scaleX(0.4);
        }

        .tourhub-menu-button.is-open .tourhub-menu-line:nth-child(3) {
            transform: translateY(-6px) rotate(-45deg);
        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }

            .tourhub-mobile-panel,
            .tourhub-menu-line,
            .tourhub-menu-button,
            .tourhub-nav-link,
            .tourhub-button {
                transition: none !important;
            }
        }
    </style>
</head>

<body class="min-h-screen bg-gradient-to-br from-slate-100 via-sky-50 to-white text-slate-900">
    <header class="tourhub-glass sticky top-0 z-50 border-b border-white/70 shadow-[0_10px_35px_rgba(15,23,42,0.07)]">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="flex min-h-[76px] items-center justify-between gap-3">
                {{-- Brand: disamakan dengan logo pada halaman rekomendasi --}}
                <a
                    href="{{ route('landing') }}"
                    class="group flex min-w-0 items-center gap-3 text-slate-950 transition duration-300 hover:text-blue-700"
                    aria-label="TourHub Bali"
                >
                    <span class="relative flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-slate-950 via-blue-950 to-blue-700 text-xl font-black text-white shadow-lg shadow-blue-900/20 transition duration-300 group-hover:-translate-y-0.5 group-hover:shadow-xl group-hover:shadow-blue-900/25">
                        <span class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(255,255,255,0.34),transparent_35%)]"></span>
                        <span class="relative">T</span>
                    </span>

                    <span class="min-w-0">
                        <span class="flex items-center gap-2">
                            <span class="truncate text-xl font-black leading-none tracking-tight text-slate-950 sm:text-2xl">
                                TourHub
                            </span>

                            <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-black text-blue-700 ring-1 ring-blue-200">
                                Bali
                            </span>
                        </span>

                        <span class="mt-1 block truncate text-xs font-semibold text-slate-500">
                            Temukan destinasi wisata terbaik
                        </span>
                    </span>
                </a>

                {{-- Desktop Navigation --}}
                <nav class="hidden items-center gap-1 rounded-2xl border border-white/80 bg-white/60 p-1 text-sm shadow-sm md:flex">
                    @auth
                        <a
                            href="{{ route('tourhub.recommendation.index') }}"
                            class="tourhub-nav-link {{ request()->routeIs('tourhub.recommendation.*') ? 'bg-slate-950 font-bold text-white shadow-md shadow-slate-900/15' : 'text-slate-600 hover:bg-white hover:text-slate-950 hover:shadow-sm' }} rounded-xl px-4 py-2 transition duration-300"
                        >
                            Rekomendasi
                        </a>

                        <a
                            href="{{ route('user.dashboard') }}"
                            class="tourhub-nav-link {{ request()->routeIs('user.dashboard') ? 'bg-slate-950 font-bold text-white shadow-md shadow-slate-900/15' : 'text-slate-600 hover:bg-white hover:text-slate-950 hover:shadow-sm' }} rounded-xl px-4 py-2 transition duration-300"
                        >
                            Dashboard
                        </a>

                        <a
                            href="{{ route('user.dashboard') }}#riwayat"
                            class="tourhub-nav-link text-slate-600 hover:bg-white hover:text-slate-950 hover:shadow-sm rounded-xl px-4 py-2 transition duration-300"
                        >
                            Riwayat
                        </a>

                        <a
                            href="{{ route('user.wishlist.index') }}"
                            class="tourhub-nav-link {{ request()->routeIs('user.wishlist.*') ? 'bg-slate-950 font-bold text-white shadow-md shadow-slate-900/15' : 'text-slate-600 hover:bg-white hover:text-slate-950 hover:shadow-sm' }} rounded-xl px-4 py-2 transition duration-300"
                        >
                            Wishlist
                        </a>

                        <a
                            href="{{ route('user.profile.edit') }}"
                            class="tourhub-nav-link {{ request()->routeIs('user.profile.*') ? 'bg-slate-950 font-bold text-white shadow-md shadow-slate-900/15' : 'text-slate-600 hover:bg-white hover:text-slate-950 hover:shadow-sm' }} rounded-xl px-4 py-2 transition duration-300"
                        >
                            Profile
                        </a>

                        <form method="POST" action="{{ route('user.logout') }}" class="ml-1">
                            @csrf
                            <button
                                type="submit"
                                class="tourhub-button rounded-xl bg-gradient-to-r from-slate-950 to-slate-800 px-4 py-2 font-semibold text-white shadow-md shadow-slate-900/15 transition duration-300 hover:-translate-y-0.5 hover:from-slate-800 hover:to-slate-700 hover:shadow-lg"
                            >
                                Logout
                            </button>
                        </form>
                    @else
                        <a
                            href="{{ route('user.login') }}"
                            class="tourhub-nav-link {{ request()->routeIs('user.login') || request()->routeIs('login') ? 'bg-slate-950 font-bold text-white shadow-md shadow-slate-900/15' : 'text-slate-600 hover:bg-white hover:text-slate-950 hover:shadow-sm' }} rounded-xl px-4 py-2 transition duration-300"
                        >
                            Login
                        </a>

                        <a
                            href="{{ route('user.register') }}"
                            class="tourhub-button rounded-xl bg-gradient-to-r from-slate-950 to-slate-800 px-4 py-2 font-semibold text-white shadow-md shadow-slate-900/15 transition duration-300 hover:-translate-y-0.5 hover:from-slate-800 hover:to-slate-700 hover:shadow-lg"
                        >
                            Register
                        </a>
                    @endauth
                </nav>

                {{-- Mobile Menu Button --}}
                <button
                    type="button"
                    id="tourhub-mobile-menu-button"
                    class="tourhub-menu-button inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-white/80 bg-white/75 text-slate-700 shadow-md shadow-slate-900/10 transition duration-300 hover:-translate-y-0.5 hover:bg-white hover:text-slate-950 hover:shadow-lg md:hidden"
                    aria-label="Buka menu navigasi"
                    aria-expanded="false"
                    aria-controls="tourhub-mobile-menu"
                >
                    <span class="flex h-5 w-5 flex-col items-center justify-center gap-1">
                        <span class="tourhub-menu-line block h-0.5 w-5 rounded-full bg-current"></span>
                        <span class="tourhub-menu-line block h-0.5 w-5 rounded-full bg-current"></span>
                        <span class="tourhub-menu-line block h-0.5 w-5 rounded-full bg-current"></span>
                    </span>
                </button>
            </div>

            {{-- Mobile Navigation --}}
            <div id="tourhub-mobile-menu" class="tourhub-mobile-panel md:hidden">
                <div class="pb-4 pt-2">
                    <nav class="rounded-3xl border border-white/80 bg-white/85 p-2 text-sm shadow-2xl shadow-slate-900/10 backdrop-blur-xl">
                        @auth
                            <a
                                href="{{ route('tourhub.recommendation.index') }}"
                                class="{{ request()->routeIs('tourhub.recommendation.*') ? 'bg-blue-50 font-bold text-blue-700 ring-1 ring-blue-100' : 'text-slate-700 hover:bg-slate-50 hover:text-slate-950' }} group flex items-center justify-between rounded-2xl px-4 py-3 transition duration-300"
                            >
                                <span>Rekomendasi</span>
                                <span class="text-slate-300 transition duration-300 group-hover:translate-x-0.5 group-hover:text-blue-500">›</span>
                            </a>

                            <a
                                href="{{ route('user.dashboard') }}"
                                class="{{ request()->routeIs('user.dashboard') ? 'bg-blue-50 font-bold text-blue-700 ring-1 ring-blue-100' : 'text-slate-700 hover:bg-slate-50 hover:text-slate-950' }} group mt-1 flex items-center justify-between rounded-2xl px-4 py-3 transition duration-300"
                            >
                                <span>Dashboard</span>
                                <span class="text-slate-300 transition duration-300 group-hover:translate-x-0.5 group-hover:text-blue-500">›</span>
                            </a>

                            <a
                                href="{{ route('user.dashboard') }}#riwayat"
                                class="group mt-1 flex items-center justify-between rounded-2xl px-4 py-3 text-slate-700 transition duration-300 hover:bg-slate-50 hover:text-slate-950"
                            >
                                <span>Riwayat</span>
                                <span class="text-slate-300 transition duration-300 group-hover:translate-x-0.5 group-hover:text-blue-500">›</span>
                            </a>

                            <a
                                href="{{ route('user.wishlist.index') }}"
                                class="{{ request()->routeIs('user.wishlist.*') ? 'bg-blue-50 font-bold text-blue-700 ring-1 ring-blue-100' : 'text-slate-700 hover:bg-slate-50 hover:text-slate-950' }} group mt-1 flex items-center justify-between rounded-2xl px-4 py-3 transition duration-300"
                            >
                                <span>Wishlist</span>
                                <span class="text-slate-300 transition duration-300 group-hover:translate-x-0.5 group-hover:text-blue-500">›</span>
                            </a>

                            <a
                                href="{{ route('user.profile.edit') }}"
                                class="{{ request()->routeIs('user.profile.*') ? 'bg-blue-50 font-bold text-blue-700 ring-1 ring-blue-100' : 'text-slate-700 hover:bg-slate-50 hover:text-slate-950' }} group mt-1 flex items-center justify-between rounded-2xl px-4 py-3 transition duration-300"
                            >
                                <span>Profile</span>
                                <span class="text-slate-300 transition duration-300 group-hover:translate-x-0.5 group-hover:text-blue-500">›</span>
                            </a>

                            <div class="my-2 h-px bg-gradient-to-r from-transparent via-slate-200 to-transparent"></div>

                            <form method="POST" action="{{ route('user.logout') }}">
                                @csrf
                                <button
                                    type="submit"
                                    class="tourhub-button flex w-full items-center justify-between rounded-2xl bg-gradient-to-r from-slate-950 via-slate-900 to-slate-800 px-4 py-3 text-left font-semibold text-white shadow-lg shadow-slate-900/15 transition duration-300 hover:-translate-y-0.5 hover:shadow-xl"
                                >
                                    <span>Logout</span>
                                    <span class="text-white/60">→</span>
                                </button>
                            </form>
                        @else
                            <a
                                href="{{ route('user.login') }}"
                                class="{{ request()->routeIs('user.login') || request()->routeIs('login') ? 'bg-blue-50 font-bold text-blue-700 ring-1 ring-blue-100' : 'text-slate-700 hover:bg-slate-50 hover:text-slate-950' }} group flex items-center justify-between rounded-2xl px-4 py-3 transition duration-300"
                            >
                                <span>Login</span>
                                <span class="text-slate-300 transition duration-300 group-hover:translate-x-0.5 group-hover:text-blue-500">›</span>
                            </a>

                            <a
                                href="{{ route('user.register') }}"
                                class="tourhub-button mt-2 flex items-center justify-between rounded-2xl bg-gradient-to-r from-slate-950 via-slate-900 to-slate-800 px-4 py-3 font-semibold text-white shadow-lg shadow-slate-900/15 transition duration-300 hover:-translate-y-0.5 hover:shadow-xl"
                            >
                                <span>Register</span>
                                <span class="text-white/60">→</span>
                            </a>
                        @endauth
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-8">
        @if (session('success'))
            <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-700 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        @if (session('status'))
            <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-700 shadow-sm">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700 shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        {{ $slot }}
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const menuButton = document.getElementById('tourhub-mobile-menu-button');
            const mobileMenu = document.getElementById('tourhub-mobile-menu');

            if (!menuButton || !mobileMenu) {
                return;
            }

            const openMenu = function () {
                mobileMenu.classList.add('is-open');
                menuButton.classList.add('is-open');
                menuButton.setAttribute('aria-expanded', 'true');
            };

            const closeMenu = function () {
                mobileMenu.classList.remove('is-open');
                menuButton.classList.remove('is-open');
                menuButton.setAttribute('aria-expanded', 'false');
            };

            const toggleMenu = function () {
                const isOpen = mobileMenu.classList.contains('is-open');

                if (isOpen) {
                    closeMenu();
                } else {
                    openMenu();
                }
            };

            menuButton.addEventListener('click', function (event) {
                event.stopPropagation();
                toggleMenu();
            });

            mobileMenu.addEventListener('click', function (event) {
                event.stopPropagation();
            });

            document.addEventListener('click', function () {
                closeMenu();
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeMenu();
                }
            });

            window.addEventListener('resize', function () {
                if (window.innerWidth >= 768) {
                    closeMenu();
                }
            });
        });
    </script>
</body>
</html>
