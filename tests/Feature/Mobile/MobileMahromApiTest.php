<?php

namespace Tests\Feature\Mobile;

use App\Models\Student;
use App\Models\StudentMahrom;
use App\Models\User;
use App\Models\WaliSantri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileMahromApiTest extends TestCase
{
    use RefreshDatabase;

    private function createSchool(): \App\Models\School
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Test',
            'code' => 'WU-'.Str::random(8),
        ]);

        $schoolId = (string) Str::uuid();
        DB::table('schools')->insert([
            'id' => $schoolId,
            'work_unit_id' => $workUnitId,
            'npsn' => (string) random_int(10000000, 99999999),
            'name' => 'Sekolah Test',
            'school_status' => 'negeri',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return \App\Models\School::find($schoolId);
    }

    private function createWali(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'is_wali' => true,
            'is_active' => true,
        ], $attrs));
    }

    private function createStudent(array $attrs = []): Student
    {
        $school = $this->createSchool();
        $uniq = str_pad((string) random_int(1, 99999999), 10, '0', STR_PAD_LEFT);

        return Student::create(array_merge([
            'school_id' => $school->id,
            'name' => 'Fulan',
            'nik' => '710201'.$uniq,
            'nisn' => $uniq,
            'gender' => 'L',
            'status' => 'active',
            'birth_place' => 'Jakarta',
            'birth_date' => now()->subYears(15),
        ], $attrs));
    }

    private function linkWaliToStudent(User $wali, Student $student): void
    {
        WaliSantri::create([
            'user_id' => $wali->id,
            'student_id' => $student->id,
            'school_id' => $student->school_id,
            'role' => 'ayah',
            'is_primary' => true,
            'status' => WaliSantri::STATUS_ACTIVE,
        ]);
    }

    private function asWali(User $user): void
    {
        Sanctum::actingAs($user, [], 'sanctum');
        config(['auth.defaults.guard' => 'sanctum']);
        app()->refresh('auth', app()['auth'], 'guards');
    }

    private function createMahrom(string $studentId, array $attrs = []): StudentMahrom
    {
        return StudentMahrom::create(array_merge([
            'student_id' => $studentId,
            'name' => 'Bapak',
            'relationship' => 'ayah',
            'is_active' => true,
            'is_primary' => false,
        ], $attrs));
    }

    // ── Index ──────────────────────────────────────────────────────────────

    public function test_index_returns_404_when_wali_has_no_access(): void
    {
        $wali = $this->createWali();
        $this->asWali($wali);

        $stu = $this->createStudent();

        $response = $this->getJson("/api/mobile/v1/students/{$stu->id}/mahrom");

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'STUDENT_NOT_FOUND');
    }

    public function test_index_lists_mahrom_for_linked_student(): void
    {
        $wali = $this->createWali();
        $stu = $this->createStudent();
        $this->linkWaliToStudent($wali, $stu);
        $this->asWali($wali);

        $this->createMahrom($stu->id, ['name' => 'Bapak', 'relationship' => 'ayah', 'is_primary' => true]);
        $this->createMahrom($stu->id, ['name' => 'Ibu', 'relationship' => 'ibu']);

        $response = $this->getJson("/api/mobile/v1/students/{$stu->id}/mahrom");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.records')
            ->assertJsonPath('data.records.0.nama', 'Bapak');
    }

    // ── Store ──────────────────────────────────────────────────────────────

    public function test_store_creates_mahrom_with_valid_payload(): void
    {
        $wali = $this->createWali();
        $stu = $this->createStudent();
        $this->linkWaliToStudent($wali, $stu);
        $this->asWali($wali);

        $response = $this->postJson("/api/mobile/v1/students/{$stu->id}/mahrom", [
            'nama' => 'Ayah',
            'hubungan' => 'ayah',
            'nomor_hp' => '081234567890',
            'is_primary' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.mahrom.nama', 'Ayah')
            ->assertJsonPath('data.mahrom.hubungan', 'ayah')
            ->assertJsonPath('data.mahrom.is_primary', true)
            ->assertJsonPath('data.mahrom.is_active', true);

        $this->assertDatabaseHas('student_mahroms', [
            'student_id' => $stu->id,
            'name' => 'Ayah',
            'relationship' => 'ayah',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $wali = $this->createWali();
        $stu = $this->createStudent();
        $this->linkWaliToStudent($wali, $stu);
        $this->asWali($wali);

        $response = $this->postJson("/api/mobile/v1/students/{$stu->id}/mahrom", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrorFor('nama')
            ->assertJsonValidationErrorFor('hubungan');
    }

    public function test_store_validates_invalid_hubungan_value(): void
    {
        $wali = $this->createWali();
        $stu = $this->createStudent();
        $this->linkWaliToStudent($wali, $stu);
        $this->asWali($wali);

        $response = $this->postJson("/api/mobile/v1/students/{$stu->id}/mahrom", [
            'nama' => 'Test',
            'hubungan' => 'not_a_value',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrorFor('hubungan');
    }

    public function test_store_validates_unique_nik(): void
    {
        $wali = $this->createWali();
        $stu = $this->createStudent();
        $this->linkWaliToStudent($wali, $stu);
        $this->asWali($wali);

        $this->createMahrom($stu->id, ['id_number' => '3201000000000001']);

        $response = $this->postJson("/api/mobile/v1/students/{$stu->id}/mahrom", [
            'nama' => 'Test',
            'hubungan' => 'ayah',
            'nik' => '3201000000000001',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrorFor('nik');
    }

    public function test_store_clears_other_primary_when_set(): void
    {
        $wali = $this->createWali();
        $stu = $this->createStudent();
        $this->linkWaliToStudent($wali, $stu);
        $this->asWali($wali);

        $first = $this->createMahrom($stu->id, ['name' => 'First', 'is_primary' => true]);

        $this->postJson("/api/mobile/v1/students/{$stu->id}/mahrom", [
            'nama' => 'Second',
            'hubungan' => 'ibu',
            'is_primary' => true,
        ])->assertStatus(201);

        $this->assertDatabaseHas('student_mahroms', [
            'id' => $first->id,
            'is_primary' => false,
        ]);
        $this->assertDatabaseHas('student_mahroms', [
            'name' => 'Second',
            'is_primary' => true,
        ]);
    }

    public function test_store_returns_404_when_wali_has_no_access(): void
    {
        $wali = $this->createWali();
        $this->asWali($wali);
        $stu = $this->createStudent();

        $response = $this->postJson("/api/mobile/v1/students/{$stu->id}/mahrom", [
            'nama' => 'Test',
            'hubungan' => 'ayah',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'STUDENT_NOT_FOUND');
    }

    public function test_store_requires_authentication(): void
    {
        $stu = $this->createStudent();

        $response = $this->postJson("/api/mobile/v1/students/{$stu->id}/mahrom", [
            'nama' => 'Test',
            'hubungan' => 'ayah',
        ]);

        $response->assertStatus(401);
    }

    // ── Update ─────────────────────────────────────────────────────────────

    public function test_update_modifies_mahrom(): void
    {
        $wali = $this->createWali();
        $stu = $this->createStudent();
        $this->linkWaliToStudent($wali, $stu);
        $this->asWali($wali);

        $m = $this->createMahrom($stu->id, ['name' => 'Old Name']);

        $response = $this->putJson("/api/mobile/v1/students/{$stu->id}/mahrom/{$m->id}", [
            'nama' => 'New Name',
            'hubungan' => 'ibu',
            'nomor_hp' => '081234567890',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.mahrom.nama', 'New Name')
            ->assertJsonPath('data.mahrom.hubungan', 'ibu');

        $this->assertDatabaseHas('student_mahroms', [
            'id' => $m->id,
            'name' => 'New Name',
            'relationship' => 'ibu',
            'phone' => '081234567890',
        ]);
    }

    public function test_update_can_deactivate_mahrom(): void
    {
        $wali = $this->createWali();
        $stu = $this->createStudent();
        $this->linkWaliToStudent($wali, $stu);
        $this->asWali($wali);

        $m = $this->createMahrom($stu->id, ['is_active' => true]);

        $response = $this->putJson("/api/mobile/v1/students/{$stu->id}/mahrom/{$m->id}", [
            'nama' => 'Mahrom X',
            'hubungan' => 'ayah',
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.mahrom.is_active', false);

        $this->assertDatabaseHas('student_mahroms', [
            'id' => $m->id,
            'is_active' => false,
        ]);
    }

    public function test_update_returns_404_for_unknown_mahrom(): void
    {
        $wali = $this->createWali();
        $stu = $this->createStudent();
        $this->linkWaliToStudent($wali, $stu);
        $this->asWali($wali);

        $response = $this->putJson("/api/mobile/v1/students/{$stu->id}/mahrom/".Str::uuid(), [
            'nama' => 'Test',
            'hubungan' => 'ayah',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'MAHROM_NOT_FOUND');
    }

    public function test_update_validates_required_fields(): void
    {
        $wali = $this->createWali();
        $stu = $this->createStudent();
        $this->linkWaliToStudent($wali, $stu);
        $this->asWali($wali);

        $m = $this->createMahrom($stu->id);

        $response = $this->putJson("/api/mobile/v1/students/{$stu->id}/mahrom/{$m->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrorFor('nama')
            ->assertJsonValidationErrorFor('hubungan');
    }

    // ── Destroy ────────────────────────────────────────────────────────────

    public function test_destroy_removes_mahrom(): void
    {
        $wali = $this->createWali();
        $stu = $this->createStudent();
        $this->linkWaliToStudent($wali, $stu);
        $this->asWali($wali);

        $m = $this->createMahrom($stu->id);

        $response = $this->deleteJson("/api/mobile/v1/students/{$stu->id}/mahrom/{$m->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.deleted', true);

        $this->assertDatabaseMissing('student_mahroms', ['id' => $m->id]);
    }

    public function test_destroy_returns_404_for_unknown_mahrom(): void
    {
        $wali = $this->createWali();
        $stu = $this->createStudent();
        $this->linkWaliToStudent($wali, $stu);
        $this->asWali($wali);

        $response = $this->deleteJson("/api/mobile/v1/students/{$stu->id}/mahrom/".Str::uuid());

        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'MAHROM_NOT_FOUND');
    }
}
