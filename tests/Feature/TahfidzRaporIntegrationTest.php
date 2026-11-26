<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\NilaiSumatif;
use App\Models\School;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TahfidzGroup;
use App\Models\TahfidzJuzProgress;
use App\Models\TahfidzSetoran;
use App\Models\TeacherAdminBook;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\TahfidzRaporService;
use App\Services\TahfidzSubjectResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TahfidzRaporIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_nilai_rapor_thf_berasal_dari_buku_halaqah_bukan_setoran(): void
    {
        $unit = WorkUnit::create(['name' => 'Sekolah Rapor', 'code' => 'SCH-RAP', 'type' => 'Unit Akademik', 'is_active' => true]);
        $school = School::create(['work_unit_id' => $unit->id, 'npsn' => '70707071', 'name' => 'Sekolah Rapor']);
        $year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);
        $teacher = User::create([
            'name' => 'Guru Rapor', 'email' => 'guru.rapor@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
        $student = Student::create([
            'school_id' => $school->id, 'name' => 'Santri Rapor', 'nis' => 'RP-1', 'nisn' => 'RP-1',
            'gender' => 'L', 'status' => 'active',
        ]);
        $subject = Subject::create([
            'school_id' => $school->id, 'code' => 'THF', 'name' => "Tahfidzul Qur'an",
            'category' => 'nasional', 'is_active' => true,
            'penilaian_format' => TahfidzSubjectResolver::FORMAT_TAHFIDZ,
        ]);
        $group = TahfidzGroup::create([
            'work_unit_id' => $unit->id, 'school_id' => $school->id,
            'academic_year_id' => $year->id, 'name' => 'Halaqah Rapor',
            'gender' => 'putra', 'teacher_id' => $teacher->id, 'is_active' => true,
        ]);
        $grade = GradeLevel::create([
            'school_id' => $school->id, 'level' => 7, 'name' => 'Kelas 7',
            'fase' => 'D', 'is_active' => true,
        ]);
        $studyGroup = StudyGroup::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'grade_level_id' => $grade->id, 'name' => '7A', 'code' => '7A', 'is_active' => true,
        ]);
        $book = TeacherAdminBook::create([
            'teacher_id' => $teacher->id, 'subject_id' => $subject->id,
            'study_group_id' => $studyGroup->id,
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'semester' => 'ganjil', 'is_active' => true,
            'nr_final_weight_rs' => 50, 'nr_final_weight_sts' => 25, 'nr_final_weight_sas' => 25,
        ]);

        TahfidzSetoran::create([
            'student_id' => $student->id, 'teacher_id' => $teacher->id,
            'tahfidz_group_id' => $group->id, 'academic_year_id' => $year->id,
            'setoran_date' => '2026-09-15', 'setoran_type' => 'ziyadah',
            'nilai_setoran' => 70, 'status' => 'lulus', 'verification_status' => 'verified',
        ]);
        NilaiSumatif::create([
            'admin_book_id' => $book->id, 'student_id' => $student->id,
            'academic_year_id' => $year->id, 'semester' => 'ganjil', 'sts' => 80, 'sas' => 90,
        ]);
        TahfidzJuzProgress::create([
            'student_id' => $student->id, 'academic_year_id' => $year->id, 'juz_number' => 1,
            'status' => 'selesai_ziyadah', 'halaman_completed' => 20, 'total_halaman_juz' => 20, 'percentage' => 100,
        ]);
        TahfidzJuzProgress::create([
            'student_id' => $student->id, 'academic_year_id' => $year->id, 'juz_number' => 2,
            'status' => 'sudah_tasmi', 'halaman_completed' => 20, 'total_halaman_juz' => 20, 'percentage' => 100,
        ]);

        $service = app(TahfidzRaporService::class);

        // Tanpa buku halaqah, rapor tahfidz tidak menebak dari setoran.
        $this->assertNull(
            $service->scoreFor($student->id, $book, $year->id, 'ganjil', 'sas', null),
            'Nilai rapor tahfidz hanya berasal dari buku halaqah.',
        );

        // Buku nilai halaqah: ziyadah 80, rabt 70, STS 90, SAS 85
        // NR Final = (80×40 + 70×20 + 90×20 + 85×20)/100 = 81
        $group->members()->create([
            'student_id' => $student->id, 'academic_year_id' => $year->id,
            'status' => 'aktif', 'join_date' => '2026-07-15',
        ]);

        $gradebook = \App\Models\TahfidzGradebook::create([
            'tahfidz_group_id' => $group->id, 'subject_id' => $subject->id,
            'academic_year_id' => $year->id, 'school_id' => $school->id, 'semester' => 'ganjil',
            'weight_ziyadah' => 40, 'weight_rabt' => 20, 'weight_sts' => 20, 'weight_sas' => 20,
        ]);
        \App\Models\TahfidzGradebookScore::create([
            'tahfidz_gradebook_id' => $gradebook->id, 'student_id' => $student->id,
            'study_group_id' => $studyGroup->id,
            'ziyadah' => 80, 'rabt' => 70, 'sts' => 90, 'sas' => 85, 'nr_final' => 81,
        ]);

        $this->assertSame(81.0, $service->scoreFor($student->id, $book, $year->id, 'ganjil', 'sas', null));
        $this->assertSame(90.0, $service->scoreFor($student->id, $book, $year->id, 'ganjil', 'sts', null));
        $this->assertSame(2, $service->jumlahJuz($student->id, $year->id));
    }
}
