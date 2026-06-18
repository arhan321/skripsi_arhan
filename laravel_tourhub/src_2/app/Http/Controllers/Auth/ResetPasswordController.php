<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

final class ResetPasswordController extends Controller
{
    /**
     * Masa berlaku link reset password dalam menit.
     */
    private const TOKEN_EXPIRE_MINUTES = 60;

    /**
     * Menampilkan halaman input password baru.
     */
    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /**
     * Memproses password baru.
     */
    public function reset(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255', 'exists:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'email.exists' => 'Email tidak ditemukan pada sistem TourHub.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }

        $email = mb_strtolower(trim((string) $request->input('email')));
        $plainToken = (string) $request->input('token');

        $tokenRecord = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (! $tokenRecord) {
            return back()
                ->withErrors(['email' => 'Token reset password tidak ditemukan. Silakan minta link reset baru.'])
                ->withInput($request->only('email'));
        }

        $createdAt = $tokenRecord->created_at ? now()->parse($tokenRecord->created_at) : null;

        if (! $createdAt || $createdAt->diffInMinutes(now()) > self::TOKEN_EXPIRE_MINUTES) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            return back()
                ->withErrors(['email' => 'Link reset password sudah kedaluwarsa. Silakan minta link baru.'])
                ->withInput($request->only('email'));
        }

        if (! Hash::check($plainToken, (string) $tokenRecord->token)) {
            return back()
                ->withErrors(['email' => 'Token reset password tidak valid.'])
                ->withInput($request->only('email'));
        }

        $user = User::query()->where('email', $email)->firstOrFail();

        $user->forceFill([
            'password' => Hash::make((string) $request->input('password')),
            'remember_token' => null,
        ])->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return redirect()
            ->route('user.login')
            ->with('status', 'Password berhasil direset. Silakan login dengan password baru.');
    }
}
