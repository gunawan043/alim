<?php

namespace Database\Seeders;

use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\School;
use App\Models\Subject;
use App\Models\SubjectGroup;
use Illuminate\Database\Seeder;

/**
 * SEEDER WAJIB — Struktur Kurikulum Pondok (SD / SMP / SMA-MA).
 *
 * Menyiapkan per satuan pendidikan:
 *  - Tingkat (grade levels) + fase;
 *  - Mapel (subjects) + rumpun;
 *  - Alokasi jam per pekan per tingkat (grade_level_subjects),
 *    termasuk peminatan BAHASA / IPA untuk SMA (kolom `track`).
 *
 * Idempoten — aman dijalankan berulang. Rombel TIDAK dibuat di sini.
 */
class CurriculumStructureSeeder extends Seeder
{
    /** Rumpun: [agama, tahfidz, bahasa, umum] — diambil dari master SubjectGroup. */

    /**
     * SD: [kode, nama, rumpun, jam per kelas I..VI]
     */
    private const SD = [
        ['AQA', 'Aqidah', 'agama', [2, 2, 2, 2, 2, 2]],
        ['ADB', 'Adab', 'agama', [2, 2, 2, 2, 2, 2]],
        ['FQH', 'Fikih', 'agama', [2, 2, 2, 2, 2, 2]],
        ['THF', "Tahfidzul Qur'an", 'tahfidz', [9, 9, 9, 9, 9, 9]],
        ['BAR', 'Bahasa Arab', 'bahasa', [7, 7, 6, 6, 6, 6]],
        ['HDS', 'Hafalan Hadits', 'agama', [2, 2, 2, 2, 2, 2]],
        ['BIN', 'Bahasa Indonesia', 'umum', [4, 4, 2, 2, 2, 2]],
        ['MAT', 'Matematika', 'umum', [4, 4, 4, 4, 4, 4]],
        ['IPA', 'IPAS', 'umum', [0, 0, 3, 3, 3, 3]],
        ['PJOK', 'PJOK', 'umum', [2, 2, 2, 2, 2, 2]],
        ['PKN', 'PKn', 'umum', [2, 2, 2, 2, 1, 1]],
        ['SEN', 'Seni Budaya', 'umum', [1, 1, 1, 1, 1, 1]],
        ['LFS', 'Life Skill / Cooking', 'umum', [0, 0, 0, 0, 1, 1]],
        ['BIG', 'Bahasa Inggris', 'bahasa', [2, 2, 2, 2, 2, 2]],
        ['LQF', 'Liqo Maftuh (Upacara)', 'umum', [1, 1, 1, 1, 1, 1]],
        ['PBC', 'Pembiasaan', 'umum', [0, 0, 0, 0, 0, 0]],
    ];

    /**
     * SMP: jam per kelas VII, VIII, IX.
     */
    private const SMP = [
        ['AQA', 'Aqidah', 'agama', [3, 3, 3]],
        ['ADB', 'Adab', 'agama', [2, 2, 2]],
        ['FQH', 'Fikih', 'agama', [2, 2, 2]],
        ['THF', "Tahfidzul Qur'an", 'tahfidz', [8, 8, 8]],
        ['BAR', 'Bahasa Arab', 'bahasa', [10, 6, 6]],
        ['QWD', 'Qowaid Lughoh', 'bahasa', [0, 2, 2]],
        ['KHT', 'Khot', 'bahasa', [1, 1, 0]],
        ['KTB', 'Kitabah', 'bahasa', [1, 1, 0]],
        ['TBR', "Ta'bir", 'bahasa', [0, 2, 2]],
        ['HDS', 'Hafalan Hadits', 'agama', [2, 2, 2]],
        ['BIN', 'Bahasa Indonesia', 'umum', [3, 3, 3]],
        ['BIG', 'Bahasa Inggris', 'bahasa', [3, 3, 3]],
        ['MAT', 'Matematika', 'umum', [3, 3, 4]],
        ['IPA', 'IPA Terpadu', 'umum', [3, 3, 4]],
        ['PJOK', 'Penjaskes', 'umum', [2, 2, 2]],
        ['INF', 'Informatika', 'umum', [1, 1, 1]],
        ['LQF', 'Liqo Maftuh (Upacara)', 'umum', [1, 1, 1]],
        ['PIS', 'PKn / IPS / Seni', 'umum', [1, 1, 1]],
    ];

    /**
     * SMA: baris eksplisit [kode, nama, rumpun, tingkat, jam, track|null].
     * track: null = semua jalur; 'bahasa'/'ipa' = peminatan.
     */
    private const SMA = [
        // Kelas X
        ['AQA', 'Aqidah', 'agama', 10, 3], ['ADB', 'Adab', 'agama', 10, 2], ['FQH', 'Fiqih', 'agama', 10, 2],
        ['THF', "Tahfidzul Qur'an", 'tahfidz', 10, 7], ['HDS', 'Hafalan Hadits', 'agama', 10, 2],
        ['BAR', 'Bahasa Arab', 'bahasa', 10, 8], ['TBR', "Ta'bir", 'bahasa', 10, 2],
        ['NHW', 'Nahwu', 'bahasa', 10, 2], ['SRF', 'Shorof', 'bahasa', 10, 2],
        ['BIN', 'Bahasa Indonesia', 'umum', 10, 2], ['BIG', 'Bahasa Inggris', 'bahasa', 10, 2],
        ['MAT', 'Matematika', 'umum', 10, 2], ['IPA', 'IPA', 'umum', 10, 5],
        ['IPS', 'IPS (Ekonomi, Sejarah, Geografi & Sosiologi)', 'umum', 10, 1],
        ['INF', 'Informatika', 'umum', 10, 1], ['PIS', 'PKn/Seni Budaya/Prakarya/Informatika (Peminatan)', 'umum', 10, 1],
        ['PJOK', 'PJOK', 'umum', 10, 2], ['LQF', 'Liqo Maftuh (Upacara)', 'umum', 10, 1],

        // Kelas XI
        ['AQA', 'Aqidah', 'agama', 11, 3], ['ADB', 'Adab', 'agama', 11, 2], ['FQH', 'Fiqih', 'agama', 11, 2],
        ['THF', "Tahfidzul Qur'an", 'tahfidz', 11, 5], ['HDS', 'Hafalan Hadits', 'agama', 11, 2],
        ['BAR', 'Bahasa Arab', 'bahasa', 11, 8], ['NHW', 'Nahwu', 'bahasa', 11, 2], ['SRF', 'Shorof', 'bahasa', 11, 1],
        ['BIN', 'Bahasa Indonesia', 'umum', 11, 2], ['MAT', 'Matematika', 'umum', 11, 2],
        ['PIS', 'PKn/Seni Budaya/Prakarya/Informatika (Peminatan)', 'umum', 11, 1],
        ['PJOK', 'PJOK', 'umum', 11, 2], ['LQF', 'Liqo Maftuh (Upacara)', 'umum', 11, 1],
        // Jalur BAHASA
        ['TBR', "Ta'bir", 'bahasa', 11, 2, 'bahasa'], ['FRD', "Fara'id", 'agama', 11, 2, 'bahasa'],
        ['BLG', 'Balagoh', 'bahasa', 11, 2, 'bahasa'], ['BIG', 'Bahasa Inggris', 'bahasa', 11, 2, 'bahasa'],
        ['PIL', 'Mapel Pilihan', 'umum', 11, 6, 'bahasa'],
        // Jalur IPA
        ['MPN', 'Matematika Peminatan', 'umum', 11, 2, 'ipa'], ['BIG', 'Bahasa Inggris', 'bahasa', 11, 3, 'ipa'],
        ['PIL', 'Mapel Pilihan', 'umum', 11, 9, 'ipa'],

        // Kelas XII
        ['AQA', 'Aqidah', 'agama', 12, 3], ['ADB', 'Adab', 'agama', 12, 2], ['FQH', 'Fiqih', 'agama', 12, 2],
        ['THF', "Tahfidzul Qur'an", 'tahfidz', 12, 5], ['HDS', 'Hafalan Hadits', 'agama', 12, 2],
        ['BAR', 'Bahasa Arab', 'bahasa', 12, 2], ['NHW', 'Nahwu', 'bahasa', 12, 2], ['SRF', 'Shorof', 'bahasa', 12, 1],
        ['MHD', 'Mustholahul Hadits', 'agama', 12, 2], ['USH', 'Ushul Fiqh', 'agama', 12, 2], ['MNJ', 'Manhaj', 'agama', 12, 2],
        ['BIN', 'Bahasa Indonesia', 'umum', 12, 2], ['MAT', 'Matematika', 'umum', 12, 2],
        ['PIS', 'PKn/Seni Budaya/Prakarya/Informatika (Peminatan)', 'umum', 12, 1],
        ['PJOK', 'PJOK', 'umum', 12, 2], ['LQF', 'Liqo Maftuh (Upacara)', 'umum', 12, 1],
        // Jalur BAHASA
        ['TBR', "Ta'bir", 'bahasa', 12, 2, 'bahasa'], ['FRD', "Fara'id", 'agama', 12, 2, 'bahasa'],
        ['BLG', 'Balagoh', 'bahasa', 12, 2, 'bahasa'], ['BIG', 'Bahasa Inggris', 'bahasa', 12, 2, 'bahasa'],
        ['PIL', 'Mapel Pilihan', 'umum', 12, 6, 'bahasa'],
        // Jalur IPA
        ['MPN', 'Matematika Peminatan', 'umum', 12, 2, 'ipa'], ['BIG', 'Bahasa Inggris', 'bahasa', 12, 3, 'ipa'],
        ['PIL', 'Mapel Pilihan', 'umum', 12, 9, 'ipa'],
    ];

    public function run(): void
    {
        $groups = SubjectGroup::pluck('id', 'code');

        foreach (School::query()->orderBy('name')->get() as $school) {
            $jenjang = strtolower((string) $school->school_level);

            match ($jenjang) {
                'sd' => $this->seedJenjang($school, $groups, [
                    1 => 'A', 2 => 'A', 3 => 'B', 4 => 'B', 5 => 'C', 6 => 'C',
                ], 'SD', self::SD),
                'smp' => $this->seedJenjang($school, $groups, [7 => 'D', 8 => 'D', 9 => 'D'], 'SMP', self::SMP),
                'sma', 'ma', 'smk' => $this->seedJenjang($school, $groups, [10 => 'E', 11 => 'F', 12 => 'F'], 'SMA', self::SMA),
                default => null,
            };
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<string, string>  $groups
     * @param  array<int, string>  $faseByLevel
     * @param  array<int, array<int, mixed>>  $defs
     */
    private function seedJenjang(School $school, $groups, array $faseByLevel, string $prefix, array $defs): void
    {
        // 1) Tingkat + fase
        $gradeIds = [];

        foreach ($faseByLevel as $level => $fase) {
            $grade = GradeLevel::updateOrCreate(
                ['school_id' => $school->id, 'level' => $level],
                [
                    'name' => 'Kelas '.$level,
                    'code' => $prefix.'-'.$level,
                    'fase' => $fase,
                    'is_active' => true,
                ]
            );

            $gradeIds[$level] = $grade->id;
        }

        // 2) Mapel + 3) alokasi jam
        foreach ($defs as $def) {
            $count = count($def);

            // Format SMA: [kode, nama, rumpun, tingkat, jam] / [.., jam, track]
            if ($count === 5 || $count === 6) {
                [$code, $name, $groupCode, $level, $hours] = $def;
                $track = $def[5] ?? null;

                $subject = Subject::updateOrCreate(
                    ['school_id' => $school->id, 'code' => $code],
                    [
                        'name' => $name,
                        'subject_group_id' => $groups[$groupCode] ?? null,
                        'source' => 'pondok',
                        'is_active' => true,
                    ]
                );

                if (isset($gradeIds[$level]) && $hours > 0) {
                    GradeLevelSubject::updateOrCreate(
                        ['grade_level_id' => $gradeIds[$level], 'subject_id' => $subject->id, 'track' => $track],
                        ['allocation_hours' => (int) $hours, 'is_active' => true]
                    );
                }

                continue;
            }

            // Format SD/SMP: [kode, nama, rumpun, jam per tingkat (urut)]
            [$code, $name, $groupCode] = $def;
            $hoursList = $def[3];

            $subject = Subject::updateOrCreate(
                ['school_id' => $school->id, 'code' => $code],
                [
                    'name' => $name,
                    'subject_group_id' => $groups[$groupCode] ?? null,
                    'source' => 'pondok',
                    'is_active' => true,
                ]
            );

            foreach (array_keys($gradeIds) as $i => $level) {
                $hours = (int) ($hoursList[$i] ?? 0);

                if ($hours <= 0) {
                    continue;
                }

                GradeLevelSubject::updateOrCreate(
                    ['grade_level_id' => $gradeIds[$level], 'subject_id' => $subject->id, 'track' => null],
                    ['allocation_hours' => $hours, 'is_active' => true]
                );
            }
        }
    }
}
