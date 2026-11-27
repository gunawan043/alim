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
use App\Models\TahfidzGroup;
use App\Models\TahfidzSetoran;
use App\Models\TahfidzStudentTarget;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\TahfidzLegerColumns;
use App\Services\TahfidzSubjectResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Leger tahfidz memakai kolom sendiri: NPT | Ziyadah | Rabt | Tikrar | STS | SAS.
 *
 * Baris terakhir juga dijaga: nilai yang tampil harus berasal dari buku
 * halaqah (bukan komponen STS/SAS mapel umum), NPT dari capaian target, dan
 * Tikrar dari rata-rata nilai tikrar setoran semester berjalan.
 */
class TahfidzLegerColumnsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private StudyGroup $class;

    protected function setUp(): void
    {
        parent::setUp();

        $unit = WorkUnit::create(['name' => 'Unit Leger', 'code' => 'UT-LGR', 'type' => 'Unit Akademik', 'is_active' => true]);
        $this->school = School::create(['work_unit_id' => $unit->id, 'npsn' => '80808091', 'name' => 'Sekolah Leger']);
        $this->year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);

        $grade = GradeLevel::firstOrCreate(
            ['school_id' => $this->school->id, 'level' => 7],
            ['name' => 'Kelas 7', 'fase' => 'D', 'is_active' => true],
        );

        $this->class = StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'grade_level_id' => $grade->id, 'name' => '7A', 'code' => '7A', 'is_active' => true,
        ]);
    }

    public function test_kolom_leger_tahfidz_berurutan_npt_ziyadah_rabt_tikrar_sts_sas(): void
    {
        $this->assertSame(
            ['npt', 'ziyadah', 'rabt', 'tikrar', 'sts', 'sas'],
            array_keys(TahfidzLegerColumns::COLUMNS),
        );
        $this->assertSame(
            ['NPT', 'Ziyadah', 'Rabt', 'Tikrar', 'STS', 'SAS'],
            array_values(TahfidzLegerColumns::COLUMNS),
        );
    }

    public function test_nilai_leger_diambil_dari_buku_halaqah_target_dan_setoran(): void
    {
        $subject = Subject::create([
            'school_id' => $this->school->id, 'code' => 'THF', 'name' => "Tahfidzul Qur'an",
            'category' => 'nasional', 'is_active' => true,
            'penilaian_format' => TahfidzSubjectResolver::FORMAT_TAHFIDZ,
        ]);

        $student = $this->makeStudent('Santri Leger', 'LG-1');

        $group = TahfidzGroup::create([
            'work_unit_id' => $this->school->work_unit_id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Halaqah Leger', 'gender' => 'putra', 'is_active' => true,
            'teacher_id' => $this->makeTeacher()->id,
        ]);

        $book = TahfidzGradebook::create([
            'school_id' => $this->school->id,
            'tahfidz_group_id' => $group->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $this->year->id,
            'semester' => 'ganjil',
            'weight_ziyadah' => 40, 'weight_rabt' => 20, 'weight_sts' => 20, 'weight_sas' => 20,
            'is_active' => true,
        ]);

        $book->scores()->create([
            'student_id' => $student->id, 'study_group_id' => $this->class->id,
            'ziyadah' => 88, 'rabt' => 76, 'sts' => 82, 'sas' => 90, 'nr_final' => 86,
        ]);

        // Target 20 halaman, setoran ziyadah 10 halaman → NPT 50.
        TahfidzStudentTarget::create([
            'student_id' => $student->id,
            'tahfidz_group_id' => $group->id,
            'academic_year_id' => $this->year->id,
            'semester' => 'ganjil',
            'target_bulan' => 1,
            'target_halaman' => 20,
            'assigned_by' => $this->makeTeacher()->id,
        ]);

        TahfidzSetoran::create([
            'student_id' => $student->id, 'tahfidz_group_id' => $group->id,
            'academic_year_id' => $this->year->id,
            'teacher_id' => $this->makeTeacher()->id,
            'setoran_date' => '2026-08-10', 'setoran_type' => 'ziyadah',
            'jumlah_halaman' => 10, 'status' => 'lulus',
        ]);

        // Dua tikrar: 80 dan 90 → rata-rata 85.
        foreach (['2026-08-11' => 80, '2026-08-12' => 90] as $date => $nilai) {
            TahfidzSetoran::create([
                'student_id' => $student->id, 'tahfidz_group_id' => $group->id,
                'academic_year_id' => $this->year->id,
                'teacher_id' => $this->makeTeacher()->id,
                'setoran_date' => $date, 'setoran_type' => 'tikror',
                'nilai_tikrar' => $nilai, 'status' => 'lulus',
            ]);
        }

        $values = app(TahfidzLegerColumns::class)->valuesFor(
            collect([$book]),
            $book->scores()->get(),
            collect([$student->id]),
            $this->year,
            'ganjil',
        );

        $row = $values[$student->id][$book->id];

        $this->assertEqualsWithDelta(50.0, $row['npt'], 0.01, 'NPT = 10/20 halaman × 100.');
        $this->assertEqualsWithDelta(88.0, $row['ziyadah'], 0.01);
        $this->assertEqualsWithDelta(76.0, $row['rabt'], 0.01);
        $this->assertEqualsWithDelta(85.0, $row['tikrar'], 0.01, 'Tikrar = rata-rata nilai tikrar setoran.');
        $this->assertEqualsWithDelta(82.0, $row['sts'], 0.01);
        $this->assertEqualsWithDelta(90.0, $row['sas'], 0.01);
    }

    public function test_npt_dibatasi_seratus_dan_kosong_tanpa_target(): void
    {
        $subject = Subject::create([
            'school_id' => $this->school->id, 'code' => 'THF', 'name' => "Tahfidzul Qur'an",
            'category' => 'nasional', 'is_active' => true,
            'penilaian_format' => TahfidzSubjectResolver::FORMAT_TAHFIDZ,
        ]);

        $group = TahfidzGroup::create([
            'work_unit_id' => $this->school->work_unit_id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Halaqah NPT', 'gender' => 'putra', 'is_active' => true,
            'teacher_id' => $this->makeTeacher()->id,
        ]);

        $book = TahfidzGradebook::create([
            'school_id' => $this->school->id,
            'tahfidz_group_id' => $group->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $this->year->id,
            'semester' => 'ganjil',
            'weight_ziyadah' => 40, 'weight_rabt' => 20, 'weight_sts' => 20, 'weight_sas' => 20,
            'is_active' => true,
        ]);

        $employee = $this->makeTeacher();
        $lampaui = $this->makeStudent('Santri Lampaui Target', 'LG-2');
        $tanpaTarget = $this->makeStudent('Santri Tanpa Target', 'LG-3');

        foreach ([$lampaui, $tanpaTarget] as $student) {
            TahfidzSetoran::create([
                'student_id' => $student->id, 'tahfidz_group_id' => $group->id,
                'academic_year_id' => $this->year->id,
                'teacher_id' => $employee->id,
                'setoran_date' => '2026-08-10', 'setoran_type' => 'ziyadah',
                'jumlah_halaman' => 30, 'status' => 'lulus',
            ]);
        }

        TahfidzStudentTarget::create([
            'student_id' => $lampaui->id,
            'tahfidz_group_id' => $group->id,
            'academic_year_id' => $this->year->id,
            'semester' => 'ganjil',
            'target_bulan' => 1,
            'target_halaman' => 20,
            'assigned_by' => $employee->id,
        ]);

        $values = app(TahfidzLegerColumns::class)->valuesFor(
            collect([$book]),
            collect(),
            collect([$lampaui->id, $tanpaTarget->id]),
            $this->year,
            'ganjil',
        );

        $this->assertEqualsWithDelta(100.0, $values[$lampaui->id][$book->id]['npt'], 0.01);
        $this->assertNull($values[$tanpaTarget->id][$book->id]['npt'], 'Tanpa target, NPT tidak dikarang.');
    }

    private function makeStudent(string $name, string $nis): Student
    {
        $student = Student::create([
            'school_id' => $this->school->id, 'name' => $name, 'nis' => $nis, 'nisn' => $nis,
            'gender' => 'L', 'status' => 'active',
        ]);

        StudentClassHistory::create([
            'student_id' => $student->id, 'study_group_id' => $this->class->id,
            'academic_year_id' => $this->year->id, 'is_active' => true,
        ]);

        return $student;
    }

    private function makeTeacher(): User
    {
        return User::firstOrCreate(
            ['email' => 'guru.leger@test.local'],
            ['name' => 'Guru Leger', 'password' => bcrypt('password'), 'is_active' => true],
        );
    }
}
