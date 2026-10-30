<?php

namespace Tests\Feature;

use App\Models\GtkEmployment;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Models\WaliSantri;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Alur penghubungan wali–santri:
 * preview identitas → konfirmasi → link aktif/pending → approval sekolah.
 */
class WaliLinkApiTest extends TestCase
{
    use RefreshDatabase;

    private function school(string $name = 'Sekolah Test'): School
    {
        $workUnit = WorkUnit::factory()->create(['name' => 'Unit '.$name, 'is_active' => true]);

        return School::factory()->create([
            'name' => $name,
            'work_unit_id' => $workUnit->id,
            'is_active' => true,
        ]);
    }

    private function student(School $school, array $attrs = []): Student
    {
        return Student::create(array_merge([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'name' => 'Ahmad Santri',
            'nisn' => $this->digits(10),
            'nik' => $this->digits(16),
            'no_kk' => $this->digits(16),
            'gender' => 'L',
            'birth_place' => 'Mataram',
            'birth_date' => '2012-01-01',
            'mother_name' => 'Siti Aisah',
            'status' => 'active',
        ], $attrs));
    }

    private function wali(string $name = 'Wali Santri'): User
    {
        return User::factory()->create([
            'name' => $name,
            'is_active' => true,
            'is_wali' => true,
        ]);
    }

    private function superAdmin(): User
    {
        return User::factory()->create([
            'name' => 'Super Admin',
            'is_active' => true,
            'is_system_admin' => true,
        ]);
    }

    private function tataUsaha(School $school, string $jabatan = 'Kepala Tata Usaha'): User
    {
        $user = User::factory()->create([
            'name' => 'Petugas '.$jabatan,
            'is_active' => true,
        ]);

        GtkEmployment::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Tenaga Kependidikan',
            'jabatan' => $jabatan,
        ]);

        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web']);
        $user->assignRole($role);

        return $user->fresh();
    }

    private function digits(int $length): string
    {
        $number = '';

        for ($i = 0; $i < $length; $i++) {
            $number .= (string) random_int(0, 9);
        }

        return $number;
    }

    /**
     * @return array<string, mixed>
     */
    private function previewPayload(Student $student, string $motherName = 'Siti Aisyah', string $relationship = 'ayah'): array
    {
        return [
            'identity_number' => (string) $student->nisn,
            'no_kk' => (string) $student->no_kk,
            'mother_name' => $motherName,
            'relationship' => $relationship,
        ];
    }

    public function test_parent_can_preview_and_link_immediately_with_typo_tolerant_mother_name(): void
    {
        $school = $this->school();
        $student = $this->student($school);
        $wali = $this->wali();
        $superAdmin = $this->superAdmin();
        $tu = $this->tataUsaha($school);

        Sanctum::actingAs($wali);

        $preview = $this->postJson('/api/mobile/v1/'.$wali->id.'/wali/links/preview', $this->previewPayload($student));

        $preview->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.requires_approval', false)
            ->assertJsonPath('data.student.id', $student->id);

        $token = (string) $preview->json('data.verification_token');
        $this->assertSame(64, strlen($token));

        $confirm = $this->postJson('/api/mobile/v1/'.$wali->id.'/wali/links/confirm', [
            'verification_token' => $token,
        ]);

        $confirm->assertCreated()
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('wali_santri', [
            'user_id' => $wali->id,
            'student_id' => $student->id,
            'status' => 'active',
            'is_primary' => true,
        ]);

        $this->assertDatabaseHas('notifications_universal', [
            'user_id' => $superAdmin->id,
            'action' => 'wali_link_active',
        ]);

        $this->assertDatabaseHas('notifications_universal', [
            'user_id' => $tu->id,
            'action' => 'wali_link_active',
        ]);

        $this->assertDatabaseMissing('notifications_universal', [
            'user_id' => $wali->id,
            'action' => 'wali_link_active',
        ]);
    }

    public function test_wrong_mother_name_is_rejected_and_generic(): void
    {
        $school = $this->school();
        $student = $this->student($school);
        $wali = $this->wali();

        Sanctum::actingAs($wali);

        $response = $this->postJson('/api/mobile/v1/'.$wali->id.'/wali/links/preview', $this->previewPayload($student, 'Nama Ibu Lain'));

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.code', 'WALI_LINK_MISMATCH');

        $this->assertDatabaseCount('wali_link_verifications', 0);
        $this->assertDatabaseCount('wali_santri', 0);
    }

    public function test_identity_field_accepts_nik_as_alternative_to_nisn(): void
    {
        $school = $this->school();
        $student = $this->student($school);
        $wali = $this->wali();

        Sanctum::actingAs($wali);

        $payload = $this->previewPayload($student);
        $payload['identity_number'] = (string) $student->nik;

        $this->postJson('/api/mobile/v1/'.$wali->id.'/wali/links/preview', $payload)
            ->assertOk()
            ->assertJsonPath('data.student.id', $student->id);
    }

    public function test_non_parent_link_requires_school_approval(): void
    {
        $school = $this->school();
        $student = $this->student($school);
        $wali = $this->wali();
        $tu = $this->tataUsaha($school, 'Staf Tata Usaha');

        Sanctum::actingAs($wali);

        $preview = $this->postJson('/api/mobile/v1/'.$wali->id.'/wali/links/preview', $this->previewPayload($student, 'Siti Aisyah', 'wali'));
        $preview->assertOk()->assertJsonPath('data.requires_approval', true);

        $confirm = $this->postJson('/api/mobile/v1/'.$wali->id.'/wali/links/confirm', [
            'verification_token' => (string) $preview->json('data.verification_token'),
        ]);

        $confirm->assertStatus(202)->assertJsonPath('data.status', 'pending');

        $link = WaliSantri::where('user_id', $wali->id)->where('student_id', $student->id)->firstOrFail();
        $this->assertSame('pending', $link->status);

        // Wali biasa tidak boleh approve.
        $this->getJson('/api/mobile/v1/'.$wali->id.'/wali/links/admin/pending')->assertForbidden();

        Sanctum::actingAs($tu);

        $this->getJson('/api/mobile/v1/'.$tu->id.'/wali/links/admin/pending')
            ->assertOk()
            ->assertJsonPath('data.0.id', $link->id);

        $this->postJson("/api/mobile/v1/{$tu->id}/wali/links/admin/{$link->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.wali_santri.status', 'active');

        $this->assertDatabaseHas('wali_santri', [
            'id' => $link->id,
            'status' => 'active',
            'verified_by' => $tu->id,
        ]);
    }

    public function test_student_with_five_active_guardians_cannot_be_linked(): void
    {
        $school = $this->school();
        $student = $this->student($school);
        $newWali = $this->wali();

        for ($i = 0; $i < 5; $i++) {
            WaliSantri::create([
                'user_id' => $this->wali('Wali '.$i)->id,
                'student_id' => $student->id,
                'school_id' => $school->id,
                'role' => 'wali',
                'is_primary' => $i === 0,
                'status' => 'active',
                'verified_at' => now(),
            ]);
        }

        Sanctum::actingAs($newWali);

        $this->postJson('/api/mobile/v1/'.$newWali->id.'/wali/links/preview', $this->previewPayload($student))
            ->assertStatus(422)
            ->assertJsonPath('errors.code', 'WALI_LINK_LIMIT_REACHED');
    }

    public function test_siblings_can_be_linked_with_masked_candidates_and_identity_confirmation(): void
    {
        $school = $this->school();
        $first = $this->student($school, ['name' => 'Ahmad Santri', 'no_kk' => '5201000000000001']);
        $second = $this->student($school, [
            'name' => 'Aisyah Santri',
            'nisn' => $this->digits(10),
            'nik' => $this->digits(16),
            'no_kk' => '5201000000000001',
            'mother_name' => 'Siti Aisah',
        ]);
        $wali = $this->wali();

        Sanctum::actingAs($wali);

        $preview = $this->postJson('/api/mobile/v1/'.$wali->id.'/wali/links/preview', $this->previewPayload($first));
        $this->postJson('/api/mobile/v1/'.$wali->id.'/wali/links/confirm', [
            'verification_token' => (string) $preview->json('data.verification_token'),
        ])->assertCreated();

        $siblings = $this->getJson("/api/mobile/v1/{$wali->id}/wali/links/siblings/{$first->id}");
        $siblings->assertOk()->assertJsonPath('data.candidates.0.student_id', $second->id);

        $candidateName = (string) $siblings->json('data.candidates.0.nama');
        $this->assertStringContainsString('*', $candidateName);
        $this->assertNotSame($second->name, $candidateName);

        $siblingPreview = $this->postJson('/api/mobile/v1/'.$wali->id.'/wali/links/siblings/preview', [
            'student_id' => $second->id,
            'identity_number' => (string) $second->nisn,
            'relationship' => 'ibu',
        ]);

        $siblingPreview->assertOk();

        $this->postJson('/api/mobile/v1/'.$wali->id.'/wali/links/confirm', [
            'verification_token' => (string) $siblingPreview->json('data.verification_token'),
        ])->assertCreated();

        $this->assertDatabaseHas('wali_santri', [
            'user_id' => $wali->id,
            'student_id' => $second->id,
            'status' => 'active',
            'role' => 'ibu',
        ]);
    }

    public function test_preview_is_idempotent_when_already_linked(): void
    {
        $school = $this->school();
        $student = $this->student($school);
        $wali = $this->wali();

        Sanctum::actingAs($wali);

        $preview = $this->postJson('/api/mobile/v1/'.$wali->id.'/wali/links/preview', $this->previewPayload($student));
        $this->postJson('/api/mobile/v1/'.$wali->id.'/wali/links/confirm', [
            'verification_token' => (string) $preview->json('data.verification_token'),
        ])->assertCreated();

        $this->postJson('/api/mobile/v1/'.$wali->id.'/wali/links/preview', $this->previewPayload($student))
            ->assertOk()
            ->assertJsonPath('data.already_linked', true)
            ->assertJsonPath('data.status', 'active');
    }

    public function test_tu_can_suspend_and_revoke_link(): void
    {
        $school = $this->school();
        $student = $this->student($school);
        $wali = $this->wali();
        $tu = $this->tataUsaha($school, 'Admin Tata Usaha');

        Sanctum::actingAs($wali);

        $preview = $this->postJson('/api/mobile/v1/'.$wali->id.'/wali/links/preview', $this->previewPayload($student));
        $this->postJson('/api/mobile/v1/'.$wali->id.'/wali/links/confirm', [
            'verification_token' => (string) $preview->json('data.verification_token'),
        ])->assertCreated();

        $link = WaliSantri::where('user_id', $wali->id)->where('student_id', $student->id)->firstOrFail();

        Sanctum::actingAs($tu);

        $this->postJson("/api/mobile/v1/{$tu->id}/wali/links/admin/{$link->id}/suspend")
            ->assertOk()
            ->assertJsonPath('data.wali_santri.status', 'suspended');

        $this->postJson("/api/mobile/v1/{$tu->id}/wali/links/admin/{$link->id}/revoke")
            ->assertOk()
            ->assertJsonPath('data.wali_santri.status', 'revoked');

        $this->assertDatabaseHas('wali_santri', [
            'id' => $link->id,
            'status' => 'revoked',
        ]);
    }
}
