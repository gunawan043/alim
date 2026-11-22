<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforce Sanctum abilities on mobile endpoints.
 *
 * Returns the canonical mobile error envelope instead of leaking the
 * framework's MissingAbilityException shape.
 */
class EnsureTokenAbility
{
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $user = $request->user();

        if ($user === null || $user->currentAccessToken() === null) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak terautentikasi.',
                'data' => null,
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Tidak terautentikasi.',
                ],
            ], 401);
        }

        foreach ($abilities as $ability) {
            if (! $user->tokenCan($ability)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token tidak memiliki izin untuk aksi ini.',
                    'data' => null,
                    'error' => [
                        'code' => 'INSUFFICIENT_ABILITY',
                        'message' => 'Token tidak memiliki izin untuk aksi ini.',
                        'ability' => $ability,
                        'reauth_required' => true,
                    ],
                ], 403);
            }
        }

        return $next($request);
    }
}
