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
        // Clear existing data
        AdditionalTaskType::query()->delete();

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
                    'Koordinator Guru Tahfidz',
                    'Koordinator Laboratorium',
                    'Koordinator Sarpras Satuan Pendidikan',
                    'Pembina Ekstrakurikuler',
                    'Tim Kurikulum',
                    'Tim Kesiswaan',
                ],
            ],
            [
                'jenis_gtk' => 'Asrama',
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
                'jenis_gtk' => 'Humas Personalia',
                'tasks' => [
                    'Koordinator Alumni',
                ],
            ],
        ];

        foreach ($data as $section) {
            $jenisGtk = DB::table('jenis_gtk')->where('nama', $section['jenis_gtk'])->first();

            if (! $jenisGtk) {
                $this->command->warn("JenisGtk '{$section['jenis_gtk']}' not found, skipping...");

                continue;
            }

            foreach ($section['tasks'] as $order => $taskName) {
                AdditionalTaskType::create([
                    'id' => Str::uuid(),
                    'jenis_gtk_id' => $jenisGtk->id,
                    'nama' => $taskName,
                    'kode' => Str::slug($taskName),
                    'deskripsi' => null,
                    'urutan' => $order + 1,
                    'is_active' => true,
                ]);
            }

            $this->command->info("✅ {$section['jenis_gtk']}: ".count($section['tasks']).' tugas tambahan');
        }

        $this->command->info('✅ AdditionalTaskTypeSeeder selesai.');
    }
}
