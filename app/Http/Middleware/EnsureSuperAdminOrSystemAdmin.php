<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdminOrSystemAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        $isSystemAdmin = method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin();
        $isSuperAdmin = ! $isSystemAdmin
            && method_exists($user, 'hasPermissionTo')
            && $user->hasPermissionTo('impersonate_role');

        if (! $isSystemAdmin && ! $isSuperAdmin) {
            abort(403, 'System Administrator or Super Admin only.');
        }

        return $next($request);
    }
}
