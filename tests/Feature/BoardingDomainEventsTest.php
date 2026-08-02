<?php

namespace Tests\Feature;

use App\Domain\Events\BoardingPermitDecided;
use App\Domain\Events\BoardingPermitSubmitted;
use App\Domain\Events\BoardingVisitCheckIn;
use App\Domain\Events\BoardingVisitDecided;
use App\Models\AcademicYear;
use App\Models\BoardingTimelineEvent;
use App\Models\Dormitory;
use App\Models\DormitoryPermit;
use App\Models\DormitoryVisitLog;
use App\Models\School;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BoardingDomainEventsTest extends TestCase
{
    use RefreshDatabase;

    private function setUpWorkUnit(): string
    {
        $id = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $id,
            'code' => 'WU-TEST-'.Str::random(4),
            'name' => 'Work Unit Test',
            'type' => 'Unit Akademik',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    private function setUpSchool(string $workUnitId): School
    {
        $schoolId = (string) Str::uuid();
        $schoolName = 'Test Madrasah '.Str::random(4);

        DB::table('schools')->insert([
            'id' => $schoolId,
            'work_unit_id' => $workUnitId,
            'npsn' => '12'.Str::random(6),
            'name' => $schoolName,
            'address' => 'Jl. Test No. 1',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return School::where('id', $schoolId)->firstOrFail();
    }

    private function setUpDormitory(School $school): array
    {
        $dormitoryId = (string) Str::uuid();
        $wingId = (string) Str::uuid();
        $roomId = (string) Str::uuid();

        DB::table('dormitories')->insert([
            'id' => $dormitoryId,
            'school_id' => $school->id,
            'work_unit_id' => $school->work_unit_id,
            'code' => 'DORM-'.Str::random(4),
            'name' => 'Asrama '.Str::random(4),
            'gender' => 'campuran',
            'address' => 'Jl. Asrama No. 1',
            'capacity' => 40,
            'total_rooms' => 10,
            'total_wings' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('dormitory_wings')->insert([
            'id' => $wingId,
            'dormitory_id' => $dormitoryId,
            'name' => 'Sayap Kiri',
            'code' => 'A',
            'floor' => 1,
            'gender' => 'putra',
            'capacity' => 20,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('dormitory_rooms')->insert([
            'id' => $roomId,
            'dormitory_id' => $dormitoryId,
            'wing_id' => $wingId,
            'code' => 'A1',
            'name' => 'Ruang A1',
            'floor' => 1,
            'gender' => 'putra',
            'capacity' => 4,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'dormitory' => Dormitory::where('id', $dormitoryId)->firstOrFail(),
            'room_id' => $roomId,
        ];
    }

    private function setUpAcademicYear(): AcademicYear
    {
        $id = (string) Str::uuid();
        DB::table('academic_years')->insert([
            'id' => $id,
            'name' => '2025/2026',
            'semester' => 'ganjil',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return AcademicYear::where('id', $id)->firstOrFail();
    }

    private function setUpStudent(School $school): Student
    {
        $studentId = (string) Str::uuid();
        DB::table('students')->insert([
            'id' => $studentId,
            'school_id' => $school->id,
            'name' => 'Fulan',
            'nisn' => '12345678',
            'email' => $studentId.'-test@test.com',
            'phone' => '081234567890',
            'gender' => 'L',
            'special_needs' => 'tidak',
            'residence_type' => 'milik_orangtua',
            'transportation' => 'jalan_kaki',
            'sibling_count' => 0,
            'is_kps_receiver' => false,
            'is_kip_receiver' => false,
            'is_pip_eligible' => false,
            'status' => 'active',
            'wali_status' => 'unlinked',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Student::where('id', $studentId)->firstOrFail();
    }

    private function setUpAdmin(): \App\Models\User
    {
        return \App\Models\User::firstOrCreate(
            ['email' => 'domain-test-'.Str::random(6).'@test.com'],
            [
                'name' => 'Domain Admin',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );
    }

    /** @test */
    public function boarding_permit_submitted_dispatches_timeline_event(): void
    {
        $wuId = $this->setUpWorkUnit();
        $school = $this->setUpSchool($wuId);
        $dorm = $this->setUpDormitory($school);
        $student = $this->setUpStudent($school);
        $academicYear = $this->setUpAcademicYear();
        $admin = $this->setUpAdmin();

        $permit = DormitoryPermit::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'dormitory_id' => $dorm['dormitory']->id,
            'room_id' => $dorm['room_id'],
            'permit_type' => 'pulang',
            'departure_datetime' => now()->addDay(),
            'expected_return_datetime' => now()->addDay()->addHours(6),
            'companion_is_mahrom' => true,
            'academic_year_id' => $academicYear->id,
            'status' => 'pending',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin, 'web');
        $before = BoardingTimelineEvent::count();

        event(new BoardingPermitSubmitted($permit));

        $this->assertGreaterThan($before, BoardingTimelineEvent::count());
        $event = BoardingTimelineEvent::orderByDesc('id')->first();
        $this->assertEquals($student->id, $event->student_id);
    }

    /** @test */
    public function boarding_permit_approved_dispatches_approval_timeline(): void
    {
        $wuId = $this->setUpWorkUnit();
        $school = $this->setUpSchool($wuId);
        $dorm = $this->setUpDormitory($school);
        $student = $this->setUpStudent($school);
        $academicYear = $this->setUpAcademicYear();
        $admin = $this->setUpAdmin();

        $permit = DormitoryPermit::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'dormitory_id' => $dorm['dormitory']->id,
            'room_id' => $dorm['room_id'],
            'permit_type' => 'pulang',
            'departure_datetime' => now()->addDay(),
            'expected_return_datetime' => now()->addDay()->addHours(6),
            'companion_is_mahrom' => true,
            'academic_year_id' => $academicYear->id,
            'status' => 'pending',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin, 'web');

        event(new BoardingPermitDecided($permit, BoardingPermitDecided::APPROVED, $admin->id));

        $event = BoardingTimelineEvent::orderByDesc('id')->first();
        $this->assertEquals(BoardingTimelineEvent::TYPE_LEAVE_APPROVED, $event->event_type);
        $this->assertEquals($student->id, $event->student_id);
    }

    /** @test */
    public function boarding_permit_rejected_dispatches_rejection_timeline(): void
    {
        $wuId = $this->setUpWorkUnit();
        $school = $this->setUpSchool($wuId);
        $dorm = $this->setUpDormitory($school);
        $student = $this->setUpStudent($school);
        $academicYear = $this->setUpAcademicYear();
        $admin = $this->setUpAdmin();

        $permit = DormitoryPermit::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'dormitory_id' => $dorm['dormitory']->id,
            'room_id' => $dorm['room_id'],
            'permit_type' => 'pulang',
            'departure_datetime' => now()->addDay(),
            'expected_return_datetime' => now()->addDay()->addHours(6),
            'companion_is_mahrom' => true,
            'academic_year_id' => $academicYear->id,
            'status' => 'pending',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin, 'web');

        event(new BoardingPermitDecided($permit, BoardingPermitDecided::REJECTED, $admin->id));

        $event = BoardingTimelineEvent::orderByDesc('id')->first();
        $this->assertEquals(BoardingTimelineEvent::TYPE_PERMIT_REJECTED, $event->event_type);
    }

    /** @test */
    public function boarding_visit_approved_dispatches_timeline(): void
    {
        $wuId = $this->setUpWorkUnit();
        $school = $this->setUpSchool($wuId);
        $dorm = $this->setUpDormitory($school);
        $student = $this->setUpStudent($school);
        $admin = $this->setUpAdmin();

        $visit = DormitoryVisitLog::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'dormitory_id' => $dorm['dormitory']->id,
            'room_id' => $dorm['room_id'],
            'visitor_name' => 'Bapak',
            'visitor_relationship' => 'wali',
            'purpose' => 'antar_jemput',
            'expected_arrival_datetime' => now()->addHour(),
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $admin->id,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin, 'web');

        event(new BoardingVisitDecided($visit, BoardingVisitDecided::APPROVED, $admin->id));

        $event = BoardingTimelineEvent::orderByDesc('id')->first();
        $this->assertEquals(BoardingTimelineEvent::TYPE_VISIT_APPROVED, $event->event_type);
    }

    /** @test */
    public function boarding_visit_rejected_no_timeline(): void
    {
        $wuId = $this->setUpWorkUnit();
        $school = $this->setUpSchool($wuId);
        $dorm = $this->setUpDormitory($school);
        $student = $this->setUpStudent($school);
        $admin = $this->setUpAdmin();

        $visit = DormitoryVisitLog::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'dormitory_id' => $dorm['dormitory']->id,
            'room_id' => $dorm['room_id'],
            'visitor_name' => 'Bapak',
            'visitor_relationship' => 'wali',
            'purpose' => 'antar_jemput',
            'expected_arrival_datetime' => now()->addHour(),
            'status' => 'rejected',
            'approved_at' => now(),
            'approved_by' => $admin->id,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin, 'web');
        $before = BoardingTimelineEvent::count();

        event(new BoardingVisitDecided($visit, BoardingVisitDecided::REJECTED, $admin->id));

        // Rejected visits should not generate timeline entries
        $this->assertEquals($before, BoardingTimelineEvent::count());
    }

    /** @test */
    public function boarding_visit_check_in_dispatches_timeline(): void
    {
        $wuId = $this->setUpWorkUnit();
        $school = $this->setUpSchool($wuId);
        $dorm = $this->setUpDormitory($school);
        $student = $this->setUpStudent($school);
        $admin = $this->setUpAdmin();

        $visit = DormitoryVisitLog::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'dormitory_id' => $dorm['dormitory']->id,
            'room_id' => $dorm['room_id'],
            'visitor_name' => 'Bapak',
            'visitor_relationship' => 'wali',
            'purpose' => 'antar_jemput',
            'expected_arrival_datetime' => now()->addHour(),
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $admin->id,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin, 'web');

        event(new BoardingVisitCheckIn($visit));

        $event = BoardingTimelineEvent::orderByDesc('id')->first();
        $this->assertEquals(BoardingTimelineEvent::TYPE_VISIT_CHECK_IN, $event->event_type);
    }
}
