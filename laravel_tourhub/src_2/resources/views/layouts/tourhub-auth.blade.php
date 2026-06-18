<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{{ $title ?? 'TourHub Bali' }}</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>

    <body class="bg-slate-100 text-slate-900">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-4">
                {{--
                    Logo / brand TourHub Bali.
                    Sebelumnya diarahkan ke user.dashboard.
                    Sekarang diarahkan ke landing page utama agar saat diklik dari login/register
                    user kembali ke halaman utama.
                --}}
                <a
                    href="{{ route('landing') }}"
                    class="text-xl font-bold text-slate-950 transition hover:text-blue-700"
                >
                    TourHub Bali
                </a>

                <nav class="flex items-center gap-3 text-sm">
                    @auth
                        <a
                            href="{{ route('tourhub.recommendation.index') }}"
                            class="text-slate-600 transition hover:text-slate-950"
                        >
                            Rekomendasi
                        </a>

                        <a
                            href="{{ route('user.dashboard') }}"
                            class="text-slate-600 transition hover:text-slate-950"
                        >
                            Dashboard
                        </a>

                        <form method="POST" action="{{ route('user.logout') }}">
                            @csrf

                            <button
                                type="submit"
                                class="rounded-lg bg-slate-900 px-4 py-2 text-white transition hover:bg-slate-700"
                            >
                                Logout
                            </button>
                        </form>
                    @else
                        <a
                            href="{{ route('user.login') }}"
                            class="text-slate-600 transition hover:text-slate-950"
                        >
                            Login
                        </a>

                        <a
                            href="{{ route('user.register') }}"
                            class="rounded-lg bg-slate-900 px-4 py-2 text-white transition hover:bg-slate-700"
                        >
                            Register
                        </a>
                    @endauth
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-6 py-8">
            @if (session('success'))
                <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-100 p-4 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('status'))
                <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-100 p-4 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            {{ $slot }}
        </main>
    </body>
</html>
