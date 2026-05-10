<x-layouts.tourhub-auth title="Login - TourHub Bali">
    <div class="max-w-md mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <div class="mb-6">
            <p class="text-sm text-slate-500">Masuk akun wisatawan</p>
            <h1 class="text-2xl font-bold">Login TourHub</h1>
            <p class="text-sm text-slate-600 mt-1">
                Login untuk melihat dan menyimpan riwayat rekomendasi wisata.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-xl bg-red-100 text-red-700 p-4 text-sm">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('user.login.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-semibold mb-1">Email</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    class="w-full rounded-xl border-slate-300"
                    placeholder="email@example.com"
                >
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold mb-1">Password</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    class="w-full rounded-xl border-slate-300"
                    placeholder="Password"
                >
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300">
                Ingat saya
            </label>

            <button type="submit" class="w-full rounded-xl bg-slate-900 text-white py-3 font-semibold hover:bg-slate-700">
                Login
            </button>
        </form>

        <p class="text-sm text-center text-slate-600 mt-5">
            Belum punya akun?
            <a href="{{ route('user.register') }}" class="font-semibold text-blue-600 hover:underline">
                Register
            </a>
        </p>
    </div>
</x-layouts.tourhub-auth>
