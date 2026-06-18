<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Throwable;

final class ApiPasswordResetController extends Controller
{
    /**
     * API mobile untuk meminta link reset password.
     *
     * Endpoint:
     * POST /api/auth/forgot-password
     *
     * Body JSON:
     * {
     *   "email": "user@email.com"
     * }
     */
    public function sendResetLinkEmail(Request $request): JsonResponse
    {
        Log::info('[API_RESET_PASSWORD] Submit forgot password request started', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'route' => optional($request->route())->getName(),
            'path' => $request->path(),
        ]);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = mb_strtolower(trim((string) $validated['email']));

        Log::info('[API_RESET_PASSWORD] Email normalized', [
            'email' => $email,
        ]);

        try {
            /*
             * Pakai LOWER(TRIM(email)) agar tetap ketemu walaupun di database
             * ada huruf besar/kecil atau spasi tidak sengaja.
             */
            $user = User::query()
                ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
                ->first();

            Log::info('[API_RESET_PASSWORD] User lookup result', [
                'email' => $email,
                'user_exists' => $user !== null,
                'user_id' => $user?->id,
            ]);

            /*
             * Untuk keamanan, response tetap dibuat sukses walaupun email tidak ada.
             * Detailnya cukup ditulis di laravel.log.
             */
            if (! $user) {
                Log::warning('[API_RESET_PASSWORD] Email not registered, reset email not sent', [
                    'email' => $email,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Jika email terdaftar, link reset password sudah dikirim ke email tersebut.',
                ]);
            }

            $emailView = 'emails.reset-password';
            $emailViewExists = view()->exists($emailView);

            Log::info('[API_RESET_PASSWORD] Email view check', [
                'view' => $emailView,
                'view_exists' => $emailViewExists,
                'expected_file' => resource_path('views/emails/reset-password.blade.php'),
                'expected_file_exists' => file_exists(resource_path('views/emails/reset-password.blade.php')),
            ]);

            if (! $emailViewExists) {
                Log::error('[API_RESET_PASSWORD] Email view not found, stop sending mail', [
                    'view' => $emailView,
                    'expected_file' => resource_path('views/emails/reset-password.blade.php'),
                    'expected_file_exists' => file_exists(resource_path('views/emails/reset-password.blade.php')),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Template email reset password belum ditemukan di server.',
                ], 500);
            }

            Log::info('[API_RESET_PASSWORD] Mail config check', [
                'mail_default' => config('mail.default'),
                'smtp_host' => config('mail.mailers.smtp.host'),
                'smtp_port' => config('mail.mailers.smtp.port'),
                'smtp_username' => config('mail.mailers.smtp.username'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
                'queue_connection' => config('queue.default'),
            ]);

            $plainToken = Str::random(64);

            DB::beginTransaction();

            DB::table('password_reset_tokens')
                ->where('email', $email)
                ->delete();

            DB::table('password_reset_tokens')->insert([
                'email' => $email,
                'token' => Hash::make($plainToken),
                'created_at' => now(),
            ]);

            DB::commit();

            Log::info('[API_RESET_PASSWORD] Reset token stored', [
                'email' => $email,
                'table' => 'password_reset_tokens',
            ]);

            /*
             * Link email diarahkan ke halaman web reset password.
             * Ini aman untuk mobile karena user bisa reset lewat browser.
             *
             * Jika nanti kamu membuat deep link Flutter, bagian ini bisa diganti menjadi:
             * tourhub://reset-password?token=...&email=...
             */
            $resetUrl = $this->buildResetUrl($plainToken, $email);

            Log::info('[API_RESET_PASSWORD] Reset URL generated', [
                'email' => $email,
                'reset_url_preview' => preg_replace('/reset-password\/[^?]+/', 'reset-password/***', $resetUrl),
            ]);

            Mail::to($email)->send(new ResetPasswordMail($resetUrl));

            Log::info('[API_RESET_PASSWORD] Reset password email sent successfully', [
                'email' => $email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Jika email terdaftar, link reset password sudah dikirim ke email tersebut.',
            ]);
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('[API_RESET_PASSWORD] Failed to send reset password email', [
                'email' => $email,
                'error_class' => $e::class,
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim link reset password.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * API mobile untuk menyimpan password baru.
     *
     * Endpoint:
     * POST /api/auth/reset-password
     *
     * Body JSON:
     * {
     *   "email": "user@email.com",
     *   "token": "token_dari_email",
     *   "password": "password_baru",
     *   "password_confirmation": "password_baru"
     * }
     */
    public function reset(Request $request): JsonResponse
    {
        Log::info('[API_RESET_PASSWORD] Submit reset password request started', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'route' => optional($request->route())->getName(),
            'path' => $request->path(),
        ]);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'token' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $email = mb_strtolower(trim((string) $validated['email']));
        $plainToken = (string) $validated['token'];

        Log::info('[API_RESET_PASSWORD] Reset data normalized', [
            'email' => $email,
            'token_length' => strlen($plainToken),
        ]);

        try {
            $tokenRow = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->first();

            Log::info('[API_RESET_PASSWORD] Token lookup result', [
                'email' => $email,
                'token_exists' => $tokenRow !== null,
                'created_at' => $tokenRow?->created_at,
            ]);

            if (! $tokenRow) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token reset password tidak ditemukan atau sudah digunakan.',
                ], 422);
            }

            $createdAt = Carbon::parse($tokenRow->created_at);
            $expiredAt = $createdAt->copy()->addMinutes(60);

            Log::info('[API_RESET_PASSWORD] Token expiration check', [
                'email' => $email,
                'created_at' => $createdAt->toDateTimeString(),
                'expired_at' => $expiredAt->toDateTimeString(),
                'is_expired' => now()->greaterThan($expiredAt),
            ]);

            if (now()->greaterThan($expiredAt)) {
                DB::table('password_reset_tokens')
                    ->where('email', $email)
                    ->delete();

                Log::warning('[API_RESET_PASSWORD] Token expired and deleted', [
                    'email' => $email,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Token reset password sudah kedaluwarsa. Silakan minta link baru.',
                ], 422);
            }

            if (! Hash::check($plainToken, $tokenRow->token)) {
                Log::warning('[API_RESET_PASSWORD] Token mismatch', [
                    'email' => $email,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Token reset password tidak valid.',
                ], 422);
            }

            $user = User::query()
                ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
                ->first();

            Log::info('[API_RESET_PASSWORD] User lookup before password update', [
                'email' => $email,
                'user_exists' => $user !== null,
                'user_id' => $user?->id,
            ]);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak ditemukan.',
                ], 422);
            }

            DB::beginTransaction();

            $user->forceFill([
                'password' => Hash::make((string) $validated['password']),
                'remember_token' => Str::random(60),
            ])->save();

            DB::table('password_reset_tokens')
                ->where('email', $email)
                ->delete();

            /*
             * Jika model User memakai Laravel Sanctum HasApiTokens,
             * token lama dihapus supaya sesi mobile lama tidak tetap aktif.
             */
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }

            DB::commit();

            Log::info('[API_RESET_PASSWORD] Password reset successfully', [
                'email' => $email,
                'user_id' => $user->id,
                'sanctum_tokens_deleted' => method_exists($user, 'tokens'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password berhasil diubah. Silakan login menggunakan password baru.',
            ]);
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('[API_RESET_PASSWORD] Failed to reset password', [
                'email' => $email,
                'error_class' => $e::class,
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mereset password.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    private function buildResetUrl(string $plainToken, string $email): string
    {
        if (Route::has('password.reset')) {
            return route('password.reset', [
                'token' => $plainToken,
                'email' => $email,
            ]);
        }

        return url('/reset-password/'.$plainToken.'?email='.urlencode($email));
    }
}
