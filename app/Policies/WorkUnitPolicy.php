<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Auth\Access\Response;

class WorkUnitPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Personalia', 'Admin Tata Usaha']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, WorkUnit $workUnit): bool
    {
        return $user->hasRole(['Super Admin', 'Personalia', 'Admin Tata Usaha']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Personalia']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, WorkUnit $workUnit): bool
    {
        return $user->hasRole(['Super Admin', 'Personalia']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WorkUnit $workUnit): bool
    {
        return $user->hasRole(['Super Admin', 'Personalia']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, WorkUnit $workUnit): bool
    {
        return $user->hasRole(['Super Admin', 'Personalia']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, WorkUnit $workUnit): bool
    {
        return $user->hasRole(['Super Admin', 'Personalia']);
    }
}