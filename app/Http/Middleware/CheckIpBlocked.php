<?php

namespace App\Http\Middleware;

use App\Models\FailedLoginAttempt;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIpBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->path() !== 'login') {
            return $next($request);
        }

        // User is already on the login page — let them see the countdown, don't re-redirect
        if ($request->session()->has('lockout')) {
            return $next($request);
        }

        $ip = $request->ip();
        $block = FailedLoginAttempt::forIp($ip)->active()->first();

        // IP hard-locked (attempt 9)
        if ($block && $block->isLockedByIp()) {
            $seconds = now()->diffInSeconds($block->locked_until, false);

            return redirect()
                ->route('login')
                ->withErrors(['ip_blocked' => "IP diblokir sementara. Silakan coba lagi setelah {$block->locked_until->diffForHumans()}."])
                ->with(['lockout' => true, 'seconds' => (int) $seconds]);
        }

        return $next($request);
    }
}
