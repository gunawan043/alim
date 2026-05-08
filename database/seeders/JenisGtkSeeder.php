<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JenisGtk;
use App\Models\Jabatan;
use Illuminate\Support\Str;

class JenisGtkSeeder extends Seeder
{
    public function run(): void
    {
        $jenisGtkData = [
            ['nama' => 'Pimpinan Pondok', 'urutan' => 1, 'deskripsi' => 'Pimpinan dan pengelola pondok', 'jabatan' => ['Mudir', 'Wakil Mudir I', 'Wakil Mudir II']],
            ['nama' => 'Tenaga Administrasi Pondok', 'urutan' => 2, 'deskripsi' => 'Tenaga administrasi dan perkantoran', 'jabatan' => ['Kepala Hubungan Masyarakat dan Personalia', 'Staf Hubungan Masyarakat', 'Staf Personalia', 'Kepala Kesekretariatan', 'Staf Kesekretariatan', 'Kepala Keuangan', 'Staf Keuangan / Bendahara']],
            ['nama' => 'Tenaga Pendidik Pondok', 'urutan' => 3, 'deskripsi' => 'Guru and uktsdadz/ustadzah', 'jabatan' => ['Kepala Lembaga Pendidikan', 'Koordinator KSP', 'Guru', 'Kepala Departemen Tahfidz', 'Ustadz/Ustadzah Tahfidz', 'Kepala Departemen Bahasa', 'Ustadz/Ustadzah Bahasa']],
            ['nama' => 'Tenaga Kependidikan Pondok', 'urutan' => 4, 'deskripsi' => 'Tenaga kependidikan non-guru', 'jabatan' => ['Staf Kependidikan', 'Operator Akademik', 'Administrasi Pendidikan']],
            ['nama' => 'Tenaga Keamanan Pondok', 'urutan' => 5, 'deskripsi' => 'Tenaga keamanan dan ketertiban', 'jabatan' => ['Kepala Keamanan Pondok', 'Koordinator Divisi Keamanan', 'Anggota Keamanan Pondok']],
            ['nama' => 'Tenaga Sarana dan Prasarana Pondok', 'urutan' => 6, 'deskripsi' => 'Sarana, prasarana, gizi dan kebersihan', 'jabatan' => ['Kepala Sarana dan Prasarana', 'Staf Sarana dan Prasarana', 'Kepala Unit Gizi dan Logistik', 'Staf Gizi dan Logistik', 'Petugas Kebersihan Pondok']],
            ['nama' => 'Tenaga Kesehatan Pondok', 'urutan' => 7, 'deskripsi' => 'Petugas kesehatan pondok', 'jabatan' => ['Kepala Unit Kesehatan', 'Petugas Kesehatan Pondok']],
            ['nama' => 'Tenaga Usaha dan Kemandirian Pondok', 'urutan' => 8, 'deskripsi' => 'Unit usaha dan kemandirian', 'jabatan' => ['Kepala Unit Usaha Pondok', 'Staf Unit Usaha Pondok']],
        ];

        foreach ($jenisGtkData as $jData) {
            $jenisGtk = JenisGtk::updateOrCreate(
                ['nama' => $jData['nama']],
                [
                    'deskripsi' => $jData['deskripsi'],
                    'urutan'    => $jData['urutan'],
                    'is_active' => true,
                ]
            );

            foreach ($jData['jabatan'] as $order => $jabatanNama) {
                Jabatan::updateOrCreate(
                    [
                        'jenis_gtk_id' => $jenisGtk->id,
                        'nama'         => $jabatanNama,
                    ],
                    [
                        'kategori' => null,
                        'urutan'    => $order + 1,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
