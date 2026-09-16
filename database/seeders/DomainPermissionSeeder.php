<?php

namespace Database\Seeders;

use App\Models\Domain;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * DomainPermissionSeeder — permission matrix initializer.
 *
 * Mirrors each of the 14 official Spatie roles' permissions into its
 * corresponding Domain row. This is an additive snapshot at seeding time;
 * future changes to a role's permissions or to this matrix are tracked
 * independently so the matrix becomes the canonical source for domain-level
 * permission grants.
 *
 * After seeding, a user's effective permissions =
 *   Direct Spatie permissions (existing syncRoles / givePermissionTo)
 *   ∪ Union of domain_role_permissions across all active StructuralAssignment domains.
 */
class DomainPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing domain → permission matrix rows.
        DB::table('domain_role_permissions')->delete();

        $roleToDomainCode = [
            'Super Admin' => 'super_admin',
            'Pimpinan' => 'pimpinan',
            'Satuan Pendidikan' => 'satuan_pendidikan',
            'Asrama' => 'asrama',
            'UKS' => 'uks',
            'Departemen Tahfidz' => 'departemen_tahfidz',
            'Departemen Bahasa' => 'departemen_bahasa',
            'Perpustakaan' => 'perpustakaan',
            'Satuan Keamanan' => 'satuan_keamanan',
            'Humas Personalia' => 'humas_personalia',
            'Unit Rumah Tangga' => 'unit_rumah_tangga',
            'Keuangan' => 'keuangan',
            'Teknologi Informasi' => 'teknologi_informasi',
            'Unit Pelayanan Gizi' => 'unit_pelayanan_gizi',
        ];

        foreach ($roleToDomainCode as $roleName => $domainCode) {
            $domainId = DB::table('domains')->where('code', $domainCode)->value('id');
            if (! $domainId) {
                continue;
            }

            $roleId = DB::table('roles')
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->value('id');

            if (! $roleId) {
                continue;
            }

            $permIds = DB::table('role_has_permissions')
                ->where('role_id', $roleId)
                ->pluck('permission_id')
                ->toArray();

            if (! empty($permIds)) {
                $rows = array_map(
                    fn ($pid) => [
                        'id' => (string) Str::uuid(),
                        'domain_id' => $domainId,
                        'permission_id' => $pid,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    $permIds
                );
                DB::table('domain_role_permissions')->insert($rows);
            }
        }

        $this->command->info('✅ DomainPermissionSeeder selesai.');
    }
}
