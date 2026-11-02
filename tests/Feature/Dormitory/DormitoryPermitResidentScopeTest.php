<?php

declare(strict_types=1);

namespace Tests\Feature\Dormitory;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoom;
use App\Models\DormitoryStaffAssignment;
use App\Models\DormitoryWing;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi: santri yang sudah ditarik dari kamar (room_id null) masih berstatus
 * penghuni aktif, sehingga sebelumnya ikut muncul di form izin padahal belum
 * ditempatkan di kamar mana pun.
 */
class DormitoryPermitResidentScopeTest extends TestCase
{
    use RefreshDatabase;

    private Dormitory $dormitory;

    private DormitoryRoom $room;

    private AcademicYear $year;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dormitory = Dormitory::factory()->create(['is_active' => true, 'gender' => 'putri']);

        $this->year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $wing = DormitoryWing::create([
            'dormitory_id' => $this->dormitory->id,
            'code' => 'W-01',
            'name' => 'Sayap 01',
            'gender' => 'putri',
        ]);

        $this->room = DormitoryRoom::create([
            'dormitory_id' => $this->dormitory->id,
            'wing_id' => $wing->id,
            'code' => 'K-01',
            'name' => 'Kamar 01',
            'gender' => 'putri',
            'capacity' => 10,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create(['is_system_admin' => true, 'is_active' => true]);
        $this->admin->assignRole(Role::firstOrCreate(['name' => 'Asrama', 'guard_name' => 'web']));

        DormitoryStaffAssignment::create([
            'user_id' => $this->admin->id,
            'dormitory_id' => $this->dormitory->id,
            'start_date' => now()->subDay(),
            'status' => 'active',
        ]);
    }

    private function makeStudent(string $name, string $nisn): Student
    {
        return Student::create([
            'school_id' => $this->dormitory->school_id,
            'nisn' => $nisn,
            'name' => $name,
            'gender' => 'P',
            'status' => 'active',
        ]);
    }

    private function makeResident(Student $student, ?string $roomId): DormitoryResident
    {
        return DormitoryResident::create([
            'dormitory_id' => $this->dormitory->id,
            'student_id' => $student->id,
            'room_id' => $roomId,
            'academic_year_id' => $this->year->id,
            'is_active' => true,
            'check_in_date' => now()->toDateString(),
        ]);
    }

    public function test_santri_tanpa_kamar_tidak_muncul_di_form_izin(): void
    {
        $placed = $this->makeStudent('Aisyah Kamar Satu', '9999200101');
        $unplaced = $this->makeStudent('Aila Attahira Nurayya', '9999200102');

        $this->makeResident($placed, $this->room->id);
        $this->makeResident($unplaced, null);

        $response = $this->actingAs($this->admin)
            ->get(route('user.asrama.permits.create', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]))
            ->assertOk();

        $response->assertSee('Aisyah Kamar Satu');
        $response->assertDontSee('Aila Attahira Nurayya');
    }

    public function test_santri_tanpa_kamar_tidak_muncul_di_izin_massal(): void
    {
        $placed = $this->makeStudent('Aisyah Kamar Satu', '9999200201');
        $unplaced = $this->makeStudent('Aila Attahira Nurayya', '9999200202');

        $this->makeResident($placed, $this->room->id);
        $this->makeResident($unplaced, null);

        $response = $this->actingAs($this->admin)
            ->get(route('user.asrama.permits.massal.create', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]))
            ->assertOk();

        $response->assertSee('Aisyah Kamar Satu');
        $response->assertDontSee('Aila Attahira Nurayya');
    }
}
