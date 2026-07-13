<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        // Super Admin can access everything
        if (canPermission('super-admin-only')) {
            return $next($request);
        }

        // Check if user has any of the required roles
        foreach ($roles as $role) {
            // 1. Snapshot path — the role-name was promoted to a permission string
            if (canPermission($role)) {
                return $next($request);
            }

            // 2. Identity fallback — the role-name is a Spatie role (not yet a
            //    registered permission). Still safe because the snapshot still
            //    gates every controller/method, and this middleware only
            //    allows coarse identity-role access.
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}
