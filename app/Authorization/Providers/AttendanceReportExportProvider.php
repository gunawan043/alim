<?php

declare(strict_types=1);

namespace App\Authorization\Providers;

use App\Authorization\Contracts\PermissionProvider;
use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\Enums\PermissionSource;
use App\Authorization\ValueObjects\ScopeKey;
use App\Models\User;

/**
 * Grants teacher-attendance_report_export based on the user's current
 * jabatan at time of the export request.
 *
 * Only the following positions may export attendance reports:
 *   - Kepala Satuan Pendidikan / Kepala Sekolah
 *   - Wakil Kepala Sekolah / Wakil Kepala Satuan
 *   - Kepala Tata Usaha
 *   - Staf Tata Usaha
 */
final class AttendanceReportExportProvider implements PermissionProvider
{
    private const ALLOWED_JABATAN = [
        'Kepala Satuan Pendidikan',
        'Kepala Sekolah',
        'Wakil Kepala Satuan Pendidikan',
        'Wakil Kepala Sekolah',
        'Kepala Tata Usaha',
        'Staf Tata Usaha',
    ];

    public function provide(int|string $userId): array
    {
        $user = User::withTrashed()->find($userId);

        if (! $user instanceof User) {
            return [];
        }

        $jabatan = $this->resolveJabatan($user);

        if ($jabatan !== null && in_array($jabatan, self::ALLOWED_JABATAN, true)) {
            return [
                new PermissionOrigin(
                    provider: 'attendance-report-export',
                    permission: 'teacher-attendance_report_export',
                    reason: "jabatan: {$jabatan}",
                    scope: ScopeKey::forUser($user),
                    source: PermissionSource::ASSIGNMENT,
                ),
            ];
        }

        return [];
    }

    /**
     * Resolve the active jabatan name for this user.
     *
     * Reads from the user's current GtkEmployment; falls back to
     * any employments where jabatan_id is set.
     */
    private function resolveJabatan(User $user): ?string
    {
        $employment = $user->employment;
        if ($employment?->jabatanRel) {
            return $employment->jabatanRel->nama;
        }

        foreach ($user->employments as $e) {
            if ($e->jabatanRel) {
                return $e->jabatanRel->nama;
            }
        }

        return null;
    }
}
