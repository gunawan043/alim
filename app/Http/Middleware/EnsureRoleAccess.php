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

        \Log::info('EnsureRoleAccess: user=' . ($user ? $user->id : 'null')
            . ' | url_userId=' . $request->route('userId')
            . ' | match=' . ($user && $user->id === $request->route('userId') ? 'YES' : 'NO'));

        if (!$user) {
            abort(403, 'Unauthorized.');
        }

        $urlUserId = $request->route('userId');

        // User may only access routes under their own user ID
        if ($user->id !== $urlUserId) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        // Store active user ID in session
        session(['active_user_id' => $user->id]);

        return $next($request);
    }
}
