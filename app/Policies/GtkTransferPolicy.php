<?php

namespace App\Policies;

use App\Models\GtkTransferRequest;
use App\Models\User;

class GtkTransferPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return canUserPermission($user, 'view transfer requests');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, GtkTransferRequest $transfer): bool
    {
        return canUserPermission($user, 'view transfer requests') ||
               $user->id === $transfer->requested_by;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return canUserPermission($user, 'create transfer requests');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, GtkTransferRequest $transfer): bool
    {
        return canUserPermission($user, 'update transfer requests') ||
               ($user->id === $transfer->requested_by && $transfer->status === 'pending');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, GtkTransferRequest $transfer): bool
    {
        return canUserPermission($user, 'delete transfer requests') ||
               ($user->id === $transfer->requested_by && $transfer->status === 'pending');
    }

    /**
     * Determine whether the user can approve the model.
     */
    public function approve(User $user, GtkTransferRequest $transfer): bool
    {
        return canUserPermission($user, 'approve transfer requests');
    }

    /**
     * Determine whether the user can reject the model.
     */
    public function reject(User $user, GtkTransferRequest $transfer): bool
    {
        return canUserPermission($user, 'reject transfer requests');
    }
}
