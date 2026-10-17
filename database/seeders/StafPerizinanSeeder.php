<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class StafPerizinanSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'menu-staf-perizinan-sidebar',
            'staf_perizinan.view',
            'staf_perizinan.create',
            'staf_perizinan.approve',
            'staf_perizinan.reject',
            'staf_perizinan.scan',
            'staf_perizinan.process_return',
            'staf_perizinan.visit_view',
            'staf_perizinan.visit_approve',
            'staf_perizinan.visit_checkin',
            'staf_perizinan.type_manage',
            'staf_perizinan.report_view',
        ];

        /*
        |--------------------------------------------------------------------------
        | Pastikan permissions tersedia
        |--------------------------------------------------------------------------
        |
        | Jangan pernah mengubah ID permission yang sudah ada karena ID tersebut
        | mungkin sudah direferensikan oleh tabel lain seperti:
        |
        | - role_has_permissions
        | | - domain_role_permissions
        | - permission_user
        |
        */

        foreach ($permissions as $permission) {
            $exists = DB::table('permissions')
                ->where('name', $permission)
                ->where('guard_name', 'web')
                ->exists();

            if (! $exists) {
                DB::table('permissions')->insert([
                    'id' => (string) Str::uuid(),
                    'name' => $permission,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Ambil Role Asrama
        |--------------------------------------------------------------------------
        */

        $asramaRoleId = DB::table('roles')
            ->where('name', 'Asrama')
            ->where('guard_name', 'web')
            ->value('id');

        if (! empty($asramaRoleId)) {

            /*
            |--------------------------------------------------------------------------
            | Ambil Permission IDs
            |--------------------------------------------------------------------------
            */

            $permIds = DB::table('permissions')
                ->where('guard_name', 'web')
                ->whereIn('name', $permissions)
                ->pluck('id')
                ->toArray();

            /*
            |--------------------------------------------------------------------------
            | Assign permissions ke role Asrama
            |--------------------------------------------------------------------------
            */

            if (! empty($permIds)) {
                $rows = array_map(
                    fn ($permissionId) => [
                        'permission_id' => $permissionId,
                        'role_id' => $asramaRoleId,
                    ],
                    $permIds
                );

                DB::table('role_has_permissions')->upsert(
                    $rows,
                    ['permission_id', 'role_id'],
                    []
                );
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
