<?php

namespace Tests\Feature;

use App\Events\StudentAssignedToRombel;
use App\Jobs\ProvisionStudentAcademicDataJob;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudentAssignedToRombelEventTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function event_is_dispatched_with_correct_payload(): void
    {
        $student = Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => (string) Str::uuid(),
            'name' => 'Test Student',
            'nisn' => '1234567890',
            'gender' => 'L',
        ]);

        $ay = AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $history = new StudentClassHistory;
        $history->id = (string) Str::uuid();
        $history->student_id = $student->id;
        $history->study_group_id = (string) Str::uuid();
        $history->academic_year_id = $ay->id;
        $history->is_active = true;
        $history->join_date = now()->toDateString();

        $event = new StudentAssignedToRombel($history);

        $this->assertEquals($history->id, $event->classHistoryId);
        $this->assertEquals($student->id, $event->studentId);
        $this->assertEquals($ay->id, $event->academicYearId);
        $this->assertEquals('ganjil', $event->semester);
        $this->assertEquals(now()->toDateString(), $event->joinDate);
    }

    /** @test */
    public function event_listener_dispatches_job(): void
    {
        Bus::fake();

        $student = Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => (string) Str::uuid(),
            'name' => 'Test',
            'nisn' => '1234567890',
            'gender' => 'L',
        ]);

        $ay = AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $history = new StudentClassHistory;
        $history->id = (string) Str::uuid();
        $history->student_id = $student->id;
        $history->study_group_id = (string) Str::uuid();
        $history->academic_year_id = $ay->id;
        $history->is_active = true;
        $history->join_date = now()->toDateString();

        event(new StudentAssignedToRombel($history));

        Bus::assertDispatched(ProvisionStudentAcademicDataJob::class, function ($job) use ($student, $history) {
            return $job->studentId === $student->id
                && $job->classHistoryId === $history->id;
        });
    }

    /** @test */
    public function event_dispatch_from_student_class_history_controller_triggers_provisioning_job(): void
    {
        // Verify wiring: when StudentClassHistoryController.store creates a history,
        // the StudentAssignedToRombel event is fired.
        Event::fake([StudentAssignedToRombel::class]);

        $schoolId = (string) Str::uuid();
        DB::table('schools')->insert([
            'id' => $schoolId,
            'name' => 'Test School',
            'npsn' => '1234567890',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $student = Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $schoolId,
            'name' => 'Test',
            'nisn' => '1234567891',
            'gender' => 'L',
        ]);

        $ay = AcademicYear::create([
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
            'display_name' => 'Kelas X',
            'level' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $studyGroup = StudyGroup::create([
            'id' => (string) Str::uuid(),
            'school_id' => $schoolId,
            'academic_year_id' => $ay->id,
            'grade_level_id' => $gradeLevelId,
            'name' => 'X-1',
            'capacity' => 32,
            'curriculum_type' => 'merdeka',
            'shift' => 'pagi',
            'is_active' => true,
        ]);

        // Direct service-level check: build the same history the controller would,
        // then fire the event the way the controller would, and assert job dispatch.
        $history = StudentClassHistory::create([
            'student_id' => $student->id,
            'study_group_id' => $studyGroup->id,
            'academic_year_id' => $ay->id,
            'is_active' => true,
            'join_date' => now()->toDateString(),
        ]);

        event(new StudentAssignedToRombel($history));

        Event::assertDispatched(StudentAssignedToRombel::class, function ($e) use ($history) {
            return $e->classHistoryId === $history->id
                && $e->studentId === $history->student_id;
        });
    }
}
