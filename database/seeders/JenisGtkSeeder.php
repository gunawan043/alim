<?php

namespace Database\Seeders;

use App\Models\AdditionalAssignment;
use App\Models\JenisGtk;
use App\Models\StructuralPosition; // Adjust to your actual model name for Tugas Tambahan
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JenisGtkSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data to avoid stale entries
        StructuralPosition::query()->delete();
        if (class_exists(AdditionalAssignment::class)) {
            AdditionalAssignment::query()->delete();
        }
        JenisGtk::query()->delete();

        $data = [
            [
                'nama' => 'Pendidik / Guru',
                'urutan' => 1,
                'deskripsi' => 'Tenaga pengajar/pendidik di satuan pendidikan',
                'role' => 'Satuan Pendidikan',
                'jabatan' => [
                    'Guru Umum',
                    'Guru Agama',
                    'Guru Hadits',
                    'Guru Bahasa Arab',
                    'Guru Tahfidz',
                ],
                'tugas_tambahan' => [
                    'Wali Kelas',
                    'Koordinator Guru Umum',
                    'Koordinator Guru Agama',
                    'Koordinator Guru Hadits',
                    'Koordinator Guru Bahasa Arab',
                    'Koordinator Guru Tahfidz',
                    'Koordinator Ekstrakurikuler',
                    'Koordinator Kurikulum',
                    'Koordinator Kesiswaan',
                    'Koordinator Laboratorium',
                    'Pembina Ekstrakurikuler',
                    'Tim Kurikulum',
                    'Tim Kesiswaan',
                ],
            ],
            [
                'nama' => 'Pimpinan & Struktural Pendidikan',
                'urutan' => 2,
                'deskripsi' => 'Pimpinan pondok dan manajemen satuan pendidikan',
                'role' => 'Pimpinan',
                'jabatan' => [
                    'Mudir',
                    'Wakil Mudir I',
                    'Wakil Mudir II',
                    'Kepala Satuan Pendidikan',
                    'Wakil Kepala Satuan Pendidikan',
                    'Kepala Departemen Tahfidz',
                    'Wakil Kepala Departemen Tahfidz',
                    'Kepala Departemen Bahasa',
                    'Wakil Kepala Departemen Bahasa',
                ],
                'tugas_tambahan' => [],
            ],
            [
                'nama' => 'Tenaga Administrasi & Keuangan',
                'urutan' => 3,
                'deskripsi' => 'Tenaga ketatausahaan, keuangan, dan personalia',
                'role' => 'Keuangan',
                'jabatan' => [
                    'Kepala Tata Usaha',
                    'Tata Usaha',
                    'Bendahara Sekolah',
                    'Kepala Keuangan',
                    'Staf Keuangan',
                    'Bendahara',
                    'Kepala Humas & Personalia',
                    'Kepala Humas',
                    'Kepala Personalia',
                    'Staf Humas',
                    'Staf Personalia',
                    'Tata Usaha Departemen Tahfidz',
                    'Tata Usaha Departemen Bahasa',
                ],
                'tugas_tambahan' => [
                    'Koordinator Alumni',
                ],
            ],
            [
                'nama' => 'Tenaga Pengasuhan & Keasramaan',
                'urutan' => 4,
                'deskripsi' => 'Pengelola dan pembina asrama santri',
                'role' => 'Asrama',
                'jabatan' => [
                    'Kepala Asrama',
                    'Wakil Kepala Asrama',
                    'Musyrif',
                    'Musyrifah',
                    'Tata Usaha Asrama',
                    'Perizinan',
                ],
                'tugas_tambahan' => [
                    'Wali Kamar',
                    'Wali Asrama',
                    'Staf Asrama',
                    'Staf Penitipan Barang',
                ],
            ],
            [
                'nama' => 'Tenaga Kesehatan',
                'urutan' => 5,
                'deskripsi' => 'Petugas layanan kesehatan dan UKS',
                'role' => 'UKS',
                'jabatan' => [
                    'Kepala UKS',
                    'Staf UKS Putra',
                    'Staf UKS Putri',
                ],
                'tugas_tambahan' => [
                    'Admin UKS Putra',
                    'Admin UKS Putri',
                ],
            ],
            [
                'nama' => 'Tenaga Perpustakaan',
                'urutan' => 6,
                'deskripsi' => 'Pengelola unit perpustakaan',
                'role' => 'Perpustakaan',
                'jabatan' => [
                    'Koordinator Perpustakaan',
                    'Staf Perpustakaan',
                ],
                'tugas_tambahan' => [],
            ],
            [
                'nama' => 'Tenaga IT & Laboratorium',
                'urutan' => 7,
                'deskripsi' => 'Teknisi dan pengelola teknologi informasi',
                'role' => 'Teknologi Informasi',
                'jabatan' => [
                    'Kepala Unit Teknologi Informasi',
                    'Staf Teknologi Informasi',
                    'Teknisi Jaringan',
                ],
                'tugas_tambahan' => [],
            ],
            [
                'nama' => 'Tenaga Keamanan & Ketertiban',
                'urutan' => 8,
                'deskripsi' => 'Petugas keamanan dan ketertiban lingkungan',
                'role' => 'Satuan Keamanan',
                'jabatan' => [
                    'Kepala Satuan Keamanan',
                    'Koordinator Satuan Keamanan',
                    'Anggota Satuan Keamanan',
                ],
                'tugas_tambahan' => [],
            ],
            [
                'nama' => 'Tenaga Layanan Umum, URT & Logistik',
                'urutan' => 9,
                'deskripsi' => 'Petugas rumah tangga, sarpras, gizi, dan logistik',
                'role' => 'Unit Rumah Tangga',
                'jabatan' => [
                    'Kepala Unit Rumah Tangga',
                    'Koordinator Sarpras',
                    'Tata Usaha URT',
                    'Staf URT',
                    'Koordinator Kebersihan',
                    'Staf Kebersihan',
                    'Kepala Unit Gizi & Logistik',
                    'Koordinator Logistik',
                    'Staf Gizi',
                    'Staf Logistik',
                ],
                'tugas_tambahan' => [
                    'Koordinator Sarpras Satuan Pendidikan',
                ],
            ],
        ];

        // Helper lokal pembuat code unik max 50 karakter
        $generateCode = function (string $text): string {
            $slug = Str::slug($text, '_');
            if (strlen($slug) <= 50) {
                return $slug;
            }
            $hash = substr(md5($slug), 0, 5);

            return substr($slug, 0, 44).'_'.$hash;
        };

        foreach ($data as $jData) {
            $jenisGtk = JenisGtk::create([
                'id' => Str::uuid(),
                'nama' => $jData['nama'],
                'deskripsi' => $jData['deskripsi'],
                'urutan' => $jData['urutan'],
                'is_active' => true,
            ]);

            $roleId = DB::table('roles')->where('name', $jData['role'])->value('id');

            // 1. Simpan ke Tabel StructuralPosition (Khusus Jabatan)
            foreach ($jData['jabatan'] as $order => $jabatanItem) {
                $code = $generateCode($jData['nama'].' '.$jabatanItem);

                StructuralPosition::updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $jabatanItem,
                        'level' => 'pondok',
                        'hierarchy_level' => 5,
                        'jenis_gtk_id' => $jenisGtk->id,
                        'kategori' => 'Jabatan',
                        'description' => null,
                        'role_id' => $roleId,
                        'urutan' => $order + 1,
                        'is_active' => true,
                    ]
                );
            }

            // 2. Simpan ke Tabel Tugas Tambahan (Jika ada model/tabel terpisah)
            foreach ($jData['tugas_tambahan'] as $order => $tugasItem) {
                $code = $generateCode($jData['nama'].' tt '.$tugasItem);

                // Sesuaikan 'AdditionalAssignment' dengan nama Model tabel tugas tambahan Anda
                if (class_exists(AdditionalAssignment::class)) {
                    AdditionalAssignment::updateOrCreate(
                        ['code' => $code],
                        [
                            'name' => $tugasItem,
                            'jenis_gtk_id' => $jenisGtk->id,
                            'urutan' => $order + 1,
                            'is_active' => true,
                        ]
                    );
                }
            }
        }

        $this->command->info('✅ JenisGtkSeeder selesai dipisah.');
    }
}
