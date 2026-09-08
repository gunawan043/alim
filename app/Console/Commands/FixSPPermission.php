<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixSPPermission extends Command
{
    protected $signature = 'sp:fix-permission';

    protected $description = 'Fix Kepala Sekolah role permission assignment (legacy: was Satuan Pendidikan)';

    public function handle()
    {
        $roleId = DB::table('roles')->where('name', 'Kepala Sekolah')->value('id');
        $permId = DB::table('permissions')->where('name', 'menu-satuan-pendidikan-sidebar')->value('id');

        if (! $roleId || ! $permId) {
            $this->error('Role or permission not found!');

            return 1;
        }

        $count = DB::table('role_has_permissions')
            ->where('role_id', $roleId)
            ->where('permission_id', $permId)
            ->count();

        if ($count === 0) {
            DB::table('role_has_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permId,
            ]);
            $this->info('Permission added successfully!');
        } else {
            $this->info('Permission already exists.');
        }

        // Verify
        $newCount = DB::table('role_has_permissions')->where('role_id', $roleId)->count();
        $this->info("Total Kepala Sekolah permissions: $newCount");

        // Test user
        $user = User::where('name', 'like', '%Saleh%')->first();
        if ($user) {
            $has = $user->hasPermissionTo('menu-satuan-pendidikan-sidebar');
            $this->info('User has permission: '.($has ? 'YES' : 'NO'));
        }

        return 0;
    }
}
