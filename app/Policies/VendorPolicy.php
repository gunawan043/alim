<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

class VendorPolicy
{
    public function view(User $user, Vendor $vendor): bool
    {
        return $user->hasAnyPermission('can_view_vendor', 'can_see_vendor');
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission('can_edit_vendor');
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return $user->hasAnyPermission('can_edit_vendor');
    }

    public function delete(User $user): bool
    {
        return $user->hasAnyPermission('can_edit_vendor');
    }
}
