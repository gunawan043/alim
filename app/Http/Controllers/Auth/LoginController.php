<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\AccountCompromisedMail;
use App\Mail\AccountLockedMail;
use App\Mail\IpBlockedMail;
use App\Models\FailedLoginAttempt;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LoginController extends Controller
{
    private const EMAIL_MAX_ATTEMPTS = 9;

    private const EMAIL_COMPROMISED_THRESHOLD = 6;

    private const IP_COOLDOWN_THRESHOLD = 5;

    private const IP_COOLDOWN_SECONDS = 60;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLogin()
    {
        // Jangan redirect di sini saat ada session lockout — biarkan halaman login
        // tampil dengan countdown. Redirect loop terjadi karena showLogin()
        // memproses ulang kondisi yang sama setiap kali page dimuat.
        // Countdown ditangani sepenuhnya oleh JavaScript di halaman login.

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'identity' => 'required|string|min:3',
            'password' => 'required|min:6',
        ]);

        $identity = trim($request->identity);
        $ip = $request->ip();

        // Cari user berdasarkan email ATAU nupy
        $user = User::where('email', strtolower($identity))
            ->orWhereHas('employment', fn ($q) => $q->whereRaw("REPLACE(nupy, '-', '') = REPLACE(?, '-', '')", [$identity]))
            ->first();

        $email = $user ? $user->email : null;
        $userFound = (bool) $user;

        // ── Cek akun terkunci ─────────────────────────────────────────────
        if ($userFound) {
            $user = User::where('email', $email)->first();
            if ($user->isLocked()) {
                $seconds = now()->diffInSeconds($user->locked_until, false);

                return $this->showLoginWithError(
                    $request,
                    'Akun terkunci. Hubungi Super Admin untuk membuka akun.',
                    max(0, $seconds),
                    'account_locked'
                );
            }
        }

        // ── Autentikasi ───────────────────────────────────────────────────
        if (! $user) {
            Log::warning('Login failed: user not found', ['identity' => $identity, 'ip' => $ip]);
            $this->trackIpAttempt($ip, null, $identity);

            return $this->showLoginWithError(
                $request,
                'Email atau NUPY tidak ditemukan.',
            );
        }

        if (Auth::attempt(['email' => $user->email, 'password' => $request->password], $request->filled('remember'))) {
            $user = Auth::user();

            // Reset semua counter sukses
            $user->resetFailedLoginAttempts();
            FailedLoginAttempt::forIp($ip)->update(['attempts' => 0]);

            $request->session()->regenerate();

            if ($user->roles->isNotEmpty()) {
                $request->session()->put('active_role_id', $user->roles->first()->id);
            }

            Log::info('Login successful', ['user_id' => Auth::id()]);

            return $this->redirectBasedOnRole($user);
        }

        // ── Login Gagal ───────────────────────────────────────────────────
        Log::warning('Login failed', ['identity' => $identity, 'ip' => $ip]);

        $user->passwordOtps()->latest()->delete();

        // A. Attempt 6: notifikasi akun terancam (sebelum increment)
        if ($user->failed_login_attempts + 1 == self::EMAIL_COMPROMISED_THRESHOLD) {
            Mail::to($user->email)->queue(new AccountCompromisedMail(
                $user->name,
                $user->email,
                $user->failed_login_attempts + 1,
                $ip
            ));
            Log::warning('Account compromised threshold reached', [
                'user_id' => $user->id,
                'identity' => $identity,
                'ip' => $ip,
                'attempts' => $user->failed_login_attempts + 1,
            ]);
        }

        // B. Increment user counter & IP counter
        $user->incrementFailedLoginAttempts();
        $this->trackIpAttempt($ip, $user->failed_login_attempts, $identity);

        // C. Attempt 9: akun dikunci
        if ($user->failed_login_attempts >= self::EMAIL_MAX_ATTEMPTS) {
            Mail::to($user->email)->send(new AccountLockedMail(
                $user->name,
                $user->email,
                $user->failed_login_attempts,
                $ip
            ));

            $superAdminIds = usersHavingPermission('general_admin.administrable');
            $superAdmins = User::whereIn('id', $superAdminIds)->pluck('email');
            Mail::to($superAdmins)->send(new AccountLockedMail(
                $user->name,
                $user->email,
                $user->failed_login_attempts,
                $ip
            ));

            Log::critical('Account locked due to failed login attempts', [
                'user_id' => $user->id,
                'identity' => $identity,
                'ip' => $ip,
                'attempts' => $user->failed_login_attempts,
            ]);

            return $this->showLoginWithError(
                $request,
                'Akun terkunci karena terlalu banyak percobaan login gagal. Super Admin telah diberitahu.',
                0,
                'account_locked'
            );
        }

        // Middle-range failures (1, 2, 3, 4, 6, 7, 8) → just error message
        // Note: IP cooldown is handled by CheckIpBlocked middleware as non-blocking warning
        return $this->showLoginWithError(
            $request,
            'Email atau NUPY salah.'
        );
    }

    private function trackIpAttempt(string $ip, ?int $userAttempts, string $email): void
    {
        $record = FailedLoginAttempt::forIp($ip)->active()->first();

        if ($record) {
            $record->recordAttempt($userAttempts !== null && $userAttempts > 0);
        } else {
            $record = FailedLoginAttempt::create([
                'ip_address' => $ip,
                'email' => $email,
                'attempts' => 0,
                'last_attempt_at' => now(),
            ]);
            $record->recordAttempt(false);
        }

        // Jika IP diblokir (attemp 9 via email salah)
        if ($record->isLockedByIp()) {
            $superAdminIds = usersHavingPermission('general_admin.administrable');
            $superAdmins = User::whereIn('id', $superAdminIds)->pluck('email');
            Mail::to($superAdmins)->queue(new IpBlockedMail(
                $ip,
                $record->locked_until->diffForHumans(),
                $record->attempts
            ));

            Log::warning('IP blocked due to failed login attempts', [
                'ip' => $ip,
                'email' => $email,
                'attempts' => $record->attempts,
                'locked_until' => $record->locked_until,
            ]);
        }
    }

    private function showLoginWithError(
        Request $request,
        string $message,
        int $seconds = 0,
        string $errorType = 'login_failed'
    ): mixed {
        return redirect('/login')
            ->withErrors([$errorType => $message])
            ->withInput(['identity' => $request->identity])
            ->with([
                'lockout' => $seconds > 0 ? true : null,
                'seconds' => $seconds,
            ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    /**
     * Redirect user based on their role after successful login.
     * This runs BEFORE employee.access middleware to catch edge cases.
     */
    private function redirectBasedOnRole($user): RedirectResponse
    {
        // Rule 0: System Administrator (is_system_admin=true) — bypass role check.
        // They may legitimately have no Spatie role; route to dedicated /system dashboard.
        if (method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin()) {
            return redirect()->route('system.dashboard');
        }

        $roles = $user->getRoleNames();

        // Rule 1: Applicant → external redirect
        if ($roles->contains(fn ($name) => stripos($name, 'applicant') !== false)) {
            return redirect()->away(
                config('app.recruitment_url', 'https://recruitment.abuhurairah.id')
            )->with('info', 'Anda akan diarahkan ke portal recruitment.');
        }

        // Rule 2: No role at all → validator page
        if ($roles->isEmpty()) {
            return redirect()->route('auth.validator')
                ->with('warning', 'Akun Anda belum memiliki role. Silakan diverifikasi.');
        }

        // Rule 3: Wali Santri → hard block on login
        if ($roles->contains('Wali Santri')) {
            Auth::logout();

            return redirect('/access-denied')
                ->with('error', 'Akun ini adalah Wali Santri dan tidak memiliki akses ke website ini.');
        }

        // Rule 4: Role-based dashboard routing (role + jabatan)
        $roleName = strtolower(trim((string) $roles->first()));
        $jabatan = strtoupper(trim((string) ($user->employment?->jabatan ?? '')));

        $roleJabatanMap = [
            'satuan pendidikan' => [
                'WAKIL KEPALA' => 'dashboard.wakil-kepala',
                'KEPALA SATUAN PENDIDIKAN' => 'dashboard.kepala-satuan-pendidikan',
                'STAF TATA USAHA' => 'dashboard.staf-tata-usaha',
            ],
            'pimpinan' => [
                'WAKIL KEPALA' => 'dashboard.wakil-kepala',
                'KEPALA SATUAN PENDIDIKAN' => 'dashboard.kepala-satuan-pendidikan',
            ],
        ];

        if (isset($roleJabatanMap[$roleName])) {
            foreach ($roleJabatanMap[$roleName] as $jabatanPattern => $route) {
                if (str_contains($jabatan, $jabatanPattern)) {
                    return redirect()->route($route);
                }
            }
            // No specific jabatan match — fall through to role keyword matching below
        }

        $dashboardRoute = match (true) {
            str_contains($roleName, 'kepala satuan') => 'dashboard.kepala-satuan-pendidikan',
            str_contains($roleName, 'wakil kepala') || str_contains($roleName, 'waka') => 'dashboard.wakil-kepala',
            str_contains($roleName, 'wali kelas') => 'dashboard.wali-kelas',
            str_contains($roleName, 'koordinator guru') => 'dashboard.koordinator-guru',
            str_contains($roleName, 'koordinator kurikulum') => 'dashboard.koordinator-kurikulum',
            str_contains($roleName, 'koordinator kesiswaan') => 'dashboard.koordinator-kesiswaan',
            str_contains($roleName, 'koordinator ekskul') || str_contains($roleName, 'koordinator ekstrakurikuler') => 'dashboard.koordinator-ekskul',
            str_contains($roleName, 'koordinator lab') || str_contains($roleName, 'koordinator laboratorium') => 'dashboard.koordinator-lab',
            str_contains($roleName, 'koordinator sarpras') || str_contains($roleName, 'koordinator prasarana') => 'dashboard.koordinator-sarpras',
            str_contains($roleName, 'kepala tata usaha') || str_contains($roleName, 'ka tata usaha') => 'dashboard.ka-tata-usaha',
            str_contains($roleName, 'staf tata usaha') || str_contains($roleName, 'staff tata usaha') => 'dashboard.staf-tata-usaha',
            str_contains($roleName, 'bendahara') || str_contains($roleName, 'keuangan') => 'dashboard.bendahara',
            str_contains($roleName, 'guru') || str_contains($roleName, 'pendidik') => 'dashboard.guru',
            str_contains($roleName, 'pengasuh') => 'dashboard.pengasuh',
            str_contains($roleName, 'wali asrama') => 'dashboard.wali-asrama',
            str_contains($roleName, 'admin tu') => 'dashboard.admin-tu',
            str_contains($roleName, 'admin asrama') => 'dashboard.admin-asrama',
            str_contains($roleName, 'asrama') => 'dashboard.asrama',
            default => null,
        };

        if ($dashboardRoute) {
            return redirect()->route($dashboardRoute);
        }

        // Rule 5: Fallback to intended or home
        return redirect()->intended('/');
    }
}
