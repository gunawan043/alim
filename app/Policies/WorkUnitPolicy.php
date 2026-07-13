<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkUnit;

class WorkUnitPolicy
{
    public function viewAny(User $user): bool
    {
        return canUserPermission($user, 'workunit-view');
    }

    public function view(User $user, WorkUnit $workUnit): bool
    {
        return canUserPermission($user, 'workunit-view');
    }

    public function create(User $user): bool
    {
        return canUserPermission($user, 'workunit-create');
    }

    public function update(User $user, WorkUnit $workUnit): bool
    {
        return canUserPermission($user, 'workunit-update');
    }

    public function delete(User $user, WorkUnit $workUnit): bool
    {
        return canUserPermission($user, 'workunit-delete');
    }

    public function restore(User $user, WorkUnit $workUnit): bool
    {
        return canUserPermission($user, 'workunit-restore');
    }

    public function forceDelete(User $user, WorkUnit $workUnit): bool
    {
        return canUserPermission($user, 'workunit-force-delete');
    }
}
