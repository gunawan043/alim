<?php

namespace Database\Seeders;

use App\Models\CurriculumProfile;
use Illuminate\Database\Seeder;

class CurriculumProfileSeeder extends Seeder
{
    public function run(): void
    {
        CurriculumProfile::updateOrCreate(
            ['code' => 'kbc_kemenag'],
            [
                'name' => 'Kurikulum Berbasis Cinta (KBC) — Kemenag',
                'authority' => 'kemenag',
                'version' => 'KBC 2025',
                'effective_from' => '2025-07-01',
                'effective_until' => null,
                'is_active' => true,
                'config' => [
                    'labels' => [
                        'unit_label' => 'Madrasah',
                        'perangkat_label' => 'Modul Ajar',
                        'perangkat_short' => 'Modul Ajar',
                        'kokurikuler_label' => 'P5RA',
                        'values_label' => 'Nilai Cinta',
                        'authority_label' => 'Kementerian Agama',
                        'print_template' => 'kemenag',
                        'curriculum_title' => 'Kurikulum Berbasis Cinta',
                    ],
                    'values' => [
                        'hubbullah' => 'Cinta kepada Allah',
                        'hubburrasul' => 'Cinta kepada Rasulullah',
                        'hubbunnafs' => 'Cinta kepada Diri Sendiri',
                        'hubbunnaas' => 'Cinta kepada Sesama',
                        'hubbulbiah' => 'Cinta kepada Lingkungan',
                        'hubbulwathan' => 'Cinta kepada Bangsa dan Negara',
                    ],
                    'sections' => [
                        'tema_kbc',
                        'materi_insersi',
                        'pilar_kbc',
                        'indikator_kbc',
                        'refleksi_kbc',
                        'asesmen_kbc',
                    ],
                    'kokurikuler_options' => [
                        'p5ra' => 'P5RA — Projek Penguatan Profil Pelajar Pancasila Rahmatan lil Alamin',
                    ],
                ],
            ]
        );

        CurriculumProfile::updateOrCreate(
            ['code' => 'merdeka_dinas'],
            [
                'name' => 'Kurikulum Merdeka / Pembelajaran Mendalam — Dinas Pendidikan',
                'authority' => 'dinas',
                'version' => 'Merdeka 2025',
                'effective_from' => '2024-07-01',
                'effective_until' => null,
                'is_active' => true,
                'config' => [
                    'labels' => [
                        'unit_label' => 'Sekolah',
                        'perangkat_label' => 'RPM / Modul Ajar',
                        'perangkat_short' => 'RPM',
                        'kokurikuler_label' => 'P5',
                        'values_label' => 'Profil Pelajar Pancasila',
                        'authority_label' => 'Dinas Pendidikan',
                        'print_template' => 'dinas',
                        'curriculum_title' => 'Kurikulum Merdeka',
                    ],
                    'values' => [
                        'beriman' => 'Beriman, Bertakwa kepada Tuhan YME, dan Berakhlak Mulia',
                        'berkebinekaan' => 'Berkebinekaan Global',
                        'bergotong_royong' => 'Bergotong Royong',
                        'mandiri' => 'Mandiri',
                        'bernalar_kritis' => 'Bernalar Kritis',
                        'kreatif' => 'Kreatif',
                    ],
                    'sections' => [
                        'dimensi_pembelajaran_mendalam',
                        'p5_tema',
                        'p5_dimensi',
                        'p5_catatan',
                    ],
                    'kokurikuler_options' => [
                        'p5' => 'P5 — Projek Penguatan Profil Pelajar Pancasila',
                    ],
                ],
            ]
        );
    }
}
