<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Throwable;

final class ApiProfileController extends Controller
{
    /**
     * GET /api/user/profile
     * Mengambil data profile user login untuk mobile.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        Log::info('[API_PROFILE] Open profile data', [
            'user_id' => $user?->id,
            'email' => $user?->email,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile berhasil diambil.',
            'data' => [
                'user' => $this->formatUser($user),
            ],
        ]);
    }

    /**
     * PUT/PATCH/POST /api/user/profile
     * Update profile user dari mobile.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        Log::info('[API_PROFILE] Submit profile update started', [
            'user_id' => $user?->id,
            'old_email' => $user?->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload_keys' => array_keys($request->except([
                'current_password',
                'password',
                'password_confirmation',
            ])),
            'password_requested' => $request->filled('password'),
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

            Log::info('[API_PROFILE] Profile update success', [
                'user_id' => $user->id,
                'old_email' => $oldEmail,
                'new_email' => $user->email,
                'password_changed' => ! empty($validated['password']),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile berhasil diperbarui.',
                'data' => [
                    'user' => $this->formatUser($user->fresh()),
                    'password_changed' => ! empty($validated['password']),
                ],
            ]);
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('[API_PROFILE] Profile update failed', [
                'user_id' => $user?->id,
                'email' => $user?->email,
                'error_class' => $e::class,
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui profile. Silakan coba lagi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    private function formatUser($user): array
    {
        return [
            'id' => $user?->id,
            'name' => $user?->name,
            'email' => $user?->email,
            'email_verified_at' => $user?->email_verified_at,
            'created_at' => $user?->created_at,
            'updated_at' => $user?->updated_at,
        ];
    }
}
