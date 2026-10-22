<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordChangedMail;
use App\Services\SecurityNotificationService;
use App\Services\SecurityPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::min(SecurityPolicy::passwordMinLength()), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
            'must_change_password' => false,
        ]);

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')
                ->where('user_id', $request->user()->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        try {
            Mail::to($request->user()->email)->send(new PasswordChangedMail(
                $request->user()->name,
                now()->locale('id')->translatedFormat('d F Y H:i'),
                $request->ip(),
            ));
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim notifikasi password berubah', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);
        }

        app(SecurityNotificationService::class)->passwordChanged($request->user(), $request);

        return back()->with('status', 'password-updated');
    }
}
