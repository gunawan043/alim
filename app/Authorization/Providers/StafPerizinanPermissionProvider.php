<?php

declare(strict_types=1);

namespace App\Authorization\Providers;

use App\Authorization\Contracts\PermissionProvider;
use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\Enums\PermissionSource;
use App\Authorization\ValueObjects\ScopeKey;
use App\Models\DormitoryStaffAssignment;
use App\Models\User;

final class StafPerizinanPermissionProvider implements PermissionProvider
{
    public function provide(int|string $userId): array
    {
        $user = User::withTrashed()->find($userId);

        if (! $user instanceof User) {
            return [];
        }

        $assignedDormitoryIds = DormitoryStaffAssignment::active()
            ->where('user_id', $user->id)
            ->pluck('dormitory_id')
            ->toArray();

        if (empty($assignedDormitoryIds)) {
            return [];
        }

        $origins = [];

        $origins[] = new PermissionOrigin(
            provider: 'staf-perizinan',
            permission: 'staf_perizinan.view',
            reason: 'active_staf_perizinan_assignment',
            scope: ScopeKey::forUser($user),
            source: PermissionSource::DELEGATION,
        );

        $origins[] = new PermissionOrigin(
            provider: 'staf-perizinan',
            permission: 'staf_perizinan.create',
            reason: 'active_staf_perizinan_assignment',
            scope: ScopeKey::forUser($user),
            source: PermissionSource::DELEGATION,
        );

        $origins[] = new PermissionOrigin(
            provider: 'staf-perizinan',
            permission: 'staf_perizinan.approve',
            reason: 'active_staf_perizinan_assignment',
            scope: ScopeKey::forUser($user),
            source: PermissionSource::DELEGATION,
        );

        $origins[] = new PermissionOrigin(
            provider: 'staf-perizinan',
            permission: 'staf_perizinan.reject',
            reason: 'active_staf_perizinan_assignment',
            scope: ScopeKey::forUser($user),
            source: PermissionSource::DELEGATION,
        );

        $origins[] = new PermissionOrigin(
            provider: 'staf-perizinan',
            permission: 'staf_perizinan.scan',
            reason: 'active_staf_perizinan_assignment',
            scope: ScopeKey::forUser($user),
            source: PermissionSource::DELEGATION,
        );

        $origins[] = new PermissionOrigin(
            provider: 'staf-perizinan',
            permission: 'staf_perizinan.process_return',
            reason: 'active_staf_perizinan_assignment',
            scope: ScopeKey::forUser($user),
            source: PermissionSource::DELEGATION,
        );

        $origins[] = new PermissionOrigin(
            provider: 'staf-perizinan',
            permission: 'staf_perizinan.visit_view',
            reason: 'active_staf_perizinan_assignment',
            scope: ScopeKey::forUser($user),
            source: PermissionSource::DELEGATION,
        );

        $origins[] = new PermissionOrigin(
            provider: 'staf-perizinan',
            permission: 'staf_perizinan.visit_approve',
            reason: 'active_staf_perizinan_assignment',
            scope: ScopeKey::forUser($user),
            source: PermissionSource::DELEGATION,
        );

        $origins[] = new PermissionOrigin(
            provider: 'staf-perizinan',
            permission: 'staf_perizinan.visit_checkin',
            reason: 'active_staf_perizinan_assignment',
            scope: ScopeKey::forUser($user),
            source: PermissionSource::DELEGATION,
        );

        $origins[] = new PermissionOrigin(
            provider: 'staf-perizinan',
            permission: 'staf_perizinan.report_view',
            reason: 'active_staf_perizinan_assignment',
            scope: ScopeKey::forUser($user),
            source: PermissionSource::DELEGATION,
        );

        foreach ($assignedDormitoryIds as $dormitoryId) {
            $origins[] = new PermissionOrigin(
                provider: 'staf-perizinan-scope',
                permission: 'staf_perizinan.dormitory:'.$dormitoryId,
                reason: 'explicit_dormitory_assignment',
                scope: ScopeKey::fromComponents(
                    schoolId: $user->school_id ?? null,
                    academicYearId: 'global',
                    roleDimension: 'staf-perizinan',
                    tenantId: $dormitoryId,
                ),
                source: PermissionSource::DELEGATION,
            );
        }

        return $origins;
    }
}
