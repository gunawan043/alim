<?php

declare(strict_types=1);

namespace App\Authorization\Providers;

use App\Authorization\Contracts\PermissionProvider;
use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\Enums\PermissionSource;
use App\Authorization\ValueObjects\ScopeKey;
use App\Models\User;

final class PayrollPermissionProvider implements PermissionProvider
{
    /**
     * Payroll-domain permissions derived from finance/personalia role level.
     */
    public function provide(int|string $userId): array
    {
        $user = User::withTrashed()->find($userId);

        if (! $user instanceof User) {
            return [];
        }

        $origins = [];
        $roleLevel = $user->roles()->min('level');

        // Finance/Accounting roles get payroll access
        if ($roleLevel !== null && (int) $roleLevel <= 21) {
            // Keuangan (level 21), Admin Tata Usaha (level 9)
            $origins[] = new PermissionOrigin(
                provider: 'payroll',
                permission: 'payroll.read',
                reason: 'finance_or_admin_role',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );

            $origins[] = new PermissionOrigin(
                provider: 'payroll',
                permission: 'payroll.write',
                reason: 'finance_or_admin_role',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Admin+ roles get approval + disbursement
        if ($roleLevel !== null && (int) $roleLevel <= 9) {
            $origins[] = new PermissionOrigin(
                provider: 'payroll',
                permission: 'payroll.approve',
                reason: 'senior_admin_or_higher',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        if ($roleLevel !== null && (int) $roleLevel <= 6) {
            $origins[] = new PermissionOrigin(
                provider: 'payroll',
                permission: 'payroll.disburse',
                reason: 'administrator_or_higher',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Executive (Wadir, Mudir, Super Admin) — all payroll rights
        if ($roleLevel !== null && (int) $roleLevel <= 2) {
            $origins[] = new PermissionOrigin(
                provider: 'payroll',
                permission: 'payroll.read',
                reason: 'executive_role',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
            $origins[] = new PermissionOrigin(
                provider: 'payroll',
                permission: 'payroll.approve',
                reason: 'executive_role',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        return $origins;
    }
}
