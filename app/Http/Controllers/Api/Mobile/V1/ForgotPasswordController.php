<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Mail\PasswordChangedMail;
use App\Models\User;
use App\Services\SecurityNotificationService;
use App\Services\SecurityPolicy;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    // ── POST /api/mobile/v1/auth/forgot-password ───────────────────────

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $key = 'mobile-password-reset:'.sha1(Str::lower($validated['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages([
                'email' => ['Terlalu banyak permintaan. Coba lagi beberapa menit lagi.'],
            ]);
        }

        RateLimiter::hit($key, 600);

        Password::sendResetLink(['email' => $validated['email']]);

        $user = User::where('email', $validated['email'])->first();

        if ($user) {
            app(SecurityNotificationService::class)->passwordForgot($user, $request);
        }

        // Respons sama untuk email terdaftar/tidak terdaftar.
        return response()->json([
            'success' => true,
            'message' => 'Jika email terdaftar, tautan reset password akan dikirim.',
        ]);
    }

    // ── POST /api/mobile/v1/auth/reset-password ────────────────────────

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => ['required', 'confirmed', PasswordRule::min(SecurityPolicy::passwordMinLength())],
            'token' => 'required',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                    'failed_login_attempts' => 0,
                    'locked_until' => null,
                    'locked_at' => null,
                ])->save();

                if (Schema::hasTable('sessions')) {
                    DB::table('sessions')->where('user_id', $user->id)->delete();
                }

                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            $user = User::where('email', $request->email)->first();

            if ($user) {
                try {
                    Mail::to($user->email)->send(new PasswordChangedMail(
                        $user->name,
                        now()->locale('id')->translatedFormat('d F Y H:i'),
                        $request->ip(),
                    ));
                } catch (\Throwable $e) {
                    Log::warning('Gagal mengirim notifikasi password berubah (mobile)', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                app(SecurityNotificationService::class)->passwordReset($user, $request);
            }

            return response()->json([
                'success' => true,
                'message' => 'Password berhasil direset. Silakan login dengan password baru.',
            ]);
        }

        throw ValidationException::withMessages([
            'email' => ['Token reset tidak valid atau sudah kadaluarsa.'],
        ]);
    }
}
