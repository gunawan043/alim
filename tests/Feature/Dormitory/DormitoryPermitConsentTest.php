<?php

declare(strict_types=1);

namespace Tests\Feature\Dormitory;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryDataConsent;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoom;
use App\Models\DormitoryStaffAssignment;
use App\Models\DormitoryWing;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DormitoryPermitConsentTest extends TestCase
{
    use RefreshDatabase;

    private Dormitory $dormitory;

    private DormitoryRoom $room;

    private Student $student;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dormitory = Dormitory::factory()->create(['is_active' => true, 'gender' => 'putra']);

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
            'name' => 'Sayap 01',
            'gender' => 'putra',
        ]);

        $this->room = DormitoryRoom::create([
            'dormitory_id' => $this->dormitory->id,
            'wing_id' => $wing->id,
            'code' => 'K-01',
            'name' => 'Kamar 01',
            'gender' => 'putra',
            'capacity' => 10,
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'school_id' => $this->dormitory->school_id,
            'nisn' => '9999200002',
            'name' => 'Santri Consent Uji',
            'gender' => 'L',
            'status' => 'active',
        ]);

        DormitoryResident::create([
            'dormitory_id' => $this->dormitory->id,
            'student_id' => $this->student->id,
            'room_id' => $this->room->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
            'check_in_date' => now()->toDateString(),
        ]);

        $this->admin = User::factory()->create([
            'is_system_admin' => true,
            'is_active' => true,
        ]);
        $this->admin->assignRole(Role::firstOrCreate(['name' => 'Asrama', 'guard_name' => 'web']));

        DormitoryStaffAssignment::create([
            'user_id' => $this->admin->id,
            'dormitory_id' => $this->dormitory->id,
            'start_date' => now()->subDay(),
            'status' => 'active',
        ]);
    }

    private function permitPayload(): array
    {
        return [
            'student_id' => $this->student->id,
            'room_id' => $this->room->id,
            'permit_type' => 'pulang',
            'destination' => 'Rumah',
            'purpose' => 'Pulang bulanan',
            'departure_datetime' => now()->addHour()->format('Y-m-d H:i:s'),
            'expected_return_datetime' => now()->addDay()->format('Y-m-d H:i:s'),
            'guardian_consent_confirmed' => 1,
        ];
    }

    public function test_izin_ditolak_bila_consent_belum_dicatat(): void
    {
        $this->actingAs($this->admin)
            ->post(route('user.asrama.permits.store', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]), $this->permitPayload())
            ->assertSessionHasErrors('consent');

        $this->assertDatabaseCount('dormitory_permits', 0);
    }

    public function test_pesan_error_menyebut_nama_santri(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('user.asrama.permits.store', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]), $this->permitPayload());

        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertStringContainsString('Santri Consent Uji', $errors->first('consent'));
    }

    public function test_form_menandai_santri_tanpa_consent(): void
    {
        $this->actingAs($this->admin)
            ->get(route('user.asrama.permits.create', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]))
            ->assertOk()
            ->assertSee('consentMissingBanner')
            ->assertSee('"has_consent":false', false);
    }

    public function test_izin_lolos_gerbang_consent_setelah_consent_dicatat(): void
    {
        DormitoryDataConsent::create([
            'dormitory_id' => $this->dormitory->id,
            'student_id' => $this->student->id,
            'guardian_name' => 'Wali Santri',
            'relationship' => 'Ayah',
            'consent_version' => 'v1',
            'consented_at' => now(),
            'recorded_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('user.asrama.permits.store', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]), $this->permitPayload());

        $response->assertSessionDoesntHaveErrors('consent');
    }

    public function test_gerbang_consent_dapat_dinonaktifkan_dari_pengaturan(): void
    {
        \App\Support\AsramaConfig::setConsentRequired(false);

        $response = $this->actingAs($this->admin)
            ->post(route('user.asrama.permits.store', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]), $this->permitPayload());

        $response->assertSessionDoesntHaveErrors('consent');
        $this->assertDatabaseCount('dormitory_permits', 1);
    }

    public function test_form_tidak_menampilkan_banner_consent_saat_dinonaktifkan(): void
    {
        \App\Support\AsramaConfig::setConsentRequired(false);

        $this->actingAs($this->admin)
            ->get(route('user.asrama.permits.create', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]))
            ->assertOk()
            ->assertDontSee('id="consentMissingBanner"', false)
            ->assertSee('"has_consent":true', false);
    }

    public function test_override_asrama_tetap_bisa_mewajibkan_consent_saat_global_nonaktif(): void
    {
        \App\Support\AsramaConfig::setConsentRequired(false);

        app(\App\Services\Boarding\VisitWorkflowService::class)
            ->saveDormitoryConsentRequirement($this->dormitory->id, true);

        $this->actingAs($this->admin)
            ->post(route('user.asrama.permits.store', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]), $this->permitPayload())
            ->assertSessionHasErrors('consent');

        $this->assertDatabaseCount('dormitory_permits', 0);
    }

    public function test_pengaturan_consent_per_asrama_dapat_disimpan_dari_halaman_kebijakan(): void
    {
        $this->dormitory->update(['head_id' => $this->admin->id]);
        \App\Support\AsramaConfig::setConsentRequired(true);

        $this->actingAs($this->admin)
            ->post(route('user.asrama.leave-policies.consent', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]), ['guardian_consent_required' => '0'])
            ->assertRedirect();

        $this->assertFalse(
            \App\Support\AsramaConfig::consentRequiredForDormitory($this->dormitory->id)
        );

        $this->actingAs($this->admin)
            ->post(route('user.asrama.permits.store', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]), $this->permitPayload())
            ->assertSessionDoesntHaveErrors('consent');
    }
}
