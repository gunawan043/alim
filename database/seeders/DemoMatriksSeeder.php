<?php

namespace Database\Seeders;

use App\Models\GtkAdditionalTask;
use App\Models\InstitutionDecree;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoMatriksSeeder extends Seeder
{
    public function run(): void
    {
        // Target: SMP IT Putra Abu Hurairah Mataram
        $schoolId = '0deb90a0-2325-42ce-b491-8d252f8cfd1d';
        $decree = InstitutionDecree::where('decree_type', 'SK Pembagian Tugas')->first();

        if (! $decree) {
            $this->command->warn('SK Pembagian Tugas tidak ditemukan. Lewati.');

            return;
        }

        // Set school_id ke decree
        $decree->update(['school_id' => $schoolId]);

        // Academic year dari decree
        $academicYearId = $decree->academic_year_id;

        // Study groups untuk SMP ini (grade 7,8,9)
        $studyGroups = StudyGroup::with('gradeLevel')
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->get();

        if ($studyGroups->isEmpty()) {
            $this->command->warn('Tidak ada study groups. Lewati.');

            return;
        }

        // Teachers
        $teachers = User::role(['Guru', 'Guru Tahfidz', 'Wakil Kepala Sekolah'])
            ->orderBy('name')->get();

        // Subjects
        $subjects = Subject::orderBy('name')->get()->unique('name')->values();

        // ── Teaching Assignments ────────────────────────────────────
        // Pattern: [
        //   teacher_name => [
        //     subject_name => [grade_level => hours],
        //   ]
        // ]
        $pattern = [
            'Muhammad Sidik, M. Pd.' => [
                'Pendidikan Agama Islam' => ['7' => 3, '8' => 3, '9' => 3],
                'Akhlak / Tasamuh' => ['7' => 1, '8' => 1, '9' => 1],
            ],
            'Guru8' => [
                'Bahasa Arab' => ['7' => 3, '8' => 3, '9' => 3],
                'Kitabah Khot' => ['7' => 2, '8' => 2],
            ],
            'Guru9' => [
                'Tahfidz Al-Quran' => ['7' => 4, '8' => 4, '9' => 4],
                'Hafalan Hadits' => ['7' => 2, '8' => 2, '9' => 2],
            ],
            'Muh. Husnul Fikri, M. Pd.' => [
                'Bahasa Indonesia' => ['7' => 4, '8' => 4, '9' => 4],
                'Sastra' => ['8' => 2, '9' => 2],
            ],
            'Gunawan Trianto, M. Pd.' => [
                'Matematika' => ['7' => 4, '8' => 4, '9' => 4],
                'IPA' => ['7' => 3, '8' => 3, '9' => 3],
            ],
        ];

        $created = 0;
        foreach ($pattern as $teacherName => $subjectsData) {
            $teacher = $teachers->firstWhere('name', $teacherName);
            if (! $teacher) {
                continue;
            }

            foreach ($subjectsData as $subjectName => $gradeHours) {
                $subject = $subjects->firstWhere('name', $subjectName);
                if (! $subject) {
                    continue;
                }

                foreach ($gradeHours as $gradeLevel => $hours) {
                    // Find matching study groups
                    $matchingGroups = $studyGroups->filter(fn ($sg) => ($sg->gradeLevel->level ?? 0) == $gradeLevel
                    );

                    foreach ($matchingGroups as $sg) {
                        TeachingAssignment::updateOrCreate(
                            [
                                'decree_id' => $decree->id,
                                'teacher_id' => $teacher->id,
                                'subject_id' => $subject->id,
                                'study_group_id' => $sg->id,
                            ],
                            [
                                'school_id' => $schoolId,
                                'academic_year_id' => $academicYearId,
                                'weekly_hours' => $hours,
                                'role' => 'guru_mapel',
                                'status' => 'active',
                            ]
                        );
                        $created++;
                    }
                }
            }
        }

        $this->command->info("Created/updated {$created} teaching assignments.");

        // ── GTK Additional Tasks ────────────────────────────────────
        $additionalTasks = [
            'Muhammad Sidik, M. Pd.' => [
                ['nama_tugas' => 'Kepala Sekolah', 'hours_per_week' => 0],
            ],
            'Guru8' => [
                ['nama_tugas' => 'Coordinator Bahasa Arab', 'hours_per_week' => 3],
                ['nama_tugas' => 'Wali Kelas 7', 'hours_per_week' => 3],
            ],
            'Guru9' => [
                ['nama_tugas' => 'Coordinator Guru Tahfidz', 'hours_per_week' => 4],
                ['nama_tugas' => 'Wali Kelas 8', 'hours_per_week' => 3],
            ],
            'Muh. Husnul Fikri, M. Pd.' => [
                ['nama_tugas' => 'Waka Kurikulum', 'hours_per_week' => 6],
            ],
            'Gunawan Trianto, M. Pd.' => [
                ['nama_tugas' => 'Coordinator OSIS', 'hours_per_week' => 3],
                ['nama_tugas' => 'Wali Kelas 9', 'hours_per_week' => 3],
            ],
        ];

        $createdTasks = 0;
        foreach ($additionalTasks as $teacherName => $tasks) {
            $teacher = $teachers->firstWhere('name', $teacherName);
            if (! $teacher) {
                continue;
            }

            foreach ($tasks as $task) {
                GtkAdditionalTask::updateOrCreate(
                    [
                        'user_id' => $teacher->id,
                        'nama_tugas' => $task['nama_tugas'],
                        'decree_id' => $decree->id,
                    ],
                    [
                        'hours_per_week' => $task['hours_per_week'],
                        'nomor_sk' => $decree->decree_number,
                        'tmt' => $decree->effective_date,
                    ]
                );
                $createdTasks++;
            }
        }

        $this->command->info("Created/updated {$createdTasks} GTK additional tasks.");
        $this->command->info('Demo matriks seeding complete!');
    }
}
