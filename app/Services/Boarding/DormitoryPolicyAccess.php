<?php

namespace App\Services\Boarding;

use App\Models\Dormitory;
use App\Models\User;

/**
 * Otorisasi pengaturan kuota/kebijakan asrama:
 * hanya Kepala Asrama (head_id) dan Wakil Kepala Asrama (jabatan) —
 * plus Super Admin/System Admin sebagai jalur sistem.
 */
class DormitoryPolicyAccess
{
    /**
     * Kepala Asrama, Wakil Kepala Asrama, dan pejabat asrama terkait boleh
     * menyetujui pengajuan penghargaan dari prestasi sekolah.
     */
    public function canApproveReward(User $user, string $dormitoryId): bool
    {
        return $this->canManageQuota($user, $dormitoryId);
    }

    /**
     * Persetujuan izin khusus wajib oleh Kepala/Wakil Asrama.
     */
    public function canApprovePermit(User $user, string $dormitoryId): bool
    {
        return $this->canManageQuota($user, $dormitoryId);
    }

    public function canManageQuota(User $user, string $dormitoryId): bool
    {
        if ($user->isSystemAdmin()) {
            return true;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        $dormitory = Dormitory::withTrashed()->find($dormitoryId);

        if ($dormitory && (string) $dormitory->head_id === (string) $user->id) {
            return true;
        }

        $jabatan = mb_strtolower(trim((string) ($user->employment?->jabatan ?? '')));

        if ($jabatan === '') {
            return false;
        }

        return str_contains($jabatan, 'wakil kepala asrama')
            || str_contains($jabatan, 'wakil asrama')
            || str_contains($jabatan, 'kepala asrama');
    }
}
