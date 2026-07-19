<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RoleLevelMiddleware
{
    public function handle(Request $request, Closure $next, int $maxLevel)
    {
        $user = $request->user();
        $roleNames = $user->effectiveRoles();

        if (! $roleNames) {
            Log::warning('RoleLevelMiddleware: no roles resolved', [
                'user_id' => $user?->id,
                'max_level' => $maxLevel,
                'ip' => $request->ip(),
            ]);

            abort(403);
        }

        $userLevel = \App\Models\Role::whereIn('name', $roleNames)
            ->min('level');

        // no role row with level → fail closed
        if ($userLevel === null) {
            Log::warning('RoleLevelMiddleware: role has no level', [
                'user_id' => $user->id,
                'max_level' => $maxLevel,
                'ip' => $request->ip(),
            ]);

            abort(403);
        }

        // lower (or equal) level = higher authority → allowed
        if ($userLevel <= $maxLevel) {
            return $next($request);
        }

        abort(403, 'Role level too low.');
    }
}
