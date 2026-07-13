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
     */
    function canPermission(string $permission): bool
    {
        if (! auth()->check()) {
            return false;
        }

        if (! app()->bound(OrganizationContext::class)) {
            return false;
        }

        $context = app(OrganizationContext::class);
        if (! $context instanceof OrganizationContext) {
            return false;
        }

        /** @var User $user */
        $user = auth()->user();

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
     */
    function authorizationContextFor(
        string $schoolId = 'unknown',
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
