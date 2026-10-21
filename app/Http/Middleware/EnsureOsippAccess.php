<?php

namespace App\Http\Middleware;

use App\Services\Osipp\OsippAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOsippAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (app(OsippAccess::class)->canView($user)) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki penugasan sebagai Guru Koordinator OSIPP pada satuan pendidikan ini.');
    }
}
