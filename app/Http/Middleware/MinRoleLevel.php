<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class MinRoleLevel
{
    public function handle(Request $request, Closure $next, int $level): Response
    {
        $user = $request->user();
        $roleNames = $user->effectiveRoles();

        if (! $roleNames) {
            Log::warning('MinRoleLevel: no roles resolved', [
                'user_id' => $user?->id,
                'min_level' => $level,
                'ip' => $request->ip(),
            ]);

            abort(403, 'Akses ditolak');
        }

        $userLevel = \App\Models\Role::whereIn('name', $roleNames)
            ->min('level');

        if ($userLevel === null || $userLevel > $level) {
            abort(403, 'Hak akses tidak mencukupi');
        }

        return $next($request);
    }
}
