<?php

declare(strict_types=1);

namespace App\Authorization\Providers;

use App\Authorization\Contracts\PermissionProvider;
use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\Enums\PermissionSource;
use App\Authorization\ValueObjects\ScopeKey;
use App\Models\User;

final class StudentPermissionProvider implements PermissionProvider
{
    /**
     * Student-domain permissions derived from role level and study-group/teaching assignments.
     *
     * This provider does NOT perform authorization — it yields PermissionOrigin
     * objects for the Student domain only.
     */
    public function provide(int|string $userId): array
    {
        $user = User::withTrashed()->find($userId);

        if (! $user instanceof User) {
            return [];
        }

        $origins = [];

        // All teachers/staff/admins get at least read access to students
        $roleLevel = $user->roles()->min('level');
        if ($roleLevel !== null) {
            $origins[] = new PermissionOrigin(
                provider: 'students',
                permission: 'students.read',
                reason: 'assigned_role',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Teachers and admins can write student data (grade input, attendance correction, etc.)
        if ($roleLevel !== null && (int) $roleLevel <= 13) {
            // Departemen Tahfidz, Satuan Pendidikan, etc.
            $origins[] = new PermissionOrigin(
                provider: 'students',
                permission: 'students.write',
                reason: 'teacher_role_level',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Higher roles get delete access (rarely used — soft-delete from alumni flow)
        if ($roleLevel !== null && (int) $roleLevel <= 6) {
            $origins[] = new PermissionOrigin(
                provider: 'students',
                permission: 'students.delete',
                reason: 'admin/executive_role_level',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Teaching assignment → write access per subject per class
        foreach ($user->hasMany(\App\Models\TeachingAssignment::class, 'teacher_id')->get() as $ta) {
            if ($ta->status === 'active') {
                $origins[] = new PermissionOrigin(
                    provider: 'students',
                    permission: 'students.write',
                    reason: sprintf('teaching_assignment_%s', $ta->study_group_id),
                    scope: ScopeKey::forUser($user),
                    source: PermissionSource::DELEGATION,
                );
            }
        }

        return $origins;
    }
}
