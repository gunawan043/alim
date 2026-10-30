<?php

namespace Database\Seeders;

use App\Models\AdditionalTaskType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdditionalTaskTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Jangan menghapus tipe buatan pengguna. Seeder hanya memastikan
        // tipe bawaan tersedia dan tetap idempoten.
        $data = [
            [
                'jenis_gtk' => 'Satuan Pendidikan',
                'tasks' => [
                    'Wali Kelas',
                    'Koordinator Guru Umum',
                    'Koordinator Guru Agama',
                    'Koordinator Guru Hadits',
                    'Koordinator Guru Bahasa Arab',
                    'Koordinator Ekstrakurikuler',
                    'Koordinator Kurikulum',
                    'Koordinator Kesiswaan',
                    'Guru Koordinator OSIPP',
                    'Koordinator Guru Tahfidz',
                    'Koordinator Laboratorium',
                    'Koordinator Sarpras Satuan Pendidikan',
                    'Pembina Ekstrakurikuler',
                    'Tim Kurikulum',
                    'Tim Kesiswaan',
                    'Bendahara Sekolah',
                ],
            ],
            [
                'jenis_gtk' => 'Keasramaan',
                'tasks' => [
                    'Wali Kamar',
                    'Wali Asrama',
                    'Staf Asrama',
                    'Staf Penitipan Barang',
                ],
            ],
            [
                'jenis_gtk' => 'UKS',
                'tasks' => [
                    'Admin UKS Putra',
                    'Admin UKS Putri',
                ],
            ],
            [
                'jenis_gtk' => 'Humas',
                'tasks' => [
                    'Koordinator Alumni',
                ],
            ],
        ];

        foreach ($data as $section) {
            $jenisGtk = DB::table('jenis_gtk')
                ->where('nama', $section['jenis_gtk'])
                ->orWhere('nama', 'like', '%'.$section['jenis_gtk'].'%')
                ->first();

            if (! $jenisGtk) {
                $this->command->warn("JenisGtk '{$section['jenis_gtk']}' not found, skipping...");

                continue;
            }

            foreach ($section['tasks'] as $order => $taskName) {
                AdditionalTaskType::updateOrCreate(
                    ['kode' => Str::slug($taskName)],
                    [
                        'jenis_gtk_id' => $jenisGtk->id,
                        'nama' => $taskName,
                        'deskripsi' => null,
                        'urutan' => $order + 1,
                        'is_active' => true,
                    ]
                );
            }

            $this->command->info("✅ {$section['jenis_gtk']}: ".count($section['tasks']).' tugas tambahan');
        }

        $this->command->info('✅ AdditionalTaskTypeSeeder selesai.');
    }
}
