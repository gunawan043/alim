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
            ['kode' => 'PAH-MR',      'nama' => 'Manual Mutu',                                              'deskripsi' => 'Manual Mutu PAH Mataram',          'is_active' => 1, 'sort_order' => 1],
            ['kode' => 'PAH-MDR',     'nama' => 'MUDIR PAH MATARAM',                                        'deskripsi' => 'Direksi Utama PAH Mataram',        'is_active' => 1, 'sort_order' => 2],
            ['kode' => 'PAH-WADIR AK', 'nama' => 'WADIR AKADEMIK & PENGASUHAN PAH MATARAM',                 'deskripsi' => 'Wakil Diretor Akademik',          'is_active' => 1, 'sort_order' => 3],
            ['kode' => 'PAH-WADIR PU', 'nama' => 'WADIR PELAYANAN UMUM PAH MATARAM',                       'deskripsi' => 'Wadir Pelayanan Umum',            'is_active' => 1, 'sort_order' => 4],
            ['kode' => 'PAH-KSP',     'nama' => 'KEPALA SATUAN PENDIDIKAN PAH MATARAM',                    'deskripsi' => 'Kepala Satuan Pendidikan',        'is_active' => 1, 'sort_order' => 5],
            ['kode' => 'PAH-KP',      'nama' => 'KEPALA PENGASUHAN',                                       'deskripsi' => 'Kepala Pengasuhan',               'is_active' => 1, 'sort_order' => 6],
            ['kode' => 'PAH-TAH',     'nama' => 'KEPALA DEPARTEMEN TAHFIZH',                               'deskripsi' => 'Departemen Tahfizh',              'is_active' => 1, 'sort_order' => 7],
            ['kode' => 'PAH-BHS',     'nama' => 'KEPALA DEPARTEMEN BAHASA DAN KADERISASI',                 'deskripsi' => 'Departemen Bahasa',               'is_active' => 1, 'sort_order' => 8],
            ['kode' => 'PAH-PERPUS',  'nama' => 'KOORDINATOR PERPUSTAKAAN',                                'deskripsi' => 'Perpustakaan',                   'is_active' => 1, 'sort_order' => 9],
            ['kode' => 'PAH-LAB',     'nama' => 'KOORDINATOR LABORATORIUM',                                'deskripsi' => 'Laboratorium',                    'is_active' => 1, 'sort_order' => 10],
            ['kode' => 'PAH-KUPT',    'nama' => 'KEPALA UNIT PELAYANAN TERPADU',                          'deskripsi' => 'Unit Pelayanan Terpadu',         'is_active' => 1, 'sort_order' => 11],
            ['kode' => 'PAH-SATPAM',  'nama' => 'SATUAN KEAMANAN',                                         'deskripsi' => 'Satuan Keamanan',                'is_active' => 1, 'sort_order' => 12],
            ['kode' => 'PAH-UGL',     'nama' => 'KEPALA UNIT GIZI DAN LOGISTIK',                          'deskripsi' => 'Unit Gizi dan Logistik',          'is_active' => 1, 'sort_order' => 13],
            ['kode' => 'PAH-KOOR-KE', 'nama' => 'KOORDINATOR KEAMANAN',                                   'deskripsi' => 'Koordinator Keamanan',           'is_active' => 1, 'sort_order' => 14],
            ['kode' => 'PAH-TIJ',     'nama' => 'KEPALA UNIT SISTEM TEKNOLOGI INFORMASI & JARINGAN',      'deskripsi' => 'TI dan Jaringan',                'is_active' => 1, 'sort_order' => 15],
            ['kode' => 'PAH-KEU',     'nama' => 'KEPALA KEUANGAN',                                        'deskripsi' => 'Bagian Keuangan',                'is_active' => 1, 'sort_order' => 16],
            ['kode' => 'PAH-HUMAS',   'nama' => 'HUMAS PERSONALIA',                                       'deskripsi' => 'Humas dan Personalia',            'is_active' => 1, 'sort_order' => 17],
        ];

        foreach ($divisis as $div) {
            // Upsert: update if exists, insert if not
            $existing = DB::table('divisis')->where('kode', $div['kode'])->first();
            if ($existing) {
                DB::table('divisis')->where('kode', $div['kode'])->update([
                    'nama' => $div['nama'],
                    'deskripsi' => $div['deskripsi'],
                    'is_active' => $div['is_active'],
                    'sort_order' => $div['sort_order'],
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('divisis')->insert([
                    'id' => Str::uuid()->toString(),
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
