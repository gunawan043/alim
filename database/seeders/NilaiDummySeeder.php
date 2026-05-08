<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeacherAdminBook;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NilaiDummySeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Subjects (SD IT level) ──────────────────────────────────────
        $subjects = [
            ['code' => 'PAI',  'name' => 'Pendidikan Agama Islam',                  'category' => 'nasional'],
            ['code' => 'PK',   'name' => 'Pendidikan Kewarganegaraan',               'category' => 'nasional'],
            ['code' => 'BI',   'name' => 'Bahasa Indonesia',                         'category' => 'nasional'],
            ['code' => 'BING', 'name' => 'Bahasa Inggris',                           'category' => 'nasional'],
            ['code' => 'MTK',  'name' => 'Matematika',                                'category' => 'nasional'],
            ['code' => 'IPA',  'name' => 'Ilmu Pengetahuan Alam',                    'category' => 'nasional'],
            ['code' => 'IPS',  'name' => 'Ilmu Pengetahuan Sosial',                  'category' => 'nasional'],
            ['code' => 'PJOK', 'name' => 'Pendidikan Jasmani Olahraga dan Kesehatan', 'category' => 'nasional'],
            ['code' => 'SBdP', 'name' => 'Seni Budaya dan Prakarya',                  'category' => 'nasional'],
            ['code' => 'INF',  'name' => 'Informatika',                               'category' => 'nasional'],
            ['code' => 'BQ',   'name' => 'Bahasa Arab',                              'category' => 'muatan_lokal'],
            ['code' => 'THF',  'name' => 'Tahfidz Al-Quran',                        'category' => 'muatan_lokal'],
        ];

        // Sekolah: SD IT Putra (satu-satunya yang punya siswa)
        $school = \App\Models\School::where('name', 'like', '%SD IT Putra Abu Hurairah Mataram%')->first();
        if (!$school) {
            $this->command?->error('School SD IT Putri tidak ditemukan.');
            return;
        }

        $subjectIds = [];
        foreach ($subjects as $sub) {
            $subject = Subject::firstOrCreate(
                ['school_id' => $school->id, 'code' => $sub['code']],
                [
                    'name'        => $sub['name'],
                    'category'    => $sub['category'],
                    'credit_hours'=> 4,
                    'is_active'   => true,
                ]
            );
            $subjectIds[$sub['code']] = $subject->id;
            echo "  Subject: {$sub['name']}\n";
        }

        // ── 2. Academic Year terbaru ─────────────────────────────────────────
        $ay = AcademicYear::orderByDesc('name')->first();
        echo "  Academic Year: {$ay->name}\n";

        // ── 3. Study Groups SD IT Putri ─────────────────────────────────────
        $studyGroups = StudyGroup::where('school_id', $school->id)
            ->where('academic_year_id', $ay->id)
            ->where('is_active', true)
            ->get();

        if ($studyGroups->isEmpty()) {
            $this->command?->warn('Tidak ada study group untuk TA ini, coba TA sebelumnya...');
            $ay = AcademicYear::orderByDesc('name')->skip(1)->first();
            $studyGroups = StudyGroup::where('school_id', $school->id)
                ->where('academic_year_id', $ay->id)
                ->where('is_active', true)
                ->get();
            echo "  Academic Year (fallback): {$ay->name}\n";
        }

        // ── 4. User guru dari GtkWorkUnit SD IT Putri ───────────────────────
        $workUnit = \App\Models\WorkUnit::where('name', 'like', '%SD IT Putra Abu Hurairah%')->first();
        $teacherUserIds = $workUnit
            ? \App\Models\GtkWorkUnit::where('work_unit_id', $workUnit->id)->pluck('user_id')->unique()->toArray()
            : [];

        $teachers = $teacherUserIds
            ? User::whereIn('id', $teacherUserIds)->get()
            : User::where('name', 'not like', '%Admin%')->where('name', 'not like', '%TU%')->limit(3)->get();

        if ($teachers->isEmpty()) {
            $teachers = User::limit(3)->get();
        }
        echo "  Teachers: {$teachers->count()}\n";

        // ── 5. Semester ──────────────────────────────────────────────────────
        $semester = now()->month >= 7 ? 'ganjil' : 'genap';
        echo "  Semester: {$semester} (bulan: " . now()->month . ")\n";

        // ── 6. TeacherAdminBook ─────────────────────────────────────────────
        $count = 0;
        foreach ($studyGroups->take(3) as $studyGroup) {
            // Cek siswa di kelas ini
            $siswaCount = StudentClassHistory::where('study_group_id', $studyGroup->id)
                ->where('academic_year_id', $ay->id)
                ->where('is_active', true)
                ->count();

            if ($siswaCount == 0) {
                echo "  Skip {$studyGroup->name}: belum ada siswa\n";
                continue;
            }

            foreach ($teachers as $teacher) {
                foreach ($subjectIds as $subjectId) {
                    $exists = TeacherAdminBook::where('teacher_id', $teacher->id)
                        ->where('subject_id', $subjectId)
                        ->where('study_group_id', $studyGroup->id)
                        ->where('academic_year_id', $ay->id)
                        ->where('semester', $semester)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    TeacherAdminBook::create([
                        'id'               => (string) Str::uuid(),
                        'teacher_id'       => $teacher->id,
                        'subject_id'       => $subjectId,
                        'study_group_id'   => $studyGroup->id,
                        'school_id'        => $school->id,
                        'academic_year_id' => $ay->id,
                        'semester'         => $semester,
                        'is_active'        => true,
                    ]);
                    $count++;
                    echo "  ✓ {$teacher->name} → {$studyGroup->name} / ID:{$subjectId} (semester {$semester})\n";
                }
            }
        }

        echo "\nTotal TeacherAdminBook dibuat: {$count}\n";
    }
}
