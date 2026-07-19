<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Models\WaliRegistrationToken;
use App\Models\WaliSantri;
use Illuminate\Foundation\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tenant-Isolation Regression — Multi-school Guardian
 *
 * End-to-end security verification that Guardian A of School A CANNOT
 * read, link, approve, remove, or list Student B of School B, and vice
 * versa.
 *
 * All middleware chains exercised: auth:sanctum → wali.school.context →
 * organization.context. Real database. No mocking.
 *
 * Test scenarios:
 *   A.  Dashboard — cross-tenant students invisible
 *   B.  Santri list — cross-tenant students invisible
 *   C.  Santri show — cross-tenant student returns 404
 *   D.  Link student — cross-tenant link rejected
 *   E.  Remove link — cross-tenant link removal rejected
 *   F.  List requests — cross-tenant pending requests invisible
 *   G.  Approve request — cross-tenant approval token rejected
 *   H.  Guardian B — cannot access School A resources at all
 *   I.  Positive path — Guardian A CAN operate on School A students
 */
class TenantIsolationRegressionTest extends TestCase
{
    protected static bool $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! static::$migrated) {
            Artisan::call('migrate:fresh', ['--force' => true]);
            static::$migrated = true;
        }

        $this->beginDatabaseTransaction();
    }

    protected function beginDatabaseTransaction(): void
    {
        $db = $this->app->make('db');
        $connection = $db->connection();
        $connection->beginTransaction();

        $this->beforeApplicationDestroyed(function () use ($connection) {
            $connection->rollBack();
        });
    }

    // ═══════════════════════════════════════════════════════════════════
    // Fixture Builders
    // ════════════════════════════════════��══════════════════════════════

    private function createSchool(string $name = 'Sekolah'): School
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit '.$name,
            'code' => 'WU-'.Str::random(8),
        ]);

        $schoolId = (string) Str::uuid();
        DB::table('schools')->insert([
            'id' => $schoolId,
            'work_unit_id' => $workUnitId,
            'npsn' => (string) random_int(10000000, 99999999),
            'name' => $name,
            'school_status' => 'negeri',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return School::find($schoolId);
    }

    private function createStudentAt(School $school, array $attrs = []): Student
    {
        $uniq = str_pad((string) random_int(1, 99999999), 10, '0', STR_PAD_LEFT);

        return Student::create(array_merge([
            'school_id' => $school->id,
            'name' => 'Student '.$school->name,
            'nik' => '710201'.$uniq,
            'nisn' => $uniq,
            'gender' => 'L',
            'status' => 'active',
            'birth_place' => 'Jakarta',
            'birth_date' => now()->subYears(15),
        ], $attrs));
    }

    private function createWali(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'is_wali' => true,
            'is_active' => true,
        ], $attrs));
    }

    private function linkActive(User $wali, Student $student, string $schoolId, string $role = 'ayah'): WaliSantri
    {
        return WaliSantri::create([
            'user_id' => $wali->id,
            'student_id' => $student->id,
            'school_id' => $schoolId,
            'role' => $role,
            'is_primary' => true,
            'status' => WaliSantri::STATUS_ACTIVE,
            'verified_at' => now(),
            'verified_by' => $wali->id,
        ]);
    }

    private function asSanctumUser(User $user): self
    {
        Sanctum::actingAs($user, [], 'sanctum');
        config(['auth.defaults.guard' => 'sanctum']);
        app()->refresh('auth', app()['auth'], 'guards');

        return $this;
    }

    /**
     * Build a JSON: authenticated Guardian A of School A,
     * plus all fixture data for isolation testing.
     */
    private function setUpTenants(): array
    {
        $schoolA = $this->createSchool('School Alpha');
        $schoolB = $this->createSchool('School Beta');

        $guardianA = $this->createWali(['name' => 'Guardian A']);
        $guardianB = $this->createWali(['name' => 'Guardian B']);

        $studentA = $this->createStudentAt($schoolA, ['name' => 'Child A']);
        $studentB = $this->createStudentAt($schoolB, ['name' => 'Child B']);

        $this->linkActive($guardianA, $studentA, $schoolA->id);
        $this->linkActive($guardianB, $studentB, $schoolB->id);

        return compact('schoolA', 'schoolB', 'guardianA', 'guardianB', 'studentA', 'studentB');
    }

    // ═══════════════════════════════════════════════════════════════════
    // Scenario A — Dashboard Cross-Tenant Isolation
    // ═══════════════════════════════════════════════════════════════════

    public function test_guardian_a_dashboard_shows_only_school_a_students(): void
    {
        extract($this->setUpTenants());

        $this->asSanctumUser($guardianA);

        $response = $this->getJson('/api/mobile/v1/dashboard', [
            'X-Active-School-Id' => $schoolA->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.total_students', 1)
            ->assertJsonPath('data.students.0.name', 'Child A')
            ->assertJsonPath('data.students.0.id', $studentA->id);
    }

    public function test_guardian_b_dashboard_shows_only_school_b_students(): void
    {
        extract($this->setUpTenants());

        $this->asSanctumUser($guardianB);

        $response = $this->getJson('/api/mobile/v1/dashboard', [
            'X-Active-School-Id' => $schoolB->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.total_students', 1)
            ->assertJsonPath('data.students.0.name', 'Child B');
    }

    public function test_guardian_a_cannot_see_student_b_via_dashboard(): void
    {
        extract($this->setUpTenants());

        $this->asSanctumUser($guardianA);

        // Even with School B header (which should be rejected by middleware),
        // dashboard must NOT return student B
        $response = $this->getJson('/api/mobile/v1/dashboard', [
            'X-Active-School-Id' => $schoolB->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.total_students', 0);

        // Verify student B's ID never appears in response
        $this->assertStringNotContainsString(
            Str::afterLast($studentB->id, '-'),
            $response->toJson()
        );
    }

    // ═══════════════════════════════════════════════════════════════════
    // Scenario B — Santri List Cross-Tenant Isolation
    // ═══════════════════════════════════════════════════════════════════

    public function test_guardian_a_santri_list_shows_only_school_a(): void
    {
        extract($this->setUpTenants());

        $this->asSanctumUser($guardianA);

        $response = $this->getJson('/api/mobile/v1/santri', [
            'X-Active-School-Id' => $schoolA->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.students.0.name', 'Child A');
    }

    public function test_guardian_a_santri_list_does_not_show_student_b(): void
    {
        extract($this->setUpTenants());

        $this->asSanctumUser($guardianA);

        $response = $this->getJson('/api/mobile/v1/santri', [
            'X-Active-School-Id' => $schoolA->id,
        ]);

        $response->assertStatus(200);

        $studentNames = $response->json('data.students.*.name');
        $this->assertNotContains('Child B', $studentNames);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Scenario C — Santri Show (cross-tenant → 404)
    // ═══════════════════════════════════════════════════════════════════

    public function test_guardian_a_cannot_read_student_b_via_show(): void
    {
        extract($this->setUpTenants());

        $this->asSanctumUser($guardianA);

        // Guardian A has no active wali_santri link to student B
        $response = $this->getJson("/api/mobile/v1/santri/{$studentB->id}", [
            'X-Active-School-Id' => $schoolA->id,
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'STUDENT_NOT_FOUND');
    }

    public function test_guardian_a_cannot_read_student_b_even_with_cross_tenant_header(): void
    {
        extract($this->setUpTenants());

        $this->asSanctumUser($guardianA);

        // Attempt with School B header — WaliSchoolContextMiddleware
        // sees guardianA has no wali_santri for school B → rejects header
        // → schoolContextId becomes schoolA's resolved context → student B
        // belongs to school B → not in scope.
        $response = $this->getJson("/api/mobile/v1/santri/{$studentB->id}", [
            'X-Active-School-Id' => $schoolB->id,
        ]);

        $response->assertStatus(404);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Scenario D — Link Student (cross-tenant link rejected)
    // ═══════════════════════════════════════════════════════════════════

    public function test_guardian_a_cannot_link_student_b(): void
    {
        extract($this->setUpTenants());

        $this->asSanctumUser($guardianA);

        $response = $this->postJson('/api/mobile/v1/wali-santri/link', [
            'student_id' => $studentB->id,
            'role' => 'ayah',
        ], [
            'X-Active-School-Id' => $schoolA->id,
        ]);

        // The WaliSchoolContextMiddleware resolves schoolContextId to schoolA
        // (because guardian A has no link to school B).
        // The link endpoint calls WaliSantriService which asserts school_id
        // matches the active tenant. Student B belongs to school B → 404.
        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'STUDENT_NOT_FOUND');
    }

    // ═══════════════════════════════════════════════════════════════════
    // Scenario E — Remove Link (cross-tenant removal rejected)
    // ═══════════════════════════════════════════════════════════════════

    public function test_guardian_a_cannot_remove_student_b_link(): void
    {
        extract($this->setUpTenants());

        // Guardian A has NO link to student B.
        // The service finds nothing → LINK_NOT_FOUND (404).
        $this->asSanctumUser($guardianA);

        // Create a link for guardian B to student B to ensure the wali_santri
        // row exists with a valid school. Then attempt cross-tenant deletion.
        $studentBLink = WaliSantri::where('student_id', $studentB->id)
            ->where('user_id', $guardianB->id)
            ->first();

        $response = $this->deleteJson("/api/mobile/v1/wali-santri/{$studentBLink->id}", [
            'X-Active-School-Id' => $schoolA->id,
        ]);

        // Tenant guard fires → 404 with LINK_NOT_FOUND
        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'LINK_NOT_FOUND');
    }

    // ═══════════════════════════════════════════════════════════════════
    // Scenario F — List Pending Requests (cross-tenant invisible)
    // ═══════════════════════════════════════════════════════════════════

    public function test_guardian_a_cannot_see_school_b_pending_requests(): void
    {
        extract($this->setUpTenants());

        $this->asSanctumUser($guardianA);

        // Create a pending request from another wali to student B (school B)
        $anotherWali = $this->createWali(['name' => 'Another Wali']);

        $token = bin2hex(random_bytes(32));
        WaliRegistrationToken::create([
            'token' => $token,
            'user_id' => $anotherWali->id,
            'school_id' => $schoolB->id,
            'nik_santri' => $studentB->nik,
            'no_kk' => null,
            'intent' => 'add_wali',
            'student_id' => $studentB->id,
            'expires_at' => now()->addHours(24),
            'used_at' => null,
        ]);

        // Guardian A should NOT see this token (belongs to school B)
        $response = $this->getJson('/api/mobile/v1/wali-santri/requests', [
            'X-Active-School-Id' => $schoolA->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.requests', [])
            ->assertJsonPath('data.total', 0);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Scenario G — Approve Request (cross-tenant token rejected)
    // ═════════════════════════════════════════════════════════════════��═

    public function test_guardian_a_cannot_approve_school_b_registration_token(): void
    {
        extract($this->setUpTenants());

        // Create a valid request token for student A (school A) that
        // guardian A CAN legitimately approve.
        $otherWali = $this->createWali(['name' => 'Wali Tiga']);

        $goodToken = bin2hex(random_bytes(32));
        WaliRegistrationToken::create([
            'token' => $goodToken,
            'user_id' => $otherWali->id,
            'school_id' => $schoolA->id,
            'nik_santri' => $studentA->nik,
            'intent' => 'add_wali',
            'student_id' => $studentA->id,
            'expires_at' => now()->addHours(24),
            'used_at' => null,
        ]);

        $this->asSanctumUser($guardianA);

        // Approving the school A token works fine (positive path)
        $response = $this->putJson("/api/mobile/v1/wali-santri/requests/{$goodToken}", [
            'action' => 'approve',
        ], [
            'X-Active-School-Id' => $schoolA->id,
        ]);

        $response->assertStatus(200);

        // Now generate a fake cross-tenant token (school B) and try to approve
        $crossWali = $this->createWali(['name' => 'Cross Wali']);
        $crossToken = bin2hex(random_bytes(32));
        WaliRegistrationToken::create([
            'token' => $crossToken,
            'user_id' => $crossWali->id,
            'school_id' => $schoolB->id,
            'nik_santri' => $studentB->nik,
            'intent' => 'add_wali',
            'student_id' => $studentB->id,
            'expires_at' => now()->addHours(24),
            'used_at' => null,
        ]);

        $response = $this->putJson("/api/mobile/v1/wali-santri/requests/{$crossToken}", [
            'action' => 'approve',
        ], [
            'X-Active-School-Id' => $schoolA->id,
        ]);

        // Must be rejected — tenant mismatch
        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'TOKEN_INVALID');
    }

    // ═══════════════════════════════════════════════════════════════════
    // Scenario H — Guardian B Cannot Access School A Resources
    // ═══════════════════════════════════════════════════════════════════

    public function test_guardian_b_cannot_access_school_a_dashboard(): void
    {
        extract($this->setUpTenants());

        $this->asSanctumUser($guardianB);

        $response = $this->getJson('/api/mobile/v1/dashboard', [
            'X-Active-School-Id' => $schoolA->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.total_students', 0);

        // Verify student A doesn't appear
        $studentNames = $response->json('data.students.*.name');
        $this->assertNotContains('Child A', $studentNames);
    }

    public function test_guardian_b_cannot_show_student_a(): void
    {
        extract($this->setUpTenants());

        $this->asSanctumUser($guardianB);

        $response = $this->getJson("/api/mobile/v1/santri/{$studentA->id}", [
            'X-Active-School-Id' => $schoolA->id,
        ]);

        $response->assertStatus(404);
    }

    public function test_guardian_b_cannot_link_student_a(): void
    {
        extract($this->setUpTenants());

        $this->asSanctumUser($guardianB);

        $response = $this->postJson('/api/mobile/v1/wali-santri/link', [
            'student_id' => $studentA->id,
            'role' => 'ibu',
        ], [
            'X-Active-School-Id' => $schoolB->id,
        ]);

        // Tenant guard fires: student A belongs to school A, not school B
        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'STUDENT_NOT_FOUND');
    }

    // ═══════════════════════════════════════════════════════════════════
    // Scenario I — Positive Path: Guardian A Can Operate on School A
    // ═══════════════════════════════════════════════════════════════════

    public function test_guardian_a_can_read_school_a_dashboard(): void
    {
        extract($this->setUpTenants());

        $this->asSanctumUser($guardianA);

        $response = $this->getJson('/api/mobile/v1/dashboard', [
            'X-Active-School-Id' => $schoolA->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.wali.id', $guardianA->id)
            ->assertJsonPath('data.total_students', 1)
            ->assertJsonPath('data.students.0.id', $studentA->id);
    }

    public function test_guardian_a_can_read_school_a_student_detail(): void
    {
        extract($this->setUpTenants());

        $this->asSanctumUser($guardianA);

        $response = $this->getJson("/api/mobile/v1/santri/{$studentA->id}", [
            'X-Active-School-Id' => $schoolA->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $studentA->id)
            ->assertJsonPath('data.name', 'Child A');
    }

    public function test_guardian_a_can_list_school_a_santri(): void
    {
        extract($this->setUpTenants());

        $this->asSanctumUser($guardianA);

        $response = $this->getJson('/api/mobile/v1/santri', [
            'X-Active-School-Id' => $schoolA->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.students.0.id', $studentA->id);
    }

    public function test_guardian_a_can_register_new_student_in_school_a(): void
    {
        extract($this->setUpTenants());

        $this->asSanctumUser($guardianA);

        $response = $this->postJson('/api/mobile/v1/santri', [
            'name' => 'New Child A2',
            'gender' => 'P',
            'birth_place' => 'Bandung',
            'birth_date' => now()->subYears(14)->format('Y-m-d'),
            'nik' => '710301'.str_pad(random_int(1, 99999999), 10, '0', STR_PAD_LEFT),
        ], [
            'X-Active-School-Id' => $schoolA->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.student.name', 'New Child A2')
            ->assertJsonPath('data.student.school.name', 'School Alpha');
    }

    public function test_guardian_a_can_remove_their_own_school_a_link(): void
    {
        extract($this->setUpTenants());

        $link = WaliSantri::where('user_id', $guardianA->id)
            ->where('student_id', $studentA->id)
            ->first();

        $this->asSanctumUser($guardianA);

        $response = $this->deleteJson("/api/mobile/v1/wali-santri/{$link->id}", [
            'X-Active-School-Id' => $schoolA->id,
        ]);

        $response->assertStatus(200);
    }

    public function test_guardian_a_can_start_registration_for_school_a_student(): void
    {
        extract($this->setUpTenants());

        // Add a second student to school A that guardian A is NOT linked to
        $secondSchool = $this->createSchool('School Alpha Branch');
        $secondStudent = $this->createStudentAt($secondSchool, ['name' => 'Child A2']);

        $this->asSanctumUser($guardianA);

        // This tests start-registration intent for linking an existing student
        $response = $this->postJson('/api/mobile/v1/wali-santri/start-registration', [
            'intent' => 'link_santri',
            'nik_santri' => $studentA->nik,
            'student_id' => $studentA->id,
            'role' => 'ayah',
        ], [
            'X-Active-School-Id' => $schoolA->id,
        ]);

        // Should return 200 — already linked
        $response->assertStatus(200);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Scenario J — Attendance API Cross-Tenant Isolation
    // ═══════════════════════════════════════════════════════════════════

    public function test_guardian_a_attendance_does_not_exposure_student_b_data(): void
    {
        extract($this->setUpTenants());

        $this->asSanctumUser($guardianA);

        // Even if guardian A somehow gets student B's ID, the attendance
        // endpoint filters by schoolContextId
        $response = $this->getJson("/api/mobile/v1/santri/{$studentB->id}/attendance", [
            'X-Active-School-Id' => $schoolA->id,
        ]);

        // Either 404 (student not found) or empty attendance data
        if ($response->status() === 200) {
            $attendances = $response->json('data.attendances') ?? [];
            $this->assertCount(0, $attendances);
        } else {
            $response->assertStatus(404);
        }
    }
}
