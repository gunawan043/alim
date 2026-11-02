<?php

declare(strict_types=1);

namespace Tests\Feature\Dormitory;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoom;
use App\Models\DormitoryWing;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentMahrom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Sinkronisasi kontak mahrom: nomor HP terisi dari data santri/wali,
 * dan form perizinan mengirim hubungan + nomor yang benar ke JS.
 */
class DormitoryMahromSyncTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Dormitory $dormitory;

    private Student $student;

    private User $admin;

    private AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Mahrom', 'code' => 'UU-MAHROM',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '88990011',
            'name' => 'Sekolah Mahrom',
            'boarding_type' => 'boarding',
        ]);

        $this->dormitory = Dormitory::create([
            'work_unit_id' => $workUnitId,
            'school_id' => $this->school->id,
            'code' => 'ASR-MAHROM',
            'name' => 'Asrama Mahrom',
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

        $room = DormitoryRoom::create([
            'id' => (string) Str::uuid(),
            'dormitory_id' => $this->dormitory->id,
            'wing_id' => $wing->id,
            'code' => 'R1',
            'name' => 'Kamar 1',
            'gender' => 'putra',
            'capacity' => 10,
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'nisn' => '7777000001',
            'name' => 'Santri Mahrom',
            'gender' => 'L',
            'status' => 'active',
            'mobile_phone' => '081234567890',
            'boarding_status' => 'boarding',
        ]);

        DormitoryResident::create([
            'id' => (string) Str::uuid(),
            'dormitory_id' => $this->dormitory->id,
            'student_id' => $this->student->id,
            'academic_year_id' => $this->academicYear->id,
            'room_id' => $room->id,
            'bed_number' => 1,
            'check_in_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'is_system_admin' => true,
            'is_active' => true,
        ]);
    }

    public function test_perintah_sinkron_mengisi_nomor_mahrom_utama_dari_nomor_santri(): void
    {
        $primary = StudentMahrom::create([
            'id' => (string) Str::uuid(),
            'student_id' => $this->student->id,
            'name' => 'Bapak Santri',
            'relationship' => 'ayah',
            'is_active' => true,
            'is_primary' => true,
        ]);

        $secondary = StudentMahrom::create([
            'id' => (string) Str::uuid(),
            'student_id' => $this->student->id,
            'name' => 'Ibu Santri',
            'relationship' => 'ibu',
            'is_active' => true,
            'is_primary' => false,
        ]);

        $this->artisan('dormitory:sync-mahrom-contacts')->assertSuccessful();

        $this->assertSame('081234567890', $primary->fresh()->phone);
        $this->assertNull($secondary->fresh()->phone, 'Mahrom non-utama tidak ikut diisi nomor yang sama.');
    }

    public function test_perintah_sinkron_tidak_menimpa_nomor_yang_sudah_ada(): void
    {
        $primary = StudentMahrom::create([
            'id' => (string) Str::uuid(),
            'student_id' => $this->student->id,
            'name' => 'Bapak Santri',
            'relationship' => 'ayah',
            'phone' => '081999999999',
            'is_active' => true,
            'is_primary' => true,
        ]);

        $this->artisan('dormitory:sync-mahrom-contacts')->assertSuccessful();

        $this->assertSame('081999999999', $primary->fresh()->phone);
    }

    public function test_form_izin_mengirim_hubungan_dan_nomor_mahrom(): void
    {
        StudentMahrom::create([
            'id' => (string) Str::uuid(),
            'student_id' => $this->student->id,
            'name' => 'Bapak Santri',
            'relationship' => 'ayah',
            'phone' => '081234567890',
            'is_active' => true,
            'is_primary' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(
            route('user.asrama.permits.create', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ])
        );

        $response->assertOk()
            ->assertSee('Bapak Santri')
            ->assertSee('081234567890')
            ->assertSee('relation_text', false)
            ->assertSee('Ayah');
    }

    public function test_form_izin_memakai_nomor_santri_bila_mahrom_belum_punya_nomor(): void
    {
        StudentMahrom::create([
            'id' => (string) Str::uuid(),
            'student_id' => $this->student->id,
            'name' => 'Bapak Santri',
            'relationship' => 'ayah',
            'phone' => null,
            'is_active' => true,
            'is_primary' => true,
        ]);

        $this->actingAs($this->admin)
            ->get(route('user.asrama.permits.create', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]))
            ->assertOk()
            ->assertSee('081234567890');
    }
}
