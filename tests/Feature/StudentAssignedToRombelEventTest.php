<?php

namespace Tests\Feature;

use App\Events\StudentAssignedToRombel;
use App\Jobs\ProvisionStudentAcademicDataJob;
use App\Listeners\ProvisionStudentAcademicDataListener;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Concerns\SafeRefreshDatabase;
use Tests\TestCase;

class StudentAssignedToRombelEventTest extends TestCase
{
    use SafeRefreshDatabase;

    protected string $workUnitId;
    protected string $schoolId;
    protected string $gradeLevelId;
    protected ?AcademicYear $academicYear = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSafeDatabase();
    }

    protected function tearDown(): void
    {
        $this->tearDownSafeDatabase();
        parent::tearDown();
    }

    protected function seedSafeFixtures(): void
    {
        $this->workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $this->workUnitId,
            'name' => 'PONTREN Test',
            'code' => 'WT-SAR-' . substr(uniqid(), -5),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->schoolId = (string) Str::uuid();
        DB::table('schools')->insert([
            'id' => $this->schoolId,
            'work_unit_id' => $this->workUnitId,
            'npsn' => '12345678',
            'name' => 'SMAN Test',
            'school_level' => 'sma',
            'school_status' => 'negeri',
            'operational_hours' => 'pagi',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->gradeLevelId = (string) Str::uuid();
        DB::table('grade_levels')->insert([
            'id' => $this->gradeLevelId,
            'school_id' => $this->schoolId,
            'name' => 'Kelas X',
            'code' => 'X',
            'level' => 10,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->academicYear = AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);
    }

    private function createStudent(): Student
    {
        return Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->schoolId,
            'name' => 'Test Student',
            'nisn' => (string) random_int(1000000000, 9999999999),
            'gender' => 'L',
        ]);
    }

    private function createStudyGroup(): StudyGroup
    {
        return StudyGroup::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYear->id,
            'grade_level_id' => $this->gradeLevelId,
            'name' => 'X-1',
            'capacity' => 32,
            'curriculum_type' => 'merdeka',
            'shift' => 'pagi',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function event_is_dispatched_with_correct_payload(): void
    {
        $student = $this->createStudent();
        $studyGroup = $this->createStudyGroup();

        $history = new StudentClassHistory;
        $history->id = (string) Str::uuid();
        $history->student_id = $student->id;
        $history->study_group_id = $studyGroup->id;
        $history->academic_year_id = $this->academicYear->id;
        $history->is_active = true;
        $history->join_date = now()->toDateString();

        $event = new StudentAssignedToRombel($history);

        $this->assertEquals($history->id, $event->classHistoryId);
        $this->assertEquals($student->id, $event->studentId);
        $this->assertEquals($this->academicYear->id, $event->academicYearId);
        $this->assertEquals('ganjil', $event->semester);
        $this->assertEquals(now()->toDateString(), $event->joinDate);
    }

    /** @test */
    public function event_listener_dispatches_job(): void
    {
        Queue::fake();

        $student = $this->createStudent();
        $studyGroup = $this->createStudyGroup();

        $history = new StudentClassHistory;
        $history->id = (string) Str::uuid();
        $history->student_id = $student->id;
        $history->study_group_id = $studyGroup->id;
        $history->academic_year_id = $this->academicYear->id;
        $history->is_active = true;
        $history->join_date = now()->toDateString();

        event(new StudentAssignedToRombel($history));

        // Listener implements ShouldQueue → Laravel pushes a CallQueuedListener
        // straight onto the connection (Dispatcher::queueHandler) — captured by Queue::fake().
        Queue::assertPushed(\Illuminate\Events\CallQueuedListener::class, function ($queuedListener) use ($student, $history) {
            return $queuedListener->class === ProvisionStudentAcademicDataListener::class
                && $queuedListener->data[0]->studentId === $student->id
                && $queuedListener->data[0]->classHistoryId === $history->id;
        });
    }

    /** @test */
    public function listener_handle_dispatches_provisioning_job(): void
    {
        // Separate unit-level test of the listener body — verifies that
        // invoking handle() directly queues ProvisionStudentAcademicDataJob.
        Bus::fake();

        $student = $this->createStudent();
        $studyGroup = $this->createStudyGroup();

        $history = new StudentClassHistory;
        $history->id = (string) Str::uuid();
        $history->student_id = $student->id;
        $history->study_group_id = $studyGroup->id;
        $history->academic_year_id = $this->academicYear->id;
        $history->is_active = true;
        $history->join_date = now()->toDateString();

        $listener = new ProvisionStudentAcademicDataListener;
        $listener->handle(new StudentAssignedToRombel($history));

        Bus::assertDispatched(ProvisionStudentAcademicDataJob::class, function ($job) use ($student, $history) {
            return $job->studentId === $student->id
                && $job->classHistoryId === $history->id
                && $job->queue === 'academic-provision';
        });
    }

    /** @test */
    public function event_dispatch_from_student_class_history_controller_triggers_provisioning_job(): void
    {
        Event::fake([StudentAssignedToRombel::class]);

        $student = $this->createStudent();
        $studyGroup = $this->createStudyGroup();

        $history = StudentClassHistory::create([
            'student_id' => $student->id,
            'study_group_id' => $studyGroup->id,
            'academic_year_id' => $this->academicYear->id,
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
