<?php

namespace App\Http\Middleware;

use App\Services\SecurityPolicy;
use App\Services\SystemMailSettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Terapkan kebijakan keamanan dari database sebelum session dimulai.
 * Session lifetime harus di-set sebelum StartSession membaca konfigurasi.
 */
class ApplySecuritySettings
{
    public function handle(Request $request, Closure $next): Response
    {
        config([
            'session.lifetime' => SecurityPolicy::sessionLifetimeMinutes(),
        ]);

        app(SystemMailSettingsService::class)->apply();

        return $next($request);
    }
}
