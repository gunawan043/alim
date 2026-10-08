<?php

declare(strict_types=1);

namespace App\Authorization\Providers;

use App\Authorization\Contracts\PermissionProvider;
use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\Enums\PermissionSource;
use App\Authorization\ValueObjects\ScopeKey;
use App\Models\User;

final class AttendancePermissionProvider implements PermissionProvider
{
    /**
     * Jabatan yang boleh melakukan ABSENSI MANUAL guru (check-in/check-out
     * tanpa scan QR) dan mengoreksi absensi. Selain jabatan ini, guru wajib
     * scan QR kelas pada jendela waktu jadwalnya.
     */
    public const MANUAL_ATTENDANCE_JABATAN = [
        'Kepala Satuan Pendidikan',
        'Wakil Kepala Satuan Pendidikan',
        'Wakasek Satuan Pendidikan',
        'Kepala Tata Usaha',
        'Staf Tata Usaha',
        'Koordinator Kurikulum',
    ];

    /**
     * Presensi/Attendance permissions derived from roles & jabatan.
     *
     * Read: All staff (assigned roles)
     * Write: Teachers record class attendance + Admins record other forms
     * Approve: Admin+ approve correction requests
     * Manual guru (QR): hanya jabatan tertentu (lihat MANUAL_ATTENDANCE_JABATAN)
     */
    public function provide(int|string $userId): array
    {
        $user = User::withTrashed()->find($userId);

        if (! $user instanceof User) {
            return [];
        }

        $origins = [];
        $roleLevel = $user->roles()->min('level');

        if ($roleLevel !== null) {
            $origins[] = new PermissionOrigin(
                provider: 'attendance',
                permission: 'presensi.read',
                reason: 'assigned_role',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );

            // Semua pegawai dapat melihat riwayat & dashboard kehadiran guru (QR).
            $origins[] = new PermissionOrigin(
                provider: 'attendance',
                permission: 'teacher-attendance_view',
                reason: 'assigned_role',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Teachers write daily attendance
        if ($roleLevel !== null && (int) $roleLevel <= 18) {
            // Admin Departemen Tahfidz and above (lower level = higher privilege)
            $origins[] = new PermissionOrigin(
                provider: 'attendance',
                permission: 'presensi.write',
                reason: 'teacher_or_admin_role',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Admin levels approve corrections
        if ($roleLevel !== null && (int) $roleLevel <= 9) {
            $origins[] = new PermissionOrigin(
                provider: 'attendance',
                permission: 'presensi.approve',
                reason: 'admin_or_higher',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Absensi manual guru: HANYA jabatan tertentu — mencegah guru absen
        // di luar jam pelajaran tanpa benar-benar berada di kelas.
        $jabatan = $this->resolveJabatan($user);
        if ($jabatan !== null && in_array($jabatan, self::MANUAL_ATTENDANCE_JABATAN, true)) {
            $origins[] = new PermissionOrigin(
                provider: 'attendance',
                permission: 'teacher-attendance_manual',
                reason: "jabatan: {$jabatan}",
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Admin ke atas boleh export data kehadiran guru.
        if ($roleLevel !== null && (int) $roleLevel <= 9) {
            $origins[] = new PermissionOrigin(
                provider: 'attendance',
                permission: 'teacher-attendance_export',
                reason: 'admin_or_higher',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        return $origins;
    }

    /**
     * Resolve nama jabatan user (Title Case), dari relasi jabatan atau string
     * gtk_employments.jabatan. Perbandingan dilakukan case-insensitive.
     */
    private function resolveJabatan(User $user): ?string
    {
        $candidates = [];

        $employment = $user->employment;
        if ($employment?->jabatanRel?->nama) {
            $candidates[] = $employment->jabatanRel->nama;
        }
        if ($employment?->jabatan) {
            $candidates[] = $employment->jabatan;
        }

        foreach ($user->employments as $e) {
            if ($e->jabatanRel?->nama) {
                $candidates[] = $e->jabatanRel->nama;
            }
            if ($e->jabatan) {
                $candidates[] = $e->jabatan;
            }
        }

        $normalized = array_map(
            static fn ($j) => strtolower(trim((string) $j)),
            $candidates,
        );

        foreach (self::MANUAL_ATTENDANCE_JABATAN as $allowed) {
            if (in_array(strtolower($allowed), $normalized, true)) {
                return $allowed;
            }
        }

        return null;
    }
}
