<?php

namespace App\Http\Middleware;

use Closure;

class RoleEnforced
{
    public function handle($request, Closure $next)
    {
        $secure = $request->get('secure_access');

        if (! canPermission($secure->role_name)) {
            abort(403);
        }

        return $next($request);
    }
}
