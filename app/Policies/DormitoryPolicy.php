<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Dormitory;

class DormitoryPolicy
{
    protected function roleLevel(User $user): int
    {
        return (int) $user->roles()->min('level');
    }

    /**
     * Melihat daftar asrama
     */
    public function viewAny(User $user): bool
    {
        return $this->roleLevel($user) <= 7;
    }

    /**
     * Melihat detail satu asrama
     */
    public function view(User $user, Dormitory $dormitory): bool
    {
        return $this->roleLevel($user) <= 7;
    }

    /**
     * Membuat asrama baru (hanya Super Admin / Mudir)
     */
    public function create(User $user): bool
    {
        return $this->roleLevel($user) <= 5;
    }

    /**
     * Mengubah data asrama
     */
    public function update(User $user, Dormitory $dormitory): bool
    {
        return $this->roleLevel($user) <= 5;
    }

    /**
     * Menghapus asrama (hanya Super Admin)
     */
    public function delete(User $user, Dormitory $dormitory): bool
    {
        return $this->roleLevel($user) <= 2;
    }

    /**
     * Mengelola penghuni
     */
    public function manageResidents(User $user, Dormitory $dormitory): bool
    {
        return $this->roleLevel($user) <= 6;
    }

    /**
     * Check-in / check-out penghuni
     */
    public function checkInOut(User $user, Dormitory $dormitory): bool
    {
        return $this->roleLevel($user) <= 6;
    }

    /**
     * Mencatat absensi
     */
    public function recordAttendance(User $user, Dormitory $dormitory): bool
    {
        return $this->roleLevel($user) <= 6;
    }

    /**
     * Memverifikasi absensi
     */
    public function verifyAttendance(User $user, Dormitory $dormitory): bool
    {
        return $this->roleLevel($user) <= 5;
    }

    /**
     * Menyetujui / menolak izin
     */
    public function approvePermit(User $user, Dormitory $dormitory): bool
    {
        return $this->roleLevel($user) <= 5;
    }

    /**
     * Mengajukan izin
     */
    public function createPermit(User $user, Dormitory $dormitory): bool
    {
        return $this->roleLevel($user) <= 7;
    }

    /**
     * Mencatat pelanggaran
     */
    public function recordViolation(User $user, Dormitory $dormitory): bool
    {
        return $this->roleLevel($user) <= 6;
    }

    /**
     * Mengirim notifikasi pelanggaran ke wali
     */
    public function notifyParent(User $user, Dormitory $dormitory): bool
    {
        return $this->roleLevel($user) <= 6;
    }

    /**
     * Posting informasi
     */
    public function postInformation(User $user, Dormitory $dormitory): bool
    {
        return $this->roleLevel($user) <= 6;
    }

    /**
     * Mengelola templat aktivitas
     */
    public function manageTemplates(User $user, Dormitory $dormitory): bool
    {
        return $this->roleLevel($user) <= 6;
    }

    /**
     * Broadcast darurat (hanya kepala asrama ke atas)
     */
    public function broadcastEmergency(User $user, Dormitory $dormitory): bool
    {
        return $this->roleLevel($user) <= 5;
    }

    /**
     * Mengelola kunjungan
     */
    public function manageVisits(User $user, Dormitory $dormitory): bool
    {
        return $this->roleLevel($user) <= 6;
    }

    /**
     * Check-in / check-out visitor
     */
    public function checkInOutVisit(User $user, Dormitory $dormitory): bool
    {
        return $this->roleLevel($user) <= 6;
    }

    /**
     * Mengelola wing & kamar
     */
    public function manageRooms(User $user, Dormitory $dormitory): bool
    {
        return $this->roleLevel($user) <= 5;
    }
}
