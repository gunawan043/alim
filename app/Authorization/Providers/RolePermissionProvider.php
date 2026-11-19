<?php

declare(strict_types=1);

namespace App\Authorization\Providers;

use App\Authorization\Contracts\PermissionProvider;
use App\Authorization\DTO\PermissionOrigin;
use App\Authorization\Enums\PermissionSource;
use App\Authorization\ValueObjects\ScopeKey;
use App\Models\User;

/**
 * Meneruskan permission yang memang ter-assign pada role/direct user
 * (`role_has_permissions` / `model_has_permissions`) ke dalam snapshot izin.
 *
 * Seeder role sudah lama meng-assign permission seperti `gtk_view`,
 * `school_view`, dan `student_export` ke role resmi. Tanpa provider ini,
 * `canPermission('gtk_view')` selalu false karena snapshot hanya membaca
 * provider turunan (jabatan/tugas), bukan tabel permission Spatie. Akibatnya
 * halaman yang dijaga permission menolak 403 walau role-nya berhak.
 */
final class RolePermissionProvider implements PermissionProvider
{
    public function provide(int|string $userId): array
    {
        $user = User::withTrashed()
            ->with(['roles.permissions', 'permissions'])
            ->find($userId);

        if (! $user instanceof User) {
            return [];
        }

        $origins = [];
        $scope = ScopeKey::forUser($user);

        foreach ($user->roles as $role) {
            foreach ($role->permissions as $permission) {
                $origins[] = new PermissionOrigin(
                    provider: 'role_permission',
                    permission: $permission->name,
                    reason: 'role:'.$role->name,
                    scope: $scope,
                    source: PermissionSource::ASSIGNMENT,
                );
            }
        }

        // Permission langsung pada user (jarang dipakai, tetapi sah).
        foreach ($user->permissions as $permission) {
            $origins[] = new PermissionOrigin(
                provider: 'role_permission',
                permission: $permission->name,
                reason: 'direct_user_permission',
                scope: $scope,
                source: PermissionSource::ASSIGNMENT,
            );
        }

        return $origins;
    }
}
