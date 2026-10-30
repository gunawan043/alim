<?php

namespace App\Support;

use App\Models\User;
use App\Services\SchoolGroupService;
use App\Support\UserJabatan;

/**
 * Aturan akses data GTK (pegawai) yang dipakai lintas middleware/controller.
 *
 * - Self-service: setiap pengguna boleh melihat/mengubah data GTK miliknya.
 * - Manager: System/Super Admin, pemegang izin `gtk-edit-all`, atau role
 *   pengelola kepegawaian yang memang dipakai di modul GTK.
 * - View-only: pemegang izin `gtk-view-all` / `gtk-view-school`.
 */
class GtkAccess
{
    /** Role yang boleh mengelola data GTK pegawai lain. */
    private const MANAGER_ROLES = [
        'Humas Personalia',
        'Personalia',
        'Satuan Pendidikan',
        'Unit Rumah Tangga',
    ];

    public static function isManager(User $user): bool
    {
        if ($user->isSystemAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        if (canPermission('gtk-edit-all')) {
            return true;
        }

        return $user->hasAnyRole(self::MANAGER_ROLES);
    }

    /**
     * Update massal GTK (halaman /gtk/massal + aksi mass-update).
     *
     * Sengaja lebih ketat daripada isManager(): hanya Personalia dan Super
     * Admin. Kepala satuan kerja / unit rumah tangga TIDAK termasuk.
     */
    public static function canMassUpdate(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isSystemAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        return $user->hasAnyRole(['Humas Personalia', 'Personalia']);
    }

    public static function canView(User $actor, User $target): bool
    {
        if ((string) $actor->id === (string) $target->id) {
            return true;
        }

        if (self::isManager($actor)) {
            return true;
        }

        return canPermission('gtk-view-all') || canPermission('gtk-view-school');
    }

    /**
     * Akses baca/detail GTK dengan isolasi satuan pendidikan.
     * Role Personalia/Humas Personalia tetap dapat melihat lintas unit;
     * role Satuan Pendidikan/KSP/Waka hanya sekolahnya sendiri.
     */
    public static function canAccess(User $actor, User $target, ?string $schoolId = null): bool
    {
        if ((string) $actor->id === (string) $target->id) {
            return true;
        }

        if ($actor->isSystemAdmin() || $actor->isSuperAdmin() || canPermission('gtk-view-all')) {
            return true;
        }

        if ($actor->hasAnyRole(['Humas Personalia', 'Personalia', 'Unit Rumah Tangga'])) {
            return true;
        }

        $actorSchoolId = self::schoolId($actor, $schoolId);
        $targetSchoolId = self::schoolId($target);

        return $actorSchoolId !== null
            && $actorSchoolId === $targetSchoolId
            && (self::isSchoolLeader($actor)
                || $actor->hasRole('Satuan Pendidikan')
                || canPermission('gtk-view-school'));
    }

    /**
     * KSP/Wakil KSP boleh mengelola akun GTK dalam satuan pendidikannya.
     * Akun pimpinan (KSP/Waka), System Admin, dan Super Admin dikecualikan
     * agar tidak terjadi eskalasi atau saling mengambil alih akun.
     */
    public static function canManageAccount(User $actor, User $target, ?string $schoolId = null): bool
    {
        if ((string) $actor->id === (string) $target->id || ! $target->is_active) {
            return false;
        }

        if ($target->isSystemAdmin() || $target->isSuperAdmin()) {
            return false;
        }

        if ($actor->isSystemAdmin() || $actor->isSuperAdmin()) {
            return true;
        }

        if (! self::isSchoolLeader($actor)) {
            return false;
        }

        if (UserJabatan::hasAny($target, [
            'kepala satuan pendidikan',
            'wakil kepala satuan pendidikan',
            'kepala sekolah',
            'wakil kepala sekolah',
        ])) {
            return false;
        }

        $actorSchoolId = self::schoolId($actor, $schoolId);
        $targetSchoolId = self::schoolId($target);

        return $actorSchoolId !== null && $actorSchoolId === $targetSchoolId;
    }

    public static function canImpersonate(User $actor, User $target, ?string $schoolId = null): bool
    {
        return self::canManageAccount($actor, $target, $schoolId);
    }

    public static function canResetPassword(User $actor, User $target, ?string $schoolId = null): bool
    {
        return self::canManageAccount($actor, $target, $schoolId);
    }

    public static function isSchoolLeader(User $user): bool
    {
        return UserJabatan::hasAny($user, [
            'kepala satuan pendidikan',
            'wakil kepala satuan pendidikan',
            'kepala sekolah',
            'wakil kepala sekolah',
        ]);
    }

    private static function schoolId(User $user, ?string $contextSchoolId = null): ?string
    {
        if ($contextSchoolId) {
            return $contextSchoolId;
        }

        return $user->employment?->school_id
            ?: SchoolGroupService::getUserSchool($user)?->id;
    }
}
