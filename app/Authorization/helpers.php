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
