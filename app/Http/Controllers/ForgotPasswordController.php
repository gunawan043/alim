<?php

namespace App\Http\Controllers;

use App\Mail\ResetPasswordOtpMail;
use App\Models\PasswordOtp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    public function showForm()
    {
        return view('auth.passwords.email');
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        try {
            $user = User::where('email', $request->email)->firstOrFail();

            $otp = random_int(100000, 999999);

            // Gunakan user_id (foreignUuid ke users.id yang merupakan UUID) untuk referensi
            PasswordOtp::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'uuid' => Str::uuid(),
                    'otp_hash' => Hash::make($otp),
                    'expires_at' => now()->addMinutes(10),
                ]
            );

            // Kirim email OTP
            try {
                Mail::to($user->email)
                    ->send(new ResetPasswordOtpMail($otp, $user->name));
            } catch (\Exception $e) {
                Log::error('Error sending OTP email: '.$e->getMessage());
                // Jangan throw error, tapi log saja
            }

            // Simpan user_id dan email di session (bukan flash, agar survive redirect)
            session(['reset_user_uuid' => $user->id]);
            session(['reset_email' => $user->email]);

            $savedOtp = PasswordOtp::where('user_id', $user->id)->first();

            // Simpan expiry timestamp di session agar survive refresh
            session(['otp_expires_at' => $savedOtp->expires_at->timestamp]);

            return redirect()->route('password.otp.form')
                ->with('status', 'Kode OTP dikirim ke email Anda.');

        } catch (\Exception $e) {
            Log::error('Error sending OTP: '.$e->getMessage());

            return back()->withErrors([
                'email' => 'Terjadi kesalahan. Silakan coba lagi.',
            ]);
        }
    }

    public function showOtpForm()
    {
        if (! session('reset_user_uuid')) {
            return redirect()->route('password.request')
                ->with('error', 'Sesi tidak valid. Silakan request OTP lagi.');
        }

        $otpData = PasswordOtp::where('user_id', session('reset_user_uuid'))
            ->where('expires_at', '>', now())
            ->first();

        $expiresAt = $otpData?->expires_at
            ?? (session()->has('otp_expires_at') ? now()->setTimestamp((int) session('otp_expires_at')) : null);

        return view('auth.passwords.verify-otp', [
            'email' => session('email'),
            'expiresAt' => $expiresAt,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        if (! session('reset_user_uuid')) {
            throw ValidationException::withMessages([
                'otp' => 'Sesi tidak valid. Silakan request OTP lagi.',
            ]);
        }

        try {
            $otpData = PasswordOtp::where('user_id', session('reset_user_uuid'))
                ->where('expires_at', '>', now())
                ->first();

            if (! $otpData) {
                throw ValidationException::withMessages([
                    'otp' => 'OTP tidak ditemukan atau sudah kadaluarsa.',
                ]);
            }

            if (! Hash::check($request->otp, $otpData->otp_hash)) {
                // Hitung percobaan yang gagal
                $failedAttempts = session('otp_failed_attempts', 0) + 1;
                session(['otp_failed_attempts' => $failedAttempts]);

                if ($failedAttempts >= 3) {
                    // Hapus OTP jika gagal 3x
                    $otpData->delete();
                    session()->forget(['reset_user_uuid', 'otp_failed_attempts']);

                    throw ValidationException::withMessages([
                        'otp' => 'Terlalu banyak percobaan gagal. Silakan request OTP baru.',
                    ]);
                }

                $remainingAttempts = 3 - $failedAttempts;
                throw ValidationException::withMessages([
                    'otp' => "OTP salah. Sisa percobaan: {$remainingAttempts}",
                ]);
            }

            // Reset failed attempts jika berhasil
            session()->forget('otp_failed_attempts');

            // Tandai OTP sebagai terpakai
            $otpData->update(['is_used' => true]);

            // Set session verifikasi
            session(['otp_verified' => true]);

            return redirect()->route('password.reset.form')
                ->with('success', 'OTP berhasil diverifikasi.');

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error verifying OTP: '.$e->getMessage());

            throw ValidationException::withMessages([
                'otp' => 'Terjadi kesalahan. Silakan coba lagi.',
            ]);
        }
    }

    public function showResetForm()
    {
        if (! session('reset_user_uuid') || ! session('otp_verified')) {
            return redirect()->route('password.request')
                ->with('error', 'Sesi tidak valid. Silakan mulai dari awal.');
        }

        $userEmail = User::where('id', session('reset_user_uuid'))->value('email');

        return view('auth.passwords.reset', [
            'email' => $userEmail,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        if (! session('reset_user_uuid')) {
            return redirect()->route('password.request')
                ->with('error', 'Sesi tidak valid. Silakan mulai dari awal.');
        }

        try {
            $user = User::where('id', session('reset_user_uuid'))->firstOrFail();

            // Update password
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            // Hapus semua OTP untuk user ini
            PasswordOtp::where('user_id', $user->id)->delete();

            // Clear semua session terkait
            session()->forget([
                'reset_user_uuid',
                'otp_verified',
                'otp_failed_attempts',
                'email',
            ]);

            // Hapus semua session lain yang mungkin terkait
            $request->session()->flush();

            return redirect()->route('login')
                ->with('success', 'Password berhasil direset. Silakan login dengan password baru.');

        } catch (\Exception $e) {
            Log::error('Error resetting password: '.$e->getMessage());

            return back()->withErrors([
                'password' => 'Terjadi kesalahan. Silakan coba lagi.',
            ]);
        }
    }

    public function resendOtp()
    {
        if (! session('reset_user_uuid')) {
            return redirect()->route('password.request')
                ->with('error', 'Sesi tidak valid. Silakan request OTP lagi.');
        }

        try {
            $user = User::where('id', session('reset_user_uuid'))->firstOrFail();

            $otp = random_int(100000, 999999);

            PasswordOtp::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'uuid' => Str::uuid(),
                    'otp_hash' => Hash::make($otp),
                    'expires_at' => now()->addMinutes(10),
                    'is_used' => false,
                ]
            );

            $newOtpData = PasswordOtp::where('user_id', $user->id)->first();

            // Kirim email OTP
            try {
                Mail::to($user->email)
                    ->send(new ResetPasswordOtpMail($otp, $user->name));
            } catch (\Exception $e) {
                Log::error('Error resending OTP email: '.$e->getMessage());
            }

            // Simpan expiry timestamp di session agar survive refresh
            session(['otp_expires_at' => $newOtpData->expires_at->timestamp]);

            return back()->with('status', 'Kode OTP baru telah dikirim ke email Anda.')
                ->with('email', $user->email);

        } catch (\Exception $e) {
            Log::error('Error resending OTP: '.$e->getMessage());

            return back()->withErrors([
                'otp' => 'Terjadi kesalahan. Silakan coba lagi.',
            ]);
        }
    }

    public function checkOtpValidity(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        if (! session('reset_user_uuid')) {
            return response()->json([
                'valid' => false,
                'message' => 'Sesi tidak valid',
            ], 400);
        }

        try {
            $otpData = PasswordOtp::where('user_id', session('reset_user_uuid'))
                ->where('expires_at', '>', now())
                ->where('is_used', false)
                ->first();

            if (! $otpData) {
                return response()->json([
                    'valid' => false,
                    'message' => 'OTP tidak ditemukan atau sudah kadaluarsa',
                ]);
            }

            if (! Hash::check($request->otp, $otpData->otp_hash)) {
                return response()->json([
                    'valid' => false,
                    'message' => 'OTP salah',
                ]);
            }

            return response()->json([
                'valid' => true,
                'message' => 'OTP valid',
            ]);

        } catch (\Exception $e) {
            Log::error('Error checking OTP validity: '.$e->getMessage());

            return response()->json([
                'valid' => false,
                'message' => 'Terjadi kesalahan',
            ], 500);
        }
    }

    public function cancelReset()
    {
        // Hapus OTP yang belum digunakan
        if (session('reset_user_uuid')) {
            PasswordOtp::where('user_id', session('reset_user_uuid'))
                ->where('is_used', false)
                ->delete();
        }

        // Clear semua session
        session()->forget([
            'reset_user_uuid',
            'otp_verified',
            'otp_failed_attempts',
            'email',
        ]);

        return redirect()->route('login')
            ->with('info', 'Proses reset password dibatalkan.');
    }
}
