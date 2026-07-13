<?php

namespace App\Policies;

use App\Models\User;

class GtkWorkspacePolicy
{
    public function view(User $user, object $workspace): bool
    {
        if (canUserPermission($user, 'gtk_workspace_all_access') || canUserPermission($user, 'inventory_view')) {
            return true;
        }

        return ($workspace->school_id ?? null) === ($user->school_id ?? null);
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

        return ($workspace->school_id ?? null) === ($user->school_id ?? null)
            && (($workspace->created_by ?? null) === $user->id || canUserPermission($user, 'gtk_workspace_edit'));
    }

    public function delete(User $user, object $workspace): bool
    {
        return canUserPermission($user, 'gtk_workspace_delete');
    }
}