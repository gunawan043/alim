<?php

namespace App\Authorization\Services;

use App\Authorization\Providers\RoleGroupPermissionProvider;

/**
 * Maps workflow role-name identifiers (approval steps, notification dispatch)
 * to snapshot permission strings.
 *
 * Replaces the previous pattern where workflow code stored role names
 * ('Kepala Sekolah', 'Wali Santri', 'admin') and checked auth via
 * string-equality with permission names. Every workflow site now declares
 * its permission requirement explicitly through this resolver.
 *
 * Lookup order:
 *   1. Direct registry (ApprovalRoleResolver::ROLE_TO_PERMISSION)
 *   2. Role group mapping (RoleGroupPermissionProvider / RoleToPermissionMapper)
 *   3. Final fallback: return the literal roleName (legacy fail-safe)
 */
final class ApprovalRoleResolver
{
    /**
     * Role name (as stored in approval_flow_steps.role_name or used by
     * notification dispatch) → permission identifier.
     */
    public const ROLE_TO_PERMISSION = [
        // Approval workflow (GTK transfer)
        'Kepala Sekolah' => 'gtk.transfer.approve.kepalasekolah',
        'Wadir 1' => 'gtk.transfer.approve.wadir1',
        'Wadir 2' => 'gtk.transfer.approve.wadir2',
        'Mudir' => 'gtk.transfer.approve.mudir',
        'Yayasan' => 'gtk.transfer.approve.yayasan',

        // Notification dispatch aliases (used by helper functions)
        'admin' => 'general_admin.administrable',
        'Admin' => 'general_admin.administrable',
        'admin_tu' => 'admin.tu.assessable',
        'Admin Tata Usaha' => 'admin.tu.assessable',
        'Wali Santri' => 'wali_santri.communicable',
    ];

    /**
     * Resolve a role name (or workflow identifier) to the permission(s) the
     * user must hold.
     *
     * @return array<int, string> One or more permission names
     */
    public static function resolvePermission(string $roleName): array
    {
        if (array_key_exists($roleName, self::ROLE_TO_PERMISSION)) {
            $perm = self::ROLE_TO_PERMISSION[$roleName];

            return $perm ? [$perm] : [];
        }

        // Look up via role groups (e.g. 'gtk_teachers' → 'general_teacher.readable')
        $permission = self::lookupViaRoleGroups($roleName);
        if ($permission) {
            return [$permission];
        }

        // Final fallback: legacy string-equality (preserves prior behaviour)
        return [$roleName];
    }

    /**
     * Try to find a permission mapping by walking registered role groups.
     */
    private static function lookupViaRoleGroups(string $roleName): ?string
    {
        $provider = app(RoleGroupPermissionProvider::class);
        $allGroups = RoleToPermissionMapper::allGroupNames();
        foreach ($allGroups as $groupName) {
            $mapping = RoleToPermissionMapper::mapping($groupName);
            if (! $mapping || empty($mapping['roles'])) {
                continue;
            }
            if (in_array($roleName, $mapping['roles'], true)) {
                $perms = $mapping['permissions'] ?? [];
                if (! empty($perms)) {
                    return $perms[0];
                }
            }
        }

        return null;
    }
}
