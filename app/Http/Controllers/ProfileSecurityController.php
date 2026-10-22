<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\TwoFactorRecoveryCode;
use App\Models\User;
use App\Services\SecurityPolicy;
use App\Services\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ProfileSecurityController extends Controller
{
    public function index(Request $request, string $userId): View
    {
        $user = $this->authorizedUser($request, $userId);
        $currentSessionId = $request->session()->getId();

        $webSessions = collect();
        if (Schema::hasTable('sessions')) {
            $webSessions = DB::table('sessions')
                ->where('user_id', $user->id)
                ->orderByDesc('last_activity')
                ->get()
                ->map(function ($session) use ($currentSessionId) {
                    $agent = (string) ($session->user_agent ?? '');
                    $session->is_current = $session->id === $currentSessionId;
                    $session->device_label = $this->deviceLabel($agent);
                    $session->last_activity_at = now()->setTimestamp((int) $session->last_activity);

                    return $session;
                });
        }

        $mobileSessions = PersonalAccessToken::query()
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $user->id)
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get();

        $recoveryCodeCount = TwoFactorRecoveryCode::where('user_id', $user->id)
            ->whereNull('used_at')
            ->count();

        $loginHistory = AuditLog::query()
            ->where('user_id', $user->id)
            ->whereIn('action', [
                'LOGIN_SUCCESS',
                'LOGIN_2FA_VERIFIED',
                'LOGIN_2FA_TOTP_VERIFIED',
                'LOGIN_2FA_RECOVERY_USED',
                'MOBILE_2FA_VERIFIED',
            ])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('profile.security', [
            'userId' => $userId,
            'user' => $user,
            'webSessions' => $webSessions,
            'mobileSessions' => $mobileSessions,
            'currentSessionId' => $currentSessionId,
            'twoFactorEnabled' => (string) setting('security_2fa_enabled', '0') === '1',
            'twoFactorScope' => SecurityPolicy::twoFactorScope(),
            'recoveryCodeCount' => $recoveryCodeCount,
            'recoveryCodes' => session('recovery_codes', []),
            'loginHistory' => $loginHistory,
            'totpEnabled' => $user->hasTotpEnabled(),
            'totpPending' => ! $user->hasTotpEnabled() && filled($user->two_factor_secret),
            'totpSetupUri' => $user->getTotpUriAttribute(),
            'totpSecret' => $user->hasTotpEnabled() ? null : $user->getTotpSecret(),
            'totpQr' => $this->makeTotpQr($user),
        ]);
    }

    public function setupTotp(Request $request, string $userId): RedirectResponse
    {
        $user = $this->authorizedUser($request, $userId);

        if ((string) setting('security_2fa_enabled', '0') !== '1') {
            return back()->with('error', 'Aktifkan 2FA terlebih dahulu sebelum memasang authenticator.');
        }

        $user->setTotpSecret(TotpService::generateSecret());

        $this->audit($request, $user, 'TOTP_SETUP_STARTED');

        return back()->with('success', 'Scan QR atau masukkan secret di aplikasi authenticator, lalu konfirmasi dengan kode 6 digit.');
    }

    public function confirmTotp(Request $request, string $userId): RedirectResponse
    {
        $validated = $request->validate([
            'totp_code' => ['required', 'digits:6'],
        ]);

        $user = $this->authorizedUser($request, $userId);
        $secret = $user->getTotpSecret();

        if (! $secret) {
            return back()->with('error', 'Secret authenticator tidak ditemukan. Mulai setup ulang.');
        }

        if (! TotpService::verify($secret, $validated['totp_code'])) {
            return back()->withErrors(['totp_code' => 'Kode authenticator tidak valid. Pastikan waktu perangkat sudah benar.']);
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        $this->audit($request, $user, 'TOTP_ENABLED');

        return back()->with('success', 'Authenticator berhasil diaktifkan. Anda bisa memakai kode 6 digit saat login.');
    }

    public function disableTotp(Request $request, string $userId): RedirectResponse
    {
        $user = $this->authorizedUser($request, $userId);

        $user->clearTotp();
        TwoFactorRecoveryCode::where('user_id', $user->id)->delete();

        $this->audit($request, $user, 'TOTP_DISABLED');

        return back()->with('success', 'Authenticator dinonaktifkan. Kode pemulihan lama juga dihapus.');
    }

    public function generateRecoveryCodes(Request $request, string $userId): RedirectResponse
    {
        $user = $this->authorizedUser($request, $userId);

        if ((string) setting('security_2fa_enabled', '0') !== '1') {
            return back()->with('error', 'Aktifkan 2FA terlebih dahulu sebelum membuat kode pemulihan.');
        }

        $codes = TwoFactorRecoveryCode::generateFor($user);

        $this->audit($request, $user, 'TWO_FACTOR_RECOVERY_CODES_GENERATED');

        return back()
            ->with('recovery_codes', $codes)
            ->with('success', 'Kode pemulihan baru dibuat. Simpan kode ini sekarang; kode hanya ditampilkan sekali.');
    }

    public function revokeWebSession(Request $request, string $userId, string $sessionId): RedirectResponse
    {
        $user = $this->authorizedUser($request, $userId);

        if ($sessionId === $request->session()->getId()) {
            return back()->with('error', 'Sesi yang sedang digunakan tidak dapat dicabut dari halaman ini.');
        }

        $deleted = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', $sessionId)
            ->delete();

        if ($deleted) {
            $this->audit($request, $user, 'WEB_SESSION_REVOKED');
        }

        return back()->with($deleted ? 'success' : 'error', $deleted
            ? 'Sesi web berhasil dicabut.'
            : 'Sesi tidak ditemukan.');
    }

    public function revokeOtherWebSessions(Request $request, string $userId): RedirectResponse
    {
        $user = $this->authorizedUser($request, $userId);

        $deleted = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        $this->audit($request, $user, 'OTHER_WEB_SESSIONS_REVOKED');

        return back()->with('success', "{$deleted} sesi web lain berhasil dicabut.");
    }

    public function revokeMobileSession(Request $request, string $userId, string $tokenId): RedirectResponse
    {
        $user = $this->authorizedUser($request, $userId);

        $deleted = PersonalAccessToken::query()
            ->where('id', $tokenId)
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $user->id)
            ->delete();

        if ($deleted) {
            $this->audit($request, $user, 'MOBILE_SESSION_REVOKED');
        }

        return back()->with($deleted ? 'success' : 'error', $deleted
            ? 'Sesi mobile berhasil dicabut.'
            : 'Sesi mobile tidak ditemukan.');
    }

    public function revokeOtherMobileSessions(Request $request, string $userId): RedirectResponse
    {
        $user = $this->authorizedUser($request, $userId);

        $deleted = PersonalAccessToken::query()
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $user->id)
            ->delete();

        $this->audit($request, $user, 'ALL_MOBILE_SESSIONS_REVOKED');

        return back()->with('success', "{$deleted} sesi mobile berhasil dicabut.");
    }

    private function authorizedUser(Request $request, string $userId): User
    {
        abort_unless((string) $request->user()?->id === (string) $userId, 403);

        return $request->user();
    }

    private function makeTotpQr(User $user): ?string
    {
        $uri = $user->getTotpUriAttribute();

        if (! $uri || $user->hasTotpEnabled()) {
            return null;
        }

        try {
            return QrCode::size(180)->format('svg')->generate($uri);
        } catch (\Throwable) {
            return null;
        }
    }

    private function audit(Request $request, User $user, string $action): void
    {
        AuditLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'table_name' => 'user_sessions',
            'record_id' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    private function deviceLabel(string $userAgent): string
    {
        $ua = strtolower($userAgent);
        $browser = match (true) {
            str_contains($ua, 'edg/') => 'Microsoft Edge',
            str_contains($ua, 'chrome/') => 'Chrome',
            str_contains($ua, 'firefox/') => 'Firefox',
            str_contains($ua, 'safari/') && ! str_contains($ua, 'chrome/') => 'Safari',
            str_contains($ua, 'curl/') => 'cURL',
            default => 'Browser',
        };
        $platform = match (true) {
            str_contains($ua, 'windows') => 'Windows',
            str_contains($ua, 'android') => 'Android',
            str_contains($ua, 'iphone') || str_contains($ua, 'ipad') => 'iOS',
            str_contains($ua, 'mac os') => 'macOS',
            str_contains($ua, 'linux') => 'Linux',
            default => 'Perangkat',
        };

        return "{$browser} · {$platform}";
    }
}
