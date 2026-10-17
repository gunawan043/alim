<?php

namespace App\Http\Middleware;

use App\Services\ViewAsService;
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
        $viewAs = app(ViewAsService::class);
        $effectiveId = $viewAs->effectiveUserId($user) ?? $user->id;

        // {userId} pada URL WAJIB cocok dengan pengguna efektif.
        // Pengecualian hanya untuk system/super admin.
        $isSuperAdmin = (method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin())
            || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin());

        if ($urlUserId !== null && ! $isSuperAdmin) {
            if ($viewAs->isViewingAs()) {
                if ($viewAs->isRoleOnly()) {
                    abort(403, 'Anda tidak memiliki akses ke halaman ini.');
                }

                if ((string) $effectiveId !== (string) $urlUserId) {
                    abort(403, 'Anda tidak memiliki akses ke halaman ini.');
                }
            } elseif ((string) $effectiveId !== (string) $urlUserId) {
                abort(403, 'Anda tidak memiliki akses ke halaman pengguna lain.');
            }
        }

        // Store active user ID in session
        session(['active_user_id' => $effectiveId]);

        return $next($request);
    }
}
