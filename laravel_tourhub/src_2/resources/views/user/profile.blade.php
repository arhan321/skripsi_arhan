<x-layouts.tourhub-auth title="Edit Profile - TourHub Bali">
    <div class="mx-auto max-w-5xl">
        <div class="overflow-hidden rounded-[2rem] bg-slate-950 shadow-2xl shadow-slate-900/10">
            <div class="relative px-6 py-8 sm:px-8">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(37,99,235,0.32),_transparent_34%),radial-gradient(circle_at_bottom_right,_rgba(20,184,166,0.24),_transparent_32%)]"></div>

                <div class="relative flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-xs font-black text-blue-100 ring-1 ring-white/10">
                            👤 Profile User
                        </div>

                        <h1 class="mt-4 text-3xl font-black tracking-tight text-white sm:text-4xl">
                            Edit Profile Akun
                        </h1>

                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300">
                            Kelola nama, email, dan password akun TourHub Bali kamu.
                        </p>
                    </div>

                    <div class="flex gap-3">
                        <a href="{{ route('user.dashboard') }}" class="rounded-2xl bg-white px-5 py-3 text-sm font-black text-slate-950 transition hover:bg-blue-50">
                            Dashboard
                        </a>

                        <a href="{{ route('tourhub.recommendation.index') }}" class="rounded-2xl bg-white/10 px-5 py-3 text-sm font-black text-white ring-1 ring-white/15 transition hover:bg-white/15">
                            Rekomendasi
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->has('profile'))
            <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700">
                {{ $errors->first('profile') }}
            </div>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-[0.85fr_1.15fr]">
            <aside class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-xl shadow-slate-900/5">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-950 text-2xl font-black text-white shadow-lg shadow-slate-900/20">
                        {{ strtoupper(mb_substr((string) ($user->name ?? 'T'), 0, 1)) }}
                    </div>

                    <div>
                        <h2 class="text-xl font-black text-slate-950">{{ $user->name }}</h2>
                        <p class="mt-1 text-sm font-semibold text-slate-500">{{ $user->email }}</p>
                    </div>
                </div>

                <div class="mt-6 space-y-3">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase tracking-wider text-slate-500">Status Akun</p>
                        <p class="mt-1 text-sm font-black text-slate-950">Aktif</p>
                    </div>

                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase tracking-wider text-slate-500">Fitur</p>
                        <p class="mt-1 text-sm font-black text-slate-950">Riwayat Rekomendasi</p>
                    </div>

                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase tracking-wider text-slate-500">Keamanan</p>
                        <p class="mt-1 text-sm font-black text-slate-950">Update Password Opsional</p>
                    </div>
                </div>
            </aside>

            <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-xl shadow-slate-900/5">
                <div class="mb-6">
                    <p class="text-sm font-black uppercase tracking-wider text-blue-700">Form Profile</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-950">Perbarui Data Akun</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Kosongkan password baru jika tidak ingin mengganti password.
                    </p>
                </div>

                <form method="POST" action="{{ route('user.profile.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="name" class="mb-2 block text-sm font-black text-slate-950">Nama Lengkap</label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name', $user->name) }}"
                            autocomplete="name"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm font-semibold text-slate-950 outline-none transition focus:border-blue-400 focus:bg-white focus:ring-4 focus:ring-blue-100"
                            placeholder="Masukkan nama lengkap"
                            required
                        />
                        @error('name')
                            <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="mb-2 block text-sm font-black text-slate-950">Email</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email', $user->email) }}"
                            autocomplete="email"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm font-semibold text-slate-950 outline-none transition focus:border-blue-400 focus:bg-white focus:ring-4 focus:ring-blue-100"
                            placeholder="email@example.com"
                            required
                        />
                        @error('email')
                            <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="rounded-3xl border border-blue-100 bg-blue-50 p-5">
                        <div class="flex gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white">🔒</div>
                            <div>
                                <h3 class="font-black text-slate-950">Ganti Password</h3>
                                <p class="mt-1 text-sm leading-6 text-slate-600">
                                    Isi bagian ini hanya jika kamu ingin mengganti password akun.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="current_password" class="mb-2 block text-sm font-black text-slate-950">Password Lama</label>
                        <input
                            id="current_password"
                            name="current_password"
                            type="password"
                            autocomplete="current-password"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm font-semibold text-slate-950 outline-none transition focus:border-blue-400 focus:bg-white focus:ring-4 focus:ring-blue-100"
                            placeholder="Wajib diisi jika mengganti password"
                        />
                        @error('current_password')
                            <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label for="password" class="mb-2 block text-sm font-black text-slate-950">Password Baru</label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="new-password"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm font-semibold text-slate-950 outline-none transition focus:border-blue-400 focus:bg-white focus:ring-4 focus:ring-blue-100"
                                placeholder="Minimal 8 karakter"
                            />
                            @error('password')
                                <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="mb-2 block text-sm font-black text-slate-950">Konfirmasi Password Baru</label>
                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                autocomplete="new-password"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm font-semibold text-slate-950 outline-none transition focus:border-blue-400 focus:bg-white focus:ring-4 focus:ring-blue-100"
                                placeholder="Ulangi password baru"
                            />
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 pt-2 sm:flex-row">
                        <button type="submit" class="rounded-2xl bg-slate-950 px-6 py-4 text-sm font-black text-white shadow-lg shadow-slate-900/10 transition hover:-translate-y-0.5 hover:bg-blue-700">
                            Simpan Perubahan
                        </button>

                        <a href="{{ route('user.dashboard') }}" class="rounded-2xl border border-slate-200 bg-white px-6 py-4 text-center text-sm font-black text-slate-700 transition hover:bg-slate-50">
                            Batal
                        </a>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-layouts.tourhub-auth>
