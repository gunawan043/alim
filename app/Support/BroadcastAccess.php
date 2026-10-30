<?php

namespace App\Support;

use App\Models\User;

class BroadcastAccess
{
    public static function canManage(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->isSystemAdmin()
            || $user->isSuperAdmin()
            || $user->hasRole('Humas Personalia')
            || $user->hasRole('Satuan Pendidikan');
    }

    public static function isGlobal(?User $user): bool
    {
        return (bool) ($user?->isSystemAdmin()
            || $user?->isSuperAdmin()
            || $user?->hasRole('Humas Personalia'));
    }
}
