<x-layouts.tourhub-auth title="Login - TourHub Bali">
    <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/10">
        <div class="grid min-h-[620px] grid-cols-1 lg:grid-cols-2">
            {{-- Left Branding --}}
            <section
                class="relative hidden overflow-hidden bg-slate-950 p-10 text-white lg:flex lg:flex-col lg:justify-between"
            >
                <div
                    class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.45),_transparent_35%),radial-gradient(circle_at_bottom_right,_rgba(16,185,129,0.35),_transparent_35%)]"
                ></div>

                <div
                    class="absolute inset-0 opacity-20"
                    style="
                        background-image:
                            linear-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px),
                            linear-gradient(90deg, rgba(255, 255, 255, 0.08) 1px, transparent 1px);
                        background-size: 32px 32px;
                    "
                ></div>

                <div class="relative">
                    <div
                        class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-2xl font-black text-slate-950 shadow-lg"
                    >
                        T
                    </div>

                    <h1 class="mt-8 max-w-md text-4xl leading-tight font-black tracking-tight">
                        TourHub Bali Recommendation System
                    </h1>

                    <p class="mt-4 max-w-md text-sm leading-6 text-slate-300">
                        Masuk untuk mencari rekomendasi wisata Bali berbasis Content-Based Filtering dan Context-Aware
                        Recommender System.
                    </p>
                </div>

                <div class="relative grid grid-cols-2 gap-3">
                    <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/10 backdrop-blur-xl">
                        <p class="text-xs text-slate-300">Algorithm</p>
                        <p class="mt-1 font-black">CBF + CARS</p>
                    </div>

                    <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/10 backdrop-blur-xl">
                        <p class="text-xs text-slate-300">Weather</p>
                        <p class="mt-1 font-black">BMKG</p>
                    </div>

                    <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/10 backdrop-blur-xl">
                        <p class="text-xs text-slate-300">Data</p>
                        <p class="mt-1 font-black">Riwayat User</p>
                    </div>

                    <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/10 backdrop-blur-xl">
                        <p class="text-xs text-slate-300">Output</p>
                        <p class="mt-1 font-black">Top-N Wisata</p>
                    </div>
                </div>
            </section>

            {{-- Right Form --}}
            <section
                class="flex items-center justify-center bg-gradient-to-br from-white via-slate-50 to-blue-50 px-6 py-10"
            >
                <div class="w-full max-w-md">
                    <div class="mb-8 text-center lg:text-left">
                        <div
                            class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-950 text-2xl font-black text-white shadow-lg shadow-slate-900/20 lg:mx-0"
                        >
                            T
                        </div>

                        <p class="text-sm font-bold tracking-wider text-blue-600 uppercase">Masuk Akun Wisatawan</p>

                        <h2 class="mt-2 text-3xl font-black tracking-tight text-slate-950">Login TourHub</h2>

                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            Login untuk melihat rekomendasi wisata, menyimpan riwayat pencarian, dan membuka dashboard
                            user.
                        </p>
                    </div>

                    @if (session('success'))
                        <div
                            class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700"
                        >
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                            <div class="flex items-start gap-3">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-red-100">
                                    ⚠️
                                </div>

                                <div>
                                    <p class="font-black">Login gagal</p>

                                    <ul class="mt-2 list-disc space-y-1 pl-5">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('user.login.store') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label for="email" class="mb-2 block text-sm font-black text-slate-800">Email</label>

                            <div class="relative">
                                <div
                                    class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-slate-400"
                                >
                                    ✉️
                                </div>

                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    value="{{ old('email') }}"
                                    required
                                    autofocus
                                    class="w-full rounded-2xl border border-slate-200 bg-white py-3 pr-4 pl-12 text-sm font-semibold text-slate-900 shadow-sm transition outline-none placeholder:text-slate-400 focus:border-blue-400 focus:ring-4 focus:ring-blue-100"
                                    placeholder="email@example.com"
                                />
                            </div>
                        </div>

                        <div>
                            <label for="password" class="mb-2 block text-sm font-black text-slate-800">Password</label>

                            <div class="relative">
                                <div
                                    class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-slate-400"
                                >
                                    🔒
                                </div>

                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    required
                                    class="w-full rounded-2xl border border-slate-200 bg-white py-3 pr-4 pl-12 text-sm font-semibold text-slate-900 shadow-sm transition outline-none placeholder:text-slate-400 focus:border-blue-400 focus:ring-4 focus:ring-blue-100"
                                    placeholder="Masukkan password"
                                />
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-600">
                                <input
                                    type="checkbox"
                                    name="remember"
                                    value="1"
                                    class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                />

                                <span>Ingat saya</span>
                            </label>
                        </div>

                        <button
                            type="submit"
                            class="group relative w-full overflow-hidden rounded-2xl bg-slate-950 px-5 py-4 text-sm font-black text-white shadow-lg shadow-slate-900/20 transition hover:-translate-y-0.5 hover:bg-slate-800"
                        >
                            <span class="relative z-10">Login</span>
                            <span
                                class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent transition duration-700 group-hover:translate-x-full"
                            ></span>
                        </button>
                    </form>

                    <div
                        class="mt-6 rounded-2xl border border-slate-200 bg-white/70 p-4 text-center text-sm text-slate-600 shadow-sm"
                    >
                        Belum punya akun?
                        <a href="{{ route('user.register') }}" class="font-black text-blue-600 hover:underline">
                            Register sekarang
                        </a>
                    </div>

                    <div class="mt-6 grid grid-cols-3 gap-3 text-center">
                        <div class="rounded-2xl bg-white p-3 shadow-sm ring-1 ring-slate-100">
                            <p class="text-lg font-black text-slate-950">CBF</p>
                            <p class="mt-1 text-[11px] text-slate-500">Similarity</p>
                        </div>

                        <div class="rounded-2xl bg-white p-3 shadow-sm ring-1 ring-slate-100">
                            <p class="text-lg font-black text-slate-950">CARS</p>
                            <p class="mt-1 text-[11px] text-slate-500">Context</p>
                        </div>

                        <div class="rounded-2xl bg-white p-3 shadow-sm ring-1 ring-slate-100">
                            <p class="text-lg font-black text-slate-950">BMKG</p>
                            <p class="mt-1 text-[11px] text-slate-500">Weather</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-layouts.tourhub-auth>
