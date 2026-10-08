<?php

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SarprasPermissionSeeder extends Seeder
{
    public function run()
    {
        // Permission
        $perm = Permission::firstOrCreate(['name' => 'sarpras_access']);

        // Assign ke role-role yang berhak
        $roles = ['Admin Sarpras', 'Admin Tata Usaha', 'Unit Rumah Tangga'];
        foreach ($roles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($perm);
            }
        }
    }
}