<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ForcePasswordController extends Controller
{
    public function show(Request $request)
    {
        if (! $request->user()?->must_change_password) {
            return app(LoginController::class)->redirectBasedOnRole($request->user());
        }

        return view('auth.force-password');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ]);

        $user = $request->user();
        $user->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        return app(LoginController::class)->redirectBasedOnRole($user)
            ->with('success', 'Password berhasil diganti.');
    }
}
