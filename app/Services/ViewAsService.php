<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Spatie\Permission\Models\Role;

class ViewAsService
{
    public const SESSION_ROLE = 'view_as_role';

    public const SESSION_CONTEXT = 'view_as_context';

    /** User being impersonated (full identity swap, not just role). */
    public const SESSION_USER_ID = 'view_as_user_id';

    /** Original authenticated user, kept for restore. */
    public const SESSION_ORIGINAL_USER_ID = 'view_as_original_user_id';

    public function getCurrentViewRole(): ?string
    {
        $role = Session::get(self::SESSION_ROLE);

        return is_string($role) && $role !== '' ? $role : null;
    }

    public function setCurrentViewRole(?string $roleName): void
    {
        if ($roleName === null || $roleName === '') {
            Session::forget(self::SESSION_ROLE);

            return;
        }
        Session::put(self::SESSION_ROLE, $roleName);
    }

    public function getCurrentViewUserId(): ?string
    {
        $id = Session::get(self::SESSION_USER_ID);

        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * Switch identity to a specific user (Login-As flow).
     * Stores original authenticated user for restore.
     */
    public function loginAs(string $userId, ?\App\Models\User $adminActor = null): void
    {
        if ($adminActor && ! Session::has(self::SESSION_ORIGINAL_USER_ID)) {
            Session::put(self::SESSION_ORIGINAL_USER_ID, $adminActor->id);
        }
        Session::put(self::SESSION_USER_ID, $userId);
        // Backwards-compat: still track role name for sidebar/menu rendering
        $user = \App\Models\User::find($userId);
        if ($user) {
            $this->setCurrentViewRole($user->getRoleNames()->first());
        }
    }

    public function clearCurrentViewRole(): void
    {
        Session::forget(self::SESSION_ROLE);
    }

    public function isViewingAs(): bool
    {
        return $this->getCurrentViewRole() !== null
            || $this->getCurrentViewUserId() !== null;
    }

    /**
     * Resolve the effective user id for the current request:
     * - If viewing as user X → return X
     * - Else, return the currently authenticated id
     */
    public function effectiveUserId(?object $user): ?string
    {
        $viewing = $this->getCurrentViewUserId();
        if ($viewing) {
            return $viewing;
        }

        return $user?->id;
    }

    public function originalUserId(): ?string
    {
        $id = Session::get(self::SESSION_ORIGINAL_USER_ID);

        return is_string($id) && $id !== '' ? $id : null;
    }

    public function getCurrentViewContext(): array
    {
        $ctx = Session::get(self::SESSION_CONTEXT, []);

        return is_array($ctx) ? $ctx : [];
    }

    public function setCurrentViewContext(array $context): void
    {
        $clean = array_filter(
            $context,
            fn ($v) => $v !== null && $v !== '' && $v !== false,
        );
        if (empty($clean)) {
            Session::forget(self::SESSION_CONTEXT);

            return;
        }
        Session::put(self::SESSION_CONTEXT, $clean);
    }

    public function clearCurrentViewContext(): void
    {
        Session::forget(self::SESSION_CONTEXT);
    }

    public function clearAll(): void
    {
        Session::forget(self::SESSION_ROLE);
        Session::forget(self::SESSION_CONTEXT);
        Session::forget(self::SESSION_USER_ID);
        Session::forget(self::SESSION_ORIGINAL_USER_ID);
    }

    /**
     * Business roles available for View As — excludes any role currently used
     * as a "developer/system" role.
     *
     * @return Collection<int, Role>
     */
    public function getAvailableViewRoles(): Collection
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', ['Super Admin'])
            ->orderBy('level')
            ->orderBy('name')
            ->get();
    }

    /**
     * Resolve effective render role for sidebar / context. If the current user
     * is a System Admin and they have View As set, return that role name;
     * otherwise null (= render as real identity).
     */
    public function effectiveRenderRole(?object $user): ?string
    {
        if (! $user || ! method_exists($user, 'isSystemAdmin') || ! $user->isSystemAdmin()) {
            return null;
        }

        return $this->getCurrentViewRole();
    }
}
