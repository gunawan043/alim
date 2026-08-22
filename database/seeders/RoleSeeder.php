<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            // Level 1: Pimpinan Pondok
            ['name' => 'Super Admin', 'level' => 1, 'description' => 'Full system access — Power User'],
            ['name' => 'Mudir', 'level' => 1, 'description' => 'Pimpinan Pondok'],

            // Level 3-4: Wakil Pimpinan
            ['name' => 'Wakil Mudir I', 'level' => 3, 'description' => 'Wakil Direktor 1'],
            ['name' => 'Wakil Mudir II', 'level' => 4, 'description' => 'Wakil Direktor 2'],

            // Level 5-6: Administrasi
            ['name' => 'Personalia', 'level' => 5, 'description' => 'Staff Personalia'],
            ['name' => 'Administrator', 'level' => 6, 'description' => 'Administrator'],

            // Level 7: Satuan Pendidikan (KSP, Wakil KSP, TU, Guru)
            ['name' => 'Satuan Pendidikan', 'level' => 7, 'description' => 'Kepala Satuan Pendidikan, Wakil KSP, TU & Guru'],

            // Level 14: Departemen Tahfidz (termasuk Koordinator)
            ['name' => 'Departemen Tahfidz', 'level' => 14, 'description' => 'Kepala, Admin & Koordinator Departemen Tahfidz'],
            ['name' => 'Departemen Bahasa', 'level' => 16, 'description' => 'Kepala & Admin Departemen Bahasa'],

            // Level 17: Asrama — single role for all dormitory divisions
            ['name' => 'Asrama', 'level' => 17, 'description' => 'Asrama (Kepala, Admin, Wali, Koordinator — divisi berdasarkan jabatan)'],
            ['name' => 'Admin Pendidikan', 'level' => 19, 'description' => 'Akademik asrama — izin, kebijakan, kalender kepulangan/kunjungan'],

            // Level 21: UKS & Kesehatan
            ['name' => 'UKS', 'level' => 21, 'description' => 'Kepala UKS & Petugas Kesehatan — CRUD data kesehatan santri'],

            // Level 25: Keuangan
            ['name' => 'Keuangan', 'level' => 25, 'description' => 'Keuangan'],

            // Level 26: Sarpras & Satpam
            ['name' => 'Sarpras', 'level' => 26, 'description' => 'Sarpras (Admin + Staf — divisi berdasarkan jabatan)'],
            ['name' => 'Satpam', 'level' => 27, 'description' => 'Kepala Satuan Keamanan & Satuan Keamanan'],

            // Level 28: Wali Santri
            ['name' => 'Wali Santri', 'level' => 28, 'description' => 'Orang Tua / Wali Santri'],
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
    }
}
