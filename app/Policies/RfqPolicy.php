<?php

namespace App\Policies;

use App\Models\User;

class RfqPolicy
{
    public function view(User $user): bool
    {
        return $user->hasAnyPermission('can_see_rfqs');
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission('can_edit_rfqs');
    }

    public function update(User $user): bool
    {
        return $user->hasAnyPermission('can_edit_rfqs');
    }

    public function delete(User $user): bool
    {
        return $user->hasAnyPermission('can_edit_rfqs');
    }
}
