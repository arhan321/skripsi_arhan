<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'TourHub Bali' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-900">
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between gap-4">
            <a href="{{ route('user.dashboard') }}" class="font-bold text-xl">
                TourHub Bali
            </a>

            <nav class="flex items-center gap-3 text-sm">
                @auth
                    <a href="{{ route('tourhub.recommendation.index') }}" class="text-slate-600 hover:text-slate-950">
                        Rekomendasi
                    </a>

                    <a href="{{ route('user.dashboard') }}" class="text-slate-600 hover:text-slate-950">
                        Dashboard
                    </a>

                    <form method="POST" action="{{ route('user.logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-slate-900 text-white px-4 py-2 hover:bg-slate-700">
                            Logout
                        </button>
                    </form>
                @else
                    <a href="{{ route('user.login') }}" class="text-slate-600 hover:text-slate-950">
                        Login
                    </a>

                    <a href="{{ route('user.register') }}" class="rounded-lg bg-slate-900 text-white px-4 py-2 hover:bg-slate-700">
                        Register
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-6 py-8">
        @if (session('success'))
            <div class="mb-5 rounded-xl bg-emerald-100 border border-emerald-200 text-emerald-700 p-4 text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{ $slot }}
    </main>
</body>
</html>
