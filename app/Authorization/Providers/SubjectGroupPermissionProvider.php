<?php

declare(strict_types=1);

namespace App\Authorization\Providers;

use App\Authorization\Contracts\PermissionProvider;
use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\Enums\PermissionSource;
use App\Authorization\ValueObjects\ScopeKey;
use App\Models\User;
use App\Services\SubjectGroupResolver;

/**
 * Tahap 1 — permission berbasis assignment rumpun mata pelajaran.
 *
 * Menghasilkan per permission rumpun:
 *  - `subject-group.{code}.member`      → guru pengampu mapel dalam rumpun (TeachingAssignment aktif)
 *  - `subject-group.{code}.coordinator` → pemegang tugas tambahan koordinator rumpun
 *
 * Snapshot dibangun ulang ketika TeachingAssignment/Subject/GtkAdditionalTask berubah
 * (lihat PermissionRebuildObserver).
 */
final class SubjectGroupPermissionProvider implements PermissionProvider
{
    public function provide(int|string $userId): array
    {
        $user = User::withTrashed()->find($userId);

        if (! $user instanceof User) {
            return [];
        }

        $resolver = app(SubjectGroupResolver::class);
        $scope = ScopeKey::forUser($user);
        $origins = [];

        foreach ($resolver->groupsForTeacher($user) as $group) {
            $origins[] = new PermissionOrigin(
                provider: 'subject-group',
                permission: "subject-group.{$group->code}.member",
                reason: 'teaching_assignment_active',
                scope: $scope,
                source: PermissionSource::DELEGATION,
            );
        }

        foreach ($resolver->coordinatorGroupsForUser($user) as $group) {
            $origins[] = new PermissionOrigin(
                provider: 'subject-group',
                permission: "subject-group.{$group->code}.coordinator",
                reason: 'coordinator_task_active',
                scope: $scope,
                source: PermissionSource::DELEGATION,
            );
        }

        return $origins;
    }
}
