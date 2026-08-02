<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Mudir',                    'level' => 1,  'description' => 'Pimpinan Pondok'],
            ['name' => 'Wadir 1',                  'level' => 3,  'description' => 'Wakil Diretor 1'],
            ['name' => 'Wadir 2',                  'level' => 4,  'description' => 'Wakil Diretor 2'],
            ['name' => 'Personalia',               'level' => 5,  'description' => 'Staff Personalia'],
            ['name' => 'Administrator',            'level' => 6,  'description' => 'Administrator'],
            ['name' => 'Kepala Sekolah',           'level' => 7,  'description' => 'Kepala Sekolah'],
            ['name' => 'Wakil Kepala Sekolah',    'level' => 8,  'description' => 'Wakil Kepala Sekolah'],
            ['name' => 'Admin Tata Usaha',        'level' => 9,  'description' => 'Admin Tata Usaha (dapat membuat mutasi)'],
            ['name' => 'Tata Usaha',              'level' => 10, 'description' => 'Tata Usaha (read-only)'],
            ['name' => 'Coordinator Guru',        'level' => 11, 'description' => 'Koordinator Guru'],
            ['name' => 'Guru',                    'level' => 12, 'description' => 'Guru / Tenaga Kependidikan (semua mapel)'],
            ['name' => 'Guru Tahfidz',            'level' => 13, 'description' => 'Guru Tahfidz'],
            ['name' => 'Coordinator Tahfidz',     'level' => 14, 'description' => 'Koordinator Tahfidz'],
            ['name' => 'Departemen Tahfidz',      'level' => 15, 'description' => 'Departemen Tahfidz'],
            ['name' => 'Admin Departemen Tahfidz', 'level' => 16, 'description' => 'Admin Departemen Tahfidz'],
            // ── ASRAMA / BOARDING — 7 granular roles ──────────────────
            ['name' => 'Kepala Asrama',           'level' => 17, 'description' => 'Puncak Asrama — approval center, laporan & seluruh asrama'],
            ['name' => 'Admin Asrama',            'level' => 18, 'description' => 'Admin sistem asrama — master data, user, konfigurasi'],
            ['name' => 'Admin Pendidikan',        'level' => 19, 'description' => 'Akademik asrama — izin, kebijakan, kalender kepulangan/kunjungan'],
            ['name' => 'Kepala UKS',              'level' => 20, 'description' => 'UKS — pengelolaan kesehatan siswa, seluruh GTK (putra & putri), layanan lengkap'],
            ['name' => 'Admin UKS',               'level' => 21, 'description' => 'Admin UKS — pengelolaan GTK & layanan siswa (semua gender)'],
            ['name' => 'Admin Kesehatan',         'level' => 22, 'description' => 'Fallback UKS — health checkup, obat, rujukan'],
            ['name' => 'Wali Asrama',             'level' => 23, 'description' => 'Pengasuh asrama — absensi, pelanggaran, visite, kegiatan harian'],
            ['name' => 'Asrama',                  'level' => 24, 'description' => 'Read-only monitoring asrama (read semua modul)'],
            ['name' => 'Keuangan',                'level' => 25, 'description' => 'Keuangan'],
            ['name' => 'Admin Sarpras',           'level' => 26, 'description' => 'Admin Sarana Prasarana'],
            ['name' => 'Sarpras',                 'level' => 27, 'description' => 'Sarana Prasarana'],
            ['name' => 'Wali Santri',             'level' => 28, 'description' => 'Orang Tua / Wali Santri'],
        ];

        foreach ($roles as $role) {
            $existing = Role::where('name', $role['name'])->where('guard_name', 'web')->first();
            if ($existing) {
                $existing->update([
                    'level' => $role['level'],
                    'description' => $role['description'] ?? null,
                ]);
            } else {
                Role::create([
                    'id' => (string) Str::uuid(),
                    'name' => $role['name'],
                    'guard_name' => 'web',
                    'level' => $role['level'],
                    'description' => $role['description'] ?? null,
                ]);
            }
        }

        $this->command->info('✅ Roles seeded: '.count($roles));
    }
}
