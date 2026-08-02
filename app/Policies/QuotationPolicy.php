<?php

namespace App\Policies;

use App\Models\User;

class QuotationPolicy
{
    public function view(User $user): bool
    {
        return $user->hasAnyPermission('can_see_quotation');
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission('can_edit_quotation');
    }

    public function update(User $user): bool
    {
        return $user->hasAnyPermission('can_edit_quotation');
    }

    public function delete(User $user): bool
    {
        return $user->hasAnyPermission('can_edit_quotation');
    }
}
