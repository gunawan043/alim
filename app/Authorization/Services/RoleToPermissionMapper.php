<?php

declare(strict_types=1);

namespace App\Authorization\Services;

/**
 * Maps legacy role names to their corresponding permission identifiers.
 *
 * This acts as the central registry for converting "role-based" queries
 * into "permission-based" queries. Every mapping entry represents a
 * semantic grouping of users that was previously achieved via
 * User::role([...]) or whereHas('roles').
 *
 * Example:
 *   Role: ['Guru Umum', 'Guru Agama', 'Guru Hadits', 'Guru Tahfidz', 'GTK']
 *   Permission: 'gtk.teacher.assignable'
 */
final class RoleToPermissionMapper
{
    /**
     * Role-group to permission string mappings.
     *
     * Key = semantic grouping name (human readable).
     * Value = { permissions: array, roles: array }
     *
     * The 'permissions' array contains ONE permission that represents the group.
     * Filtering by that permission gives the same user set as filtering by the role list.
     */
    private static array $ROLE_GROUP_MAPPINGS = [
        'general_teacher' => [
            'permissions' => ['general_teacher.readable'],
            'roles' => [
                'Guru Umum',
                'Guru Agama',
                'Guru Hadits',
                'Guru Tahfidz',
                'GTK',
                'Coordinator Guru',
                'Wakil Kepala Sekolah',
            ],
        ],

        'student_teacher' => [
            'permissions' => ['student_teacher.readable'],
            'roles' => [
                'Guru Umum',
                'Guru Agama',
                'Guru Hadits',
                'Guru Tahfidz',
                'GTK',
                'Coordinator Guru',
                'Kepala Sekolah',
                'Wakil Kepala Sekolah',
            ],
        ],

        'general_tutor' => [
            'permissions' => ['general_tutor.readable'],
            'roles' => [
                'Guru Mata Pelajaran',
                'Guru',
                'GTK',
            ],
        ],

        'admin_staff' => [
            'permissions' => ['admin.tu.assessable'],
            'roles' => ['Admin Tata Usaha'],
        ],

        'general_admin' => [
            'permissions' => ['general_admin.administrable'],
            'roles' => ['Super Admin', 'Administrator'],
        ],

        'personalia' => [
            'permissions' => ['personalia.recruitable'],
            'roles' => ['Personalia'],
        ],

        'technician' => [
            'permissions' => ['sarpras.technician.assignable'],
            'roles' => ['teknisi', 'technician', 'sarpras_teknisi'],
        ],

        'sarpras_team' => [
            'permissions' => ['sarpras.administrator.accessible'],
            'roles' => ['sarpras_admin', 'sarpras_kepala', 'teknisi'],
        ],

        'sarpras_managers' => [
            'permissions' => ['sarpras.manager.approvable'],
            'roles' => ['sarpras_admin', 'sarpras_kepala'],
        ],

        'stock_opname_auditors' => [
            'permissions' => ['sarpras.auditor.auditable'],
            'roles' => ['sarpras_admin', 'sarpras_kepala', 'auditor'],
        ],

        'non_teaching_staff' => [
            'permissions' => ['general_staff.ineligible'],
            'roles' => [
                'Super Admin',
                'Mudir',
                'Wadir 1',
                'Wadir 2',
                'Administrator',
                'Keuangan',
                'Asrama',
            ],
        ],

        'hr_notification_recipients' => [
            'permissions' => ['hr.notification.recipient'],
            'roles' => [
                'Personalia',
                'Super Admin',
                'Admin Tata Usaha',
            ],
        ],

        'decree_signers' => [
            'permissions' => ['decree.signer.certifiable'],
            'roles' => [
                'Super Admin',
                'Mudir',
                'Kepala Sekolah',
                'Wadir 1',
                'Administrator',
            ],
        ],

        'wali_santri' => [
            'permissions' => ['wali_santri.communicable'],
            'roles' => [
                'Wali Santri',
            ],
        ],

        'gtk_transfer_approvers' => [
            'permissions' => [
                'gtk.transfer.approve.kepalasekolah',
                'gtk.transfer.approve.wadir1',
                'gtk.transfer.approve.wadir2',
                'gtk.transfer.approve.mudir',
                'gtk.transfer.approve.yayasan',
            ],
            'roles' => [
                'Kepala Sekolah',
                'Wadir 1',
                'Wadir 2',
                'Mudir',
                'Yayasan',
            ],
        ],

        // ─── Dormitory (Asrama) master-data access ───────────────────
        'dormitory_master_full' => [
            'permissions' => [
                'dormitory-master-all-access',
            ],
            'roles' => [
                'Super Admin',
                'Administrator',
                'Mudir',
                'Wadir 2',
            ],
        ],

        'dormitory_master_admin' => [
            'permissions' => [
                'dormitory-master-admin-access',
            ],
            'roles' => [
                'Super Admin',
                'Administrator',
            ],
        ],
    ];

    /**
     * Get the list of roles that map to a permission.
     *
     * @return array<int, string>
     */
    public static function getRolesFor(string $groupName): array
    {
        $mapping = self::findMapping($groupName);
        return $mapping ? $mapping['roles'] : [];
    }

    /**
     * Get the permission strings associated with a role group.
     *
     * @return array<int, string>
     */
    public static function getPermissionsFor(string $groupName): array
    {
        $mapping = self::findMapping($groupName);
        return $mapping ? $mapping['permissions'] : [];
    }

    /**
     * Reverse-map: given a role name, find the group name it belongs to.
     *
     * @return array<int, string> Group names the role belongs to
     */
    public static function getGroupNamesForRole(string $roleName): array
    {
        $groups = [];
        foreach (self::$ROLE_GROUP_MAPPINGS as $groupName => $mapping) {
            if (in_array($roleName, $mapping['roles'], true)) {
                $groups[] = $groupName;
            }
        }

        return $groups;
    }

    /**
     * Check if a role group exists in the mapper.
     */
    public static function hasGroup(string $groupName): bool
    {
        return array_key_exists($groupName, self::$ROLE_GROUP_MAPPINGS);
    }

    /**
     * List all registered group names.
     *
     * @return array<int, string>
     */
    public static function allGroupNames(): array
    {
        return array_keys(self::$ROLE_GROUP_MAPPINGS);
    }

    /**
     * Get full mapping for a group.
     *
     * @return array{permissions: array<int, string>, roles: array<int, string>}|null
     */
    public static function mapping(string $groupName): ?array
    {
        return self::findMapping($groupName);
    }

    /**
     * Find a mapping by group name.
     *
     * @return array{permissions: array<int, string>, roles: array<int, string>}|null
     */
    private static function findMapping(string $groupName): ?array
    {
        return self::$ROLE_GROUP_MAPPINGS[$groupName] ?? null;
    }
}
