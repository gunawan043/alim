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
     * Presensi/Attendance permissions derived from roles.
     *
     * Read: All staff (assigned roles)
     * Write: Teachers record class attendance + Admins record other forms
     * Approve: Admin+ approve correction requests
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

        return $origins;
    }
}