<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class UksRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * UKS Role Mapping (v3):
     *   - UKS : Kepala UKS & Petugas Kesehatan — CRUD data kesehatan santri
     */
    public function run(): void
    {
        $uksRoles = [
            [
                'name' => 'Kepala UKS',
                'level' => 20,
                'description' => 'Kepala Unit Kesehatan Sekolah — admin penuh UKS Putra & Putri',
            ],
            [
                'name' => 'Admin UKS Putra',
                'level' => 21,
                'description' => 'Petugas UKS putra — CRUD data kesehatan santri putra',
            ],
            [
                'name' => 'Admin UKS Putri',
                'level' => 21,
                'description' => 'Petugas UKS putri — CRUD data kesehatan santri putri',
            ],
            [
                'name' => 'UKS',
                'level' => 22,
                'description' => 'Role umum UKS (fallback untuk Kepala UKS & Admin UKS)',
            ],
        ];

        foreach ($uksRoles as $roleData) {
            $role = Role::firstOrCreate(
                ['name' => $roleData['name']],
                [
                    'level' => $roleData['level'],
                    'description' => $roleData['description'],
                ]
            );
            // Ensure UUID is set for existing records
            if (!$role->exists || empty($role->id)) {
                $role->forceFill(['id' => \Illuminate\Support\Str::uuid()->toString()])->save();
            }
        }

        $this->command->info('✅ UKS role updated successfully!');
        $this->command->info('  - UKS (level 21) — CRUD full access');
    }
}
