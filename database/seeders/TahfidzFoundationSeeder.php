<?php

namespace Database\Seeders;

use App\Models\TahfidzAssessmentRubric;
use App\Models\TahfidzPackage;
use App\Models\WorkUnit;
use Illuminate\Database\Seeder;

/**
 * Fondasi Tahfidz: work unit departemen, paket 30 juz, dan rubrik tasmi'.
 *
 * Aman dijalankan berulang; tidak menimpa data yang sudah diubah pengguna.
 */
class TahfidzFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $department = WorkUnit::firstOrCreate(
            ['code' => 'DEP-TAH'],
            [
                'name' => 'Departemen Tahfidz',
                'type' => 'Unit Pelayanan',
                'is_active' => true,
            ]
        );

        TahfidzPackage::firstOrCreate(
            [
                'work_unit_id' => $department->id,
                'code' => 'THF-30',
            ],
            [
                'name' => "Tahfidzul Qur'an — 30 Juz",
                'description' => 'Paket dasar tahfidz 30 juz, standar Mushaf Madinah.',
                'total_target_juz' => 30,
                'total_target_halaman' => 604,
                'lines_per_page' => 15,
                'total_pages' => 604,
                'duration_semesters' => 6,
                'is_active' => true,
            ]
        );

        $this->call(TahfidzJuzMasterSeeder::class);
        $this->call(TahfidzSurahMasterSeeder::class);

        TahfidzAssessmentRubric::firstOrCreate(
            [
                'work_unit_id' => $department->id,
                'name' => "Rubrik Tasmi' Standar",
            ],
            [
                'components' => [
                    ['key' => 'tajwid', 'label' => 'Tajwid', 'max' => 100, 'lahn_khofi' => 0.5, 'lahn_jali' => 1, 'weight' => 1],
                    ['key' => 'fashohah', 'label' => 'Fashohah', 'max' => 100, 'lahn_khofi' => 0.5, 'lahn_jali' => 1, 'weight' => 1],
                    ['key' => 'quwwatul_hifzhi', 'label' => 'Quwwatul Hifzhi', 'max' => 100, 'lahn_khofi' => 0.5, 'lahn_jali' => 1, 'weight' => 1],
                ],
                'kkm' => 60,
                'is_active' => true,
            ]
        );
    }
}
