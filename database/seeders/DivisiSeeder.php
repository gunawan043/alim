<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DivisiSeeder extends Seeder
{
    public function run(): void
    {
        $divisis = [
            ['kode' => 'PAH-SP',       'nama' => 'Satuan Pendidikan',                                    'deskripsi' => 'Satuan Pendidikan',                        'is_active' => 1, 'sort_order' => 1],
            ['kode' => 'PAH-PNG',      'nama' => 'Pengasuhan',                                           'deskripsi' => 'Pengasuhan',                               'is_active' => 1, 'sort_order' => 2],
            ['kode' => 'PAH-TAH',      'nama' => 'Departemen Tahfidz',                                   'deskripsi' => 'Departemen Tahfidz',                       'is_active' => 1, 'sort_order' => 3],
            ['kode' => 'PAH-BHS',      'nama' => 'Departemen Bahasa',                                    'deskripsi' => 'Departemen Bahasa',                        'is_active' => 1, 'sort_order' => 4],
            ['kode' => 'PAH-PERPUS',   'nama' => 'Perpustakaan',                                         'deskripsi' => 'Perpustakaan',                             'is_active' => 1, 'sort_order' => 5],
            ['kode' => 'PAH-LAB',      'nama' => 'Laboratorium',                                         'deskripsi' => 'Laboratorium',                             'is_active' => 1, 'sort_order' => 6],
            ['kode' => 'PAH-UKS',      'nama' => 'UKS',                                                  'deskripsi' => 'Unit Kesehatan Sekolah',                   'is_active' => 1, 'sort_order' => 7],
            ['kode' => 'PAH-URT',      'nama' => 'Unit Rumah Tangga',                                    'deskripsi' => 'Unit Rumah Tangga',                        'is_active' => 1, 'sort_order' => 8],
            ['kode' => 'PAH-SATPAM',   'nama' => 'Satuan Keamanan',                                      'deskripsi' => 'Satuan Keamanan',                          'is_active' => 1, 'sort_order' => 9],
            ['kode' => 'PAH-UPGIZI',   'nama' => 'Unit Pelayanan Gizi',                                  'deskripsi' => 'Unit Pelayanan Gizi',                      'is_active' => 1, 'sort_order' => 10],
            ['kode' => 'PAH-TIJ',      'nama' => 'Teknologi Informasi & Jaringan',                       'deskripsi' => 'TI dan Jaringan',                          'is_active' => 1, 'sort_order' => 11],
            ['kode' => 'PAH-KEU',      'nama' => 'Keuangan',                                             'deskripsi' => 'Bagian Keuangan',                          'is_active' => 1, 'sort_order' => 12],
            ['kode' => 'PAH-HUMAS',    'nama' => 'Humas & Personalia',                                   'deskripsi' => 'Humas dan Personalia',                     'is_active' => 1, 'sort_order' => 13],
        ];

        foreach ($divisis as $div) {
            $existing = DB::table('divisis')->where('kode', $div['kode'])->first();
            if ($existing) {
                DB::table('divisis')->where('id', $existing->id)->update([
                    'nama' => $div['nama'],
                    'deskripsi' => $div['deskripsi'],
                    'is_active' => $div['is_active'],
                    'sort_order' => $div['sort_order'],
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('divisis')->insert([
                    'id' => (string) Str::uuid(),
                    'nama' => $div['nama'],
                    'kode' => $div['kode'],
                    'deskripsi' => $div['deskripsi'],
                    'is_active' => $div['is_active'],
                    'sort_order' => $div['sort_order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $count = DB::table('divisis')->count();
        $this->command->info("Divisi seeder done. Total divisi: $count");
    }
}
