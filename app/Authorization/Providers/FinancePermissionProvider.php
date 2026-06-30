<?php

declare(strict_types=1);

namespace App\Authorization\Providers;

use App\Authorization\Contracts\PermissionProvider;
use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\Enums\PermissionSource;
use App\Authorization\ValueObjects\ScopeKey;
use App\Models\User;

final class FinancePermissionProvider implements PermissionProvider
{
    /**
     * Finance domain: procurement, asset management, room booking, maintenance.
     *
     * Admin Sarpras (level 22) → read/write on asset/building
     * Tata Usaha+ (level 10) → procurement approvals
     * Admin+ (level 9) → financial disbursement approvals
     */
    public function provide(int|string $userId): array
    {
        $user = User::withTrashed()->find($userId);

        if (! $user instanceof User) {
            return [];
        }

        $origins = [];
        $roleLevel = $user->roles()->min('level');

        // Sarpras roles
        if ($roleLevel !== null && (int) $roleLevel <= 22) {
            $origins[] = new PermissionOrigin(
                provider: 'finance',
                permission: 'audit.read',
                reason: 'sarpras_admin_or_higher',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Admin Tata Usaha and above → procurement approvals
        if ($roleLevel !== null && (int) $roleLevel <= 9) {
            $origins[] = new PermissionOrigin(
                provider: 'finance',
                permission: 'payroll.approve',
                reason: 'admin_tata_usaha_or_higher',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Auditor-like access (Wadir+, Administrator)
        if ($roleLevel !== null && (int) $roleLevel <= 6) {
            $origins[] = new PermissionOrigin(
                provider: 'finance',
                permission: 'audit.read',
                reason: 'administrator_or_higher',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
            $origins[] = new PermissionOrigin(
                provider: 'finance',
                permission: 'audit.export',
                reason: 'administrator_or_higher',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Executive → full audit access
        if ($roleLevel !== null && (int) $roleLevel <= 2) {
            $origins[] = new PermissionOrigin(
                provider: 'finance',
                permission: 'audit.read',
                reason: 'executive_role',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
            $origins[] = new PermissionOrigin(
                provider: 'finance',
                permission: 'audit.export',
                reason: 'executive_role',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        return $origins;
    }
}