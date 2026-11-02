<?php

namespace App\Support;

use App\Models\User;

/**
 * Aturan global: kepala satuan kerja hanya boleh ditetapkan/diganti oleh
 * Personalia atau Super Admin.
 *
 * Yang dimaksud kepala satuan kerja termasuk:
 * - Kepala Asrama (dormitories.head_id)
 * - Kepala Satuan Pendidikan (schools.principal_user_id)
 * - Pimpinan satuan kerja lain yang menggunakan field serupa.
 */
class UnitHeadAccess
{
    public static function canManage(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isSystemAdmin()) {
            return true;
        }

        try {
            $roles = $user->getRoleNames()
                ->map(fn ($role) => mb_strtolower(trim((string) $role)))
                ->filter()
                ->all();
        } catch (\Throwable) {
            $roles = [];
        }

        foreach ($roles as $role) {
            if ($role === 'personalia' || str_contains($role, 'personalia')) {
                return true;
            }
        }

        return UserJabatan::hasAny($user, [
            'personalia',
            'kepala personalia',
            'wakil kepala personalia',
            'kepala humas & personalia',
            'kepala humas dan personalia',
            'kepala humas',
            'admin personalia',
            'staf personalia',
        ]);
    }

    public static function ensureCanChange(?string $currentHeadId, ?string $newHeadId, ?User $user): void
    {
        if ((string) ($currentHeadId ?? '') === (string) ($newHeadId ?? '')) {
            return;
        }

        abort_unless(
            self::canManage($user),
            403,
            'Hanya Personalia dan Super Admin yang dapat mengganti kepala satuan kerja.'
        );
    }
}
