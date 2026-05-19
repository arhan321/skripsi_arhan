<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

final class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:150', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        /*
         * Jika project menggunakan Spatie Permission dan role "user" tersedia,
         * user baru otomatis diberikan role user.
         *
         * Jika role belum ada, bagian ini akan dilewati agar register tetap berhasil.
         */
        if (class_exists(Role::class) && Role::query()->where('name', 'user')->exists()) {
            $user->assignRole('user');
        }

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()
            ->route('user.dashboard')
            ->with('success', 'Registrasi berhasil. Selamat datang di TourHub Bali.');
    }
}
