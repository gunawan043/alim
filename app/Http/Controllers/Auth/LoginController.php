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

        $user = User::where('email', strtolower($identity))
            ->orWhereHas('employment', fn ($q) => $q->whereRaw("REPLACE(nupy, '-', '') = REPLACE(?, '-', '')", [$identity]))
            ->first();

        $email = $user ? $user->email : null;
        $userFound = (bool) $user;

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

        if (! $user) {
            Log::warning('Login failed: user not found', ['identity' => $identity, 'ip' => $ip]);
            $this->trackIpAttempt($ip, null, $identity);

            return $this->showLoginWithError($request, 'Email atau NUPY tidak ditemukan.');
        }

        if (Auth::attempt(['email' => $user->email, 'password' => $request->password], $request->filled('remember'))) {
            $user = Auth::user();

            $user->resetFailedLoginAttempts();
            FailedLoginAttempt::forIp($ip)->update(['attempts' => 0]);

            $request->session()->regenerate();

            if ($user->roles->isNotEmpty()) {
                $request->session()->put('active_role_id', $user->roles->first()->id);
            }

            Log::info('Login successful', ['user_id' => Auth::id()]);

            return $this->redirectBasedOnRole($user);
        }

        Log::warning('Login failed', ['identity' => $identity, 'ip' => $ip]);

        $user->passwordOtps()->latest()->delete();

        if ($user->failed_login_attempts + 1 == self::EMAIL_COMPROMISED_THRESHOLD) {
            Mail::to($user->email)->queue(new AccountCompromisedMail(
                $user->name, $user->email, $user->failed_login_attempts + 1, $ip
            ));
            Log::warning('Account compromised threshold reached', [
                'user_id' => $user->id, 'identity' => $identity, 'ip' => $ip,
                'attempts' => $user->failed_login_attempts + 1,
            ]);
        }

        $user->incrementFailedLoginAttempts();
        $this->trackIpAttempt($ip, $user->failed_login_attempts, $identity);

        if ($user->failed_login_attempts >= self::EMAIL_MAX_ATTEMPTS) {
            Mail::to($user->email)->send(new AccountLockedMail(
                $user->name, $user->email, $user->failed_login_attempts, $ip
            ));

            $superAdminIds = usersHavingPermission('general_admin.administrable');
            $superAdmins = User::whereIn('id', $superAdminIds)->pluck('email');
            Mail::to($superAdmins)->send(new AccountLockedMail(
                $user->name, $user->email, $user->failed_login_attempts, $ip
            ));

            Log::critical('Account locked due to failed login attempts', [
                'user_id' => $user->id, 'identity' => $identity, 'ip' => $ip,
                'attempts' => $user->failed_login_attempts,
            ]);

            return $this->showLoginWithError(
                $request,
                'Akun terkunci karena terlalu banyak percobaan login gagal. Super Admin telah diberitahu.',
                0,
                'account_locked'
            );
        }

        return $this->showLoginWithError($request, 'Email atau NUPY salah.');
    }

    private function trackIpAttempt(string $ip, ?int $userAttempts, string $email): void
    {
        $record = FailedLoginAttempt::forIp($ip)->active()->first();

        if ($record) {
            $record->recordAttempt($userAttempts !== null && $userAttempts > 0);
        } else {
            $record = FailedLoginAttempt::create([
                'ip_address' => $ip, 'email' => $email,
                'attempts' => 0, 'last_attempt_at' => now(),
            ]);
            $record->recordAttempt(false);
        }

        if ($record->isLockedByIp()) {
            $superAdminIds = usersHavingPermission('general_admin.administrable');
            $superAdmins = User::whereIn('id', $superAdminIds)->pluck('email');
            Mail::to($superAdmins)->queue(new IpBlockedMail(
                $ip, $record->locked_until->diffForHumans(), $record->attempts
            ));

            Log::warning('IP blocked due to failed login attempts', [
                'ip' => $ip, 'email' => $email,
                'attempts' => $record->attempts, 'locked_until' => $record->locked_until,
            ]);
        }
    }

    private function showLoginWithError(
        Request $request, string $message, int $seconds = 0, string $errorType = 'login_failed'
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

    private function redirectBasedOnRole($user): RedirectResponse
    {
        if (method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin()) {
            return redirect()->route('system.dashboard');
        }

        if ($user->hasRole('Super Admin')) {
            return redirect()->route('system.dashboard');
        }

        $roles = $user->getRoleNames();

        if ($roles->contains(fn ($name) => stripos($name, 'applicant') !== false)) {
            return redirect()->away(config('app.recruitment_url', 'https://recruitment.abuhurairah.id'))
                ->with('info', 'Anda akan diarahkan ke portal recruitment.');
        }

        if ($roles->isEmpty()) {
            return redirect()->route('auth.validator')
                ->with('warning', 'Akun Anda belum memiliki role. Silakan diverifikasi.');
        }

        if ($roles->contains('Wali Santri')) {
            Auth::logout();
            return redirect('/access-denied')
                ->with('error', 'Akun ini adalah Wali Santri dan tidak memiliki akses ke website ini.');
        }

        $userId = $user->id;
        $jabatanLower = strtolower(trim((string) ($user->employment?->jabatan ?? '')));
        $roleName = strtolower(trim((string) $roles->first()));

        $jabatanMap = [
            'kepala satuan pendidikan'  => 'user.dashboard.kepala-satuan-pendidikan',
            'wakil kepala satuan'       => 'user.dashboard.wakil-kepala',
            'wakasek'                   => 'user.dashboard.wakil-kepala',
            'kepala tata usaha'         => 'user.dashboard.ka-tata-usaha',
            'kepala tu sekolah'         => 'user.dashboard.ka-tata-usaha',
            'staf tata usaha'           => 'user.dashboard.staf-tata-usaha',
            'tata usaha'                => 'user.dashboard.staf-tata-usaha',
            'tu sekolah'                => 'user.dashboard.staf-tata-usaha',
            'bendahara sekolah'         => 'user.dashboard.bendahara',
            'bendahara penerimaan'      => 'user.dashboard.keuangan',
            'bendahara pengeluaran'     => 'user.dashboard.keuangan',
            'kasir spp'                 => 'user.dashboard.keuangan',
            'guru umum'                 => 'user.dashboard.guru',
            'guru agama'                => 'user.dashboard.guru',
            'guru hadits'               => 'user.dashboard.guru',
            'guru bahasa arab'          => 'user.dashboard.guru',
            'guru tahfidz'              => 'user.dashboard.guru',
            'guru kelas'                => 'user.dashboard.guru',
            'guru'                      => 'user.dashboard.guru',
            'musrif'                    => 'user.dashboard.asrama',
            'musrifah'                  => 'user.dashboard.asrama',
            'musyrif'                   => 'user.dashboard.asrama',
            'musyrifah'                 => 'user.dashboard.asrama',
            'kepala asrama'             => 'user.dashboard.asrama',
            'wakil kepala asrama'       => 'user.dashboard.asrama',
            'tata usaha asrama'         => 'user.dashboard.asrama',
            'tu asrama'                 => 'user.dashboard.asrama',
            'staf perizinan'            => 'user.dashboard.asrama',
            'kepala uks'                => 'user.uks.dashboard',
            'staf uks putra'            => 'user.uks.dashboard',
            'staf uks putri'            => 'user.uks.dashboard',
            'mudir'                     => 'user.dashboard.pimpinan',
            'pengasuh pesantren'        => 'user.dashboard.pimpinan',
            'wadir 1'                   => 'user.dashboard.pimpinan',
            'wadir 2'                   => 'user.dashboard.pimpinan',
            'kepala departemen tahfidz' => 'user.dashboard.tahfidz',
            'wakil kepala departemen tahfidz' => 'user.dashboard.tahfidz',
            'tata usaha departemen tahfidz' => 'user.dashboard.tahfidz',
            'tu departemen tahfidz'     => 'user.dashboard.tahfidz',
            'staf tu departemen tahfidz' => 'user.dashboard.tahfidz',
            'kepala departemen bahasa'  => 'user.dashboard',
            'wakil kepala departemen bahasa'  => 'user.dashboard',
            'koordinator perpustakaan'  => 'user.dashboard',
            'kepala perpustakaan'       => 'user.dashboard',
            'pustakawan'                => 'user.dashboard',
            'staf perpustakaan'         => 'user.dashboard',
            'kepala satuan keamanan'    => 'user.dashboard',
            'koordinator satuan keamanan' => 'user.dashboard',
            'anggota satuan keamanan'   => 'user.dashboard',
            'petugas keamanan'          => 'user.dashboard',
            'kepala humas & personalia' => 'user.dashboard.personalia',
            'kepala humas'              => 'user.dashboard.personalia',
            'kepala personalia'         => 'user.dashboard.personalia',
            'staf humas'                => 'user.dashboard.personalia',
            'staf personalia'           => 'user.dashboard.personalia',
            'kepala unit rumah tangga'  => 'user.dashboard.unit-rumah-tangga',
            'koordinator sarpras'       => 'user.dashboard.unit-rumah-tangga',
            'koordinator sarana prasarana' => 'user.dashboard.unit-rumah-tangga',
            'teknisi maintenance'       => 'user.dashboard.unit-rumah-tangga',
            'teknisi'                   => 'user.dashboard.unit-rumah-tangga',
            'petugas kebersihan'        => 'user.dashboard.unit-rumah-tangga',
            'janitor'                   => 'user.dashboard.unit-rumah-tangga',
            'driver'                    => 'user.dashboard.unit-rumah-tangga',
            'pengemudi'                 => 'user.dashboard.unit-rumah-tangga',
            'tata usaha urt'            => 'user.dashboard.unit-rumah-tangga',
            'tu urt'                    => 'user.dashboard.unit-rumah-tangga',
            'staf urt'                  => 'user.dashboard.unit-rumah-tangga',
            'kepala departemen keuangan' => 'user.dashboard.keuangan',
            'kepala keuangan'           => 'user.dashboard.keuangan',
            'staf payroll'              => 'user.dashboard.keuangan',
            'staf gaji'                 => 'user.dashboard.keuangan',
            'akuntan'                   => 'user.dashboard.keuangan',
            'staf pembukuan'            => 'user.dashboard.keuangan',
            'staf akuntansi'            => 'user.dashboard.keuangan',
            'staf keuangan'             => 'user.dashboard.keuangan',
            'kepala unit ti'            => 'user.dashboard',
            'kepala unit teknologi informasi' => 'user.dashboard',
            'network & infrastructure administrator' => 'user.dashboard',
            'application & database specialist' => 'user.dashboard',
            'it support'                => 'user.dashboard',
            'hardware technician'       => 'user.dashboard',
            'teknisi jaringan'          => 'user.dashboard',
            'kepala unit pelayanan gizi' => 'user.dashboard',
            'kepala unit gizi & logistik' => 'user.dashboard',
            'ahli gizi'                 => 'user.dashboard',
            'menu planner'              => 'user.dashboard',
            'chef'                      => 'user.dashboard',
            'staf dapur'                => 'user.dashboard',
            'staf logistik bahan pangan' => 'user.dashboard',
            'koordinator logistik'      => 'user.dashboard',
            'staf gizi'                 => 'user.dashboard',
            'staf logistik'             => 'user.dashboard',
        ];

        if ($jabatanLower && isset($jabatanMap[$jabatanLower])) {
            $route = $jabatanMap[$jabatanLower];
            return $route === 'sarpras.dashboard'
                ? redirect()->route($route)
                : redirect()->route($route, ['userId' => $userId]);
        }

        if ($jabatanLower) {
            foreach ($jabatanMap as $pattern => $route) {
                if (str_contains($jabatanLower, $pattern)) {
                    return $route === 'sarpras.dashboard'
                        ? redirect()->route($route)
                        : redirect()->route($route, ['userId' => $userId]);
                }
            }
        }

        $userTugas = method_exists($user, 'tugasTambahan')
            ? $user->tugasTambahan->pluck('nama')->toArray()
            : [];

        $tugasMap = [
            'Wali Kelas'                  => 'user.dashboard.wali-kelas',
            'Koordinator Kurikulum'       => 'user.dashboard.koordinator-kurikulum',
            'Koordinator Kesiswaan'       => 'user.dashboard.koordinator-kesiswaan',
            'Koordinator Ekstrakurikuler' => 'user.dashboard.koordinator-ekskul',
            'Koordinator Laboratorium'    => 'user.dashboard.koordinator-lab',
            'Koordinator Sarpras Satuan Pendidikan' => 'user.dashboard.koordinator-sarpras',
            'Koordinator Guru Umum'       => 'user.dashboard.koordinator-guru',
            'Koordinator Guru Agama'      => 'user.dashboard.koordinator-guru',
            'Koordinator Guru Hadits'     => 'user.dashboard.koordinator-guru',
            'Koordinator Guru Bahasa Arab' => 'user.dashboard.koordinator-guru',
            'Koordinator Guru Tahfidz'    => 'user.dashboard.koordinator-guru',
        ];

        foreach ($userTugas as $tugas) {
            if (isset($tugasMap[$tugas])) {
                return redirect()->route($tugasMap[$tugas], ['userId' => $userId]);
            }
        }

        $roleDashboardMap = match (true) {
            str_contains($roleName, 'kepala satuan')       => 'user.dashboard.satuan-pendidikan',
            str_contains($roleName, 'pimpinan')            => 'user.dashboard.pimpinan',
            str_contains($roleName, 'satuan pendidikan')   => 'user.dashboard.satuan-pendidikan',
            str_contains($roleName, 'asrama')              => 'user.dashboard.asrama',
            str_contains($roleName, 'uks')                 => 'user.uks.dashboard',
            str_contains($roleName, 'tahfidz')             => 'user.dashboard.tahfidz',
            str_contains($roleName, 'bahasa')              => 'user.dashboard',
            str_contains($roleName, 'perpustakaan')        => 'user.dashboard',
            str_contains($roleName, 'keamanan')            => 'user.dashboard',
            str_contains($roleName, 'humas')               => 'user.dashboard.personalia',
            str_contains($roleName, 'rumah tangga')        => 'user.dashboard.unit-rumah-tangga',
            str_contains($roleName, 'keuangan')            => 'user.dashboard.keuangan',
            str_contains($roleName, 'teknologi informasi') => 'user.dashboard',
            str_contains($roleName, 'gizi')                => 'user.dashboard',
            default                                        => null,
        };

        if ($roleDashboardMap) {
            return $roleDashboardMap === 'sarpras.dashboard'
                ? redirect()->route($roleDashboardMap)
                : redirect()->route($roleDashboardMap, ['userId' => $userId]);
        }

        return redirect()->route('user.dashboard', ['userId' => $userId]);
    }
}