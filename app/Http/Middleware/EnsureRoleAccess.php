<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoleAccess
{
    /**
     * Verify the authenticated user matches the {userId} in the URL.
     * Each user has exactly one role, so this replaces the old roleId check.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        $urlUserId = $request->route('userId');

        // Resolve effective user id (Login-As support)
        $viewAs = app(\App\Services\ViewAsService::class);
        $effectiveId = $viewAs->effectiveUserId($user) ?? $user->id;

        // If {userId} param is present in URL, validate against effective id.
        // While Login-As is active, the SA is allowed to access the target user's pages.
        if ($urlUserId !== null) {
            if ((string) $effectiveId !== (string) $urlUserId) {
                abort(403, 'Anda tidak memiliki akses ke halaman ini.');
            }
        }

        // Store active user ID in session
        session(['active_user_id' => $effectiveId]);

        return $next($request);
    }
}
