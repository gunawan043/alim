<?php

namespace App\Http\Middleware;

use App\Services\Lab\LabAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLabAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $access = app(LabAccess::class);

        if ($access->isSystemAdmin($user) || $access->isPrincipal($user) || $access->isCoordinator($user)) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki akses ke modul Laboratorium.');
    }
}
