<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Throwable;

final class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        Log::info('[USER_PROFILE] Open profile page', [
            'user_id' => $user?->id,
            'email' => $user?->email,
            'view' => 'user.profile',
            'view_exists' => view()->exists('user.profile'),
        ]);

        return view('user.profile', [
            'user' => $user,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        Log::info('[USER_PROFILE] Submit profile update started', [
            'user_id' => $user?->id,
            'old_email' => $user?->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan oleh akun lain.',
            'current_password.required_with' => 'Password lama wajib diisi jika ingin mengganti password.',
            'current_password.current_password' => 'Password lama tidak sesuai.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak sesuai.',
        ]);

        try {
            DB::beginTransaction();

            $oldEmail = (string) $user->email;
            $newEmail = mb_strtolower(trim((string) $validated['email']));

            $user->name = trim((string) $validated['name']);
            $user->email = $newEmail;

            if ($oldEmail !== $newEmail && Schema::hasColumn('users', 'email_verified_at')) {
                $user->email_verified_at = null;
            }

            if (! empty($validated['password'])) {
                $user->password = Hash::make((string) $validated['password']);

                if (Schema::hasColumn('users', 'remember_token')) {
                    $user->remember_token = null;
                }
            }

            $user->save();

            DB::commit();

            Log::info('[USER_PROFILE] Profile update success', [
                'user_id' => $user->id,
                'old_email' => $oldEmail,
                'new_email' => $user->email,
                'password_changed' => ! empty($validated['password']),
            ]);

            return redirect()
                ->route('user.profile.edit')
                ->with('success', 'Profile berhasil diperbarui.');
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('[USER_PROFILE] Profile update failed', [
                'user_id' => $user?->id,
                'email' => $user?->email,
                'error_class' => $e::class,
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            report($e);

            return back()
                ->withInput($request->except(['password', 'password_confirmation', 'current_password']))
                ->withErrors([
                    'profile' => 'Gagal memperbarui profile. Silakan coba lagi.',
                ]);
        }
    }
}
