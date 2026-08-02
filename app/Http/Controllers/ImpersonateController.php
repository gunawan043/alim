<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class ImpersonateController extends Controller
{
    public function start(Request $request, User $targetUser): RedirectResponse
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        if (! canPermission('impersonate_role')) {
            abort(403, 'Anda tidak memiliki hak impersonate.');
        }

        if ($actor->id === $targetUser->id) {
            return back()->with('error', 'Anda tidak dapat login sebagai diri sendiri.');
        }

        $targetRoles = array_map('strtolower', $targetUser->effectiveRoles());

        if (in_array('super admin', $targetRoles, true)) {
            abort(403, 'Tidak dapat impersonate Super Admin.');
        }

        if (! $targetUser->is_active) {
            return back()->with('error', 'User target non-aktif.');
        }

        if ($targetUser->isSystemAdmin()) {
            abort(403, 'Tidak dapat impersonate System Admin.');
        }

        $isAlreadyImpersonating = $request->session()->has('impersonate.actor_id');
        $originalActorId = $isAlreadyImpersonating
            ? (string) $request->session()->get('impersonate.actor_id')
            : (string) $actor->id;
        $originalActorName = $isAlreadyImpersonating
            ? (string) $request->session()->get('impersonate.actor_name', $actor->name)
            : $actor->name;
        $originalActor = User::find($originalActorId) ?? $actor;

        AuditLog::create([
            'user_id' => $originalActor->id,
            'action' => 'impersonate.start',
            'table_name' => 'users',
            'record_id' => $targetUser->id,
            'record_type' => 'user',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        Auth::login($targetUser);
        $request->session()->regenerate();

        $request->session()->put('impersonate.actor_id', $originalActorId);
        $request->session()->put('impersonate.actor_name', $originalActorName);
        $request->session()->flash('impersonate.active', true);

        return redirect()->route('root')
            ->with('warning', "Anda sekarang login sebagai {$targetUser->name}. Klik 'Stop Impersonate' untuk kembali ke akun Super Admin.");
    }

    public function stop(Request $request): RedirectResponse
    {
        $current = $request->user();
        $previousActorId = $request->session()->get('impersonate.actor_id');

        if (! $current instanceof User) {
            return redirect()->route('login');
        }

        if ($previousActorId === null) {
            return redirect()->route('login')
                ->with('info', 'Tidak ada sesi impersonate yang aktif.');
        }

        $previousActor = User::find((string) $previousActorId);

        if (! $previousActor instanceof User || ! $previousActor->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Akun Super Admin asal tidak aktif. Silakan login ulang.');
        }

        AuditLog::create([
            'user_id' => (string) $previousActorId,
            'action' => 'impersonate.stop',
            'table_name' => 'users',
            'record_id' => $current->id,
            'record_type' => 'user',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        Auth::login($previousActor);

        $request->session()->forget(['impersonate.actor_id', 'impersonate.actor_name']);
        $request->session()->regenerate();

        return redirect()->route('root')
            ->with('success', "Sesi impersonate diakhiri. Anda kembali login sebagai {$previousActor->name}.");
    }
}
