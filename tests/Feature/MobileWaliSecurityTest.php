<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Models\WaliSantri;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileWaliSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function school(string $name): School
    {
        $unit = WorkUnit::factory()->create(['name' => 'Unit '.$name, 'is_active' => true]);

        return School::factory()->create([
            'name' => $name,
            'work_unit_id' => $unit->id,
            'is_active' => true,
        ]);
    }

    private function student(School $school, string $name): Student
    {
        $unique = str_pad((string) random_int(1, 99999999), 10, '0', STR_PAD_LEFT);

        return Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'name' => $name,
            'nik' => '710201'.$unique,
            'nisn' => $unique,
            'gender' => 'L',
            'status' => 'active',
            'birth_place' => 'Mataram',
            'birth_date' => '2012-01-01',
        ]);
    }

    private function wali(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'is_wali' => true,
            'is_active' => true,
            'password' => Hash::make('password123'),
        ], $attributes));
    }

    private function link(User $wali, Student $student): void
    {
        WaliSantri::create([
            'user_id' => $wali->id,
            'student_id' => $student->id,
            'school_id' => $student->school_id,
            'role' => 'ayah',
            'is_primary' => true,
            'status' => WaliSantri::STATUS_ACTIVE,
            'verified_at' => now(),
            'verified_by' => $wali->id,
        ]);
    }

    public function test_gtk_account_cannot_login_to_wali_mobile(): void
    {
        $gtk = User::factory()->create([
            'email' => 'gtk-mobile@example.test',
            'is_wali' => false,
            'is_active' => true,
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/mobile/v1/auth/login', [
            'email' => $gtk->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email')
            ->assertJsonPath('errors.email.0', 'Aplikasi mobile ini hanya untuk akun wali santri.');

        $this->assertSame(0, $gtk->tokens()->count());
    }

    public function test_gtk_token_cannot_access_wali_student_endpoints(): void
    {
        $gtk = User::factory()->create(['is_wali' => false, 'is_active' => true]);
        $token = $gtk->createToken('mobile-test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mobile/v1/santri')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'WALI_ONLY');
    }

    public function test_multi_school_wali_can_list_and_select_school_context(): void
    {
        $schoolA = $this->school('Sekolah A');
        $schoolB = $this->school('Sekolah B');
        $wali = $this->wali();

        $this->link($wali, $this->student($schoolA, 'Santri A'));
        $this->link($wali, $this->student($schoolB, 'Santri B'));

        Sanctum::actingAs($wali, ['*'], 'sanctum');

        $response = $this->getJson('/api/mobile/v1/'.$wali->id.'/wali/schools');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.active_school_id', null)
            ->assertJsonPath('data.requires_selection', true)
            ->assertJsonCount(2, 'data.schools');

        $selected = $this->withHeader('X-Active-School-Id', $schoolA->id)
            ->getJson('/api/mobile/v1/'.$wali->id.'/wali/schools');

        $selected->assertOk()
            ->assertJsonPath('data.active_school_id', $schoolA->id)
            ->assertJsonPath('data.requires_selection', false);

        $this->withHeader('X-Active-School-Id', $schoolA->id)
            ->getJson('/api/mobile/v1/auth/me')
            ->assertOk()
            ->assertJsonCount(2, 'data.schools')
            ->assertJsonPath('data.active_school_id', $schoolA->id);
    }

    public function test_invalid_school_hint_does_not_escape_wali_scope(): void
    {
        $schoolA = $this->school('Sekolah Aman');
        $schoolB = $this->school('Sekolah Lain');
        $wali = $this->wali();

        $this->link($wali, $this->student($schoolA, 'Santri Aman'));

        Sanctum::actingAs($wali, ['*'], 'sanctum');

        $response = $this->withHeader('X-Active-School-Id', $schoolB->id)
            ->getJson('/api/mobile/v1/'.$wali->id.'/wali/schools');

        $response->assertOk()
            ->assertJsonPath('data.active_school_id', null)
            ->assertJsonPath('data.requires_selection', true);
    }

    public function test_wali_token_requires_domain_ability_and_cannot_overreach(): void
    {
        $school = $this->school('Sekolah Ability');
        $student = $this->student($school, 'Santri Ability');
        $wali = $this->wali();
        $this->link($wali, $student);

        Sanctum::actingAs($wali, ['dashboard.read'], 'sanctum');

        $this->withHeader('X-Active-School-Id', $school->id)
            ->getJson('/api/mobile/v1/santri/'.$student->id.'/grades')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'INSUFFICIENT_ABILITY')
            ->assertJsonPath('error.reauth_required', true);

        Sanctum::actingAs($wali, ['grades.read'], 'sanctum');

        $this->withHeader('X-Active-School-Id', $school->id)
            ->getJson('/api/mobile/v1/santri/'.$student->id.'/grades')
            ->assertOk();
    }

    public function test_legacy_guardian_with_active_link_receives_wali_abilities(): void
    {
        $school = $this->school('Sekolah Legacy');
        $student = $this->student($school, 'Santri Legacy');
        $guardian = $this->wali([
            'email' => 'legacy-guardian@example.test',
            'is_wali' => false,
        ]);
        $this->link($guardian, $student);

        $response = $this->postJson('/api/mobile/v1/auth/login', [
            'email' => $guardian->email,
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['child.read'])
            ->assertJsonFragment(['grades.read']);
    }
}
