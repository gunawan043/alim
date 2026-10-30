<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memastikan {userId} pada URL API sama dengan user pemilik token.
 * Pola ini mencegah endpoint dipakai untuk mengakses data user lain
 * meskipun token bocor/tertukar.
 */
class EnsureApiUserScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $routeUserId = $request->route('userId');

        if (! $user || ! $routeUserId || (string) $user->id !== (string) $routeUserId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'USER_SCOPE_MISMATCH',
                    'message' => 'Akses ditolak: userId tidak sesuai dengan pemilik token.',
                ],
            ], 403);
        }

        return $next($request);
    }
}
