<?php

declare(strict_types=1);

namespace App\Authorization\Providers;

use App\Authorization\Contracts\PermissionProvider;
use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\Enums\PermissionSource;
use App\Authorization\ValueObjects\ScopeKey;
use App\Models\User;

final class ReportPermissionProvider implements PermissionProvider
{
    /**
     * Report-domain permissions derived from role level.
     *
     * Read: All staff (subject to scope).
     * Export: Admin+ only.
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
                provider: 'reports',
                permission: 'reports.read',
                reason: 'assigned_role',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        if ($roleLevel !== null && (int) $roleLevel <= 9) {
            $origins[] = new PermissionOrigin(
                provider: 'reports',
                permission: 'reports.export',
                reason: 'admin_or_higher',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Executive+ has full export
        if ($roleLevel !== null && (int) $roleLevel <= 2) {
            $origins[] = new PermissionOrigin(
                provider: 'reports',
                permission: 'reports.export',
                reason: 'executive_role',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        return $origins;
    }
}