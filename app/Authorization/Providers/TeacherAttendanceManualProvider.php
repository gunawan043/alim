<?php

declare(strict_types=1);

namespace App\Authorization\Providers;

use App\Authorization\Contracts\PermissionProvider;
use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\Enums\PermissionSource;
use App\Authorization\ValueObjects\ScopeKey;
use App\Models\User;

/**
 * Grants teacher-attendance_manual only to GTK users whose position is wakasek.
 *
 * Manual check-in/out is restricted to Waka (Wakil Kepala Sekolah) personnel
 * to prevent unauthorized attendance manipulation.
 */
final class TeacherAttendanceManualProvider implements PermissionProvider
{
    public function provide(int|string $userId): array
    {
        $user = User::withTrashed()->find($userId);

        if (! $user instanceof User) {
            return [];
        }

        // Must have active GTK employment
        if (! $user->employment || ! $user->employment->is_active) {
            return [];
        }

        // Waka only — check position_type or jabatan
        $positionType = $user->employment->position_type ?? null;
        $jabatan = $user->employment->jabatan ?? null;

        $isWaka = match (true) {
            $positionType === 'wakasek' => true,
            $jabatan !== null && stripos($jabatan, 'wakasek') !== false => true,
            default => false,
        };

        if (! $isWaka) {
            return [];
        }

        return [
            new PermissionOrigin(
                provider: 'teacher-attendance-manual',
                permission: 'teacher-attendance_manual',
                reason: 'wakasek_position',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::EMPLOYMENT,
            ),
        ];
    }
}
