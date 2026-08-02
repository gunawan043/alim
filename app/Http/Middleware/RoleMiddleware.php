<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Authorization\Services\AuthorizationManager;
use App\Authorization\ValueObjects\OrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        // System Admin bypasses role checks entirely
        if (method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin()) {
            return $next($request);
        }

        if (! app()->bound(OrganizationContext::class)) {
            $this->logSnapshotMissing($request, $user, null, $roles);

            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $context = app(OrganizationContext::class);
        if (! $context instanceof OrganizationContext) {
            $this->logSnapshotMissing($request, $user, null, $roles);

            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $scopeKey = (string) $context->toScopeKey();
        $bag = app(AuthorizationManager::class)->getSnapshot($user, $context);

        if ($bag === null) {
            $this->logSnapshotMissing($request, $user, $scopeKey, $roles);

            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        // Snapshot-based permission check (preferred)
        $manager = app(AuthorizationManager::class);
        foreach ($roles as $role) {
            if ($manager->canPermission($bag, $role)) {
                return $next($request);
            }
        }

        // Fallback: identity-only role names (e.g. "super_admin") check via $user->hasRole()
        foreach ($roles as $role) {
            if (method_exists($user, 'hasRole') && $user->hasRole($role)) {
                return $next($request);
            }
        }

        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }

    /**
     * @param  array<int, string>  $roles
     */
    private function logSnapshotMissing(
        Request $request,
        mixed $user,
        ?string $scopeKey,
        array $roles,
    ): void {
        Log::warning('authorization_snapshot_missing', [
            'user_id' => $user?->getAuthIdentifier(),
            'scope_key' => $scopeKey,
            'route' => $request->path(),
            'method' => $request->method(),
            'permission' => $roles,
            'ip' => $request->ip(),
        ]);
    }
}
