<?php

declare(strict_types=1);

namespace App\Authorization\Providers;

use App\Authorization\Contracts\PermissionProvider;
use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\Enums\PermissionSource;
use App\Authorization\ValueObjects\ScopeKey;
use App\Models\User;

final class AcademicPermissionProvider implements PermissionProvider
{
    /**
     * Academic/Exam/Teaching permissions derived from roles and assignments.
     */
    public function provide(int|string $userId): array
    {
        $user = User::withTrashed()->find($userId);

        if (! $user instanceof User) {
            return [];
        }

        $origins = [];
        $roleLevel = $user->roles()->min('level');

        // Everyone with any role gets exam read
        if ($roleLevel !== null) {
            $origins[] = new PermissionOrigin(
                provider: 'academic',
                permission: 'exam.read',
                reason: 'assigned_role',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );

            $origins[] = new PermissionOrigin(
                provider: 'academic',
                permission: 'jadwalkbm.read',
                reason: 'assigned_role',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Teachers and higher can write/publish exam and schedule
        if ($roleLevel !== null && (int) $roleLevel <= 13) {
            // Departemen Tahfidz and above (lower level number = higher)
            $origins[] = new PermissionOrigin(
                provider: 'academic',
                permission: 'exam.write',
                reason: 'teacher_or_coordinator',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );

            $origins[] = new PermissionOrigin(
                provider: 'academic',
                permission: 'jadwalkbm.write',
                reason: 'teacher_or_coordinator',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Admin+ can publish
        if ($roleLevel !== null && (int) $roleLevel <= 9) {
            $origins[] = new PermissionOrigin(
                provider: 'academic',
                permission: 'exam.publish',
                reason: 'admin_or_higher',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );

            $origins[] = new PermissionOrigin(
                provider: 'academic',
                permission: 'jadwalkbm.publish',
                reason: 'admin_or_higher',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Supervision — anyone at coordinator level or above
        if ($roleLevel !== null && (int) $roleLevel <= 11) {
            $origins[] = new PermissionOrigin(
                provider: 'academic',
                permission: 'exam.supervise',
                reason: 'coordinator_or_higher',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Extracurricular permissions (part of academic)
        if ($roleLevel !== null) {
            $origins[] = new PermissionOrigin(
                provider: 'academic',
                permission: 'extracurricular.read',
                reason: 'assigned_role',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        if ($roleLevel !== null && (int) $roleLevel <= 12) {
            $origins[] = new PermissionOrigin(
                provider: 'academic',
                permission: 'extracurricular.write',
                reason: 'guru_or_higher',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        return $origins;
    }
}
