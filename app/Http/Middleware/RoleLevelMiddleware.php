<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleLevelMiddleware
{
    public function handle(Request $request, Closure $next, int $maxLevel)
    {
        $user = $request->user();

        if (!$user || !$user->role) {
            abort(403);
        }

        // contoh: level <= 5
        if ($user->role->level <= $maxLevel) {
            return $next($request);
        }

        abort(403, 'Role level too low.');
    }
}
