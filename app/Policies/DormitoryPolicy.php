<?php

namespace App\Policies;

use App\Models\Dormitory;
use App\Models\User;

class DormitoryPolicy
{
    /**
     * Melihat daftar asrama
     */
    public function viewAny(User $user): bool
    {
        return canPermission('asrama-view');
    }

    /**
     * Melihat detail satu asrama
     */
    public function view(User $user, Dormitory $dormitory): bool
    {
        return canPermission('asrama-view');
    }

    /**
     * Membuat asrama baru (hanya Super Admin / Wadir 1)
     */
    public function create(User $user): bool
    {
        return canPermission('asrama-create');
    }

    /**
     * Mengubah data asrama
     */
    public function update(User $user, Dormitory $dormitory): bool
    {
        return canPermission('asrama-create');
    }

    /**
     * Menghapus asrama (hanya Super Admin)
     */
    public function delete(User $user, Dormitory $dormitory): bool
    {
        return canPermission('super-admin-only');
    }

    /**
     * Mengelola penghuni
     */
    public function manageResidents(User $user, Dormitory $dormitory): bool
    {
        return canPermission('asrama-manage');
    }

    /**
     * Check-in / check-out penghuni
     */
    public function checkInOut(User $user, Dormitory $dormitory): bool
    {
        return canPermission('asrama-manage');
    }

    /**
     * Mencatat absensi
     */
    public function recordAttendance(User $user, Dormitory $dormitory): bool
    {
        return canPermission('asrama-manage');
    }

    /**
     * Memverifikasi absensi
     */
    public function verifyAttendance(User $user, Dormitory $dormitory): bool
    {
        return canPermission('asrama-create');
    }

    /**
     * Menyetujui / menolak izin
     */
    public function approvePermit(User $user, Dormitory $dormitory): bool
    {
        return canPermission('asrama-create');
    }

    /**
     * Mengajukan izin
     */
    public function createPermit(User $user, Dormitory $dormitory): bool
    {
        return canPermission('asrama-view');
    }

    /**
     * Mencatat pelanggaran
     */
    public function recordViolation(User $user, Dormitory $dormitory): bool
    {
        return canPermission('asrama-manage');
    }

    /**
     * Mengirim notifikasi pelanggaran ke wali
     */
    public function notifyParent(User $user, Dormitory $dormitory): bool
    {
        return canPermission('asrama-manage');
    }

    /**
     * Posting informasi
     */
    public function postInformation(User $user, Dormitory $dormitory): bool
    {
        return canPermission('asrama-manage');
    }

    /**
     * Mengelola templat aktivitas
     */
    public function manageTemplates(User $user, Dormitory $dormitory): bool
    {
        return canPermission('asrama-manage');
    }

    /**
     * Broadcast darurat (hanya Wadir 1 / Kepala Asrama ke atas)
     */
    public function broadcastEmergency(User $user, Dormitory $dormitory): bool
    {
        return canPermission('asrama-create');
    }

    /**
     * Mengelola kunjungan
     */
    public function manageVisits(User $user, Dormitory $dormitory): bool
    {
        return canPermission('asrama-manage');
    }

    /**
     * Check-in / check-out visitor
     */
    public function checkInOutVisit(User $user, Dormitory $dormitory): bool
    {
        return canPermission('asrama-manage');
    }

    /**
     * Mengelola wing & kamar
     */
    public function manageRooms(User $user, Dormitory $dormitory): bool
    {
        return canPermission('asrama-create');
    }
}
