<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoom;
use App\Models\DormitoryWing;
use App\Models\School;
use App\Models\Student;
use App\Services\StudentLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudentLookupServiceTest extends TestCase
{
    use RefreshDatabase;

    private function setUpSchool(): School
    {
        return School::create([
            'id' => (string) Str::uuid(),
            'name' => 'Test Madrasah',
            'address' => 'Jl. Test No. 1',
        ]);
    }

    private function setUpDormitory(School $school): Dormitory
    {
        $dormitory = Dormitory::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'name' => 'Asrama A',
            'address' => 'Jl. Asrama No. 1',
            'status' => 'active',
        ]);

        $wing = DormitoryWing::create([
            'id' => (string) Str::uuid(),
            'dormitory_id' => $dormitory->id,
            'name' => 'Sayap Kiri',
            'code' => 'A',
            'is_active' => true,
        ]);

        DormitoryRoom::create([
            'id' => (string) Str::uuid(),
            'dormitory_id' => $dormitory->id,
            'wing_id' => $wing->id,
            'code' => 'A1',
            'name' => 'Ruang A1',
            'capacity' => 4,
            'room_type' => 'L',
            'is_active' => true,
        ]);

        return $dormitory;
    }

    private function setUpAcademicYear(): AcademicYear
    {
        return AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2025/2026',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);
    }

    private function setUpStudent(School $school): Student
    {
        return Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'name' => 'Fulan',
            'nisn' => '12345678',
            'nis' => 'NIS001',
            'nik' => '3201012345678901',
            'gender' => 'L',
            'religion' => 'Islam',
            'birth_place' => 'Jakarta',
            'birth_date' => '2008-05-15',
            'status' => 'active',
            'full_address' => 'Jl. Mawar No. 10',
            'phone' => '081234567890',
            'email' => 'ahmad@test.com',
            'entry_date' => '2024-07-01',
            'entry_grade_level' => '2',
            'graduation_year' => 2027,
        ]);
    }

    private function getService(): StudentLookupService
    {
        return app(StudentLookupService::class);
    }

    // ─── search ──────────────────────────────────────────────

    /** @test */
    public function search_returns_matching_students(): void
    {
        $school = $this->setUpSchool();
        $student = $this->setUpStudent($school);
        $this->setUpAcademicYear();

        $service = $this->getService();

        Cache::flush();

        $results = $service->search('Ahmad', $school->id.'.dormitorys.1');

        $this->assertCount(1, $results);
        $this->assertEquals('Fulan', $results->first()->name);
    }

    /** @test */
    public function search_filters_by_nisn(): void
    {
        $school = $this->setUpSchool();
        $this->setUpStudent($school);
        $this->setUpAcademicYear();

        $service = $this->getService();
        Cache::flush();

        $results = $service->search('12345678');

        $this->assertCount(1, $results);
    }

    /** @test */
    public function search_excludes_inactive_students(): void
    {
        $school = $this->setUpSchool();
        Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'name' => 'Graduated Student',
            'nisn' => '87654321',
            'gender' => 'L',
            'status' => 'graduate',
            'full_address' => 'Jl. Test',
            'entry_date' => '2024-07-01',
        ]);
        $this->setUpAcademicYear();

        $service = $this->getService();
        Cache::flush();

        $results = $service->search('Graduated');

        $this->assertEmpty($results);
    }

    // ─── getProfile ──────────────────────────────────────────

    /** @test */
    public function get_profile_returns_student_data(): void
    {
        $school = $this->setUpSchool();
        $student = $this->setUpStudent($school);
        $this->setUpAcademicYear();

        $profile = $this->getService()->getProfile($student->id);

        $this->assertNotNull($profile);
        $this->assertEquals('Fulan', $profile->name);
        $this->assertEquals('12345678', $profile->nisn);
        $this->assertEquals('Jakarta', $profile->birth_place);
        $this->assertEquals('active', $profile->status);
    }

    /** @test */
    public function get_profile_returns_null_for_unknown_student(): void
    {
        $profile = $this->getService()->getProfile((string) Str::uuid());
        $this->assertNull($profile);
    }

    // ─── validateAssignment ──────────────────────────────────

    /** @test */
    public function validate_assignment_passes_for_active_student(): void
    {
        $school = $this->setUpSchool();
        $student = $this->setUpStudent($school);
        $dormitory = $this->setUpDormitory($school);
        $this->setUpAcademicYear();

        $validation = $this->getService()->validateAssignment($student->id, $dormitory->id, null);

        $this->assertTrue($validation->valid);
        $this->assertNull($validation->error);
    }

    /** @test */
    public function validate_assignment_fails_for_graduate_student(): void
    {
        $school = $this->setUpSchool();
        $student = $this->setUpStudent($school);
        $student->update(['status' => 'graduate']);
        $dormitory = $this->setUpDormitory($school);
        $this->setUpAcademicYear();

        $validation = $this->getService()->validateAssignment($student->id, $dormitory->id, null);

        $this->assertFalse($validation->valid);
        $this->assertStringContainsString('graduate', strtolower($validation->error));
    }

    /** @test */
    public function validate_assignment_fails_if_student_already_assigned(): void
    {
        $school = $this->setUpSchool();
        $student = $this->setUpStudent($school);
        $dormitory = $this->setUpDormitory($school);
        $room = \App\Models\DormitoryRoom::where('dormitory_id', $dormitory->id)->first();
        $year = $this->setUpAcademicYear();

        DormitoryResident::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'dormitory_id' => $dormitory->id,
            'room_id' => $room->id,
            'academic_year_id' => $year->id,
            'bed_number' => 1,
            'is_active' => true,
            'check_in_date' => now(),
        ]);

        $validation = $this->getService()->validateAssignment($student->id, $dormitory->id, $year->id);

        $this->assertFalse($validation->valid);
        $this->assertStringContainsString('sudah terdaftar', $validation->error);
    }

    /** @test */
    public function validate_assignment_fails_for_unknown_student(): void
    {
        $dormitory = $this->setUpDormitory($this->setUpSchool());

        $validation = $this->getService()->validateAssignment((string) Str::uuid(), $dormitory->id, null);

        $this->assertFalse($validation->valid);
        $this->assertStringContainsString('tidak ditemukan', $validation->error);
    }

    // ─── getAllActiveAssignments ─────────────────────────────

    /** @test */
    public function get_all_active_assignments_returns_assigned_dormitories(): void
    {
        $school = $this->setUpSchool();
        $student = $this->setUpStudent($school);
        $dormitory = $this->setUpDormitory($school);
        $room = \App\Models\DormitoryRoom::where('dormitory_id', $dormitory->id)->first();
        $year = $this->setUpAcademicYear();

        DormitoryResident::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'dormitory_id' => $dormitory->id,
            'room_id' => $room->id,
            'academic_year_id' => $year->id,
            'bed_number' => 1,
            'is_active' => true,
            'check_in_date' => now(),
        ]);

        $assignments = $this->getService()->getAllActiveAssignments($student->id);

        $this->assertCount(1, $assignments);
        $this->assertEquals($dormitory->id, $assignments->first()->dormitory_id);
    }

    /** @test */
    public function get_all_active_assignments_returns_empty_for_unassigned_student(): void
    {
        $school = $this->setUpSchool();
        $student = $this->setUpStudent($school);
        $this->setUpAcademicYear();

        $assignments = $this->getService()->getAllActiveAssignments($student->id);
        $this->assertEmpty($assignments);
    }

    // ─── room helpers ────────────────────────────────────────

    /** @test */
    public function room_belongs_to_dormitory(): void
    {
        $school = $this->setUpSchool();
        $dormitory = $this->setUpDormitory($school);
        $room = \App\Models\DormitoryRoom::where('dormitory_id', $dormitory->id)->first();

        $this->assertTrue($this->getService()->roomBelongsToDormitory($room->id, $dormitory->id));
        $this->assertFalse($this->getService()->roomBelongsToDormitory($room->id, (string) Str::uuid()));
    }

    /** @test */
    public function get_available_rooms_filters_by_capacity(): void
    {
        $school = $this->setUpSchool();
        $dormitory = $this->setUpDormitory($school);
        $student = $this->setUpStudent($school);
        $room = \App\Models\DormitoryRoom::where('dormitory_id', $dormitory->id)->first();
        $year = $this->setUpAcademicYear();

        // Fill the room to capacity (4)
        for ($i = 0; $i < 4; $i++) {
            DormitoryResident::create([
                'id' => (string) Str::uuid(),
                'student_id' => (string) Str::uuid(),
                'dormitory_id' => $dormitory->id,
                'room_id' => $room->id,
                'academic_year_id' => $year->id,
                'bed_number' => $i + 1,
                'is_active' => true,
                'check_in_date' => now(),
            ]);
        }

        $available = $this->getService()->getAvailableRooms($dormitory->id);
        // Room is full (occupancy == capacity), should not appear
        $this->assertCount(0, $available);
    }
}
