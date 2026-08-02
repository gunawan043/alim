<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkOrder;

class WorkOrderPolicy
{
    public function before(User $user): ?bool
    {
        if (canUserPermission($user, 'sarpras.administrator.accessible')) {
            return true;
        }

        return null;
    }

    public function view(User $user, WorkOrder $order): bool
    {
        return $user->id === $order->assignee_id
            || canUserPermission($user, 'sarpras.administrator.accessible')
            || canUserPermission($user, 'sarpras.technician.assignable');
    }

    public function updateProgress(User $user, WorkOrder $order): bool
    {
        return $user->id === $order->assignee_id
            || canUserPermission($user, 'sarpras.administrator.accessible');
    }

    public function recordCost(User $user, WorkOrder $order): bool
    {
        return canUserPermission($user, 'sarpras.administrator.accessible');
    }
}
