<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\LoginActivity;
use App\Models\PasswordOtp;
use App\Models\User;
use Illuminate\Http\Request;

class PasswordResetLogController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->route('userId');
        $events = [
            'password_otp_requested' => 'OTP Diminta',
            'password_otp_resent' => 'OTP Dikirim Ulang',
            'password_otp_failed' => 'OTP Gagal',
            'password_otp_verified' => 'OTP Terverifikasi',
            'password_reset_completed' => 'Password Direset',
        ];

        $query = LoginActivity::with('user')->whereIn('event', array_keys($events));

        if ($request->filled('search')) {
            $search = '%'.trim((string) $request->search).'%';
            $query->where(function ($q) use ($search) {
                $q->where('identity', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('ip_address', 'like', $search)
                    ->orWhere('reason', 'like', $search)
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', $search)->orWhere('email', 'like', $search);
                    });
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $otpLogs = $query->orderByDesc('created_at')->paginate(30)->withQueryString();

        // OTP records (unused/expired)
        $otpQuery = PasswordOtp::with('user');

        if ($request->has('search') && $request->search) {
            $otpQuery->where(function ($q) use ($request) {
                $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$request->search}%"));
            });
        }

        $otpRecords = $otpQuery->orderBy('created_at', 'desc')->paginate(30, ['*'], 'otp_page');

        $users = User::orderBy('name')->get();

        $stats = [
            'requested' => LoginActivity::whereIn('event', ['password_otp_requested', 'password_otp_resent'])->count(),
            'requested_today' => LoginActivity::whereIn('event', ['password_otp_requested', 'password_otp_resent'])->whereDate('created_at', today())->count(),
            'verified' => LoginActivity::where('event', 'password_otp_verified')->count(),
            'verified_today' => LoginActivity::where('event', 'password_otp_verified')->whereDate('created_at', today())->count(),
            'failed' => LoginActivity::where('event', 'password_otp_failed')->count(),
            'failed_today' => LoginActivity::where('event', 'password_otp_failed')->whereDate('created_at', today())->count(),
            'completed' => LoginActivity::where('event', 'password_reset_completed')->count(),
            'otp_total' => PasswordOtp::count(),
            'otp_today' => PasswordOtp::whereDate('created_at', today())->count(),
        ];

        return view('super-admin.password-reset-logs.index', compact(
            'otpLogs', 'otpRecords', 'users', 'userId', 'stats', 'events'
        ));
    }
}
