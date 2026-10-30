<?php

namespace Tests\Feature\Dormitory;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoom;
use App\Models\DormitoryWing;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\Boarding\LeaveWorkflowService;
use App\Services\Boarding\VisitScheduleService;
use Database\Seeders\PermitTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DormitoryPolicyAlignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_izin_biasa_langsung_disetujui_tanpa_approval(): void
    {
        $this->seed(PermitTypeSeeder::class);
        [$user, $dorm, $student, $year] = $this->fixtures();

        $this->actingAs($user);
        $permit = app(LeaveWorkflowService::class)->submit([
            'student_id' => $student->id,
            'room_id' => DormitoryResident::where('student_id', $student->id)->value('room_id'),
            'permit_type' => 'pulang',
            'destination' => 'Rumah orang tua',
            'purpose' => 'Pulang terjadwal',
            'departure_datetime' => now()->addDay()->toDateTimeString(),
            'expected_return_datetime' => now()->addDay()->addHours(6)->toDateTimeString(),
            'guardian_consent_confirmed' => true,
            'guardian_approval_channel' => 'manual',
            'created_by' => $user->id,
        ], $dorm->id, $year->id);

        $this->assertSame('approved', $permit->status);
        $this->assertSame('approved', $permit->guardian_approval_status);
    }

    public function test_izin_khusus_tetap_menunggu_approval(): void
    {
        $this->seed(PermitTypeSeeder::class);
        [$user, $dorm, $student, $year] = $this->fixtures();

        $this->actingAs($user);
        $permit = app(LeaveWorkflowService::class)->submit([
            'student_id' => $student->id,
            'room_id' => DormitoryResident::where('student_id', $student->id)->value('room_id'),
            'permit_type' => 'musibah',
            'destination' => 'Rumah keluarga',
            'purpose' => 'Musibah keluarga',
            'departure_datetime' => now()->addDay()->toDateTimeString(),
            'expected_return_datetime' => now()->addDay()->addHours(6)->toDateTimeString(),
            'guardian_consent_confirmed' => true,
            'guardian_approval_channel' => 'manual',
            'created_by' => $user->id,
        ], $dorm->id, $year->id);

        $this->assertSame('pending', $permit->status);
    }

    public function test_izin_lebih_dari_tiga_hari_ditolak(): void
    {
        $this->seed(PermitTypeSeeder::class);
        [$user, $dorm, $student, $year] = $this->fixtures();

        $this->actingAs($user);

        $this->expectException(\InvalidArgumentException::class);

        app(LeaveWorkflowService::class)->submit([
            'student_id' => $student->id,
            'room_id' => DormitoryResident::where('student_id', $student->id)->value('room_id'),
            'permit_type' => 'musibah',
            'destination' => 'Rumah keluarga',
            'purpose' => 'Musibah keluarga',
            'departure_datetime' => now()->addDay()->toDateTimeString(),
            'expected_return_datetime' => now()->addDays(5)->toDateTimeString(),
            'guardian_consent_confirmed' => true,
            'created_by' => $user->id,
        ], $dorm->id, $year->id);
    }

    public function test_jadwal_kunjungan_putra_dan_pekan_kelima_divalidasi(): void
    {
        [$user, $dorm, $student] = $this->fixtures();
        $service = app(VisitScheduleService::class);

        $weekOneSunday = '2026-10-04 09:00:00'; // Ahad pekan 1
        $weekTwoSunday = '2026-10-11 09:00:00'; // Ahad pekan 2
        $weekFive = '2026-10-31 09:00:00'; // Sabtu pekan 5

        $this->assertTrue($service->validate($student, $weekOneSunday, $dorm->id)['allowed']);
        $this->assertFalse($service->validate($student, $weekTwoSunday, $dorm->id)['allowed']);
        $this->assertFalse($service->validate($student, $weekFive, $dorm->id)['allowed']);
    }

    public function test_permit_type_baru_tersedia(): void
    {
        $this->seed(PermitTypeSeeder::class);

        foreach (['musibah', 'walimah_keluarga', 'haji_umrah', 'wisuda_keluarga', 'keperluan_penting'] as $code) {
            $this->assertDatabaseHas('permit_types', ['code' => $code, 'is_active' => true]);
        }
    }

    private function fixtures(): array
    {
        $workUnitId = (string) Str::uuid();
        \DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Izin',
            'code' => 'UIZ-'.Str::upper(Str::random(4)),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '66778899',
            'name' => 'Sekolah Izin',
            'boarding_type' => 'boarding',
        ]);
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);
        $admin = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);
        $dorm = Dormitory::create([
            'work_unit_id' => $workUnitId,
            'school_id' => $school->id,
            'code' => 'IZ-01',
            'name' => 'Asrama Izin',
            'gender' => 'putra',
            'capacity' => 10,
            'is_active' => true,
        ]);
        $wing = DormitoryWing::create([
            'dormitory_id' => $dorm->id,
            'code' => 'W1',
            'name' => 'Gedung 1',
            'floor' => 1,
            'gender' => 'putra',
            'capacity' => 10,
            'total_rooms' => 1,
            'is_active' => true,
        ]);
        $room = DormitoryRoom::create([
            'dormitory_id' => $dorm->id,
            'wing_id' => $wing->id,
            'code' => 'P-01',
            'name' => 'Kamar P1',
            'floor' => 1,
            'gender' => 'putra',
            'capacity' => 10,
            'room_type' => 'reguler',
            'is_active' => true,
        ]);
        $student = Student::create([
            'school_id' => $school->id,
            'name' => 'Santri Izin',
            'nisn' => '6677889900',
            'gender' => 'L',
            'status' => 'active',
        ]);
        DormitoryResident::create([
            'student_id' => $student->id,
            'room_id' => $room->id,
            'dormitory_id' => $dorm->id,
            'academic_year_id' => $year->id,
            'bed_number' => 1,
            'is_active' => true,
            'check_in_date' => now()->subMonth()->toDateString(),
        ]);

        return [$admin, $dorm, $student, $year];
    }
}
