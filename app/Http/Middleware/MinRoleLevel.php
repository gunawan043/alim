<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MinRoleLevel
{
    public function handle(Request $request, Closure $next, int $level): Response
    {
        $user = $request->user();

        if (!$user || !$user->roles()->exists()) {
            abort(403, 'Akses ditolak');
        }

        // ambil level TERKECIL (paling tinggi)
        $userLevel = $user->roles()->min('level');

        if ($userLevel > $level) {
            abort(403, 'Hak akses tidak mencukupi');
        }

        return $next($request);
    }
}