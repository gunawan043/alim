<?php

declare(strict_types=1);

namespace Tests\Feature\Dormitory;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryResident;
use App\Models\DormitoryStaffAssignment;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentMahrom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Diagnostik: user ASRAMA (bukan admin) pada modul Data Mahrom.
 *
 * Memastikan menu, halaman import, dan aksi simpan benar-benar dapat
 * dipakai staf asrama — bukan hanya admin sistem.
 */
class AsramaMahromAccessTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Student $resident;

    private Student $outsider;

    private User $asramaUser;

    private Dormitory $dormitory;

    private AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Asrama Mahrom', 'code' => 'UU-ASR',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '55667788',
            'name' => 'Sekolah Asrama Mahrom',
            'boarding_type' => 'boarding',
        ]);

        $this->dormitory = Dormitory::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'work_unit_id' => $workUnitId,
            'name' => 'Asrama Diagnostik',
            'gender' => 'putra',
            'is_active' => true,
        ]);

        $this->resident = Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'nisn' => '2222000001',
            'nis' => 'A-0001',
            'name' => 'Santri Penghuni',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $this->outsider = Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'nisn' => '2222000002',
            'nis' => 'A-0002',
            'name' => 'Santri Luar Asrama',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $this->academicYear = AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        DormitoryResident::create([
            'id' => (string) Str::uuid(),
            'dormitory_id' => $this->dormitory->id,
            'student_id' => $this->resident->id,
            'academic_year_id' => $this->academicYear->id,
            'check_in_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $this->asramaUser = User::factory()->create(['is_active' => true]);
        $this->asramaUser->assignRole('Asrama');

        DormitoryStaffAssignment::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->asramaUser->id,
            'dormitory_id' => $this->dormitory->id,
            'position' => 'staf_asrama',
            'status' => 'active',
            'start_date' => now()->toDateString(),
        ]);
    }

    public function test_user_asrama_dikenali_sebagai_user_asrama(): void
    {
        $this->assertTrue($this->asramaUser->isDormitoryUser(), 'User asrama tidak terdeteksi sebagai user asrama.');
        $this->assertContains($this->dormitory->id, $this->asramaUser->accessibleDormitoryIds());
    }

    public function test_halaman_data_mahrom_dapat_dibuka_user_asrama(): void
    {
        $this->actingAs($this->asramaUser)
            ->get(route('user.students.mahroms.global', ['userId' => $this->asramaUser->id]))
            ->assertOk();
    }

    public function test_halaman_import_mahrom_dapat_dibuka_user_asrama(): void
    {
        $this->actingAs($this->asramaUser)
            ->get(route('user.students.mahroms.import', ['userId' => $this->asramaUser->id]))
            ->assertOk();
    }

    public function test_template_terisi_hanya_memuat_santri_asrama(): void
    {
        StudentMahrom::create([
            'id' => (string) Str::uuid(),
            'student_id' => $this->resident->id,
            'name' => 'Ayah Penghuni',
            'relationship' => 'ayah',
            'is_active' => true,
            'is_primary' => true,
        ]);

        $this->actingAs($this->asramaUser)
            ->get(route('user.students.mahroms.import.template-filled', ['userId' => $this->asramaUser->id]))
            ->assertOk()
            ->assertDownload();
    }

    public function test_user_asrama_bisa_menambah_mahrom_santri_asrama(): void
    {
        $this->actingAs($this->asramaUser)
            ->post(route('user.students.mahroms.globalStore', ['userId' => $this->asramaUser->id]), [
                'student_id' => $this->resident->id,
                'name' => 'Ibu Penghuni',
                'relationship' => 'ibu',
                'phone' => '081200000001',
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('student_mahroms', [
            'student_id' => $this->resident->id,
            'name' => 'Ibu Penghuni',
        ]);
    }

    public function test_user_asrama_tidak_bisa_menambah_mahrom_santri_luar_asrama(): void
    {
        $this->actingAs($this->asramaUser)
            ->post(route('user.students.mahroms.globalStore', ['userId' => $this->asramaUser->id]), [
                'student_id' => $this->outsider->id,
                'name' => 'Ibu Luar',
                'relationship' => 'ibu',
            ]);

        $this->assertDatabaseMissing('student_mahroms', [
            'student_id' => $this->outsider->id,
        ]);
    }

    public function test_sidebar_asrama_memuat_menu_data_mahrom_dan_import(): void
    {
        $this->actingAs($this->asramaUser)
            ->get(route('user.students.mahroms.global', ['userId' => $this->asramaUser->id]))
            ->assertOk()
            ->assertSee('Data Mahrom')
            ->assertSee('Import Mahrom');
    }
}
