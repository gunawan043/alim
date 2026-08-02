<?php

declare(strict_types=1);

namespace App\Authorization\Providers;

use App\Authorization\Contracts\PermissionProvider;
use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\Enums\PermissionSource;
use App\Authorization\ValueObjects\ScopeKey;
use App\Models\User;

final class DormitoryPermissionProvider implements PermissionProvider
{
    /**
     * Dormitory-domain permissions derived from asrama role level.
     */
    public function provide(int|string $userId): array
    {
        $user = User::withTrashed()->find($userId);

        if (! $user instanceof User) {
            return [];
        }

        $origins = [];
        $roleLevel = $user->roles()->min('level');

        // Asrama role and above get read access
        if ($roleLevel !== null && (int) $roleLevel <= 20) {
            // Asrama (level 20), Admin Asrama (level 19) and above
            $origins[] = new PermissionOrigin(
                provider: 'dormitory',
                permission: 'dormitory.read',
                reason: 'asrama_or_higher',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Admin Asrama and above get write
        if ($roleLevel !== null && (int) $roleLevel <= 19) {
            $origins[] = new PermissionOrigin(
                provider: 'dormitory',
                permission: 'dormitory.write',
                reason: 'admin_asrama_or_higher',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Wadir+ can broadcast
        if ($roleLevel !== null && (int) $roleLevel <= 4) {
            $origins[] = new PermissionOrigin(
                provider: 'dormitory',
                permission: 'dormitory.broadcast',
                reason: 'wadir_or_higher',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Master data access: Administrator or higher
        if ($roleLevel !== null && (int) $roleLevel <= 4) {
            $origins[] = new PermissionOrigin(
                provider: 'dormitory',
                permission: 'dormitory-master-all-access',
                reason: 'wadir_or_higher_master_access',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Master data write: Administrator or higher (level 6 or below)
        if ($roleLevel !== null && (int) $roleLevel <= 6) {
            $origins[] = new PermissionOrigin(
                provider: 'dormitory',
                permission: 'dormitory-master-admin-access',
                reason: 'administrator_or_higher_master_write',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        return $origins;
    }
}
