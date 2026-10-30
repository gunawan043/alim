<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        // Saat KSP/Waka/SA sedang masuk sementara ke akun GTK, jangan paksa
        // mengganti password akun target. Aturan wajib ganti tetap berlaku
        // saat GTK login dengan akunnya sendiri.
        if ($request->session()->has('impersonate.actor_id')) {
            return $next($request);
        }

        if ($request->routeIs('password.force', 'password.force.update', 'logout', 'login', 'login.process', 'password.*', 'two-factor.*')) {
            return $next($request);
        }

        return redirect()->route('password.force')
            ->with('warning', 'Password awal wajib diganti sebelum menggunakan aplikasi.');
    }
}
