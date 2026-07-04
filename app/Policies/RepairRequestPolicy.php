<?php

namespace App\Policies;

use App\Models\User;
use App\Models\RepairRequest;

class RepairRequestPolicy
{
    /** Admin & PIC Sarpras can manage all requests. */
    public function before(User $user): ?bool
    {
        if (canUserPermission($user, 'sarpras.administrator.accessible')) {
            return true;
        }
        return null;
    }

    public function verify(User $user, RepairRequest $request): bool
    {
        return canUserPermission($user, 'sarpras.administrator.accessible')
            || canUserPermission($user, 'sarpras.manager.approvable');
    }

    public function generateWorkOrder(User $user, RepairRequest $request): bool
    {
        return canUserPermission($user, 'sarpras.administrator.accessible')
            || canUserPermission($user, 'sarpras.manager.approvable');
    }

    public function view(User $user, RepairRequest $request): bool
    {
        return $user->id === $request->reported_by
            || canUserPermission($user, 'sarpras.administrator.accessible')
            || canUserPermission($user, 'sarpras.technician.assignable');
    }
}
