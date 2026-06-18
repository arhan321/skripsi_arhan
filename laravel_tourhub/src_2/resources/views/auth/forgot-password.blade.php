<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Lupa Password - TourHub Bali</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            input { outline: none; }
            input:focus { box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.14); }
            .soft-grid {
                background-image:
                    linear-gradient(rgba(15, 23, 42, 0.04) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(15, 23, 42, 0.04) 1px, transparent 1px);
                background-size: 28px 28px;
            }
        </style>
    </head>

    <body class="min-h-screen bg-slate-100 text-slate-950 antialiased">
        <div class="soft-grid min-h-screen">
            <header class="border-b border-slate-200 bg-white/90 backdrop-blur-xl">
                <div class="mx-auto flex min-h-[72px] max-w-6xl items-center justify-between px-6">
                    <a href="{{ route('user.login') }}" class="text-xl font-black tracking-tight text-slate-950">
                        TourHub Bali
                    </a>

                    <div class="flex items-center gap-2 text-sm font-bold">
                        <a href="{{ route('user.login') }}" class="rounded-2xl px-4 py-2 text-slate-700 hover:bg-slate-100">
                            Login
                        </a>
                        <a href="{{ route('user.register') }}" class="rounded-2xl bg-slate-950 px-4 py-2 text-white hover:bg-slate-800">
                            Register
                        </a>
                    </div>
                </div>
            </header>

            <main class="mx-auto max-w-6xl px-6 py-10">
                <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-xl shadow-slate-900/10">
                    <div class="grid grid-cols-1 lg:grid-cols-2">
                        <section class="relative overflow-hidden bg-slate-950 p-8 text-white md:p-10">
                            <div class="soft-grid absolute inset-0 opacity-20"></div>
                            <div class="relative flex min-h-[560px] flex-col justify-between">
                                <div>
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-2xl font-black text-slate-950">
                                        T
                                    </div>

                                    <h1 class="mt-8 text-4xl font-black tracking-tight md:text-5xl">
                                        Reset Password TourHub Bali
                                    </h1>

                                    <p class="mt-5 max-w-md text-sm leading-6 text-slate-300">
                                        Masukkan email akun TourHub kamu. Sistem akan mengirim link reset password ke email tersebut.
                                    </p>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-3xl bg-white/10 p-5 backdrop-blur">
                                        <p class="text-xs text-slate-300">Email</p>
                                        <p class="mt-1 font-black">SMTP Gmail</p>
                                    </div>
                                    <div class="rounded-3xl bg-white/10 p-5 backdrop-blur">
                                        <p class="text-xs text-slate-300">Token</p>
                                        <p class="mt-1 font-black">Berlaku 60 menit</p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="p-8 md:p-10">
                            <div class="mx-auto max-w-md">
                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-950 text-xl font-black text-white shadow-lg shadow-slate-900/20">
                                    T
                                </div>

                                <p class="mt-8 text-sm font-black tracking-wider text-blue-600 uppercase">
                                    Lupa Password
                                </p>
                                <h2 class="mt-2 text-3xl font-black tracking-tight text-slate-950">
                                    Kirim Link Reset
                                </h2>
                                <p class="mt-3 text-sm leading-6 text-slate-600">
                                    Link reset password akan dikirim melalui email. Setelah membuka link, kamu bisa membuat password baru.
                                </p>

                                @if (session('status'))
                                    <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-700">
                                        {{ session('status') }}
                                    </div>
                                @endif

                                @if ($errors->any())
                                    <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                                        <p class="font-black">Terjadi error</p>
                                        <ul class="mt-2 list-disc space-y-1 pl-5">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <form method="POST" action="{{ route('password.email') }}" class="mt-7 space-y-5">
                                    @csrf

                                    <div>
                                        <label for="email" class="mb-2 block text-sm font-black text-slate-900">Email</label>
                                        <div class="relative">
                                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">✉️</span>
                                            <input
                                                id="email"
                                                type="email"
                                                name="email"
                                                value="{{ old('email') }}"
                                                required
                                                autofocus
                                                placeholder="email@example.com"
                                                class="w-full rounded-2xl border border-slate-200 bg-white py-4 pl-12 pr-4 text-sm font-semibold text-slate-900 placeholder:text-slate-400"
                                            />
                                        </div>
                                    </div>

                                    <button
                                        type="submit"
                                        class="w-full rounded-2xl bg-slate-950 px-5 py-4 text-sm font-black text-white shadow-lg shadow-slate-900/20 transition hover:-translate-y-0.5 hover:bg-slate-800"
                                    >
                                        Kirim Link Reset Password
                                    </button>
                                </form>

                                <a href="{{ route('user.login') }}" class="mt-5 flex w-full justify-center rounded-2xl border border-slate-200 px-5 py-4 text-sm font-bold text-slate-700 hover:bg-slate-50">
                                    Kembali ke Login
                                </a>
                            </div>
                        </section>
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
