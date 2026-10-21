<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan untuk halaman web tanpa memaksa CSP ketat yang dapat
 * memblokir aset CDN/inline yang memang dipakai UI ALIM. CSP dikirim dalam
 * mode report-only agar tim dapat memantau pelanggaran sebelum enforcement.
 */
class WebSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response instanceof Response) {
            return $response;
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(self), microphone=(), camera=(self), payment=()');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $response->headers->set(
            'Content-Security-Policy-Report-Only',
            "default-src 'self' https: data: blob:; ".
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; ".
            "style-src 'self' 'unsafe-inline' https:; ".
            "img-src 'self' data: blob: https:; ".
            "font-src 'self' data: https:; ".
            "connect-src 'self' https: ws: wss:; ".
            "frame-ancestors 'self'; form-action 'self'; base-uri 'self'; object-src 'none'"
        );

        return $response;
    }
}
