<?php

declare(strict_types=1);

namespace App\Authorization\Providers;

use App\Authorization\Contracts\PermissionProvider;
use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\Enums\PermissionSource;
use App\Authorization\Services\RoleToPermissionMapper;
use App\Authorization\ValueObjects\ScopeKey;
use App\Models\User;

/**
 * Maps legacy role names to user-set semantic permissions.
 *
 * This provider exists to maintain compatibility with code that previously
 * queried users by role name (e.g. User::role(['Guru Umum', 'GTK'])) by
 * yielding snapshot-level permissions that represent each role group.
 *
 * As the codebase migrates to direct semantic queries, this provider can
 * be deprecated. Until then, it allows the runtime to answer:
 *   "Does user X belong to the teacher group?" (via permission check)
 *   "Which users belong to the teacher group?"     (via UserFilterService)
 * without ever touching role names at the call site.
 */
final class RoleGroupPermissionProvider implements PermissionProvider
{
    public function provide(int|string $userId): array
    {
        $user = User::withTrashed()->find($userId);

        if (! $user instanceof User) {
            return [];
        }

        $origins = [];
        $userRoles = $user->roles()->pluck('name')->all();

        foreach ($userRoles as $roleName) {
            $groupNames = RoleToPermissionMapper::getGroupNamesForRole($roleName);

            foreach ($groupNames as $groupName) {
                $permissions = RoleToPermissionMapper::getPermissionsFor($groupName);

                foreach ($permissions as $permission) {
                    $origins[] = new PermissionOrigin(
                        provider: 'role_group',
                        permission: $permission,
                        reason: sprintf('role_group_%s_via_role_%s', $groupName, $roleName),
                        scope: ScopeKey::forUser($user),
                        source: PermissionSource::ASSIGNMENT,
                    );
                }
            }
        }

        return $origins;
    }
}
