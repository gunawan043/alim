<?php

declare(strict_types=1);

namespace Tests\Feature\Dormitory;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoom;
use App\Models\DormitoryWing;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudentMahrom;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

/**
 * Wali kelas (termasuk yang juga ber-role asrama) dapat memperbarui data
 * santri & mahrom HANYA untuk kelasnya, dan perubahan langsung terbaca
 * di modul asrama (sinkron).
 */
class DormitoryWaliKelasAccessTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $academicYear;

    private StudyGroup $ownGroup;

    private StudyGroup $otherGroup;

    private Student $ownStudent;

    private Student $otherStudent;

    private User $wali;

    private User $admin;

    private Dormitory $dormitory;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Wali', 'code' => 'UU-WALI',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '66554433',
            'name' => 'Sekolah Wali',
            'boarding_type' => 'boarding',
        ]);

        $this->academicYear = AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $grade = GradeLevel::create([
            'school_id' => $this->school->id,
            'level' => 7,
            'name' => 'Kelas 7',
            'code' => 'K7',
            'is_active' => true,
        ]);

        $this->ownGroup = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'grade_level_id' => $grade->id,
            'name' => '7A',
            'code' => '7A',
            'capacity' => 30,
            'is_active' => true,
        ]);

        $this->otherGroup = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'grade_level_id' => $grade->id,
            'name' => '7B',
            'code' => '7B',
            'capacity' => 30,
            'is_active' => true,
        ]);

        $this->ownStudent = $this->makeStudent('Santri Kelas Wali', '7777000101', $this->ownGroup);
        $this->otherStudent = $this->makeStudent('Santri Kelas Lain', '7777000102', $this->otherGroup);

        $this->wali = User::factory()->create([
            'name' => 'Guru Wali Kelas',
            'is_active' => true,
        ]);
        $this->wali->assignRole(Role::firstOrCreate(['name' => 'Asrama', 'guard_name' => 'web'], ['level' => 4]));
        $this->wali->assignRole(Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 3]));

        $this->ownGroup->forceFill(['homeroom_teacher_id' => $this->wali->id])->save();

        $this->admin = User::factory()->create([
            'is_system_admin' => true,
            'is_active' => true,
        ]);

        // Asrama + kamar + penghuni, untuk uji sinkronisasi data.
        $this->dormitory = Dormitory::create([
            'work_unit_id' => $workUnitId,
            'school_id' => $this->school->id,
            'code' => 'ASR-WALI',
            'name' => 'Asrama Wali',
            'gender' => 'putra',
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

        foreach ([$this->ownStudent, $this->otherStudent] as $i => $student) {
            DormitoryResident::create([
                'id' => (string) Str::uuid(),
                'dormitory_id' => $this->dormitory->id,
                'student_id' => $student->id,
                'academic_year_id' => $this->academicYear->id,
                'room_id' => $room->id,
                'bed_number' => $i + 1,
                'check_in_date' => now()->toDateString(),
                'is_active' => true,
            ]);
        }
    }

    private function makeStudent(string $name, string $nisn, StudyGroup $group): Student
    {
        $student = Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'nisn' => $nisn,
            'name' => $name,
            'gender' => 'L',
            'status' => 'active',
            'boarding_status' => 'boarding',
        ]);

        StudentClassHistory::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'study_group_id' => $group->id,
            'academic_year_id' => $this->academicYear->id,
            'is_active' => true,
        ]);

        return $student;
    }

    private function updatePayload(array $overrides = []): array
    {
        return array_merge([
            'school_id' => $this->school->id,
            'name' => 'Nama Diperbarui',
            'gender' => 'L',
            'status' => 'active',
        ], $overrides);
    }

    public function test_wali_kelas_yang_juga_role_asrama_dapat_memperbarui_santri_kelasnya(): void
    {
        $this->actingAs($this->wali)
            ->get(route('user.students.edit', [
                'userId' => $this->wali->id,
                'santriUuid' => $this->ownStudent->id,
            ]))
            ->assertOk();

        $this->actingAs($this->wali)
            ->put(route('user.students.update', [
                'userId' => $this->wali->id,
                'santriUuid' => $this->ownStudent->id,
            ]), $this->updatePayload(['name' => 'Santri Kelas Wali Update']))
            ->assertRedirect();

        $this->assertSame('Santri Kelas Wali Update', $this->ownStudent->fresh()->name);
    }

    public function test_wali_kelas_tidak_dapat_mengubah_santri_kelas_lain(): void
    {
        $this->actingAs($this->wali)
            ->get(route('user.students.edit', [
                'userId' => $this->wali->id,
                'santriUuid' => $this->otherStudent->id,
            ]))
            ->assertForbidden();

        $this->actingAs($this->wali)
            ->put(route('user.students.update', [
                'userId' => $this->wali->id,
                'santriUuid' => $this->otherStudent->id,
            ]), $this->updatePayload(['name' => 'Tidak Boleh']))
            ->assertForbidden();

        $this->assertNotSame('Tidak Boleh', $this->otherStudent->fresh()->name);
    }

    public function test_wali_kelas_dapat_menambah_dan_memperbarui_mahrom_kelasnya(): void
    {
        $this->actingAs($this->wali)
            ->post(route('user.students.mahroms.store', [
                'userId' => $this->wali->id,
                'santriUuid' => $this->ownStudent->id,
            ]), [
                'name' => 'Bapak Wali Santri',
                'relationship' => 'ayah',
                'phone' => '081200001111',
                'is_active' => '1',
                'is_primary' => '1',
            ])
            ->assertRedirect();

        $mahrom = StudentMahrom::where('student_id', $this->ownStudent->id)
            ->where('relationship', 'ayah')->firstOrFail();

        $this->assertSame('081200001111', $mahrom->phone);

        $this->actingAs($this->wali)
            ->put(route('user.students.mahroms.update', [
                'userId' => $this->wali->id,
                'santriUuid' => $this->ownStudent->id,
                'mahromUuid' => $mahrom->id,
            ]), [
                'name' => 'Bapak Wali Santri',
                'relationship' => 'ayah',
                'phone' => '081200002222',
                'is_active' => '1',
                'is_primary' => '1',
            ])
            ->assertRedirect();

        $this->assertSame('081200002222', $mahrom->fresh()->phone);
    }

    public function test_wali_kelas_tidak_dapat_mengelola_mahrom_santri_kelas_lain(): void
    {
        $mahrom = StudentMahrom::create([
            'id' => (string) Str::uuid(),
            'student_id' => $this->otherStudent->id,
            'name' => 'Mahrom Kelas Lain',
            'relationship' => 'ayah',
            'is_active' => true,
            'is_primary' => true,
        ]);

        $this->actingAs($this->wali)
            ->get(route('user.students.mahroms.index', [
                'userId' => $this->wali->id,
                'santriUuid' => $this->otherStudent->id,
            ]))
            ->assertNotFound();

        $this->actingAs($this->wali)
            ->delete(route('user.students.mahroms.destroy', [
                'userId' => $this->wali->id,
                'santriUuid' => $this->otherStudent->id,
                'mahromUuid' => $mahrom->id,
            ]))
            ->assertNotFound();

        $this->assertDatabaseHas('student_mahroms', ['id' => $mahrom->id]);
    }

    public function test_perubahan_mahrom_wali_kelas_langsung_terbaca_di_form_izin_asrama(): void
    {
        $this->actingAs($this->wali)
            ->post(route('user.students.mahroms.store', [
                'userId' => $this->wali->id,
                'santriUuid' => $this->ownStudent->id,
            ]), [
                'name' => 'Ibu Santri Sinkron',
                'relationship' => 'ibu',
                'phone' => '081233334444',
                'is_active' => '1',
                'is_primary' => '0',
            ])
            ->assertRedirect();

        // Halaman izin asrama membaca data mahrom terbaru (SSOT, tanpa cache basi).
        $this->actingAs($this->admin)
            ->get(route('user.asrama.permits.create', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]))
            ->assertOk()
            ->assertSee('Ibu Santri Sinkron')
            ->assertSee('081233334444');
    }

    public function test_admin_tetap_dapat_mengelola_santri_semua_kelas(): void
    {
        $this->actingAs($this->admin)
            ->get(route('user.students.edit', [
                'userId' => $this->admin->id,
                'santriUuid' => $this->otherStudent->id,
            ]))
            ->assertOk();
    }

    public function test_wali_kelas_tidak_dapat_mengarsipkan_atau_mengubah_status_santri(): void
    {
        $this->actingAs($this->wali)
            ->delete(route('user.students.destroy', [
                'userId' => $this->wali->id,
                'santriUuid' => $this->ownStudent->id,
            ]))
            ->assertForbidden();

        $this->actingAs($this->wali)
            ->put(route('user.students.update', [
                'userId' => $this->wali->id,
                'santriUuid' => $this->ownStudent->id,
            ]), $this->updatePayload([
                'status' => 'graduate',
                'graduation_year' => 2027,
                'graduation_date' => '2027-06-20',
            ]))
            ->assertRedirect();

        $this->assertSame('active', $this->ownStudent->fresh()->status, 'Status lifecycle hanya boleh diubah admin.');
    }
}
