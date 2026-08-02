<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class UksRolesSeeder extends Seeder
{
    /**
     * UKS roles and permissions for the Satuan Kerja UKS module.
     *
     * Role hierarchy:
     *   - Kepala UKS       : Overall head of UKS, can view all (putra & putri) data
     *   - Admin UKS        : Admin/staf UKS — semua gender (merge Admin UKS Putra & Putri)
     */
    public function run(): void
    {
        // ── PERMISSIONS ──────────────────────────────────────────────
        $permissions = [
            'view_uks_data',
            'manage_uks_gtk',
            'manage_uks_patients',
            'manage_uks_health_records',
            'manage_uks_immunizations',
            'manage_uks_medicine',
            'manage_ucs_sanitation',
            'manage_uks_facility_referrals',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm, 'guard_name' => 'web'],
                ['guard_name' => 'web']
            );
        }

        // ── ROLES ────────────────────────────────────────────────────
        $roles = [
            [
                'name' => 'Kepala UKS',
                'guard_name' => 'web',
                'permissions' => $permissions,
                'description' => 'Kepala UKS — dapat melihat semua GTK UKS (putra & putri)',
            ],
            [
                'name' => 'Admin UKS',
                'guard_name' => 'web',
                'permissions' => array_intersect($permissions, ['view_uks_data', 'manage_uks_patients', 'manage_uks_health_records']),
                'description' => 'Admin UKS — pengelolaan GTK UKS semua gender (merge Putra & Putri)',
            ],
        ];

        foreach ($roles as $roleData) {
            $role = Role::firstOrCreate(
                ['name' => $roleData['name'], 'guard_name' => $roleData['guard_name']],
                ['guard_name' => $roleData['guard_name']]
            );
            $role->syncPermissions($roleData['permissions']);
        }

        // Cache roles
        app()['cache']->forget('spatie.permission.cache');

        $this->command->info('UKS roles seeded successfully.');
    }
}
