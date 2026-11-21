<?php

namespace App\Services\Boarding;

use App\Models\Dormitory;
use App\Models\DormitoryStaffAssignment;
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

    /**
     * Aturan ketat khusus command persetujuan dari ALIM Connect.
     *
     * Berbeda dari `canApprovePermit()` milik UI web, jalur ini HANYA menerima
     * Kepala Asrama dengan penugasan aktif pada asrama terkait. Wakil Kepala
     * Asrama, Super Admin/System Admin, dan pencocokan teks `employment.jabatan`
     * sengaja tidak berlaku sampai pemilik sistem memutuskan sebaliknya.
     */
    public function canApprovePermitCommand(User $user, string $dormitoryId): bool
    {
        if (! $user->is_active) {
            return false;
        }

        $dormitory = Dormitory::withTrashed()->find($dormitoryId);

        if (! $dormitory) {
            return false;
        }

        if ((string) $dormitory->head_id === (string) $user->id) {
            return true;
        }

        return DormitoryStaffAssignment::query()
            ->active()
            ->forUser($user->id)
            ->forDormitory($dormitoryId)
            ->where('position', 'kepala_asrama')
            ->exists();
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

        // Jabatan asrama yang ditetapkan Kepala melalui halaman Pembagian Tugas
        // juga berlaku sebagai izin operasional untuk asrama tersebut.
        $isAssignedLeader = DormitoryStaffAssignment::query()
            ->active()
            ->forUser($user->id)
            ->forDormitory($dormitoryId)
            ->whereIn('position', ['kepala_asrama', 'wakil_kepala_asrama'])
            ->exists();

        if ($isAssignedLeader) {
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
