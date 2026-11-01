<?php

declare(strict_types=1);

namespace Tests\Feature\Dormitory;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryDailyTask;
use App\Models\DormitoryDailyTaskLog;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoom;
use App\Models\DormitoryStaffAssignment;
use App\Models\DormitoryWing;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DormitoryDailyTaskTest extends TestCase
{
    use RefreshDatabase;

    private Dormitory $dormitory;

    private User $leader;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->leader = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => false,
        ]);

        $this->staff = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => false,
        ]);

        $this->dormitory = Dormitory::factory()
            ->withHead($this->leader->id)
            ->create([
                'is_active' => true,
                'gender' => 'putra',
            ]);

        DormitoryStaffAssignment::create([
            'user_id' => $this->staff->id,
            'dormitory_id' => $this->dormitory->id,
            'assigned_by_id' => $this->leader->id,
            'start_date' => now()->subWeek()->toDateString(),
            'status' => 'active',
        ]);

        $year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $wing = DormitoryWing::create([
            'dormitory_id' => $this->dormitory->id,
            'code' => 'W-01',
            'name' => 'Gedung 01',
            'gender' => 'putra',
        ]);

        $room = DormitoryRoom::create([
            'dormitory_id' => $this->dormitory->id,
            'wing_id' => $wing->id,
            'code' => 'K-01',
            'name' => 'Kamar 01',
            'gender' => 'putra',
            'capacity' => 10,
            'is_active' => true,
        ]);

        $student = Student::create([
            'school_id' => $this->dormitory->school_id,
            'nisn' => '9999200011',
            'name' => 'Santri Tugas',
            'gender' => 'L',
            'status' => 'active',
        ]);

        DormitoryResident::create([
            'dormitory_id' => $this->dormitory->id,
            'student_id' => $student->id,
            'room_id' => $room->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
            'check_in_date' => now()->toDateString(),
        ]);
    }

    public function test_kepala_asrama_dapat_membuat_tugas_harian_per_shift(): void
    {
        $this->actingAs($this->leader)
            ->post(route('user.asrama.daily-tasks.store', [
                'userId' => $this->leader->id,
                'asramaUuid' => $this->dormitory->id,
            ]), [
                'name' => 'Cek kebersihan kamar',
                'shift' => 'pagi',
                'description' => 'Periksa tempat sampah dan tempat tidur.',
                'sort_order' => 1,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('dormitory_daily_tasks', [
            'dormitory_id' => $this->dormitory->id,
            'name' => 'Cek kebersihan kamar',
            'shift' => 'pagi',
            'is_active' => 1,
        ]);
    }

    public function test_staf_asrama_dapat_mengisi_checklist_tugas(): void
    {
        $task = DormitoryDailyTask::create([
            'dormitory_id' => $this->dormitory->id,
            'shift' => 'malam',
            'name' => 'Kunci pintu utama',
            'is_active' => true,
            'sort_order' => 1,
            'created_by' => $this->leader->id,
        ]);

        $date = now()->toDateString();

        $this->actingAs($this->staff)
            ->post(route('user.asrama.daily-tasks.checklist', [
                'userId' => $this->staff->id,
                'asramaUuid' => $this->dormitory->id,
            ]), [
                'task_date' => $date,
                'shift' => 'malam',
                'done' => [$task->id => 1],
                'notes' => [$task->id => 'Sudah dikunci.'],
            ])
            ->assertRedirect();

        $log = DormitoryDailyTaskLog::where('task_id', $task->id)->whereDate('task_date', $date)->firstOrFail();

        $this->assertTrue($log->is_done);
        $this->assertSame('Sudah dikunci.', $log->notes);
        $this->assertSame($this->staff->id, $log->checked_by);
        $this->assertNotNull($log->checked_at);
    }

    public function test_staf_asrama_tidak_dapat_membuat_atau_mengubah_tugas(): void
    {
        $task = DormitoryDailyTask::create([
            'dormitory_id' => $this->dormitory->id,
            'shift' => 'pagi',
            'name' => 'Tugas Kepala',
            'is_active' => true,
            'created_by' => $this->leader->id,
        ]);

        $this->actingAs($this->staff)
            ->post(route('user.asrama.daily-tasks.store', [
                'userId' => $this->staff->id,
                'asramaUuid' => $this->dormitory->id,
            ]), ['name' => 'Tugas Liar', 'shift' => 'pagi'])
            ->assertForbidden();

        $this->actingAs($this->staff)
            ->put(route('user.asrama.daily-tasks.update', [
                'userId' => $this->staff->id,
                'asramaUuid' => $this->dormitory->id,
                'taskId' => $task->id,
            ]), ['name' => 'Diubah', 'shift' => 'pagi'])
            ->assertForbidden();
    }
}
