<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PasswordOtp;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class PasswordResetLogController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->route('userId');
        $query = AuditLog::with('user')
            ->where('action', 'PASSWORD_OTP_VERIFIED');

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$request->search}%"))
                    ->orWhere('record_id', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('from_date') && $request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date') && $request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $otpVerifiedLogs = $query->orderBy('created_at', 'desc')->paginate(30);

        // OTP records (unused/expired)
        $otpQuery = PasswordOtp::with('user');

        if ($request->has('search') && $request->search) {
            $otpQuery->where(function ($q) use ($request) {
                $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$request->search}%"));
            });
        }

        $otpRecords = $otpQuery->orderBy('created_at', 'desc')->paginate(30, ['*'], 'otp_page');

        $users = \App\Models\User::orderBy('name')->get();

        return view('super-admin.password-reset-logs.index', compact(
            'otpVerifiedLogs', 'otpRecords', 'users', 'userId'
        ));
    }
}
