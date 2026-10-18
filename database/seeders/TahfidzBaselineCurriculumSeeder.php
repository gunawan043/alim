<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\TahfidzLessonPlan;
use App\Models\TahfidzMuqorrorPlan;
use App\Models\TahfidzPackage;
use App\Models\WorkUnit;
use Illuminate\Database\Seeder;

/**
 * Master awal Muqorror dan RPP Tahfizh/Iqro tingkat departemen.
 *
 * Seeder ini adalah baseline produksi, bukan data uji coba. Isinya
 * menggunakan struktur standar aplikasi dan dapat diedit melalui menu
 * Muqorror/RPP. Dokumen RPP resmi dapat menggantikan konten ini kapan saja.
 */
class TahfidzBaselineCurriculumSeeder extends Seeder
{
    public function run(): void
    {
        $department = WorkUnit::where('code', 'DEP-TAH')->first();
        $academicYear = AcademicYear::where('is_active', true)->first()
            ?? AcademicYear::orderByDesc('start_date')->first();

        if (! $department || ! $academicYear) {
            return;
        }

        $package = TahfidzPackage::where('is_active', true)->first();
        $jenjangList = ['sd' => 'SD', 'smp' => 'SMP', 'sma' => 'SMA'];

        foreach ($jenjangList as $jenjang => $label) {
            foreach (['ganjil' => 0, 'genap' => 6] as $semester => $monthOffset) {
                $plan = TahfidzMuqorrorPlan::firstOrCreate(
                    [
                        'work_unit_id' => $department->id,
                        'school_id' => null,
                        'academic_year_id' => $academicYear->id,
                        'semester' => $semester,
                        'jenjang' => $jenjang,
                        'title' => 'Muqorror Tahfizh Standar — '.$label.' ('.ucfirst($semester).')',
                    ],
                    [
                        'tahfidz_package_id' => $package?->id,
                        'grade_level_id' => null,
                        'status' => 'active',
                        'is_active' => true,
                        'created_by' => null,
                        'notes' => 'Baseline produksi. Sesuaikan target dengan dokumen kurikulum resmi Departemen Tahfidz.',
                    ]
                );

                if (! $plan->months()->exists()) {
                    foreach (range(1, 6) as $localMonth) {
                        $globalMonth = $monthOffset + $localMonth;
                        $pageStart = (int) (1 + floor(($globalMonth - 1) * 604 / 12));
                        $pageEnd = (int) round($globalMonth * 604 / 12);
                        $juzStart = (int) (1 + floor(($globalMonth - 1) * 30 / 12));
                        $juzEnd = (int) max($juzStart, ceil($globalMonth * 30 / 12));
                        $pages = max(1, $pageEnd - $pageStart + 1);

                        $plan->months()->create([
                            'month' => $globalMonth,
                            'target_juz_start' => min(30, $juzStart),
                            'target_juz_end' => min(30, $juzEnd),
                            'target_pages' => $pages,
                            'target_lines_month' => $pages * 15,
                            'target_lines_day' => 15,
                            'active_days' => 30,
                            'exam_type' => match ($localMonth) {
                                3 => 'STS', 6 => 'SAS', default => null
                            },
                            'target_semester' => 'Juz '.min(30, $juzStart).'–'.min(30, $juzEnd),
                            'target_mid_semester' => 'Evaluasi tengah semester',
                            'ziyadah_meetings' => 16,
                            'rabth_meetings' => 8,
                            'notes' => 'Target standar; dapat disesuaikan.',
                            'sort_order' => $localMonth,
                        ]);
                    }
                }

                foreach (['tahfizh' => 'Tahfizh', 'iqro' => 'Iqro'] as $type => $typeLabel) {
                    $rpp = TahfidzLessonPlan::firstOrCreate(
                        [
                            'work_unit_id' => $department->id,
                            'school_id' => null,
                            'grade_level_id' => null,
                            'academic_year_id' => $academicYear->id,
                            'semester' => $semester,
                            'type' => $type,
                            'title' => 'RPP '.$typeLabel.' Standar — '.$label.' ('.ucfirst($semester).')',
                        ],
                        [
                            'kbm_month' => 'Semester '.ucfirst($semester),
                            'monthly_target' => $type === 'tahfizh'
                                ? 'Ziyadah, murajaah, dan tasmi’ sesuai target halaqah.'
                                : 'Penguatan bacaan Iqro sesuai jilid masing-masing.',
                            'notes' => 'Baseline produksi; konten dapat diganti dengan RPP resmi.',
                            'status' => 'active',
                            'created_by' => null,
                        ]
                    );

                    if ($rpp->items()->exists()) {
                        continue;
                    }

                    $items = $type === 'tahfizh' ? [
                        ['Talaqqi & tahsin', 'Talaqqi 5 baris/halaman baru', 'Membacakan dan mengoreksi bacaan.', 'Menyimak dan menirukan bacaan.'],
                        ['Setoran ziyadah', 'Setoran hafalan baru', 'Menyimak setoran dan mencatat nilai.', 'Menyetorkan hafalan baru.'],
                        ['Murajaah & rabth', 'Murajaah hafalan lama', 'Membimbing rabth dan tikror.', 'Mengulang hafalan secara mandiri.'],
                        ['Tasmi’ pekanan', 'Tasmi’ bagian yang telah dihafal', 'Menilai kelancaran dan tajwid.', 'Menampilkan hafalan di depan guru.'],
                    ] : [
                        ['Pengenalan huruf', 'Mengenal huruf dan harakat', 'Mencontohkan makhraj huruf.', 'Menirukan dan membaca huruf.'],
                        ['Makhraj & sifat huruf', 'Latihan makhraj', 'Mengoreksi makhraj dan sifat huruf.', 'Berlatih membaca dengan makhraj tepat.'],
                        ['Latihan membaca', 'Membaca kata dan kalimat', 'Membimbing bacaan bertahap.', 'Membaca bersama dan mandiri.'],
                        ['Evaluasi jilid', 'Evaluasi bacaan pekanan', 'Menilai kelancaran dan ketepatan.', 'Membaca bahan evaluasi.'],
                    ];

                    foreach ($items as $index => [$target, $daily, $teacher, $student]) {
                        $rpp->items()->create([
                            'week' => $index + 1,
                            'section' => $type,
                            'learning_day' => 'Senin–Kamis',
                            'week_target' => $target,
                            'daily_target' => $daily,
                            'teacher_activity' => $teacher,
                            'student_activity' => $student,
                            'duration_minutes' => 60,
                            'sort_order' => $index,
                        ]);
                    }
                }
            }
        }

        $this->command?->info('Baseline Muqorror dan RPP Tahfizh/Iqro tersedia untuk SD, SMP, dan SMA.');
    }
}
