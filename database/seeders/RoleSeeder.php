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

            // Level 7-10: Satuan Pendidikan (KSP, Wakil KSP, TU)
            ['name' => 'Satuan Pendidikan', 'level' => 7, 'description' => 'Kepala Satuan Pendidikan'],
            ['name' => 'Kepala Sekolah', 'level' => 7, 'description' => 'Kepala Satuan Pendidikan (alias lama)'],
            ['name' => 'Wakil Kepala Sekolah', 'level' => 8, 'description' => 'Wakil Kepala Satuan Pendidikan'],
            ['name' => 'Admin Tata Usaha', 'level' => 9, 'description' => 'Kepala Unit Tata Usaha'],
            ['name' => 'Tata Usaha', 'level' => 10, 'description' => 'Staf Tata Usaha'],
            ['name' => 'Kepala Tata Usaha', 'level' => 9, 'description' => 'Kepala Tata Usaha (alias lama)'],
            ['name' => 'Staf Tata Usaha', 'level' => 10, 'description' => 'Staf Tata Usaha (alias lama)'],

            // Level 14: Guru (pendidik) — semua jabatan GTK pendidikan
            ['name' => 'Guru', 'level' => 14, 'description' => 'Guru Umum / Hadits / Agama / Bahasa Arab'],
            ['name' => 'Guru Umum', 'level' => 14, 'description' => 'Guru Mata Pelajaran Umum'],
            ['name' => 'Guru Agama', 'level' => 14, 'description' => 'Guru Mata Pelajaran Agama'],
            ['name' => 'Guru Hadits', 'level' => 14, 'description' => 'Guru Mata Pelajaran Hadits'],
            ['name' => 'Guru Bahasa Arab', 'level' => 14, 'description' => 'Guru Mata Pelajaran Bahasa Arab'],
            ['name' => 'Wali Kelas', 'level' => 14, 'description' => 'Wali Kelas'],
            ['name' => 'Koordinator Kurikulum', 'level' => 14, 'description' => 'Koordinator Kurikulum'],
            ['name' => 'Koordinator Kesiswaan', 'level' => 14, 'description' => 'Koordinator Kesiswaan'],
            ['name' => 'Koordinator Sarpras Sekolah', 'level' => 14, 'description' => 'Koordinator Sarpras Sekolah'],
            ['name' => 'Koordinator Ekstrakurikuler', 'level' => 14, 'description' => 'Koordinator Ekstrakurikuler'],
            ['name' => 'Koordinator Guru Bahasa Arab', 'level' => 14, 'description' => 'Koordinator Rumpun Guru Bahasa Arab'],
            ['name' => 'Koordinator Guru Umum', 'level' => 14, 'description' => 'Koordinator Rumpun Guru Umum'],
            ['name' => 'Koordinator Guru Agama', 'level' => 14, 'description' => 'Koordinator Rumpun Guru Agama'],
            ['name' => 'Koordinator Guru Hadits', 'level' => 14, 'description' => 'Koordinator Rumpun Guru Hadits'],

            // Level 14: Guru Tahfidz
            ['name' => 'Guru Tahfidz', 'level' => 14, 'description' => 'Guru Tahfidz'],
            ['name' => 'Coordinator Tahfidz', 'level' => 14, 'description' => 'Koordinator Tahfidz'],
            ['name' => 'Koordinator Guru Tahfidz', 'level' => 14, 'description' => 'Koordinator Rumpun Guru Tahfidz'],

            // Level 14: Koordinator Guru (umum)
            ['name' => 'Coordinator Guru', 'level' => 14, 'description' => 'Koordinator Rumpun Guru'],

            // Level 16: Departemen
            ['name' => 'Kepala Departemen Bahasa', 'level' => 16, 'description' => 'Kepala Departemen Bahasa'],
            ['name' => 'Admin Departemen Bahasa', 'level' => 16, 'description' => 'Admin Departemen Bahasa'],
            ['name' => 'Departemen Bahasa', 'level' => 16, 'description' => 'Departemen Bahasa (old role)'],
            ['name' => 'Kepala Departemen Tahfidz', 'level' => 14, 'description' => 'Kepala Departemen Tahfidz'],
            ['name' => 'Admin Departemen Tahfidz', 'level' => 14, 'description' => 'Admin Departemen Tahfidz'],
            ['name' => 'Departemen Tahfidz', 'level' => 14, 'description' => 'Departemen Tahfidz (old role)'],
            ['name' => 'Coordinator Guru', 'level' => 14, 'description' => 'Koordinator Guru (old role)'],

            // Level 17-23: Asrama
            ['name' => 'Asrama', 'level' => 17, 'description' => 'Asrama (Kepala, Admin, Wali, Koordinator — divisi berdasarkan jabatan)'],
            ['name' => 'Kepala Asrama', 'level' => 17, 'description' => 'Kepala Asrama'],
            ['name' => 'Wakil Kepala Asrama', 'level' => 17, 'description' => 'Wakil Kepala Asrama'],
            ['name' => 'Admin Asrama', 'level' => 18, 'description' => 'Admin Asrama'],
            ['name' => 'Admin Pendidikan', 'level' => 19, 'description' => 'Akademik asrama — izin, kebijakan, kalender kepulangan/kunjungan'],
            ['name' => 'Wali Asrama', 'level' => 23, 'description' => 'Wali Kamar / Musyrif / Musyrifah'],
            ['name' => 'Musyrif', 'level' => 23, 'description' => 'Wali Kamar Laki-laki'],
            ['name' => 'Musyrifah', 'level' => 23, 'description' => 'Wali Kamar Perempuan'],
            ['name' => 'Wali Kamar', 'level' => 23, 'description' => 'Wali Kamar'],
            ['name' => 'Pembina Asrama', 'level' => 23, 'description' => 'Pembina Asrama'],
            ['name' => 'Staf Asrama', 'level' => 23, 'description' => 'Staf Asrama'],
            ['name' => 'Tata Usaha Asrama', 'level' => 23, 'description' => 'Tata Usaha Asrama'],

            // Level 20-22: UKS & Kesehatan
            ['name' => 'Kepala UKS', 'level' => 20, 'description' => 'Kepala UKS'],
            ['name' => 'Admin UKS Putri', 'level' => 20, 'description' => 'Admin UKS Putri'],
            ['name' => 'Admin UKS Putra', 'level' => 20, 'description' => 'Admin UKS Putra'],
            ['name' => 'UKS', 'level' => 21, 'description' => 'Petugas UKS — CRUD data kesehatan santri'],
            ['name' => 'Admin UKS', 'level' => 21, 'description' => 'Admin UKS'],
            ['name' => 'Kepala UKS', 'level' => 20, 'description' => 'Kepala UKS'],
            ['name' => 'Staf UKS', 'level' => 21, 'description' => 'Staf UKS'],
            ['name' => 'Admin Kesehatan', 'level' => 22, 'description' => 'Staf Kesehatan'],

            // Level 25: Keuangan
            ['name' => 'Keuangan', 'level' => 25, 'description' => 'Kepala Unit Keuangan'],
            ['name' => 'Kepala Keuangan', 'level' => 25, 'description' => 'Kepala Keuangan (alias lama)'],
            ['name' => 'Kepala Unit Keuangan', 'level' => 25, 'description' => 'Kepala Unit Keuangan'],
            ['name' => 'Staf Keuangan', 'level' => 25, 'description' => 'Staf Keuangan'],

            // Level 26: Sarpras & Satpam
            ['name' => 'Admin Sarpras', 'level' => 26, 'description' => 'Kepala Unit Sarana dan Prasarana'],
            ['name' => 'Kepala Unit Sarana dan Prasarana', 'level' => 26, 'description' => 'Kepala Unit Sarana dan Prasarana'],
            ['name' => 'Koordinator Sarana dan Prasarana', 'level' => 26, 'description' => 'Koordinator Sarana dan Prasarana'],
            ['name' => 'Sarpras', 'level' => 26, 'description' => 'Staf Sarana dan Prasarana'],
            ['name' => 'Staf Sarana dan Prasarana', 'level' => 26, 'description' => 'Staf Sarana dan Prasarana'],
            ['name' => 'Satpam', 'level' => 27, 'description' => 'Kepala Satuan Keamanan & Satuan Keamanan'],
            ['name' => 'Kepala Satuan Keamanan', 'level' => 27, 'description' => 'Kepala Satuan Keamanan'],
            ['name' => 'Anggota Keamanan', 'level' => 27, 'description' => 'Anggota Satuan Keamanan'],
            ['name' => 'Anggota Satuan Keamanan', 'level' => 27, 'description' => 'Anggota Satuan Keamanan'],
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
