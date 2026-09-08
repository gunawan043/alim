<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Daftar role sesuai struktur ponpes
        $roles = [
            ['name' => 'Super Admin', 'level' => 1, 'description' => 'Full system access — Power User'],
            ['name' => 'Pimpinan', 'level' => 2, 'description' => 'Pimpinan Pondok (Mudir & Wakil)'],
            ['name' => 'Satuan Pendidikan', 'level' => 3, 'description' => 'Unit Pendidikan (Sekolah)'],
            ['name' => 'Asrama', 'level' => 4, 'description' => 'Unit Asrama Santri'],
            ['name' => 'UKS', 'level' => 5, 'description' => 'Unit Kesehatan Siswa'],
            ['name' => 'Departemen Tahfidz', 'level' => 6, 'description' => 'Departemen Tahfidz'],
            ['name' => 'Departemen Bahasa', 'level' => 7, 'description' => 'Departemen Bahasa Arab'],
            ['name' => 'Perpustakaan', 'level' => 8, 'description' => 'Unit Perpustakaan'],
            ['name' => 'Satuan Keamanan', 'level' => 9, 'description' => 'Unit Keamanan & Ketertiban'],
            ['name' => 'Humas Personalia', 'level' => 10, 'description' => 'Unit Humas & Personalia'],
            ['name' => 'Unit Rumah Tangga', 'level' => 11, 'description' => 'Unit Sarana Prasarana & Tata Usaha'],
            ['name' => 'Keuangan', 'level' => 12, 'description' => 'Unit Keuangan'],
            ['name' => 'Teknologi Informasi', 'level' => 13, 'description' => 'Unit Teknologi Informasi'],
            ['name' => 'Unit Pelayanan Gizi', 'level' => 14, 'description' => 'Unit Gizi & Logistik'],
        ];

        foreach ($roles as $role) {
            $existing = Role::where('name', $role['name'])->first();
            if ($existing) {
                $existing->update([
                    'level' => $role['level'],
                    'description' => $role['description'],
                ]);
            } else {
                Role::create([
                    'id' => (string) Str::uuid(),
                    'name' => $role['name'],
                    'guard_name' => 'web',
                    'level' => $role['level'],
                    'description' => $role['description'],
                ]);
            }
        }

        // Hapus role yang tidak diperlukan (Admin Pendidikan, ATS)
        Role::where('guard_name', 'web')
            ->whereIn('name', ['Admin Pendidikan', 'ATS'])
            ->delete();

        $this->command->info('✅ RoleSeeder selesai. Total '.Role::where('guard_name', 'web')->count().' role web.');
    }
}
