<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Authorization\Services\AuthorizationManager;
use App\Authorization\ValueObjects\OrganizationContext;
use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate HTTP routes against a list of snapshot-derived permissions.
 *
 *   Route::get('/students', ...)
 *       ->middleware('permission:students.view');              // single
 *
 *   Route::get('/archive', ...)
 *       ->middleware('permission:students.view,students.edit'); // OR
 *
 *   Route::post('/audit', ...)
 *       ->middleware('permission-all:students.audit,admin.run'); // AND
 *
 * Reads the OrganizationContext that must be bound upstream by
 * BindOrganizationContext (Phase 2B out-of-scope; tracked as a design
 * gap when missing). Without the context the middleware fails closed
 * with 403.
 */
final class RequirePermission
{
    public function __construct(
        private readonly AuthorizationManager $manager,
    ) {}

    public function handle(Request $request, Closure $next, string ...$args): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $context = $this->resolveContext();
        if ($context === null) {
            abort(403, 'Organization context is not bound to the request.');
        }

        $mode = $this->modeOf($request);
        $permissions = $this->extractPermissions($args, $mode);

        if (empty($permissions)) {
            abort(403, 'No permissions specified for permission middleware.');
        }

        $granted = $mode === 'all'
            ? $this->allAllowed($user, $permissions, $context)
            : $this->anyAllowed($user, $permissions, $context);

        if (! $granted) {
            abort(403, sprintf(
                'Missing required permission(s): %s',
                implode(', ', $permissions),
            ));
        }

        return $next($request);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function anyAllowed(mixed $user, array $permissions, OrganizationContext $context): bool
    {
        foreach ($permissions as $permission) {
            if ($this->manager->allows($user, $permission, $context)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function allAllowed(mixed $user, array $permissions, OrganizationContext $context): bool
    {
        foreach ($permissions as $permission) {
            if (! $this->manager->allows($user, $permission, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, string>  $args
     * @return array<int, string>
     */
    private function extractPermissions(array $args, string $mode): array
    {
        if (count($args) === 1 && str_contains($args[0], ',')) {
            return array_values(array_filter(array_map('trim', explode(',', $args[0]))));
        }

        if ($mode === 'all' && count($args) > 1) {
            return $args;
        }

        return $args;
    }

    private function modeOf(Request $request): string
    {
        $route = $request->route();
        if ($route === null) {
            return 'any';
        }

        $middleware = $route->middleware();

        foreach ($middleware as $m) {
            if (str_starts_with($m, 'permission-all:')) {
                return 'all';
            }
            if (str_starts_with($m, 'permission:')) {
                return 'any';
            }
        }

        return 'any';
    }

    private function resolveContext(): ?OrganizationContext
    {
        try {
            $context = app(OrganizationContext::class);
        } catch (BindingResolutionException) {
            return null;
        }

        return $context instanceof OrganizationContext ? $context : null;
    }
}
