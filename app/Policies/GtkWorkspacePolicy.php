<?php

namespace App\Policies;

use App\Authorization\ValueObjects\OrganizationContext;
use App\Models\User;

class GtkWorkspacePolicy
{
    public function __construct(
        private readonly OrganizationContext $context
    ) {}

    public function view(User $user, object $workspace): bool
    {
        if (canUserPermission($user, 'gtk_workspace_all_access') || canUserPermission($user, 'inventory_view')) {
            return true;
        }

        // Require a real tenant context before comparing school IDs.
        // Sentinel school IDs ('global', 'unknown', etc.) must never
        // grant access to a workspace — fail-closed.
        if (! $this->context->hasValidSchool()) {
            return false;
        }

        return ($workspace->school_id ?? null) === $this->context->schoolId;
    }

    public function create(User $user): bool
    {
        return canUserPermission($user, 'gtk_workspace_create');
    }

    public function update(User $user, object $workspace): bool
    {
        if (canUserPermission($user, 'gtk_workspace_all_access') || canUserPermission($user, 'inventory_view')) {
            return true;
        }

        if (! $this->context->hasValidSchool()) {
            return false;
        }

        return ($workspace->school_id ?? null) === $this->context->schoolId
            && (($workspace->created_by ?? null) === $user->id || canUserPermission($user, 'gtk_workspace_edit'));
    }

    public function delete(User $user, object $workspace): bool
    {
        return canUserPermission($user, 'gtk_workspace_delete');
    }
}
