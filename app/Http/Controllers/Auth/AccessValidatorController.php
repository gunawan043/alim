<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\WaliSantri;
use App\Support\GtkRoleResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AccessValidatorController extends Controller
{
    /**
     * Show the validator page — for users with no valid employee role.
     */
    public function show()
    {
        $user = Auth::user();

        // System Administrator bypasses the validator entirely.
        if (method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin()) {
            return redirect()->route('system.dashboard')
                ->with('info', 'Akun Anda sudah memiliki akses sistem penuh.');
        }

        // If already has valid role, redirect to dashboard
        if ($this->hasEmployeeRole($user)) {
            return redirect('/')->with('info', 'Akun Anda sudah memiliki akses.');
        }

        return view('auth.access-validator');
    }

    /**
     * Process user verification — they submit ID/nik to prove identity.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'verification_type' => 'required|in:nik,employee_id,phone',
            'verification_value' => 'required|string|min:5|max:50',
            'password' => 'required|current_password',
        ]);

        $user = Auth::user();

        // Verify password first
        if (! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Password tidak cocok.',
            ]);
        }

        // Check verification against stored data
        $verified = $this->checkVerificationData($user, $request->verification_type, $request->verification_value);

        if (! $verified) {
            throw ValidationException::withMessages([
                'verification_value' => 'Data yang dimasukkan tidak cocok dengan data kami. Hubungi HRD untuk verifikasi.',
            ]);
        }

        // Log verification attempt
        Log::info('Access verification attempt', [
            'user_id' => $user->id,
            'type' => $request->verification_type,
            'success' => true,
            'ip' => $request->ip(),
        ]);

        // Setelah identitas terbukti, beri role supaya tidak terlempar lagi
        // ke halaman verifikasi saat membuka dashboard.
        if ($user->roles()->doesntExist()) {
            $role = GtkRoleResolver::resolve(
                $user->employment?->jabatan,
                $user->employment?->jabatan_id,
                $user->gtkWorkUnits()->where('is_primary', true)->value('work_unit_id'),
            );

            if ($role && Role::where('name', $role)->exists()) {
                $user->syncRoles([$role]);
            }
        }

        return redirect('/')->with('success', 'Identitas berhasil diverifikasi. Selamat datang di ALIM.');
    }

    private function checkVerificationData($user, string $type, string $value): bool
    {
        $value = trim($value);

        switch ($type) {
            case 'nik':
                // NIK tersimpan sebagai teks biasa (users / gtk_profiles / wali),
                // jadi dibandingkan langsung — bukan lewat Hash::check.
                $niks = array_filter([
                    $user->getAttribute('nik'),
                    $user->getAttribute('no_ktp'),
                    $user->nik_wali,
                    $user->gtkProfile?->nik,
                ]);

                foreach ($niks as $nik) {
                    if (hash_equals((string) $nik, $value)) {
                        return true;
                    }
                }

                return WaliSantri::where('user_id', $user->id)
                    ->where('nik_wali', $value)
                    ->exists();

            case 'employee_id':
                $ids = array_filter([
                    $user->getAttribute('employee_id'),
                    $user->employment?->nupy,
                ]);

                foreach ($ids as $id) {
                    if (strcasecmp((string) $id, $value) === 0) {
                        return true;
                    }
                }

                return false;

            case 'phone':
                $phones = array_filter([
                    $user->getAttribute('no_hp'),
                    $user->getAttribute('phone'),
                    $user->gtkContact?->no_hp,
                ]);

                foreach ($phones as $phone) {
                    if (preg_match('/'.preg_quote($value, '/').'$/', (string) $phone)) {
                        return true;
                    }
                }

                return WaliSantri::where('user_id', $user->id)
                    ->where('no_hp', 'like', '%'.$value)
                    ->exists();

            default:
                return false;
        }
    }

    private function hasEmployeeRole($user): bool
    {
        if (! $user) {
            return false;
        }

        $roles = $user->getRoleNames();
        if ($roles->isEmpty()) {
            return false;
        }

        return ! $roles->contains(fn ($name) => $name === 'Wali Santri');
    }
}
