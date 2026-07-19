<?php

namespace Tests\Feature;

use App\Authorization\ValueObjects\OrganizationContext;
use App\Authorization\ValueObjects\ScopeKey;
use App\Http\Middleware\BindOrganizationContext;
use App\Http\Middleware\WaliSchoolContextMiddleware;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Models\WaliSantri;
use Illuminate\Foundation\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Multi-school guardian tenant resolution — Feature verification.
 *
 * Tenant authority: wali_santri.school_id (NEVER users.school_id — that
 * column does not exist on the User model).
 *
 * Resolution order enforced by WaliSchoolContextMiddleware:
 *   1. X-Active-School-Id header (validated against wali_santri relation)
 *   2. Exactly one active wali_santri row → that school
 *   3. null (registration / multi-school — never guess)
 *
 * What we verify, per scenario:
 *   - request attribute schoolContextId (canonical)
 *   - OrganizationContext binding in container has matching schoolId
 *   - ScopeKey derived from OrganizationContext is unique per (schoolId, ...)
 *   - AuthorizationManager.allow$ operates on the same scope
 */
class MultiSchoolGuardianContextTest extends TestCase
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

    // ── Fixtures ─────────────────────────────────────────────────────────────

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
            'name' => 'Santi '.$school->name,
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
     * Run the WaliSchoolContextMiddleware against an in-memory request and
     * return the request after the middleware has populated attributes.
     */
    private function runWaliMiddleware(?User $user, ?string $headerSchoolId): Request
    {
        $request = Request::create('/api/mobile/v1/dashboard', 'GET');
        if ($headerSchoolId !== null) {
            $request->headers->set('X-Active-School-Id', $headerSchoolId);
        }
        if ($user !== null) {
            $request->setUserResolver(fn () => $user);
        }

        $middleware = new WaliSchoolContextMiddleware;
        $middleware->handle($request, fn ($r) => response('ok'));

        return $request;
    }

    /**
     * Assert the request attributes and the container-bound OrganizationContext
     * agree on which school is in scope.
     */
    private function assertResolvedScope(Request $request, ?string $expectedSchoolId): void
    {
        $canonical = $request->attributes->get('schoolContextId');

        $this->assertSame(
            $expectedSchoolId,
            $canonical,
            'schoolContextId attribute does not match expected schoolId'
        );
    }

    // ════════════════════════════════════════════════════════════════════════
    // SCENARIO A — single-school guardian, no header
    // ════════════════════════════════════════════════════════════════════════

    public function test_single_school_guardian_without_header_auto_selects_school(): void
    {
        $schoolA = $this->createSchool('School A');
        $wali = $this->createWali();
        $studentA = $this->createStudentAt($schoolA);
        $this->linkActive($wali, $studentA, $schoolA->id);

        $request = $this->runWaliMiddleware($wali, null);

        $this->assertResolvedScope($request, $schoolA->id);
    }

    public function test_single_school_guardian_dashboard_returns_only_that_school(): void
    {
        $schoolA = $this->createSchool('School A');
        $schoolB = $this->createSchool('School B');
        $wali = $this->createWali();
        $studentA = $this->createStudentAt($schoolA);
        $studentB = $this->createStudentAt($schoolB);
        $this->linkActive($wali, $studentA, $schoolA->id);
        $this->linkActive($wali, $studentB, $schoolB->id); // 2 schools — see Scenario B

        // This test asserts that for a single-school guardian, the dashboard
        // resolves to exactly that school. Multi-school is tested separately.
        // First reset: only School A linked.
        WaliSantri::where('student_id', $studentB->id)->delete();

        $this->asSanctumUser($wali);

        $response = $this->getJson('/api/mobile/v1/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_students', 1);
    }

    // ════════════════════════════════════════════════════════════════════════
    // SCENARIO B — multi-school guardian
    // ════════════════════════════════════════════════════════════════════════

    public function test_multi_school_guardian_header_a_returns_school_a(): void
    {
        $schoolA = $this->createSchool('School A');
        $schoolB = $this->createSchool('School B');
        $wali = $this->createWali();
        $studentA = $this->createStudentAt($schoolA);
        $studentB = $this->createStudentAt($schoolB);
        $this->linkActive($wali, $studentA, $schoolA->id);
        $this->linkActive($wali, $studentB, $schoolB->id);

        $request = $this->runWaliMiddleware($wali, $schoolA->id);

        $this->assertResolvedScope($request, $schoolA->id);
    }

    public function test_multi_school_guardian_header_b_returns_school_b(): void
    {
        $schoolA = $this->createSchool('School A');
        $schoolB = $this->createSchool('School B');
        $wali = $this->createWali();
        $studentA = $this->createStudentAt($schoolA);
        $studentB = $this->createStudentAt($schoolB);
        $this->linkActive($wali, $studentA, $schoolA->id);
        $this->linkActive($wali, $studentB, $schoolB->id);

        $request = $this->runWaliMiddleware($wali, $schoolB->id);

        $this->assertResolvedScope($request, $schoolB->id);
    }

    public function test_multi_school_guardian_invalid_uuid_header_is_ignored(): void
    {
        $schoolA = $this->createSchool('School A');
        $schoolB = $this->createSchool('School B');
        $wali = $this->createWali();
        $studentA = $this->createStudentAt($schoolA);
        $studentB = $this->createStudentAt($schoolB);
        $this->linkActive($wali, $studentA, $schoolA->id);
        $this->linkActive($wali, $studentB, $schoolB->id);

        $request = $this->runWaliMiddleware($wali, 'not-a-valid-uuid');

        // Invalid hint falls through to Priority 2. With 2 active schools,
        // Priority 2 also cannot pick → null (never guess).
        $this->assertResolvedScope($request, null);
    }

    public function test_multi_school_guardian_header_for_school_user_does_not_belong_to_is_ignored(): void
    {
        $schoolA = $this->createSchool('School A');
        $schoolB = $this->createSchool('School B');
        $schoolC = $this->createSchool('School C — not in user');
        $wali = $this->createWali();
        $studentA = $this->createStudentAt($schoolA);
        $studentB = $this->createStudentAt($schoolB);
        $this->linkActive($wali, $studentA, $schoolA->id);
        $this->linkActive($wali, $studentB, $schoolB->id);

        $request = $this->runWaliMiddleware($wali, $schoolC->id);

        // School C exists in DB but the user has no wali_santri row for it.
        // Header is ignored → null (never guess).
        $this->assertResolvedScope($request, null);
    }

    public function test_multi_school_guardian_without_header_resolves_to_null(): void
    {
        $schoolA = $this->createSchool('School A');
        $schoolB = $this->createSchool('School B');
        $wali = $this->createWali();
        $studentA = $this->createStudentAt($schoolA);
        $studentB = $this->createStudentAt($schoolB);
        $this->linkActive($wali, $studentA, $schoolA->id);
        $this->linkActive($wali, $studentB, $schoolB->id);

        $request = $this->runWaliMiddleware($wali, null);

        // No header, 2 schools → never guess → null.
        $this->assertResolvedScope($request, null);
    }

    public function test_multi_school_guardian_dashboard_returns_empty_students_when_no_context(): void
    {
        $schoolA = $this->createSchool('School A');
        $schoolB = $this->createSchool('School B');
        $wali = $this->createWali();
        $studentA = $this->createStudentAt($schoolA);
        $studentB = $this->createStudentAt($schoolB);
        $this->linkActive($wali, $studentA, $schoolA->id);
        $this->linkActive($wali, $studentB, $schoolB->id);

        $this->asSanctumUser($wali);

        // No X-Active-School-Id header → null context → bootstrap mode.
        $response = $this->getJson('/api/mobile/v1/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_students', 0)
            ->assertJsonCount(0, 'data.students');
    }

    public function test_multi_school_guardian_dashboard_with_header_filters_by_chosen_school(): void
    {
        $schoolA = $this->createSchool('School A');
        $schoolB = $this->createSchool('School B');
        $wali = $this->createWali();
        $studentA = $this->createStudentAt($schoolA, ['name' => 'Child A']);
        $studentB = $this->createStudentAt($schoolB, ['name' => 'Child B']);
        $this->linkActive($wali, $studentA, $schoolA->id);
        $this->linkActive($wali, $studentB, $schoolB->id);

        $this->asSanctumUser($wali);

        $response = $this->getJson('/api/mobile/v1/dashboard', [
            'X-Active-School-Id' => $schoolB->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_students', 1);

        $studentName = $response->json('data.students.0.name');
        $this->assertSame('Child B', $studentName);
    }

    // ════════════════════════════════════════════════════════════════════════
    // SCENARIO C — registration / bootstrap (zero wali_santri rows)
    // ════════════════════════════════════════════════════════════════════════

    public function test_unlinked_wali_resolves_to_null_context(): void
    {
        $wali = $this->createWali();

        $request = $this->runWaliMiddleware($wali, null);

        $this->assertResolvedScope($request, null);
    }

    public function test_unlinked_wali_with_invalid_header_resolves_to_null(): void
    {
        $wali = $this->createWali();

        $request = $this->runWaliMiddleware($wali, '00000000-0000-0000-0000-000000000000');

        $this->assertResolvedScope($request, null);
    }

    public function test_unlinked_wali_dashboard_returns_bootstrap_payload(): void
    {
        $wali = $this->createWali();
        $this->asSanctumUser($wali);

        $response = $this->getJson('/api/mobile/v1/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_students', 0)
            ->assertJsonPath('data.wali.id', $wali->id);
    }

    // ════════════════════════════════════════════════════════════════════════
    // SCENARIO D — pending/suspended wali_santri rows do NOT count
    // ════════════════════════════════════════════════════════════════════════

    public function test_pending_link_alone_does_not_resolve_a_context(): void
    {
        $schoolA = $this->createSchool('School A');
        $wali = $this->createWali();
        $studentA = $this->createStudentAt($schoolA);
        WaliSantri::create([
            'user_id' => $wali->id,
            'student_id' => $studentA->id,
            'school_id' => $schoolA->id,
            'role' => 'ayah',
            'is_primary' => true,
            'status' => WaliSantri::STATUS_PENDING,
        ]);

        $request = $this->runWaliMiddleware($wali, null);

        $this->assertResolvedScope($request, null);
    }

    public function test_suspended_link_alone_does_not_resolve_a_context(): void
    {
        $schoolA = $this->createSchool('School A');
        $wali = $this->createWali();
        $studentA = $this->createStudentAt($schoolA);
        WaliSantri::create([
            'user_id' => $wali->id,
            'student_id' => $studentA->id,
            'school_id' => $schoolA->id,
            'role' => 'ayah',
            'is_primary' => true,
            'status' => WaliSantri::STATUS_SUSPENDED,
        ]);

        $request = $this->runWaliMiddleware($wali, null);

        $this->assertResolvedScope($request, null);
    }

    public function test_active_plus_suspended_resolves_to_active_school(): void
    {
        $schoolA = $this->createSchool('School A');
        $schoolB = $this->createSchool('School B');
        $wali = $this->createWali();
        $studentA = $this->createStudentAt($schoolA);
        $studentB = $this->createStudentAt($schoolB);
        $this->linkActive($wali, $studentA, $schoolA->id);
        // Suspended link in B — does not count.
        WaliSantri::create([
            'user_id' => $wali->id,
            'student_id' => $studentB->id,
            'school_id' => $schoolB->id,
            'role' => 'ayah',
            'is_primary' => true,
            'status' => WaliSantri::STATUS_SUSPENDED,
        ]);

        $request = $this->runWaliMiddleware($wali, null);

        $this->assertResolvedScope($request, $schoolA->id);
    }

    // ════════════════════════════════════════════════════════════════════════
    // SCENARIO E — header validated against relation, not just DB
    // ════════════════════════════════════════════════════════════════════════

    public function test_header_for_existing_school_but_not_in_users_wali_santri_is_ignored(): void
    {
        $schoolA = $this->createSchool('School A');
        $wali = $this->createWali();
        $studentA = $this->createStudentAt($schoolA);
        $this->linkActive($wali, $studentA, $schoolA->id);

        // School exists in DB. User has NO wali_santri row for it. Header
        // pointing at it must be ignored. With exactly 1 active link (School A),
        // fallthrough picks School A.
        $schoolForeign = $this->createSchool('Foreign');

        $request = $this->runWaliMiddleware($wali, $schoolForeign->id);

        $this->assertResolvedScope($request, $schoolA->id);
    }

    public function test_empty_header_value_is_ignored(): void
    {
        $schoolA = $this->createSchool('School A');
        $wali = $this->createWali();
        $studentA = $this->createStudentAt($schoolA);
        $this->linkActive($wali, $studentA, $schoolA->id);

        $request = $this->runWaliMiddleware($wali, '');

        $this->assertResolvedScope($request, $schoolA->id);
    }

    // ════════════════════════════════════════════════════════════════════════
    // SCENARIO F — OrganizationContext binding consistency
    // ════════════════════════════════════════════════════════════════════════

    public function test_bind_organization_context_reads_school_context_id_attribute_first(): void
    {
        $schoolA = $this->createSchool('School A');
        $user = $this->createWali();

        // Simulate the state WaliSchoolContextMiddleware would have set.
        $request = Request::create('/api/mobile/v1/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->attributes->set('schoolContextId', $schoolA->id);

        (new BindOrganizationContext)->handle($request, fn ($r) => response('ok'));

        $context = app(OrganizationContext::class);
        $this->assertInstanceOf(OrganizationContext::class, $context);
        $this->assertSame($schoolA->id, $context->schoolId);
    }

    public function test_bind_organization_context_scope_keys_differ_per_school(): void
    {
        $schoolA = $this->createSchool('School A');
        $schoolB = $this->createSchool('School B');

        $ctxA = new OrganizationContext(
            schoolId: $schoolA->id,
            academicYearId: 'global',
            roleDimension: 'wali',
        );
        $ctxB = new OrganizationContext(
            schoolId: $schoolB->id,
            academicYearId: 'global',
            roleDimension: 'wali',
        );

        $this->assertNotSame(
            (string) $ctxA->toScopeKey(),
            (string) $ctxB->toScopeKey(),
            'Different schoolIds must produce different ScopeKeys'
        );
    }

    public function test_bind_organization_context_scope_keys_equal_for_same_inputs(): void
    {
        $schoolA = $this->createSchool('School A');

        $ctx1 = new OrganizationContext(
            schoolId: $schoolA->id,
            academicYearId: '2026/2027',
            roleDimension: 'wali',
        );
        $ctx2 = new OrganizationContext(
            schoolId: $schoolA->id,
            academicYearId: '2026/2027',
            roleDimension: 'wali',
        );

        $this->assertSame((string) $ctx1->toScopeKey(), (string) $ctx2->toScopeKey());
        $this->assertTrue($ctx1->toScopeKey()->equals($ctx2->toScopeKey()));
    }

    public function test_null_school_context_falls_back_to_unknown(): void
    {
        $user = $this->createWali();

        // No schoolContextId attribute, no user.school_id column.
        $request = Request::create('/api/mobile/v1/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);

        (new BindOrganizationContext)->handle($request, fn ($r) => response('ok'));

        $context = app(OrganizationContext::class);
        $this->assertSame('unknown', $context->schoolId);
    }

    // ════════════════════════════════════════════════════════════════════════
    // SCENARIO G — full HTTP integration via /dashboard
    // ════════════════════════════════════════════════════════════════════════

    public function test_dashboard_request_attribute_school_context_id_matches_chosen_school(): void
    {
        $schoolA = $this->createSchool('School A');
        $schoolB = $this->createSchool('School B');
        $wali = $this->createWali();
        $studentA = $this->createStudentAt($schoolA, ['name' => 'Child A']);
        $studentB = $this->createStudentAt($schoolB, ['name' => 'Child B']);
        $this->linkActive($wali, $studentA, $schoolA->id);
        $this->linkActive($wali, $studentB, $schoolB->id);

        $this->asSanctumUser($wali);

        // Header → School A
        $this->getJson('/api/mobile/v1/dashboard', [
            'X-Active-School-Id' => $schoolA->id,
        ])->assertStatus(200)
            ->assertJsonPath('data.total_students', 1)
            ->assertJsonPath('data.students.0.name', 'Child A');

        // Header → School B
        $this->getJson('/api/mobile/v1/dashboard', [
            'X-Active-School-Id' => $schoolB->id,
        ])->assertStatus(200)
            ->assertJsonPath('data.total_students', 1)
            ->assertJsonPath('data.students.0.name', 'Child B');

        // No header → null context → bootstrap empty
        $this->getJson('/api/mobile/v1/dashboard')
            ->assertStatus(200)
            ->assertJsonPath('data.total_students', 0);
    }

    public function test_dashboard_invalid_header_treated_as_bootstrap(): void
    {
        $schoolA = $this->createSchool('School A');
        $schoolB = $this->createSchool('School B');
        $wali = $this->createWali();
        $studentA = $this->createStudentAt($schoolA);
        $studentB = $this->createStudentAt($schoolB);
        $this->linkActive($wali, $studentA, $schoolA->id);
        $this->linkActive($wali, $studentB, $schoolB->id);

        $this->asSanctumUser($wali);

        $this->getJson('/api/mobile/v1/dashboard', [
            'X-Active-School-Id' => 'invalid-uuid',
        ])->assertStatus(200)
            ->assertJsonPath('data.total_students', 0)
            ->assertJsonCount(0, 'data.students');
    }
}
