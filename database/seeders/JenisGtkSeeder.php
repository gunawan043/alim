<?php

namespace Database\Seeders;

use App\Models\JenisGtk;
use App\Models\Position;
use Illuminate\Database\Seeder;

class JenisGtkSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data to avoid stale entries
        Position::query()->delete();
        JenisGtk::query()->delete();

        $data = [
            [
                'nama' => 'Pimpinan Pondok',
                'urutan' => 1,
                'deskripsi' => 'Pimpinan dan pengelola pondok',
                'jabatan' => [
                    ['nama' => 'Mudir', 'roles' => ['Mudir']],
                    ['nama' => 'Wakil Mudir I', 'roles' => ['Wakil Mudir I']],
                    ['nama' => 'Wakil Mudir II', 'roles' => ['Wakil Mudir II']],
                ],
            ],
            [
                'nama' => 'Satuan Pendidikan',
                'urutan' => 2,
                'deskripsi' => 'Kepengurusan satuan pendidikan',
                'jabatan' => [
                    ['nama' => 'Kepala Satuan Pendidikan', 'roles' => ['Kepala Satuan Pendidikan']],
                    ['nama' => 'Wakil Kepala Satuan Pendidikan', 'roles' => ['Wakil Kepala Satuan Pendidikan']],
                    ['nama' => 'Kepala Tata Usaha', 'roles' => ['Kepala Tata Usaha']],
                    ['nama' => 'Staf Tata Usaha', 'roles' => ['Staf Tata Usaha']],
                ],
            ],
            [
                'nama' => 'Departemen Bahasa',
                'urutan' => 3,
                'deskripsi' => 'Kepengurusan departemen bahasa',
                'jabatan' => [
                    ['nama' => 'Kepala Departemen Bahasa', 'roles' => ['Kepala Departemen Bahasa']],
                    ['nama' => 'Admin Departemen Bahasa', 'roles' => ['Admin Departemen Bahasa']],
                    ['nama' => 'Guru Bahasa Arab', 'roles' => ['Guru Bahasa Arab']],
                ],
            ],
            [
                'nama' => 'Departemen Tahfidz',
                'urutan' => 4,
                'deskripsi' => 'Kepengurusan departemen tahfidz',
                'jabatan' => [
                    ['nama' => 'Kepala Departemen Tahfidz', 'roles' => ['Kepala Departemen Tahfidz']],
                    ['nama' => 'Admin Departemen Tahfidz', 'roles' => ['Admin Departemen Tahfidz']],
                    ['nama' => 'Guru Tahfidz', 'roles' => ['Guru Tahfidz']],
                ],
            ],
            [
                'nama' => 'Tenaga Pendidik',
                'urutan' => 5,
                'deskripsi' => 'Guru dan tenaga kependidikan akademik',
                'jabatan' => [
                    ['nama' => 'Guru Hadits', 'roles' => ['Guru Hadits']],
                    ['nama' => 'Guru Umum', 'roles' => ['Guru Umum']],
                    ['nama' => 'Guru Agama', 'roles' => ['Guru Agama']],
                    ['nama' => 'Wali Kelas', 'roles' => ['Wali Kelas']],
                    ['nama' => 'Koordinator Kurikulum', 'roles' => ['Koordinator Kurikulum']],
                    ['nama' => 'Koordinator Kesiswaan', 'roles' => ['Koordinator Kesiswaan']],
                    ['nama' => 'Koordinator Sarpras Sekolah', 'roles' => ['Koordinator Sarpras Sekolah']],
                    ['nama' => 'Koordinator Ekstrakurikuler', 'roles' => ['Koordinator Ekstrakurikuler']],
                    ['nama' => 'Koordinator Guru Bahasa Arab', 'roles' => ['Koordinator Guru Bahasa Arab']],
                    ['nama' => 'Koordinator Guru Umum', 'roles' => ['Koordinator Guru Umum']],
                    ['nama' => 'Koordinator Guru Agama', 'roles' => ['Koordinator Guru Agama']],
                    ['nama' => 'Koordinator Guru Hadits', 'roles' => ['Koordinator Guru Hadits']],
                    ['nama' => 'Koordinator Guru Tahfidz', 'roles' => ['Koordinator Guru Tahfidz']],
                ],
            ],
            [
                'nama' => 'Asrama',
                'urutan' => 6,
                'deskripsi' => 'Kepengurusan asrama santri',
                'jabatan' => [
                    ['nama' => 'Kepala Asrama', 'roles' => ['Asrama']],
                    ['nama' => 'Wakil Kepala Asrama', 'roles' => ['Asrama']],
                    ['nama' => 'Tata Usaha Asrama', 'roles' => ['Asrama']],
                    ['nama' => 'Staf Asrama', 'roles' => ['Asrama']],
                    ['nama' => 'Wali Kamar', 'roles' => ['Asrama']],
                    ['nama' => 'Musyrif', 'roles' => ['Asrama']],
                    ['nama' => 'Musyrifah', 'roles' => ['Asrama']],
                    ['nama' => 'Pembina Asrama', 'roles' => ['Asrama']],
                ],
            ],
            [
                'nama' => 'UKS',
                'urutan' => 7,
                'deskripsi' => 'Unit Kesehatan Siswa',
                'jabatan' => [
                    ['nama' => 'Kepala UKS', 'roles' => ['Kepala UKS']],
                    ['nama' => 'Staf UKS', 'roles' => ['Staf UKS']],
                ],
            ],
            [
                'nama' => 'Sarana dan Prasarana',
                'urutan' => 8,
                'deskripsi' => 'Kepengurusan sarana dan prasarana',
                'jabatan' => [
                    ['nama' => 'Kepala Unit Sarana dan Prasarana', 'roles' => ['Sarpras']],
                    ['nama' => 'Koordinator Sarana dan Prasarana', 'roles' => ['Sarpras']],
                    ['nama' => 'Staf Sarana dan Prasarana', 'roles' => ['Sarpras']],
                ],
            ],
            [
                'nama' => 'Keuangan',
                'urutan' => 9,
                'deskripsi' => 'Kepengurusan keuangan pondok',
                'jabatan' => [
                    ['nama' => 'Kepala Unit Keuangan', 'roles' => ['Kepala Keuangan']],
                    ['nama' => 'Staf Keuangan', 'roles' => ['Staf Keuangan']],
                ],
            ],
            [
                'nama' => 'Keamanan',
                'urutan' => 10,
                'deskripsi' => 'Tenaga keamanan dan ketertiban',
                'jabatan' => [
                    ['nama' => 'Kepala Satuan Keamanan', 'roles' => ['Kepala Keamanan']],
                    ['nama' => 'Anggota Satuan Keamanan', 'roles' => ['Anggota Keamanan']],
                ],
            ],
        ];

        foreach ($data as $jData) {
            $jenisGtk = JenisGtk::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'nama' => $jData['nama'],
                'deskripsi' => $jData['deskripsi'],
                'urutan' => $jData['urutan'],
                'is_active' => true,
            ]);

            foreach ($jData['jabatan'] as $order => $jabatanItem) {
                Position::create([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'jenis_gtk_id' => $jenisGtk->id,
                    'nama' => $jabatanItem['nama'],
                    'kategori' => null,
                    'deskripsi' => null,
                    'roles' => $jabatanItem['roles'],
                    'urutan' => $order + 1,
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('✅ JenisGtkSeeder selesai. '.count($data).' jenis GTK, '.Position::count().' jabatan.');
    }
}
