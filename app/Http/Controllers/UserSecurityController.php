<?php

namespace App\Http\Controllers;

use App\Mail\AccountUnlockedMail;
use App\Models\FailedLoginAttempt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class UserSecurityController extends Controller
{
    public function unlock(User $user)
    {
        $user->update([
            'failed_login_attempts' => 0,
            'locked_at' => null,
            'locked_until' => null,
            'locked_reason' => null,
        ]);

        Mail::to($user->email)->queue(new AccountUnlockedMail($user->name, $user->email));

        return back()->with('success', 'Akun berhasil dibuka.');
    }

    public function unblockIp(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|ip',
        ]);

        $deleted = FailedLoginAttempt::forIp($request->ip_address)->delete();

        if ($deleted) {
            return back()->with('success', "IP {$request->ip_address} berhasil dibuka blokirnya.");
        }

        return back()->with('error', "IP {$request->ip_address} tidak ditemukan dalam daftar blokir.");
    }
}
