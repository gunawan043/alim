<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\RaportRegistration;
use App\Models\Student;
use App\Models\StudentAbsence;
use App\Models\StudyGroup;
use App\Services\AcademicProvisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AcademicProvisionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function seedFixture(): array
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Test Work Unit',
            'code' => 'TWU',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $schoolId = (string) Str::uuid();
        DB::table('schools')->insert([
            'id' => $schoolId,
            'work_unit_id' => $workUnitId,
            'name' => 'Test School',
            'npsn' => '1234567890',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $student = Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $schoolId,
            'name' => 'Test Student',
            'nisn' => '0012345678',
            'gender' => 'L',
        ]);

        $academicYear = AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $gradeLevelId = (string) Str::uuid();
        DB::table('grade_levels')->insert([
            'id' => $gradeLevelId,
            'school_id' => $schoolId,
            'name' => 'X',
            'code' => 'X',
            'level' => 10,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $studyGroup = StudyGroup::create([
            'id' => (string) Str::uuid(),
            'school_id' => $schoolId,
            'academic_year_id' => $academicYear->id,
            'grade_level_id' => $gradeLevelId,
            'name' => 'IPA 1',
            'code' => 'X-IPA-1',
            'capacity' => 32,
            'curriculum_type' => 'merdeka',
            'shift' => 'pagi',
            'is_active' => true,
        ]);

        // Insert a teacher_admin_book fixture
        $subjectId = (string) Str::uuid();
        DB::table('subjects')->insert([
            'id' => $subjectId,
            'school_id' => $schoolId,
            'code' => 'MTK',
            'name' => 'Matematika',
            'category' => 'nasional',
            'credit_hours' => 4,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $teacherId = (string) Str::uuid();
        DB::table('users')->insert([
            'id' => $teacherId,
            'name' => 'Teacher',
            'email' => 'teacher@test.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bookId = (string) Str::uuid();
        DB::table('teacher_admin_books')->insert([
            'id' => $bookId,
            'teacher_id' => $teacherId,
            'subject_id' => $subjectId,
            'study_group_id' => $studyGroup->id,
            'school_id' => $schoolId,
            'academic_year_id' => $academicYear->id,
            'semester' => 'ganjil',
            'is_active' => true,
            'nr_final_weight_rs' => 40.0,
            'nr_final_weight_sts' => 40.0,
            'nr_final_weight_sas' => 20.0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'student_id' => $student->id,
            'study_group_id' => $studyGroup->id,
            'academic_year_id' => $academicYear->id,
            'semester' => 'ganjil',
            'student' => $student,
            'study_group' => $studyGroup,
            'academic_year' => $academicYear,
            'book_id' => $bookId,
        ];
    }

    /** @test */
    public function it_creates_student_absence_and_rapor_and_nilai_sumatif(): void
    {
        $fixture = $this->seedFixture();

        $service = new AcademicProvisionService(
            $fixture['student_id'],
            $fixture['study_group_id'],
            $fixture['academic_year_id'],
            now()->toDateString(),
            $fixture['semester'],
        );

        $result = $service->provision();

        $this->assertEquals(1, $result['student_absence']);
        $this->assertEquals(1, $result['raport_registrations']);
        $this->assertEquals(1, $result['nilai_sumatif']);

        $this->assertDatabaseHas('student_absences', [
            'student_id' => $fixture['student_id'],
            'study_group_id' => $fixture['study_group_id'],
            'academic_year_id' => $fixture['academic_year_id'],
            'semester' => $fixture['semester'],
            'enrollment_status' => 'active',
        ]);

        $this->assertDatabaseHas('raport_registrations', [
            'student_id' => $fixture['student_id'],
            'study_group_id' => $fixture['study_group_id'],
            'academic_year_id' => $fixture['academic_year_id'],
            'semester' => $fixture['semester'],
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('admin_nilai_sumatif', [
            'student_id' => $fixture['student_id'],
            'admin_book_id' => $fixture['book_id'],
            'academic_year_id' => $fixture['academic_year_id'],
            'semester' => $fixture['semester'],
        ]);
    }

    /** @test */
    public function it_is_idempotent_on_second_provision(): void
    {
        $fixture = $this->seedFixture();

        $service = new AcademicProvisionService(
            $fixture['student_id'],
            $fixture['study_group_id'],
            $fixture['academic_year_id'],
            now()->toDateString(),
            $fixture['semester'],
        );

        // First run — creates data
        $result1 = $service->provision();
        $this->assertEquals(1, $result1['student_absence']);
        $this->assertEquals(1, $result1['raport_registrations']);
        $this->assertEquals(1, $result1['nilai_sumatif']);

        // Second run — no duplicates
        $result2 = $service->provision();
        $this->assertEquals(0, $result2['student_absence']);
        $this->assertEquals(0, $result2['raport_registrations']);
        $this->assertEquals(0, $result2['nilai_sumatif']);

        // Exactly one row for each
        $this->assertEquals(1, StudentAbsence::where('student_id', $fixture['student_id'])->count());
        $this->assertEquals(1, RaportRegistration::where('student_id', $fixture['student_id'])->count());
        $this->assertEquals(1, DB::table('admin_nilai_sumatif')
            ->where('student_id', $fixture['student_id'])->count());
    }
}
