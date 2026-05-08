<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Mail\AccountUnlockedMail;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Mail\AccountLockedMail;

class UserSecurityController extends Controller
{

    public function unlock(User $user)
    {
        $user->update([
            'failed_login_attempts' => 0,
            'locked_at' => null,
            'locked_reason' => null,
        ]);

        $superAdmins = User::role('Super Admin')->pluck('email');

        Mail::to($superAdmins)->send(
            new AccountLockedMail($user)
        );

        return back()->with('success', 'Akun berhasil dibuka.');
    }


}
