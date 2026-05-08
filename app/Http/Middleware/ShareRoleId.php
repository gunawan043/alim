<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ShareRoleId
{
    public function handle(Request $request, Closure $next): Response
    {
        // Share userId globally to all views for navigation links
        $userId = $request->route('userId');

        if (!$userId && Auth::check()) {
            $userId = Auth::id();
        }

        if ($userId) {
            view()->share('userId', $userId);
        }

        return $next($request);
    }
}
