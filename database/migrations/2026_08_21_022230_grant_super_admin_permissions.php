<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $superAdminRoleId = '9a49a377-bd7a-48b5-b0e3-3afd86445ceb';

        $permissions = [
            'school-create',
            'school-edit',
            'school-delete',
            'dormitory-master-create',
            'dormitory-master-update',
            'dormitory-master-delete',
            'dormitory-master-all-access',
            'view_global_school_data',
        ];

        foreach ($permissions as $permissionName) {
            $permissionId = DB::table('permissions')
                ->where('name', $permissionName)
                ->value('id');

            if ($permissionId) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $superAdminRoleId)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (!$exists) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $superAdminRoleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $superAdminRoleId = '9a49a377-bd7a-48b5-b0e3-3afd86445ceb';

        $permissions = [
            'school-create',
            'school-edit',
            'school-delete',
            'dormitory-master-create',
            'dormitory-master-update',
            'dormitory-master-delete',
            'dormitory-master-all-access',
            'view_global_school_data',
        ];

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $permissions)
            ->pluck('id');

        DB::table('role_has_permissions')
            ->where('role_id', $superAdminRoleId)
            ->whereIn('permission_id', $permissionIds)
            ->delete();
    }
};
