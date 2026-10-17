<?php

namespace App\Policies;

use App\Models\User;

class PurchaseOrderPolicy
{
    public function view(User $user): bool
    {
        return $user->hasAnyPermission('can_see_purchase_orders', 'can_view_purchase_order');
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission('can_edit_purchase_orders');
    }

    public function update(User $user): bool
    {
        return $user->hasAnyPermission('can_edit_purchase_orders');
    }

    public function delete(User $user): bool
    {
        return $user->hasAnyPermission('can_edit_purchase_orders');
    }
}
