<?php

declare(strict_types=1);

namespace App\Authorization\Providers;

use App\Authorization\Contracts\PermissionProvider;
use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\Enums\PermissionSource;
use App\Authorization\ValueObjects\ScopeKey;
use App\Models\User;

final class TahfidzPermissionProvider implements PermissionProvider
{
    /**
     * Tahfidz-program permissions derived from tahfidz role level.
     *
     * Tahfidz teachers (Departemen Tahfidz,
     * Admin Departemen Tahfidz) operate within the tahfidz department.
     */
    public function provide(int|string $userId): array
    {
        $user = User::withTrashed()->find($userId);

        if (! $user instanceof User) {
            return [];
        }

        $origins = [];
        $roleLevel = $user->roles()->min('level');

        // Departemen Tahfidz and above (level 16 admin departemen, 13 guru tahfidz)
        if ($roleLevel !== null && (int) $roleLevel <= 16) {
            $origins[] = new PermissionOrigin(
                provider: 'tahfidz',
                permission: 'students.read',
                reason: 'tahfidz_department_role',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );

            $origins[] = new PermissionOrigin(
                provider: 'tahfidz',
                permission: 'students.write',
                reason: 'tahfidz_department_role',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Coordinator Tahfidz and above can publish hafalan reports
        if ($roleLevel !== null && (int) $roleLevel <= 16) {
            $origins[] = new PermissionOrigin(
                provider: 'tahfidz',
                permission: 'exam.publish',
                reason: 'tahfidz_coordinator',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        return $origins;
    }
}
