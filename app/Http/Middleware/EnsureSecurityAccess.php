<?php

namespace App\Http\Middleware;

use App\Support\SecurityAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gerbang modul Satuan Keamanan.
 */
class EnsureSecurityAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! SecurityAccess::hasAccess($user)) {
            abort(403, 'Anda tidak memiliki akses ke modul Satuan Keamanan.');
        }

        return $next($request);
    }
}
