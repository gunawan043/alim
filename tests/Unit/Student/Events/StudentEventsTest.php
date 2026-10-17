<?php

namespace Tests\Unit\Student\Events;

use App\Events\StudentMutatedOut;
use App\Events\StudentStatusChanged;
use App\Models\School;
use App\Models\Student;
use App\Support\LifecycleMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Fase 4 — modernisasi test event lifecycle (sebelumnya memakai
 * helper dengan kolom DB yang sudah tidak ada).
 */
class StudentEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_mutated_out_dapat_diinstansiasi(): void
    {
        $student = $this->makeStudent();

        $event = new StudentMutatedOut(
            $student,
            null,
            StudentMutatedOut::TYPE_MUTATION,
            now()->toDateString(),
            null,
        );

        $this->assertSame($student->id, $event->student->id);
        $this->assertSame(StudentMutatedOut::TYPE_MUTATION, $event->outType);
    }

    public function test_lifecycle_message_for_event_memuat_properti_notifikasi(): void
    {
        $student = $this->makeStudent();

        $event = new StudentMutatedOut(
            $student,
            null,
            StudentMutatedOut::TYPE_GRADUATION,
            '2027-06-20',
            null,
        );

        $message = LifecycleMessage::forEvent($event);

        $this->assertInstanceOf(LifecycleMessage::class, $message);
        $this->assertSame('student.mutated_out', $message->event);
        $this->assertSame('graduate', $message->newStatus);

        // Properti turunan yang dipakai SendLifecycleNotificationJob
        $this->assertSame('success', $message->priority);
        $this->assertNotEmpty($message->title);
        $this->assertStringContainsString($student->name, $message->body);
    }

    public function test_status_changed_membawa_payload_perubahan(): void
    {
        $student = $this->makeStudent();

        $event = new StudentStatusChanged($student, [
            'previous_status' => 'active',
            'new_status' => 'inactive',
        ]);

        $this->assertSame($student->id, $event->student->id);
        $this->assertSame('active', $event->payload['previous_status']);
        $this->assertSame('inactive', $event->payload['new_status']);
    }

    private function makeStudent(): Student
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Uji Event',
            'code' => 'UUEV',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '12121212',
            'name' => 'Sekolah Event Uji',
        ]);

        return Student::create([
            'school_id' => $school->id,
            'nisn' => '121200001',
            'name' => 'Santri Event',
            'gender' => 'L',
            'status' => 'active',
        ]);
    }
}
