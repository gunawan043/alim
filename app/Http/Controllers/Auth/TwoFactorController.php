<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\LoginOtpMail;
use App\Models\AuditLog;
use App\Models\LoginOtp;
use App\Models\TwoFactorRecoveryCode;
use App\Models\User;
use App\Services\SecurityNotificationService;
use App\Services\SecurityPolicy;
use App\Services\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function __construct()
    {
        $this->middleware('guest');
    }

    public function show(Request $request): View|RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login')
                ->with('error', 'Sesi verifikasi tidak valid. Silakan login kembali.');
        }

        $otp = LoginOtp::where('user_id', $user->id)->latest()->first();

        return view('auth.two-factor', [
            'email' => $this->maskEmail($user->email),
            'expiresAt' => $otp?->expires_at,
            'expiresMinutes' => $this->expiryMinutes(),
            'recoveryCodesAvailable' => TwoFactorRecoveryCode::where('user_id', $user->id)
                ->whereNull('used_at')
                ->exists(),
            'totpEnabled' => $user->hasTotpEnabled(),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login')
                ->with('error', 'Sesi verifikasi sudah berakhir. Silakan login kembali.');
        }

        $throttleKey = 'two-factor-verify:'.$user->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            throw ValidationException::withMessages([
                'otp' => 'Terlalu banyak percobaan. Tunggu beberapa menit lalu coba lagi.',
            ]);
        }

        RateLimiter::hit($throttleKey, 300);

        $result = DB::transaction(function () use ($user, $validated) {
            $record = LoginOtp::where('user_id', $user->id)->lockForUpdate()->first();

            if (! $record || ! $record->isValid()) {
                return ['error' => 'Kode verifikasi tidak ditemukan atau sudah kedaluwarsa.'];
            }

            if ($record->attempts >= self::MAX_ATTEMPTS) {
                $record->delete();

                return ['error' => 'Terlalu banyak percobaan. Silakan minta kode baru.'];
            }

            if (! Hash::check($validated['otp'], $record->otp_hash)) {
                $attempts = $record->attempts + 1;
                $record->forceFill(['attempts' => $attempts])->save();

                if ($attempts >= self::MAX_ATTEMPTS) {
                    $record->delete();
                }

                return ['error' => 'Kode verifikasi salah.'];
            }

            $record->forceFill(['used_at' => now()])->save();

            return ['record' => $record];
        });

        if (isset($result['error'])) {
            throw ValidationException::withMessages(['otp' => $result['error']]);
        }

        $record = $result['record'];
        RateLimiter::clear($throttleKey);

        return $this->completeLogin($user, $request, 'LOGIN_2FA_VERIFIED', $record->id, 'login_otps');
    }

    public function verifyTotp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'totp_code' => ['required', 'digits:6'],
        ]);

        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login')
                ->with('error', 'Sesi verifikasi sudah berakhir. Silakan login kembali.');
        }

        if (! $user->hasTotpEnabled()) {
            throw ValidationException::withMessages([
                'totp_code' => 'Authenticator belum diaktifkan untuk akun ini.',
            ]);
        }

        $throttleKey = 'two-factor-totp:'.$user->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            throw ValidationException::withMessages([
                'totp_code' => 'Terlalu banyak percobaan. Tunggu beberapa menit lalu coba lagi.',
            ]);
        }

        RateLimiter::hit($throttleKey, 300);

        if (! TotpService::verify((string) $user->getTotpSecret(), $validated['totp_code'])) {
            throw ValidationException::withMessages([
                'totp_code' => 'Kode authenticator tidak valid. Pastikan waktu perangkat sudah benar.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        return $this->completeLogin($user, $request, 'LOGIN_2FA_TOTP_VERIFIED', $user->id, 'users');
    }

    public function verifyRecovery(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recovery_code' => ['required', 'string', 'max:20'],
        ]);

        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login')
                ->with('error', 'Sesi verifikasi sudah berakhir. Silakan login kembali.');
        }

        $throttleKey = 'two-factor-recovery:'.$user->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'recovery_code' => 'Terlalu banyak percobaan. Tunggu beberapa menit lalu coba lagi.',
            ]);
        }

        RateLimiter::hit($throttleKey, 300);

        if (! TwoFactorRecoveryCode::verifyFor($user, $validated['recovery_code'])) {
            throw ValidationException::withMessages([
                'recovery_code' => 'Kode pemulihan tidak valid atau sudah pernah digunakan.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        return $this->completeLogin($user, $request, 'LOGIN_2FA_RECOVERY_USED', null, 'two_factor_recovery_codes');
    }

    private function completeLogin(User $user, Request $request, string $auditAction, ?string $recordId, string $auditTable): RedirectResponse
    {
        $remember = (bool) $request->session()->pull('2fa_remember', false);
        $request->session()->forget(['2fa_user_id', '2fa_started_at']);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        if ($user->roles->isNotEmpty()) {
            $request->session()->put('active_role_id', $user->roles->first()->id);
        }

        LoginOtp::where('user_id', $user->id)->delete();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => $auditAction,
            'table_name' => $auditTable,
            'record_id' => $recordId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        app(SecurityNotificationService::class)->login($user, $request);

        return app(LoginController::class)->redirectBasedOnRole($user);
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login')
                ->with('error', 'Sesi verifikasi sudah berakhir. Silakan login kembali.');
        }

        $throttleKey = 'two-factor-resend:'.$user->id;

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            return back()->withErrors([
                'otp' => 'Tunggu beberapa saat sebelum meminta kode baru.',
            ]);
        }

        RateLimiter::hit($throttleKey, 300);

        $record = null;

        try {
            [$record, $otp] = LoginOtp::issue($user, $this->expiryMinutes());
            Mail::to($user->email)->send(new LoginOtpMail(
                $otp,
                $user->name,
                $this->expiryMinutes(),
            ));
        } catch (\Throwable $e) {
            $record?->delete();

            Log::error('Gagal mengirim ulang OTP 2FA', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'otp' => 'Kode verifikasi tidak dapat dikirim saat ini. Silakan coba lagi.',
            ]);
        }

        $request->session()->put('2fa_started_at', now()->timestamp);

        return back()->with('status', 'Kode verifikasi baru telah dikirim ke email Anda.');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $userId = $request->session()->pull('2fa_user_id');

        if ($userId) {
            LoginOtp::where('user_id', $userId)->delete();
        }

        $request->session()->forget(['2fa_remember', '2fa_started_at']);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('info', 'Verifikasi login dibatalkan.');
    }

    private function pendingUser(Request $request): ?User
    {
        $userId = $request->session()->get('2fa_user_id');

        if (! $userId) {
            return null;
        }

        $user = User::whereKey($userId)->where('is_active', true)->first();

        if (! $user || $user->isLocked()) {
            $request->session()->forget(['2fa_user_id', '2fa_remember', '2fa_started_at']);

            return null;
        }

        return $user;
    }

    private function expiryMinutes(): int
    {
        return SecurityPolicy::otpExpiryMinutes();
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        if ($name === '' || $domain === '') {
            return $email;
        }

        return mb_substr($name, 0, 2).str_repeat('*', max(2, mb_strlen($name) - 2)).'@'.$domain;
    }
}
