<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * SuperAdminDomainPermissionSeeder — grants all permissions to the Super Admin domain.
 *
 * This seeder must run AFTER PermissionRoleSeeder (which assigns all permissions
 * to the Super Admin Spatie role) so the domain matrix mirrors the complete
 * permission set for full system access.
 */
class SuperAdminDomainPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $superRoleId = DB::table('roles')
            ->where('name', 'Super Admin')
            ->where('guard_name', 'web')
            ->value('id');

        $superDomainId = DB::table('domains')
            ->where('code', 'super_admin')
            ->value('id');

        if (! $superRoleId || ! $superDomainId) {
            $this->command->warn('⚠️ Super Admin role or domain not found. Skipping.');

            return;
        }

        $allPermIds = DB::table('permissions')
            ->where('guard_name', 'web')
            ->pluck('id')
            ->toArray();

        if (! empty($allPermIds)) {
            // Clear existing domain perms for super_admin to avoid duplicates
            DB::table('domain_role_permissions')
                ->where('domain_id', $superDomainId)
                ->delete();

            $rows = array_map(
                fn ($pid) => [
                    'id' => Str::uuid()->toString(),
                    'domain_id' => $superDomainId,
                    'permission_id' => $pid,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                $allPermIds
            );

            DB::table('domain_role_permissions')->insert($rows);

            $this->command->info('✅ Super Admin domain permissions seeded: '.count($allPermIds).' permissions.');
        } else {
            $this->command->warn('⚠️ No permissions found for Super Admin domain.');
        }
    }
}
