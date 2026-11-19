<?php

namespace App\Http\Middleware;

use App\Support\WaliLinkAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mobile wali surface guard.
 *
 * The mobile application is for guardians (wali santri). It must not be
 * usable by GTK accounts unless the account is also an active guardian or
 * has the explicit wali-link management permission required by the link
 * administration endpoints.
 */
class EnsureWaliMobile
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $isWali = $user !== null
            && (
                (bool) $user->is_wali
                || $user->activeWaliSantri()->exists()
            );

        $canManageWaliLinks = $user !== null && WaliLinkAccess::canManage($user);

        if (! $isWali && ! $canManageWaliLinks) {
            return response()->json([
                'success' => false,
                'message' => 'Aplikasi mobile ini hanya untuk akun wali santri.',
                'data' => null,
                'error' => [
                    'code' => 'WALI_ONLY',
                    'message' => 'Aplikasi mobile ini hanya untuk akun wali santri.',
                ],
            ], 403);
        }

        return $next($request);
    }
}
