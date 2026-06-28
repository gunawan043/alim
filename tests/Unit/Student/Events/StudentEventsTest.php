<?php

namespace Tests\Unit\Student\Events;

use App\Events\StudentGraduated;
use App\Events\StudentMutatedIn;
use App\Events\StudentMutatedOut;
use App\Events\StudentPromoted;
use App\Events\StudentStatusChanged;
use App\Models\Student;
use App\Models\StudentMutationIn;
use App\Models\StudentMutationOut;
use App\Models\StudentPromotion;
use App\Models\StudentPromotionDetail;
use App\Support\LifecycleMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentEventsTest extends TestCase
{
    use RefreshDatabase;

    private function createStudent(): Student
    {
        return Student::create([
            'school_id' => 1,
            'nis' => '99001',
            'name' => 'Ahmad Fauzi',
            'status' => 'active',
            'gender' => 'male',
            'birth_date' => '2008-01-15',
        ]);
    }

    private function createPromotionWithDetail(
        string $action,
        ?string $status = null,
        ?string $errorMsg = null,
    ): StudentPromotion {
        $promo = StudentPromotion::create([
            'from_academic_year_id' => 1,
            'to_academic_year_id' => 2,
            'from_study_group_id' => 1,
            'to_study_group_id' => 2,
            'promotion_date' => now(),
            'status' => 'draft',
            'auto_enroll' => true,
            'grade_shift' => 0,
        ]);
        StudentPromotionDetail::create([
            'promotion_id' => $promo->id,
            'student_id' => $this->student()->id,
            'action' => $action,
            'status' => $status ?? 'success',
            'error_message' => $errorMsg,
        ]);

        return $promo;
    }

    private function createMutationOut(string $outType = 'mutation'): StudentMutationOut
    {
        return StudentMutationOut::create([
            'student_id' => $this->student()->id,
            'mutation_date' => now(),
            'destination_school' => 'SMAN 2 Jakarta',
            'out_type' => $outType,
            'status' => 'draft',
        ]);
    }

    private function createMutationIn(): StudentMutationIn
    {
        return StudentMutationIn::create([
            'student_id' => $this->student()->id,
            'arrival_date' => now(),
            'from_school' => 'SMAN 1 Bandung',
            'status' => 'draft',
        ]);
    }

    public function test_event_classes_can_be_instantiated_with_correct_signature(): void
    {
        $student = $this->createStudent();

        $event = new StudentPromoted($student);
        $this->assertSame($student, $event->student);

        $event = new StudentGraduated($student);
        $this->assertSame($student, $event->student);

        $event = new StudentMutatedOut($student, outType: 'mutation');
        $this->assertSame($student, $event->student);
        $this->assertEquals('mutation', $event->outType);

        $event = new StudentMutatedIn($student);
        $this->assertSame($student, $event->student);

        $event = new StudentStatusChanged($student, ['status' => 'active', 'reason' => 'initial']);
        $this->assertEquals('active', $event->payload['status']);
    }

    public function test_lifecycle_message_can_be_created(): void
    {
        $msg = new LifecycleMessage('promote');
        $this->assertEquals('promote', $msg->action);
        $this->assertEmpty($msg->reason);
        $this->assertEmpty($msg->details);

        $payload = $msg->toArray();
        $this->assertArrayHasKey('action', $payload);
        $this->assertArrayHasKey('reason', $payload);
        $this->assertArrayHasKey('details', $payload);
        $this->assertArrayHasKey('timestamp', $payload);
    }

    public function test_controller_calls_update_status_without_error(): void
    {
        $student = $this->createStudent();
        $student->update(['status' => 'active']);
        $this->assertEquals('active', $student->refresh()->status);
    }
}
