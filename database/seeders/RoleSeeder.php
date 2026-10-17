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

        // Hapus role duplikat lama (nama singkat yang sudah digabung ke role baru)
        $legacyAliases = [
            'Pendidikan' => 'Satuan Pendidikan',
            'Tahfidz' => 'Departemen Tahfidz',
            'Bahasa' => 'Departemen Bahasa',
            'Rumah Tangga' => 'Unit Rumah Tangga',
            'Gizi Logistik' => 'Unit Pelayanan Gizi',
            'Keamanan' => 'Satuan Keamanan',
        ];

        foreach ($legacyAliases as $oldName => $newName) {
            $oldRole = Role::where('guard_name', 'web')->where('name', $oldName)->first();
            if ($oldRole) {
                // Pindahkan relasi user jika ada
                $newRole = Role::where('guard_name', 'web')->where('name', $newName)->first();
                if ($newRole) {
                    \DB::table('model_has_roles')
                        ->where('role_id', $oldRole->id)
                        ->update(['role_id' => $newRole->id]);
                }
                $oldRole->delete();
                $this->command->info("  ✅ Menghapus role lama '{$oldName}' (diganti '{$newName}')");
            }
        }

        // Hapus role Admin Pendidikan, Admin Sarpras, Admin Asrama, Admin Tata Usaha, Wali Kelas, Kepala Sekolah, Kepala Asrama, Kepala UKS
        $legacyRoles = ['Admin Pendidikan', 'Admin Sarpras', 'Admin Asrama', 'Admin Tata Usaha',
            'Wali Kelas', 'Kepala Sekolah', 'Kepala Asrama', 'Kepala UKS', 'ATS'];
        Role::where('guard_name', 'web')->whereIn('name', $legacyRoles)->delete();

        $this->command->info('✅ RoleSeeder selesai. Total '.Role::where('guard_name', 'web')->count().' role web.');
    }
}
