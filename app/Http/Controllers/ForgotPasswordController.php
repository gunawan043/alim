<?php

namespace App\Http\Controllers;

use App\Mail\PasswordChangedMail;
use App\Mail\ResetPasswordOtpMail;
use App\Models\AuditLog;
use App\Models\PasswordOtp;
use App\Models\User;
use App\Services\SecurityNotificationService;
use App\Services\SecurityPolicy;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    private const MAX_OTP_ATTEMPTS = 5;

    public function showForm()
    {
        return view('auth.passwords.email');
    }

    public function sendOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = Str::lower(trim($validated['email']));
        $throttleKey = 'password-otp-request:'.sha1($email.'|'.$request->ip());
        $emailThrottleKey = 'password-otp-email:'.sha1($email);

        if (RateLimiter::tooManyAttempts($throttleKey, 5) || RateLimiter::tooManyAttempts($emailThrottleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak permintaan. Tunggu beberapa menit sebelum mencoba lagi.',
            ]);
        }

        RateLimiter::hit($throttleKey, 600);
        RateLimiter::hit($emailThrottleKey, 900);

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('is_active', true)
            ->first();

        // Jangan membedakan email terdaftar/tidak terdaftar di UI.
        if (! $user) {
            return back()
                ->withInput($request->only('email'))
                ->with('status', 'Jika email terdaftar, kode OTP akan dikirim.');
        }

        $otp = (string) random_int(100000, 999999);

        try {
            DB::transaction(function () use ($user, $otp) {
                PasswordOtp::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'otp_hash' => Hash::make($otp),
                        'expires_at' => now()->addMinutes(SecurityPolicy::otpExpiryMinutes()),
                        'attempts' => 0,
                        'used_at' => null,
                        'last_sent_at' => now(),
                    ]
                );
            });

            Mail::to($user->email)->send(new ResetPasswordOtpMail(
                $otp,
                $user->name,
                SecurityPolicy::otpExpiryMinutes(),
            ));
        } catch (\Throwable $e) {
            Log::error('Gagal mengirim OTP reset password', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput($request->only('email'))
                ->with('status', 'Jika email terdaftar, kode OTP akan dikirim.');
        }

        $otpData = PasswordOtp::where('user_id', $user->id)->first();

        session([
            'reset_user_uuid' => $user->id,
            'reset_email' => $user->email,
            'otp_expires_at' => $otpData?->expires_at?->timestamp,
            'otp_verified' => false,
        ]);
        session()->forget(['otp_failed_attempts', 'otp_verified_at']);

        app(SecurityNotificationService::class)->passwordForgot($user, $request);

        return redirect()
            ->route('password.otp.form')
            ->with('status', 'Jika email terdaftar, kode OTP akan dikirim.');
    }

    public function showOtpForm()
    {
        $userId = session('reset_user_uuid');

        if (! $userId) {
            return redirect()->route('password.request')
                ->with('error', 'Sesi tidak valid. Silakan request OTP lagi.');
        }

        if (session('otp_verified')) {
            return redirect()->route('password.reset.form');
        }

        $otpData = PasswordOtp::where('user_id', $userId)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $otpData) {
            return redirect()->route('password.request')
                ->with('error', 'Kode OTP sudah kadaluarsa. Silakan request OTP baru.');
        }

        return view('auth.passwords.verify-otp', [
            'email' => $this->maskEmail((string) session('reset_email')),
            'expiresAt' => $otpData->expires_at,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $userId = session('reset_user_uuid');

        if (! $userId) {
            throw ValidationException::withMessages([
                'otp' => 'Sesi tidak valid. Silakan request OTP lagi.',
            ]);
        }

        $verified = false;
        $error = null;

        DB::transaction(function () use ($userId, $validated, &$verified, &$error) {
            $otpData = PasswordOtp::where('user_id', $userId)->lockForUpdate()->first();

            if (! $otpData || $otpData->used_at !== null || ! $otpData->isValid()) {
                $error = 'OTP tidak ditemukan atau sudah kadaluarsa.';

                return;
            }

            if ($otpData->attempts >= self::MAX_OTP_ATTEMPTS) {
                $otpData->delete();
                $error = 'Terlalu banyak percobaan gagal. Silakan request OTP baru.';

                return;
            }

            if (! Hash::check($validated['otp'], $otpData->otp_hash)) {
                $attempts = $otpData->attempts + 1;
                $otpData->forceFill(['attempts' => $attempts])->save();
                $remaining = max(0, self::MAX_OTP_ATTEMPTS - $attempts);

                if ($remaining === 0) {
                    $otpData->delete();
                }

                $error = $remaining > 0
                    ? "OTP salah. Sisa percobaan: {$remaining}"
                    : 'Terlalu banyak percobaan gagal. Silakan request OTP baru.';

                return;
            }

            $otpData->forceFill([
                'used_at' => now(),
                'attempts' => 0,
            ])->save();

            $verified = true;
        });

        if ($error !== null) {
            throw ValidationException::withMessages(['otp' => $error]);
        }

        if (! $verified) {
            throw ValidationException::withMessages(['otp' => 'OTP tidak valid.']);
        }

        AuditLog::create([
            'user_id' => $userId,
            'action' => 'PASSWORD_OTP_VERIFIED',
            'table_name' => 'password_otps',
            'record_id' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $request->session()->regenerate();
        session([
            'reset_user_uuid' => $userId,
            'otp_verified' => true,
            'otp_verified_at' => now()->timestamp,
        ]);

        return redirect()->route('password.reset.form')
            ->with('success', 'OTP berhasil diverifikasi. Silakan buat password baru.');
    }

    public function showResetForm()
    {
        $userId = session('reset_user_uuid');

        if (! $userId || ! session('otp_verified')) {
            return redirect()->route('password.request')
                ->with('error', 'Sesi reset password tidak valid. Silakan mulai dari awal.');
        }

        $verifiedOtp = PasswordOtp::where('user_id', $userId)
            ->whereNotNull('used_at')
            ->where('used_at', '>=', now()->subMinutes(15))
            ->exists();

        if (! $verifiedOtp) {
            session()->forget(['otp_verified', 'otp_verified_at']);

            return redirect()->route('password.request')
                ->with('error', 'Verifikasi OTP sudah kadaluarsa. Silakan request OTP baru.');
        }

        $email = User::whereKey($userId)->value('email');

        return view('auth.passwords.reset', compact('email'));
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', PasswordRule::min(SecurityPolicy::passwordMinLength())],
        ]);

        $userId = session('reset_user_uuid');

        if (! $userId || ! session('otp_verified')) {
            return redirect()->route('password.request')
                ->with('error', 'Sesi reset password tidak valid. Silakan mulai dari awal.');
        }

        $user = User::whereKey($userId)->where('is_active', true)->first();

        if (! $user) {
            session()->forget(['reset_user_uuid', 'reset_email', 'otp_verified', 'otp_verified_at', 'otp_expires_at']);

            return redirect()->route('password.request')
                ->with('error', 'Akun tidak ditemukan atau tidak aktif.');
        }

        $verifiedOtp = PasswordOtp::where('user_id', $user->id)
            ->whereNotNull('used_at')
            ->where('used_at', '>=', now()->subMinutes(15))
            ->exists();

        if (! $verifiedOtp) {
            session()->forget(['otp_verified', 'otp_verified_at']);

            return redirect()->route('password.request')
                ->with('error', 'Verifikasi OTP sudah kadaluarsa. Silakan request OTP baru.');
        }

        try {
            DB::transaction(function () use ($user, $validated, $request) {
                $user->forceFill([
                    'password' => Hash::make($validated['password']),
                    'remember_token' => Str::random(60),
                    'failed_login_attempts' => 0,
                    'locked_until' => null,
                    'locked_at' => null,
                ])->save();

                PasswordOtp::where('user_id', $user->id)->delete();

                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => 'PASSWORD_RESET',
                    'table_name' => 'users',
                    'record_id' => $user->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                event(new PasswordReset($user));
            });
        } catch (\Throwable $e) {
            Log::error('Gagal reset password', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['password' => 'Terjadi kesalahan. Silakan coba lagi.']);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $this->revokeOtherSessions($user);

        try {
            Mail::to($user->email)->send(new PasswordChangedMail(
                $user->name,
                now()->locale('id')->translatedFormat('d F Y H:i'),
                $request->ip(),
            ));
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim notifikasi password berubah', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        app(SecurityNotificationService::class)->passwordReset($user, $request);

        return redirect()->route('login')
            ->with('success', 'Password berhasil direset. Silakan login dengan password baru.');
    }

    public function resendOtp(Request $request)
    {
        $userId = session('reset_user_uuid');

        if (! $userId) {
            return redirect()->route('password.request')
                ->with('error', 'Sesi tidak valid. Silakan request OTP lagi.');
        }

        $throttleKey = 'password-otp-resend:'.$userId;

        if (RateLimiter::tooManyAttempts($throttleKey, 2)) {
            return back()->withErrors(['otp' => 'Tunggu beberapa saat sebelum meminta OTP lagi.']);
        }

        RateLimiter::hit($throttleKey, 300);

        $user = User::whereKey($userId)->where('is_active', true)->first();

        if (! $user) {
            return redirect()->route('password.request')
                ->with('error', 'Akun tidak ditemukan atau tidak aktif.');
        }

        $otp = (string) random_int(100000, 999999);

        try {
            DB::transaction(function () use ($user, $otp) {
                PasswordOtp::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'otp_hash' => Hash::make($otp),
                        'expires_at' => now()->addMinutes(SecurityPolicy::otpExpiryMinutes()),
                        'attempts' => 0,
                        'used_at' => null,
                        'last_sent_at' => now(),
                    ]
                );
            });

            Mail::to($user->email)->send(new ResetPasswordOtpMail(
                $otp,
                $user->name,
                SecurityPolicy::otpExpiryMinutes(),
            ));
        } catch (\Throwable $e) {
            Log::error('Gagal mengirim ulang OTP reset password', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['otp' => 'Kode OTP tidak dapat dikirim saat ini. Silakan coba lagi.']);
        }

        $otpData = PasswordOtp::where('user_id', $user->id)->first();

        session([
            'otp_expires_at' => $otpData?->expires_at?->timestamp,
            'otp_verified' => false,
        ]);
        session()->forget(['otp_failed_attempts', 'otp_verified_at']);

        return back()->with('status', 'Kode OTP baru telah dikirim ke email Anda.');
    }

    public function checkOtpValidity(Request $request)
    {
        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $userId = session('reset_user_uuid');

        if (! $userId) {
            return response()->json(['valid' => false, 'message' => 'Sesi tidak valid'], 400);
        }

        $throttleKey = 'password-otp-check:'.$userId;
        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            return response()->json(['valid' => false, 'message' => 'Terlalu banyak percobaan'], 429);
        }
        RateLimiter::hit($throttleKey, 300);

        $otpData = PasswordOtp::where('user_id', $userId)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $otpData) {
            return response()->json(['valid' => false, 'message' => 'OTP tidak ditemukan atau sudah kadaluarsa']);
        }

        if (! Hash::check($validated['otp'], $otpData->otp_hash)) {
            $attempts = $otpData->attempts + 1;
            $otpData->forceFill(['attempts' => $attempts])->save();

            if ($attempts >= self::MAX_OTP_ATTEMPTS) {
                $otpData->delete();
            }

            return response()->json(['valid' => false, 'message' => 'OTP salah']);
        }

        return response()->json(['valid' => true, 'message' => 'OTP valid']);
    }

    public function cancelReset(Request $request)
    {
        if (session('reset_user_uuid')) {
            PasswordOtp::where('user_id', session('reset_user_uuid'))->delete();
        }

        $request->session()->forget([
            'reset_user_uuid',
            'reset_email',
            'otp_expires_at',
            'otp_verified',
            'otp_verified_at',
            'otp_failed_attempts',
        ]);

        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('info', 'Proses reset password dibatalkan.');
    }

    private function revokeOtherSessions(User $user, ?string $currentSessionId = null): void
    {
        if (! Schema::hasTable('sessions')) {
            return;
        }

        $query = DB::table('sessions')->where('user_id', $user->id);

        if ($currentSessionId) {
            $query->where('id', '!=', $currentSessionId);
        }

        $query->delete();
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($name === '' || $domain === '') {
            return $email;
        }

        $visible = mb_substr($name, 0, 2);

        return $visible.str_repeat('*', max(2, mb_strlen($name) - 2)).'@'.$domain;
    }
}
