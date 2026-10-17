<?php

declare(strict_types=1);

namespace App\Authorization\Providers;

use App\Authorization\Contracts\PermissionProvider;
use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\Enums\PermissionSource;
use App\Authorization\ValueObjects\ScopeKey;
use App\Models\User;
use App\Services\WorkspaceActivationService;

final class WorkspacePermissionProvider implements PermissionProvider
{
    /**
     * Workspace-domain permissions derived from active assignment tables.
     *
     * Unlike role-based permissions (static), these are dynamic:
     * they appear/disappear based on whether the user currently holds
     * an active homeroom, coordinator, or structural assignment.
     *
     * Assigned models are observed by PermissionRebuildObserver so that
     * changes to assignments trigger an async snapshot rebuild.
     */
    public function provide(int|string $userId): array
    {
        $user = User::withTrashed()->find($userId);

        if (! $user instanceof User) {
            return [];
        }

        $ws = app(WorkspaceActivationService::class)->resolve($user);

        $origins = [];

        if ($ws['wali_kelas']) {
            $origins[] = new PermissionOrigin(
                provider: 'workspace',
                permission: 'workspace.wali-kelas',
                reason: 'homeroom_assignment_active',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::DELEGATION,
            );
        }

        if ($ws['koordinator_rumpun']) {
            $origins[] = new PermissionOrigin(
                provider: 'workspace',
                permission: 'workspace.coordinator-rumpun',
                reason: 'coordinator_assignment_active',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::DELEGATION,
            );
        }

        if ($ws['waka_kurikulum']) {
            $origins[] = new PermissionOrigin(
                provider: 'workspace',
                permission: 'workspace.waka-kurikulum',
                reason: 'structural_assignment_active',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::DELEGATION,
            );
        }

        return $origins;
    }
}
