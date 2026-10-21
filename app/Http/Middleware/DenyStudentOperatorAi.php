<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DenyStudentOperatorAi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $route = $request->route()?->getName();
        $isAiRoute = $route && preg_match('/(^|\.)ai(\.|$)/', $route);

        if ($user && $user->isStudentOperator() && $isAiRoute) {
            abort(403, 'Fitur AI tidak tersedia untuk operator santri.');
        }

        return $next($request);
    }
}
