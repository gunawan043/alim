<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use App\Models\JenisGtk;
use Illuminate\Database\Seeder;

class JenisGtkSeeder extends Seeder
{
    public function run(): void
    {
        $jenisGtkData = [
            [
                'nama' => 'Pimpinan Pondok',
                'urutan' => 1,
                'deskripsi' => 'Pimpinan dan pengelola pondok',
                'jabatan' => [
                    ['nama' => 'Mudir', 'roles' => ['Mudir']],
                    ['nama' => 'Wakil Mudir I', 'roles' => ['Wadir 1']],
                    ['nama' => 'Wakil Mudir II', 'roles' => ['Wadir 2']],
                ],
            ],
            [
                'nama' => 'Tenaga Administrasi Pondok',
                'urutan' => 2,
                'deskripsi' => 'Tenaga administrasi dan perkantoran',
                'jabatan' => [
                    ['nama' => 'Kepala Hubungan Masyarakat dan Personalia', 'roles' => ['Personalia']],
                    ['nama' => 'Staf Hubungan Masyarakat', 'roles' => ['Personalia']],
                    ['nama' => 'Staf Personalia', 'roles' => ['Personalia']],
                    ['nama' => 'Kepala Kesekretariatan', 'roles' => ['Administrator']],
                    ['nama' => 'Staf Kesekretariatan', 'roles' => ['Tata Usaha']],
                    ['nama' => 'Kepala Keuangan', 'roles' => ['Keuangan']],
                    ['nama' => 'Staf Keuangan / Bendahara', 'roles' => ['Keuangan']],
                ],
            ],
            [
                'nama' => 'Tenaga Pendidik Pondok',
                'urutan' => 3,
                'deskripsi' => 'Guru and ustadz/ustadzah',
                'jabatan' => [
                    ['nama' => 'Kepala Lembaga Pendidikan', 'roles' => ['Guru']],
                    ['nama' => 'Koordinator KSP', 'roles' => ['Coordinator Guru']],
                    ['nama' => 'Guru', 'roles' => ['Guru']],
                    ['nama' => 'Kepala Departemen Tahfidz', 'roles' => ['Departemen Tahfidz']],
                    ['nama' => 'Ustadz/Ustadzah Tahfidz', 'roles' => ['Guru Tahfidz']],
                    ['nama' => 'Kepala Departemen Bahasa', 'roles' => ['Guru']],
                    ['nama' => 'Ustadz/Ustadzah Bahasa', 'roles' => ['Guru']],
                ],
            ],
            [
                'nama' => 'Tenaga Kependidikan Pondok',
                'urutan' => 4,
                'deskripsi' => 'Tenaga kependidikan non-guru',
                'jabatan' => [
                    ['nama' => 'Staf Kependidikan', 'roles' => ['Guru']],
                    ['nama' => 'Operator Akademik', 'roles' => ['Guru']],
                    ['nama' => 'Administrasi Pendidikan', 'roles' => ['Guru']],
                ],
            ],
            [
                'nama' => 'Tenaga Keamanan Pondok',
                'urutan' => 5,
                'deskripsi' => 'Tenaga keamanan dan ketertiban',
                'jabatan' => [
                    ['nama' => 'Kepala Keamanan Pondok', 'roles' => ['Guru']],
                    ['nama' => 'Koordinator Divisi Keamanan', 'roles' => ['Guru']],
                    ['nama' => 'Anggota Keamanan Pondok', 'roles' => ['Guru']],
                ],
            ],
            [
                'nama' => 'Tenaga Sarana dan Prasarana Pondok',
                'urutan' => 6,
                'deskripsi' => 'Sarana, prasarana, gizi dan kebersihan',
                'jabatan' => [
                    ['nama' => 'Kepala Sarana dan Prasarana', 'roles' => ['Admin Sarpras']],
                    ['nama' => 'Staf Sarana dan Prasarana', 'roles' => ['Sarpras']],
                    ['nama' => 'Kepala Unit Gizi dan Logistik', 'roles' => ['Guru']],
                    ['nama' => 'Staf Gizi dan Logistik', 'roles' => ['Guru']],
                    ['nama' => 'Petugas Kebersihan Pondok', 'roles' => ['Sarpras']],
                ],
            ],
            [
                'nama' => 'Tenaga Kesehatan Pondok',
                'urutan' => 7,
                'deskripsi' => 'Petugas kesehatan pondok',
                'jabatan' => [
                    ['nama' => 'Kepala Unit Kesehatan', 'roles' => ['Admin Kesehatan']],
                    ['nama' => 'Petugas Kesehatan Pondok', 'roles' => ['Admin Kesehatan']],
                ],
            ],
            [
                'nama' => 'Tenaga Usaha dan Kemandirian Pondok',
                'urutan' => 8,
                'deskripsi' => 'Unit usaha dan kemandirian',
                'jabatan' => [
                    ['nama' => 'Kepala Unit Usaha Pondok', 'roles' => ['Guru']],
                    ['nama' => 'Staf Unit Usaha Pondok', 'roles' => ['Guru']],
                ],
            ],
        ];

        foreach ($jenisGtkData as $jData) {
            $jenisGtk = JenisGtk::updateOrCreate(
                ['nama' => $jData['nama']],
                [
                    'deskripsi' => $jData['deskripsi'],
                    'urutan' => $jData['urutan'],
                    'is_active' => true,
                ]
            );

            foreach ($jData['jabatan'] as $order => $jabatanItem) {
                Jabatan::updateOrCreate(
                    [
                        'jenis_gtk_id' => $jenisGtk->id,
                        'nama' => $jabatanItem['nama'],
                    ],
                    [
                        'kategori' => null,
                        'roles' => $jabatanItem['roles'],
                        'urutan' => $order + 1,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
