<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use Throwable;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\RedirectResponse;

final class ForgotPasswordController extends Controller
{
    /**
     * Menampilkan halaman lupa password.
     */
    public function showLinkRequestForm(): View
    {
        Log::info('[RESET_PASSWORD] Open forgot password page', [
            'view' => 'auth.forgot-password',
            'view_exists' => view()->exists('auth.forgot-password'),
        ]);

        return view('auth.forgot-password');
    }

    /**
     * Mengirim link reset password ke email user.
     *
     * Catatan keamanan:
     * - Response tetap dibuat umum agar tidak membocorkan apakah email terdaftar atau tidak.
     * - Detail proses ditulis ke laravel.log agar mudah ditelusuri saat debugging.
     * - Token asli hanya dikirim lewat email.
     * - Token yang disimpan di database sudah di-hash.
     */
    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        Log::info('[RESET_PASSWORD] Submit forgot password form started', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'route' => optional($request->route())->getName(),
            'path' => $request->path(),
        ]);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = mb_strtolower(trim((string) $validated['email']));

        Log::info('[RESET_PASSWORD] Email normalized', [
            'email' => $email,
        ]);

        try {
            $user = User::query()
                ->where('email', $email)
                ->first();

            Log::info('[RESET_PASSWORD] User lookup result', [
                'email' => $email,
                'user_exists' => $user !== null,
                'user_id' => $user?->id,
            ]);

            /*
             * Jika email tidak terdaftar, jangan kirim email.
             * Response tetap sukses agar tidak membocorkan data user.
             */
            if (! $user) {
                Log::warning('[RESET_PASSWORD] Email not registered, reset email not sent', [
                    'email' => $email,
                ]);

                return back()->with(
                    'status',
                    'Jika email terdaftar, link reset password sudah dikirim ke email tersebut.'
                );
            }

            /*
             * Cek template email sebelum proses kirim.
             * Jika false, berarti file resources/views/emails/reset-password.blade.php
             * belum terbaca oleh Laravel atau cache view belum dibersihkan.
             */
            $emailView = 'emails.reset-password';
            $emailViewExists = view()->exists($emailView);

            Log::info('[RESET_PASSWORD] Email view check', [
                'view' => $emailView,
                'view_exists' => $emailViewExists,
                'expected_file' => resource_path('views/emails/reset-password.blade.php'),
                'expected_file_exists' => file_exists(resource_path('views/emails/reset-password.blade.php')),
            ]);

            if (! $emailViewExists) {
                Log::error('[RESET_PASSWORD] Email view not found, stop sending mail', [
                    'view' => $emailView,
                    'expected_file' => resource_path('views/emails/reset-password.blade.php'),
                    'expected_file_exists' => file_exists(resource_path('views/emails/reset-password.blade.php')),
                ]);

                return back()->withErrors([
                    'email' => 'Template email reset password belum ditemukan. Cek storage/logs/laravel.log.',
                ]);
            }

            /*
             * Cek konfigurasi mail tanpa menulis password ke log.
             */
            Log::info('[RESET_PASSWORD] Mail config check', [
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

            Log::info('[RESET_PASSWORD] Reset token stored', [
                'email' => $email,
                'table' => 'password_reset_tokens',
            ]);

            $resetUrl = route('password.reset', [
                'token' => $plainToken,
                'email' => $email,
            ]);

            Log::info('[RESET_PASSWORD] Reset URL generated', [
                'email' => $email,
                'reset_url_preview' => preg_replace('/token=[^&]+|reset-password\/[^?]+/', 'token=***', $resetUrl),
                'route_name' => 'password.reset',
            ]);

            Mail::to($email)->send(new ResetPasswordMail($resetUrl));

            Log::info('[RESET_PASSWORD] Reset password email sent successfully', [
                'email' => $email,
            ]);

            return back()->with(
                'status',
                'Jika email terdaftar, link reset password sudah dikirim ke email tersebut.'
            );
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('[RESET_PASSWORD] Failed to send reset password email', [
                'email' => $email,
                'error_class' => $e::class,
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            report($e);

            return back()->withErrors([
                'email' => 'Gagal mengirim link reset password. Silakan cek storage/logs/laravel.log.',
            ]);
        }
    }
}
