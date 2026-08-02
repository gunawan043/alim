<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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

        // Assign default employee role if verification succeeds
        // This is configurable — you may want to instead just flag the account
        return redirect('/')->with('success', 'Identitas berhasil diverifikasi. Selamat datang di ALIM.');
    }

    private function checkVerificationData($user, string $type, string $value): bool
    {
        switch ($type) {
            case 'nik':
                // Check against NIK in users table or wali_santri table
                $userNik = $user->getAttribute('nik') ?? $user->getAttribute('no_ktp');
                if ($userNik && Hash::check($value, $userNik)) {
                    return true;
                }
                // Also check wali_santri table
                $waliSantri = \App\Models\WaliSantri::where('user_id', $user->id)
                    ->where('nik_wali', $value)
                    ->exists();

                return $waliSantri;

            case 'employee_id':
                // Check against employee_id field if exists
                $employeeId = $user->getAttribute('employee_id');
                if ($employeeId && strcasecmp($employeeId, $value) === 0) {
                    return true;
                }

                return false;

            case 'phone':
                // Check against phone number in user or wali_santri table
                $phone = $user->getAttribute('no_hp') ?? $user->getAttribute('phone');
                if ($phone && preg_match('/'.preg_quote($value, '/').'$/', $phone)) {
                    return true;
                }
                $waliSantri = \App\Models\WaliSantri::where('user_id', $user->id)
                    ->where('no_hp', 'like', '%'.$value)
                    ->exists();

                return $waliSantri;

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
