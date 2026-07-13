<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Super Admin',             'level' => 1,  'description' => 'Full system access'],
            ['name' => 'Mudir',                    'level' => 2,  'description' => 'Pimpinan Pondok'],
            ['name' => 'Wadir 1',                  'level' => 3,  'description' => 'Wakil Diretor 1'],
            ['name' => 'Wadir 2',                  'level' => 4,  'description' => 'Wakil Diretor 2'],
            ['name' => 'Personalia',               'level' => 5,  'description' => 'Staff Personalia'],
            ['name' => 'Administrator',            'level' => 6,  'description' => 'Administrator'],
            ['name' => 'Kepala Sekolah',           'level' => 7,  'description' => 'Kepala Sekolah'],
            ['name' => 'Wakil Kepala Sekolah',    'level' => 8,  'description' => 'Wakil Kepala Sekolah'],
            ['name' => 'Admin Tata Usaha',        'level' => 9,  'description' => 'Admin Tata Usaha (dapat membuat mutasi)'],
            ['name' => 'Tata Usaha',              'level' => 10, 'description' => 'Tata Usaha (read-only)'],
            ['name' => 'Coordinator Guru',        'level' => 11, 'description' => 'Koordinator Guru'],
            ['name' => 'Guru Umum',               'level' => 12, 'description' => 'Guru Umum'],
            ['name' => 'Guru Agama',              'level' => 13, 'description' => 'Guru Agama'],
            ['name' => 'Guru Hadits',             'level' => 14, 'description' => 'Guru Hadits'],
            ['name' => 'Guru Tahfidz',            'level' => 15, 'description' => 'Guru Tahfidz'],
            ['name' => 'Coordinator Tahfidz',     'level' => 16, 'description' => 'Koordinator Tahfidz'],
            ['name' => 'Departemen Tahfidz',      'level' => 17, 'description' => 'Departemen Tahfidz'],
            ['name' => 'Admin Departemen Tahfidz', 'level' => 18, 'description' => 'Admin Departemen Tahfidz'],
            ['name' => 'Asrama',                  'level' => 19, 'description' => 'Asrama'],
            ['name' => 'Admin Asrama',            'level' => 20, 'description' => 'Admin Asrama'],
            ['name' => 'Keuangan',               'level' => 21, 'description' => 'Keuangan'],
            ['name' => 'Admin Sarpras',           'level' => 22, 'description' => 'Admin Sarana Prasarana'],
            ['name' => 'Sarpras',                'level' => 23, 'description' => 'Sarana Prasarana'],
            ['name' => 'Wali Santri',            'level' => 24, 'description' => 'Wali Santri'],
            ['name' => 'GTK',                     'level' => 25, 'description' => 'Guru/Tenaga Kependidikan'],
        ];

        foreach ($roles as $role) {
            $existing = Role::where('name', $role['name'])->where('guard_name', 'web')->first();
            if ($existing) {
                $existing->update([
                    'level'       => $role['level'],
                    'description' => $role['description'] ?? null,
                ]);
            } else {
                Role::create([
                    'id'          => (string) Str::uuid(),
                    'name'        => $role['name'],
                    'guard_name' => 'web',
                    'level'       => $role['level'],
                    'description' => $role['description'] ?? null,
                ]);
            }
        }

        $this->command->info('✅ Roles seeded: ' . count($roles));
    }
}
