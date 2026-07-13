<?php

namespace App\Policies;

use App\Models\User;

class SarprasWorkspacePolicy
{
    public function viewAll(User $user): bool
    {
        return canUserPermission($user, 'sarpras_all_access')
            || canUserPermission($user, 'inventory_view');
    }

    public function view(User $user, object $resource): bool
    {
        if ($this->viewAll($user)) {
            return true;
        }

        return ($resource->school_id ?? null) === ($user->school_id ?? null);
    }

    public function create(User $user): bool
    {
        return canUserPermission($user, 'sarpras_create');
    }

    public function update(User $user, object $resource): bool
    {
        if ($this->viewAll($user)) {
            return true;
        }

        return ($resource->school_id ?? null) === ($user->school_id ?? null)
            && (($resource->created_by ?? null) === $user->id || canUserPermission($user, 'sarpras_edit'));
    }

    public function delete(User $user, object $resource): bool
    {
        return canUserPermission($user, 'sarpras_delete');
    }
}