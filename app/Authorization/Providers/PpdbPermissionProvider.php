<?php

declare(strict_types=1);

namespace App\Authorization\Providers;

use App\Authorization\Contracts\PermissionProvider;
use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\Enums\PermissionSource;
use App\Authorization\ValueObjects\ScopeKey;
use App\Models\User;

final class PpdbPermissionProvider implements PermissionProvider
{
    /**
     * PPDB/Recruitment-domain permissions derived from admin role level.
     *
     * PPDB needs to:
     *   - Read/write students (prospective and newly admitted)
     *   - Manage recruitment (jobs, applications, profiles)
     */
    public function provide(int|string $userId): array
    {
        $user = User::withTrashed()->find($userId);

        if (! $user instanceof User) {
            return [];
        }

        $origins = [];
        $roleLevel = $user->roles()->min('level');

        // Admin TU and higher manage PPDB flow
        if ($roleLevel !== null && (int) $roleLevel <= 9) {
            $origins[] = new PermissionOrigin(
                provider: 'ppdb',
                permission: 'students.read',
                reason: 'admin_tata_usaha_or_higher',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );

            $origins[] = new PermissionOrigin(
                provider: 'ppdb',
                permission: 'students.write',
                reason: 'admin_tata_usaha_or_higher',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        // Personalia+ handles recruitment
        if ($roleLevel !== null && (int) $roleLevel <= 5) {
            $origins[] = new PermissionOrigin(
                provider: 'ppdb',
                permission: 'students.read',
                reason: 'personalia_or_higher',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );

            $origins[] = new PermissionOrigin(
                provider: 'ppdb',
                permission: 'gtk.read',
                reason: 'personalia_or_higher',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );

            $origins[] = new PermissionOrigin(
                provider: 'ppdb',
                permission: 'gtk.write',
                reason: 'personalia_or_higher',
                scope: ScopeKey::forUser($user),
                source: PermissionSource::ASSIGNMENT,
            );
        }

        return $origins;
    }
}