<?php

namespace App\Policies;

use App\Models\ApprovalRequest;
use App\Models\User;

class ApprovalRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ApprovalRequest $request): bool
    {
        return true;
    }

    public function approve(User $user, ApprovalRequest $request): bool
    {
        if ($request->status !== 'PENDING') {
            return false;
        }

        // System / Super Admin boleh approve tahap apa pun
        if ((method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin())
            || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())) {
            return true;
        }

        $step = $request->currentStep();

        if (! $step) {
            return false;
        }

        if ($step->step_permission && canPermission($step->step_permission)) {
            return true;
        }

        return $step->role_name && method_exists($user, 'hasRole') && $user->hasRole($step->role_name);
    }

    public function reject(User $user, ApprovalRequest $request): bool
    {
        return $this->approve($user, $request);
    }
}
