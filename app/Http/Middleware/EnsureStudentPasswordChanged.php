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

        if ($request->routeIs('password.force', 'password.force.update', 'logout', 'login', 'login.process', 'password.*', 'two-factor.*')) {
            return $next($request);
        }

        return redirect()->route('password.force')
            ->with('warning', 'Password awal wajib diganti sebelum menggunakan aplikasi.');
    }
}
