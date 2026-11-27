<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TahfidzGradebook;
use App\Models\TahfidzGradebookScore;
use App\Models\TahfidzGroup;
use App\Models\TeacherAdminBook;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\Arsip\LegerPdfService;
use App\Services\TahfidzSubjectResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Render leger (layar dan PDF) untuk rombel yang berisi mapel tahfidz.
 * Format kolom tahfidz: NPT | Ziyadah | Rabt | Tikrar | STS | SAS.
 */
class TahfidzLegerRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_leger_menampilkan_satu_kolom_nr_untuk_tahfidz(): void
    {
        $unit = WorkUnit::create(['name' => 'Unit Render', 'code' => 'UT-RND', 'type' => 'Unit Akademik', 'is_active' => true]);
        $school = School::create(['work_unit_id' => $unit->id, 'npsn' => '80808092', 'name' => 'Sekolah Render']);
        $year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);
        $teacher = User::create([
            'name' => 'Guru Render', 'email' => 'guru.render@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        $grade = GradeLevel::create([
            'school_id' => $school->id, 'level' => 7, 'name' => 'Kelas 7', 'fase' => 'D', 'is_active' => true,
        ]);
        $class = StudyGroup::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'grade_level_id' => $grade->id, 'name' => '7A', 'code' => '7A', 'is_active' => true,
        ]);

        $tahfidz = Subject::create([
            'school_id' => $school->id, 'code' => 'THF', 'name' => "Tahfidzul Qur'an",
            'category' => 'nasional', 'is_active' => true,
            'penilaian_format' => TahfidzSubjectResolver::FORMAT_TAHFIDZ,
        ]);
        $umum = Subject::create([
            'school_id' => $school->id, 'code' => 'MTK', 'name' => 'Matematika',
            'category' => 'nasional', 'is_active' => true,
            'penilaian_format' => TahfidzSubjectResolver::FORMAT_UMUM,
        ]);

        foreach ([$tahfidz, $umum] as $subject) {
            TeacherAdminBook::create([
                'school_id' => $school->id, 'teacher_id' => $teacher->id,
                'subject_id' => $subject->id, 'study_group_id' => $class->id,
                'academic_year_id' => $year->id, 'semester' => 'ganjil', 'is_active' => true,
            ]);
        }

        $student = Student::create([
            'school_id' => $school->id, 'name' => 'Santri Render', 'nis' => 'RD-1', 'nisn' => 'RD-1',
            'gender' => 'L', 'status' => 'active',
        ]);
        StudentClassHistory::create([
            'student_id' => $student->id, 'study_group_id' => $class->id,
            'academic_year_id' => $year->id, 'is_active' => true,
        ]);

        $group = TahfidzGroup::create([
            'work_unit_id' => $unit->id, 'school_id' => $school->id,
            'academic_year_id' => $year->id, 'name' => 'Halaqah Render',
            'gender' => 'putra', 'teacher_id' => $teacher->id, 'is_active' => true,
        ]);
        $book = TahfidzGradebook::create([
            'school_id' => $school->id, 'tahfidz_group_id' => $group->id,
            'subject_id' => $tahfidz->id, 'academic_year_id' => $year->id, 'semester' => 'ganjil',
            'is_active' => true,
        ]);
        TahfidzGradebookScore::create([
            'tahfidz_gradebook_id' => $book->id,
            'student_id' => $student->id, 'study_group_id' => $class->id,
            'ziyadah' => 88, 'rabt' => 76, 'sts' => 82, 'sas' => 90, 'nr_final' => 86,
        ]);

        $data = app(LegerPdfService::class)->buildData($school->id, $class, $year, $year->id, 'ganjil', 'sts');

        // Mapel tahfidz tidak lagi punya kolom sendiri di leger.
        $this->assertArrayNotHasKey('tahfidzColumns', $data);

        $html = view('nilai-kelas.leger-cetak', $data + ['userId' => null])->render();

        foreach (['NPT', 'Ziyadah', 'Rabt', 'Tikrar'] as $label) {
            $this->assertStringNotContainsString('>'.$label.'</th>', $html, "Kolom {$label} tidak boleh muncul di leger.");
        }

        // Satu slot nilai per mapel; mapel umum yang belum punya nilai adalah
        // sel kosong (class="empty"), tahfidz terisi NR Final.
        $slots = substr_count($html, 'class="comp') + substr_count($html, 'class="below') + substr_count($html, 'class="empty');
        $this->assertSame(1, $slots, 'Leger hanya memuat satu sel nilai milik mapel umum (tahfidz terisi).');
        // Nilai tahfidz di leger = NR Final.
        $pos = strpos($html, 'Santri Render');
        $pos = strpos($html, 'Santri Render');
        $rowStart = strrpos(substr($html, 0, $pos), '<tr>');
        $rowHtml = substr($html, $rowStart);
        $rowHtml = substr($rowHtml, 0, strpos($rowHtml, '</tr>'));
        $this->assertStringContainsString('86', $rowHtml, 'NR Final tahfidz harus tampil di baris santri.');
    }
}
