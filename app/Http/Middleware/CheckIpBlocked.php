<?php

namespace App\Http\Middleware;

use App\Models\FailedLoginAttempt;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIpBlocked
{
    private const IP_COOLDOWN_SECONDS = 60;

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

        // Soft cooldown (attempt 3 → 60 detik jeda)
        if ($block && $block->attempts >= 3 && $block->locked_until === null) {
            $cooldown = $block->last_attempt_at->addSeconds(self::IP_COOLDOWN_SECONDS);
            if ($cooldown->isFuture()) {
                $seconds = now()->diffInSeconds($cooldown, false);

                return redirect()
                    ->route('login')
                    ->withErrors(['cooldown' => "Terlalu banyak percobaan. Silakan tunggu {$seconds} detik sebelum mencoba lagi."])
                    ->with(['lockout' => true, 'seconds' => (int) $seconds]);
            }
            // Cooldown expired → clear counter
            $block->attempts = 0;
            $block->save();
        }

        return $next($request);
    }
}
