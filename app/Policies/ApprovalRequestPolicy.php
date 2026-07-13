<?php

namespace App\Policies;

use App\Models\ApprovalRequest;
use App\Models\User;

class ApprovalRequestPolicy
{
    public function approve(User $user, ApprovalRequest $request)
    {
        if ($request->status !== 'pending') {
            return false;
        }

        $step = $request->currentStep();

        return $step && canPermission($step->role_name);
    }

    public function reject(User $user, ApprovalRequest $request)
    {
        return $this->approve($user, $request);
    }
}
