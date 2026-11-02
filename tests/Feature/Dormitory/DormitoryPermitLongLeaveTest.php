<?php

declare(strict_types=1);

namespace Tests\Feature\Dormitory;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryPermit;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoom;
use App\Models\DormitoryWing;
use App\Models\PermitType;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Support\AsramaConfig;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Izin lebih dari batas hari (default 3) tetap boleh diajukan, tetapi tidak
 * di-auto-approve — harus disetujui Kepala Asrama.
 */
class DormitoryPermitLongLeaveTest extends TestCase
{
    use RefreshDatabase;

    private Dormitory $dormitory;

    private DormitoryRoom $room;

    private AcademicYear $academicYear;

    private User $admin;

    /** @var array<int, Student> */
    private array $students = [];

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Long Leave', 'code' => 'UU-LONG',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '55443322',
            'name' => 'Sekolah Long Leave',
            'boarding_type' => 'boarding',
        ]);

        $this->dormitory = Dormitory::create([
            'work_unit_id' => $workUnitId,
            'school_id' => $school->id,
            'code' => 'ASR-LONG',
            'name' => 'Asrama Long Leave',
            'gender' => 'putra',
        ]);

        $this->academicYear = AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $wing = DormitoryWing::create([
            'id' => (string) Str::uuid(),
            'dormitory_id' => $this->dormitory->id,
            'code' => 'W1',
            'name' => 'Sayap 1',
            'gender' => 'putra',
        ]);

        $this->room = DormitoryRoom::create([
            'id' => (string) Str::uuid(),
            'dormitory_id' => $this->dormitory->id,
            'wing_id' => $wing->id,
            'code' => 'R1',
            'name' => 'Kamar 1',
            'gender' => 'putra',
            'capacity' => 20,
            'is_active' => true,
        ]);

        // Master jenis izin sudah di-seed oleh migrasi; pastikan aktif & kategori default.
        PermitType::updateOrCreate(
            ['code' => 'pulang'],
            ['label' => 'Izin Pulang', 'category' => 'default', 'is_active' => true],
        );

        foreach (['Santri Pendek', 'Santri Panjang'] as $i => $name) {
            $student = Student::create([
                'id' => (string) Str::uuid(),
                'school_id' => $school->id,
                'nisn' => '999800000'.($i + 1),
                'name' => $name,
                'gender' => 'L',
                'status' => 'active',
                'boarding_status' => 'boarding',
            ]);

            DormitoryResident::create([
                'id' => (string) Str::uuid(),
                'dormitory_id' => $this->dormitory->id,
                'student_id' => $student->id,
                'academic_year_id' => $this->academicYear->id,
                'room_id' => $this->room->id,
                'bed_number' => $i + 1,
                'check_in_date' => now()->toDateString(),
                'is_active' => true,
            ]);

            $this->students[] = $student;
        }

        $this->admin = User::factory()->create([
            'is_system_admin' => true,
            'is_active' => true,
        ]);

        AsramaConfig::setConsentRequired(false);
    }

    private function submit(Student $student, CarbonImmutable $departure, CarbonImmutable $return)
    {
        return $this->actingAs($this->admin)->post(
            route('user.asrama.permits.store', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]),
            [
                'student_id' => $student->id,
                'room_id' => $this->room->id,
                'permit_type' => 'pulang',
                'destination' => 'Rumah wali',
                'purpose' => 'Keperluan keluarga',
                'departure_datetime' => $departure->format('Y-m-d H:i:s'),
                'expected_return_datetime' => $return->format('Y-m-d H:i:s'),
                'guardian_consent_confirmed' => '1',
            ]
        );
    }

    public function test_izin_dalam_batas_hari_tetap_otomatis_disetujui(): void
    {
        $departure = CarbonImmutable::parse('2026-11-03 08:00:00');

        $this->submit($this->students[0], $departure, $departure->addDays(2))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $permit = DormitoryPermit::where('student_id', $this->students[0]->id)->firstOrFail();

        $this->assertSame('approved', $permit->status);
    }

    public function test_izin_lebih_dari_tiga_hari_tetap_tersimpan_menunggu_kepala_asrama(): void
    {
        $departure = CarbonImmutable::parse('2026-11-03 08:00:00');

        $this->submit($this->students[1], $departure, $departure->addDays(5))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $permit = DormitoryPermit::where('student_id', $this->students[1]->id)->firstOrFail();

        $this->assertSame('pending', $permit->status, 'Izin panjang harus menunggu persetujuan Kepala Asrama.');
        $this->assertNull($permit->approved_by);
    }
}
