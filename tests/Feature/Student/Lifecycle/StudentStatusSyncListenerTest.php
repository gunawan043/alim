<?php

namespace Tests\Feature\Student\Lifecycle;

use App\Events\StudentGraduated;
use App\Events\StudentMutatedOut;
use App\Events\StudentPromoted;
use App\Jobs\RecordLifecycleAuditJob;
use App\Jobs\SendLifecycleNotificationJob;
use App\Listeners\UpdateStudentStatusOnLifecycle;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudentStatusSyncListenerTest extends TestCase
{
    use RefreshDatabase;

    private function setupStudent(): array
    {
        $workUnit = WorkUnit::create([
            'id' => (string) Str::uuid(),
            'name' => 'Test Work Unit',
            'code' => 'TWU-'.uniqid(),
        ]);

        $school = School::create([
            'id' => (string) Str::uuid(),
            'name' => 'Test School',
            'npsn' => '12345678',
            'work_unit_id' => $workUnit->id,
        ]);

        $student = Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'name' => 'Test Student',
            'nisn' => '1234567890',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $ay = AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2025/2026',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $gradeLevel = GradeLevel::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'level' => 7,
            'name' => 'Kelas 7',
        ]);

        $sg = StudyGroup::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'academic_year_id' => $ay->id,
            'name' => '7-A',
            'grade_level_id' => $gradeLevel->id,
        ]);

        StudentClassHistory::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'study_group_id' => $sg->id,
            'academic_year_id' => $ay->id,
            'is_active' => true,
            'join_date' => '2025-07-15',
        ]);

        return compact('school', 'student', 'ay', 'sg');
    }

    /** @test */
    public function promotion_listener_sets_student_status_to_active(): void
    {
        $data = $this->setupStudent();
        $toAy = AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => false,
        ]);

        $event = new StudentPromoted(
            student: $data['student'],
            fromStudyGroup: $data['sg'],
            toStudyGroup: $data['sg'],
            fromAcademicYear: $data['ay'],
            toAcademicYear: $toAy,
            promotionDate: '2026-06-15',
            actorId: null,
        );

        (new UpdateStudentStatusOnLifecycle)->handle($event);

        $this->assertEquals('active', $data['student']->fresh()->status);
    }

    /** @test */
    public function graduation_listener_sets_status_to_graduate(): void
    {
        $data = $this->setupStudent();

        $event = new StudentGraduated(
            student: $data['student'],
            fromStudyGroup: $data['sg'],
            fromAcademicYear: $data['ay'],
            graduationDate: '2026-06-15',
            graduationYear: '2026',
        );

        (new UpdateStudentStatusOnLifecycle)->handle($event);

        $student = $data['student']->fresh();
        $this->assertEquals('graduate', $student->status);
        $this->assertEquals('2026', $student->graduation_year);
        $this->assertEquals('2026-06-15', $student->graduation_date->format('Y-m-d'));
    }

    /** @test */
    public function mutation_out_listener_graduation_type_sets_graduate_status(): void
    {
        $data = $this->setupStudent();

        $event = new StudentMutatedOut(
            student: $data['student'],
            outType: 'graduation',
            reason: 'Lulus',
            leaveDate: '2026-06-15',
        );

        (new UpdateStudentStatusOnLifecycle)->handle($event);

        $this->assertEquals('graduate', $data['student']->fresh()->status);
    }

    /** @test */
    public function mutation_out_listener_dropout_type_sets_dropped_status(): void
    {
        $data = $this->setupStudent();

        $event = new StudentMutatedOut(
            student: $data['student'],
            outType: 'dropout',
            reason: 'Pindah keluarga',
            leaveDate: '2026-06-15',
        );

        (new UpdateStudentStatusOnLifecycle)->handle($event);

        $this->assertEquals('dropped', $data['student']->fresh()->status);
    }

    /** @test */
    public function mutation_out_listener_mutation_type_sets_transfer_out_status(): void
    {
        $data = $this->setupStudent();

        $event = new StudentMutatedOut(
            student: $data['student'],
            outType: 'mutation',
            reason: 'Mutasi ke sekolah lain',
            leaveDate: '2026-06-15',
        );

        (new UpdateStudentStatusOnLifecycle)->handle($event);

        $this->assertEquals('transfer_out', $data['student']->fresh()->status);
    }

    /** @test */
    public function events_dispatch_audit_and_notification_jobs(): void
    {
        Bus::fake();

        $data = $this->setupStudent();
        $toAy = AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => false,
        ]);

        $event = new StudentPromoted(
            student: $data['student'],
            fromStudyGroup: $data['sg'],
            toStudyGroup: $data['sg'],
            fromAcademicYear: $data['ay'],
            toAcademicYear: $toAy,
            promotionDate: '2026-06-15',
            actorId: null,
        );

        event($event);

        Bus::assertDispatched(RecordLifecycleAuditJob::class);
        Bus::assertDispatched(SendLifecycleNotificationJob::class);
    }

    /** @test */
    public function promotion_closes_previous_history_and_creates_new(): void
    {
        $data = $this->setupStudent();

        $toAy = AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => false,
        ]);

        $toGradeLevel = GradeLevel::create([
            'id' => (string) Str::uuid(),
            'school_id' => $data['school']->id,
            'level' => 8,
            'name' => 'Kelas 8',
        ]);

        $toSg = StudyGroup::create([
            'id' => (string) Str::uuid(),
            'school_id' => $data['school']->id,
            'academic_year_id' => $toAy->id,
            'name' => '8-A',
            'grade_level_id' => $toGradeLevel->id,
        ]);

        $event = new StudentPromoted(
            student: $data['student'],
            fromStudyGroup: $data['sg'],
            toStudyGroup: $toSg,
            fromAcademicYear: $data['ay'],
            toAcademicYear: $toAy,
            promotionDate: '2026-06-15',
            actorId: null,
        );

        (new UpdateStudentStatusOnLifecycle)->handle($event);

        $oldHistory = StudentClassHistory::where('student_id', $data['student']->id)
            ->where('academic_year_id', $data['ay']->id)
            ->first();
        $this->assertNotNull($oldHistory);
        $this->assertFalse($oldHistory->is_active);
        $this->assertEquals('2026-06-15', $oldHistory->leave_date->toDateString());

        $newHistory = StudentClassHistory::where('student_id', $data['student']->id)
            ->where('academic_year_id', $toAy->id)
            ->first();
        $this->assertNotNull($newHistory);
        $this->assertTrue($newHistory->is_active);
        $this->assertEquals('2026-06-15', $newHistory->join_date->toDateString());
    }
}
