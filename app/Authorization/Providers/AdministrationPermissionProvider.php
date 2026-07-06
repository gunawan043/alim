<?php

declare(strict_types=1);

namespace App\Authorization\Providers;

use App\Authorization\Contracts\PermissionProvider;
use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\Enums\PermissionSource;
use App\Authorization\ValueObjects\ScopeKey;
use App\Models\User;

final class AdministrationPermissionProvider implements PermissionProvider
{
    /**
     * Administration-domain permissions: settings, approvals, global access toggles.
     *
     * System-level administration spans:
     *   - System settings (Personalia, Administrator, Super Admin)
     *   - Approval management (anyone can create, only approvers manage)
     *   - Global view toggles (Wadir+, Administrator, Super Admin)
     *   - School group access (Wadir+)
     */
    public function provide(int|string $userId): array
    {
        $user = User::withTrashed()->find($userId);

        if (! $user instanceof User) {
            return [];
        }

        $origins = [];
        $roleLevel = $user->roles()->min('level');

        // Admin+ can modify system settings
        if ($roleLevel !== null && (int) $roleLevel <= 6) {
            $origins[] = new PermissionOrigin(
                provider: 'administration',
                permission: 'gtk.write',
                reason: 'administrator_settings',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Wadir+ get global school data toggle
        if ($roleLevel !== null && (int) $roleLevel <= 4) {
            $origins[] = new PermissionOrigin(
                provider: 'administration',
                permission: 'students.read',
                reason: 'wadir_global_access',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Super Admin+ gets full admin
        if ($roleLevel !== null && (int) $roleLevel <= 1) {
            $origins[] = new PermissionOrigin(
                provider: 'administration',
                permission: 'gtk.assign',
                reason: 'super_admin_full_access',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );

            $origins[] = new PermissionOrigin(
                provider: 'administration',
                permission: 'super-admin-only',
                reason: 'super_admin_full_access',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Anyone can view approval requests
        if ($roleLevel !== null) {
            $origins[] = new PermissionOrigin(
                provider: 'administration',
                permission: 'walis.read',
                reason: 'admin_approval_access',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        return $origins;
    }
}