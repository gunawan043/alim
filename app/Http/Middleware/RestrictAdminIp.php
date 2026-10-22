<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Opsional: batasi akses area Super Admin ke IP/CIDR tertentu.
 * Kosong berarti semua IP diizinkan.
 */
class RestrictAdminIp
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $this->isAdmin($user)) {
            return $next($request);
        }

        $routeName = (string) ($request->route()?->getName() ?? '');

        if (! str_starts_with($routeName, 'user.sa.') && ! str_starts_with($routeName, 'system.')) {
            return $next($request);
        }

        $raw = trim((string) setting('security_admin_ip_allowlist', ''));

        if ($raw === '') {
            return $next($request);
        }

        $allowed = array_values(array_filter(array_map('trim', explode(',', $raw))));

        if ($allowed === [] || IpUtils::checkIp((string) $request->ip(), $allowed)) {
            return $next($request);
        }

        Log::warning('Akses area admin ditolak oleh IP allowlist', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'route' => $routeName,
        ]);

        abort(403, 'Akses area Super Admin dibatasi untuk alamat IP tertentu.');
    }

    private function isAdmin($user): bool
    {
        return (method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin())
            || (method_exists($user, 'hasRole') && $user->hasRole('Super Admin'));
    }
}
