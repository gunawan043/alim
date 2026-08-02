<?php

declare(strict_types=1);

use App\Authorization\DTO\PermissionBag;
use App\Authorization\Services\AuthorizationManager;
use App\Authorization\ValueObjects\OrganizationContext;
use App\Models\User;

if (! function_exists('canPermission')) {
    /**
     * Check whether the current user has a snapshot-derived permission.
     *
     * Returns false if no user is authenticated or no OrganizationContext
     * is bound to the request container. Fail-closed by design.
     *
     * Special case: `super-admin-only` is true for system admins only — does
     * NOT require the now-removed 'Super Admin' role.
     */
    function canPermission(string $permission): bool
    {
        if (! auth()->check()) {
            return false;
        }

        /** @var User $user */
        $user = auth()->user();

        // Global super-admin gate: system admins have access to every
        // permission unless they are currently in View-As mode.
        if ($user->isSystemAdmin()) {
            $viewAs = app(\App\Services\ViewAsService::class);
            if ($viewAs->getCurrentViewRole() === null) {
                return true;
            }
        }

        // System-admin gate shortcut. View-As SA loses this short-circuit so
        // the impersonated user does NOT inherit system permissions.
        if ($permission === 'super-admin-only') {
            return false;
        }

        if (! app()->bound(OrganizationContext::class)) {
            return false;
        }

        $context = app(OrganizationContext::class);
        if (! $context instanceof OrganizationContext) {
            return false;
        }

        return app(AuthorizationManager::class)->allows($user, $permission, $context);
    }
}

if (! function_exists('cannotPermission')) {
    function cannotPermission(string $permission): bool
    {
        return ! canPermission($permission);
    }
}

if (! function_exists('canUserPermission')) {
    /**
     * Check whether a specific user has a snapshot-derived permission.
     */
    function canUserPermission(User $user, string $permission): bool
    {
        if (! app()->bound(OrganizationContext::class)) {
            return false;
        }

        $context = app(OrganizationContext::class);
        if (! $context instanceof OrganizationContext) {
            return false;
        }

        return app(AuthorizationManager::class)->allows($user, $permission, $context);
    }
}

if (! function_exists('permissionSnapshot')) {
    function permissionSnapshot(): ?PermissionBag
    {
        if (! auth()->check()) {
            return null;
        }

        if (! app()->bound(OrganizationContext::class)) {
            return null;
        }

        $context = app(OrganizationContext::class);
        if (! $context instanceof OrganizationContext) {
            return null;
        }

        /** @var User $user */
        $user = auth()->user();

        return app(AuthorizationManager::class)->getSnapshot($user, $context);
    }
}

if (! function_exists('authorizationContextFor')) {
    /**
     * Build an OrganizationContext for filtering/lookup operations where
     * a request context is not available.
     *
     * This helper is ONLY for CLI commands, queue workers, and offline admin
     * scripts — never for HTTP middleware or tenant-aware service calls.
     * In HTTP contexts, read the OrganizationContext that
     * BindOrganizationContext binds via app(OrganizationContext::class) and
     * check hasValidSchool() before using schoolId.
     */
    function authorizationContextFor(
        ?string $schoolId = null,
        string $academicYearId = 'global',
        string $roleDimension = 'default',
    ): OrganizationContext {
        return new OrganizationContext(
            schoolId: $schoolId,
            academicYearId: $academicYearId,
            roleDimension: $roleDimension,
        );
    }
}

if (! function_exists('usersHavingPermission')) {
    /**
     * Return user IDs whose snapshot contains the given permission.
     * Use this instead of User::role([...]) — never trust role names.
     *
     * @return array<int, string>
     */
    function usersHavingPermission(string $permission, ?OrganizationContext $context = null): array
    {
        if ($context === null) {
            $context = app()->bound(OrganizationContext::class)
                ? app(OrganizationContext::class)
                : authorizationContextFor();
        }

        return app(\App\Authorization\Services\UserFilterService::class)
            ->userIdsWithPermission($permission, $context);
    }
}

if (! function_exists('usersMissingPermission')) {
    /**
     * Return user IDs whose snapshot does NOT contain the given permission.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    function usersMissingPermission(string $permission, ?OrganizationContext $context = null): \Illuminate\Database\Eloquent\Collection
    {
        if ($context === null) {
            $context = app()->bound(OrganizationContext::class)
                ? app(OrganizationContext::class)
                : authorizationContextFor();
        }

        return app(\App\Authorization\Services\UserFilterService::class)
            ->usersWithoutPermission($permission, $context);
    }
}
