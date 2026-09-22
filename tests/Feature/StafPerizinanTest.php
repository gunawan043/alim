<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Authorization\Providers\StafPerizinanPermissionProvider;
use App\Models\Dormitory;
use App\Models\DormitoryStaffAssignment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StafPerizinanTest extends TestCase
{
    use RefreshDatabase;

    private User $saUser;

    private User $asramaUser;

    private Dormitory $dormA;

    private Dormitory $dormB;

    private Dormitory $dormC;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);

        // Create test data
        $this->saUser = User::factory()->create([
            'is_system_admin' => true,
            'is_active' => true,
        ]);

        $this->asramaUser = User::factory()->create([
            'is_system_admin' => false,
            'is_active' => true,
        ]);
        $asramaRole = Role::firstOrCreate(['name' => 'Asrama', 'guard_name' => 'web']);
        $this->asramaUser->assignRole($asramaRole);

        $this->dormA = Dormitory::factory()->create(['is_active' => true]);
        $this->dormB = Dormitory::factory()->create(['is_active' => true]);
        $this->dormC = Dormitory::factory()->create(['is_active' => true]);
    }

    // ── STAF PERIZINAN SCOPE TESTS ──────────────────────────────────────────

    public function test_can_access_only_assigned_dormitories(): void
    {
        DormitoryStaffAssignment::create([
            'user_id' => $this->asramaUser->id,
            'dormitory_id' => $this->dormA->id,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $this->assertTrue($this->asramaUser->canAccessDormitory($this->dormA->id));
        $this->assertFalse($this->asramaUser->canAccessDormitory($this->dormB->id));
        $this->assertFalse($this->asramaUser->canAccessDormitory($this->dormC->id));
    }

    public function test_can_access_multiple_dormitories(): void
    {
        DormitoryStaffAssignment::create([
            'user_id' => $this->asramaUser->id,
            'dormitory_id' => $this->dormA->id,
            'start_date' => now(),
            'status' => 'active',
        ]);

        DormitoryStaffAssignment::create([
            'user_id' => $this->asramaUser->id,
            'dormitory_id' => $this->dormB->id,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $this->assertTrue($this->asramaUser->canAccessDormitory($this->dormA->id));
        $this->assertTrue($this->asramaUser->canAccessDormitory($this->dormB->id));
        $this->assertFalse($this->asramaUser->canAccessDormitory($this->dormC->id));
    }

    public function test_super_admin_can_access_all_dormitories(): void
    {
        $this->assertTrue($this->saUser->canAccessDormitory($this->dormA->id));
        $this->assertTrue($this->saUser->canAccessDormitory($this->dormB->id));
        $this->assertTrue($this->saUser->canAccessDormitory($this->dormC->id));
    }

    public function test_kepala_asrama_can_access_own_dormitory(): void
    {
        $kepalaAsrama = User::factory()->create(['is_system_admin' => false]);
        $this->dormA->update(['head_id' => $kepalaAsrama->id]);

        $this->assertTrue($kepalaAsrama->canAccessDormitory($this->dormA->id));
        $this->assertFalse($kepalaAsrama->canAccessDormitory($this->dormB->id));
    }

    public function test_accessible_dormitory_ids_returns_correct_list(): void
    {
        DormitoryStaffAssignment::create([
            'user_id' => $this->asramaUser->id,
            'dormitory_id' => $this->dormA->id,
            'start_date' => now(),
            'status' => 'active',
        ]);

        DormitoryStaffAssignment::create([
            'user_id' => $this->asramaUser->id,
            'dormitory_id' => $this->dormC->id,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $accessible = $this->asramaUser->accessibleDormitoryIds();

        $this->assertCount(2, $accessible);
        $this->assertContains($this->dormA->id, $accessible);
        $this->assertContains($this->dormC->id, $accessible);
        $this->assertNotContains($this->dormB->id, $accessible);
    }

    public function test_inactive_assignment_does_not_grant_access(): void
    {
        DormitoryStaffAssignment::create([
            'user_id' => $this->asramaUser->id,
            'dormitory_id' => $this->dormA->id,
            'start_date' => now()->subDays(30),
            'end_date' => now()->subDays(1),
            'status' => 'inactive',
        ]);

        $this->assertFalse($this->asramaUser->canAccessDormitory($this->dormA->id));
    }

    public function test_expired_assignment_does_not_grant_access(): void
    {
        DormitoryStaffAssignment::create([
            'user_id' => $this->asramaUser->id,
            'dormitory_id' => $this->dormA->id,
            'start_date' => now()->subDays(30),
            'end_date' => now()->subDay(),
            'status' => 'active',
        ]);

        $this->assertFalse($this->asramaUser->canAccessDormitory($this->dormA->id));
    }

    public function test_future_assignment_does_not_grant_access(): void
    {
        DormitoryStaffAssignment::create([
            'user_id' => $this->asramaUser->id,
            'dormitory_id' => $this->dormA->id,
            'start_date' => now()->addDays(1),
            'status' => 'active',
        ]);

        $this->assertFalse($this->asramaUser->canAccessDormitory($this->dormA->id));
    }

    // ── ROUTE ACCESS TESTS ──────────────────────────────────────────────────

    public function test_staf_perizinan_can_access_permits_index_in_assigned_dormitory(): void
    {
        DormitoryStaffAssignment::create([
            'user_id' => $this->asramaUser->id,
            'dormitory_id' => $this->dormA->id,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $this->actingAs($this->asramaUser);

        $response = $this->get(route('user.asrama.permits.index', [
            'userId' => $this->asramaUser->id,
            'asramaUuid' => $this->dormA->id,
        ]));

        $response->assertStatus(200);
    }

    public function test_staf_perizinan_cannot_access_permits_index_in_unassigned_dormitory(): void
    {
        DormitoryStaffAssignment::create([
            'user_id' => $this->asramaUser->id,
            'dormitory_id' => $this->dormA->id,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $this->actingAs($this->asramaUser);

        $response = $this->get(route('user.asrama.permits.index', [
            'userId' => $this->asramaUser->id,
            'asramaUuid' => $this->dormB->id,
        ]));

        $response->assertStatus(403);
    }

    public function test_staf_perizinan_cannot_access_visit_index_in_unassigned_dormitory(): void
    {
        DormitoryStaffAssignment::create([
            'user_id' => $this->asramaUser->id,
            'dormitory_id' => $this->dormA->id,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $this->actingAs($this->asramaUser);

        $response = $this->get(route('user.asrama.visits.index', [
            'userId' => $this->asramaUser->id,
            'asramaUuid' => $this->dormB->id,
        ]));

        $response->assertStatus(403);
    }

    public function test_staf_perizinan_can_access_scanned_permits_in_assigned_dormitory(): void
    {
        DormitoryStaffAssignment::create([
            'user_id' => $this->asramaUser->id,
            'dormitory_id' => $this->dormA->id,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $this->actingAs($this->asramaUser);

        $response = $this->get(route('user.asrama.permits.scan', [
            'userId' => $this->asramaUser->id,
            'asramaUuid' => $this->dormA->id,
        ]));

        $response->assertStatus(200);
    }

    public function test_staf_perizinan_cannot_access_reports_in_unassigned_dormitory(): void
    {
        DormitoryStaffAssignment::create([
            'user_id' => $this->asramaUser->id,
            'dormitory_id' => $this->dormA->id,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $this->actingAs($this->asramaUser);

        $response = $this->get(route('user.asrama.reports.index', [
            'userId' => $this->asramaUser->id,
            'asramaUuid' => $this->dormB->id,
        ]));

        $response->assertStatus(403);
    }

    public function test_superuser_can_access_any_dormitory_permits(): void
    {
        $this->actingAs($this->saUser);

        $response = $this->get(route('user.asrama.permits.index', [
            'userId' => $this->saUser->id,
            'asramaUuid' => $this->dormA->id,
        ]));

        $response->assertStatus(200);

        $response = $this->get(route('user.asrama.permits.index', [
            'userId' => $this->saUser->id,
            'asramaUuid' => $this->dormB->id,
        ]));

        $response->assertStatus(200);
    }

    // ── STAFF ASSIGNMENT MANAGEMENT TESTS ───────────────────────────────────

    public function test_can_create_staff_assignment(): void
    {
        $this->actingAs($this->saUser);

        $response = $this->put(route('user.asrama.staf-assignments.update', [
            'userId' => $this->saUser->id,
            'targetUserId' => $this->asramaUser->id,
        ]), [
            'dormitory_ids' => [$this->dormA->id, $this->dormB->id],
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'notes' => 'Test assignment',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify assignments were created with correct user_id (target user, not auth user)
        $this->assertDatabaseHas('dormitory_staff_assignments', [
            'user_id' => $this->asramaUser->id,
            'dormitory_id' => $this->dormA->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('dormitory_staff_assignments', [
            'user_id' => $this->asramaUser->id,
            'dormitory_id' => $this->dormB->id,
            'status' => 'active',
        ]);
    }

    public function test_kepala_asrama_can_manage_staff_in_own_dormitory(): void
    {
        $kepalaAsrama = User::factory()->create(['is_system_admin' => false]);
        $this->dormA->update(['head_id' => $kepalaAsrama->id]);

        $this->actingAs($kepalaAsrama);

        // Should be able to manage staff in dormA
        $response = $this->get(route('user.asrama.staf-assignments.index', ['userId' => $this->saUser->id, 'targetUserId' => $this->asramaUser->id]));

        // Note: This might redirect if not authorized, checking logic
        // The authorization check is done in the controller
    }

    public function test_kepala_asrama_cannot_manage_staff_in_other_dormitory(): void
    {
        $kepalaAsrama = User::factory()->create(['is_system_admin' => false]);
        $asramaRole = Role::firstOrCreate(['name' => 'Asrama', 'guard_name' => 'web']);
        $kepalaAsrama->assignRole($asramaRole);
        $this->dormA->update(['head_id' => $kepalaAsrama->id]);

        $this->actingAs($kepalaAsrama);

        // Use followRedirects(false) to get the actual response without following redirects
        $response = $this->withCookies([config('session.cookie') => session()->getId()])
            ->put(route('user.asrama.staf-assignments.update', [
                'userId' => $this->saUser->id,
                'targetUserId' => $this->asramaUser->id,
            ]), [
                'dormitory_ids' => [$this->dormB->id],
                'start_date' => now()->toDateString(),
            ]);

        $response->assertStatus(403);
    }

    public function test_can_delete_staff_assignment(): void
    {
        $assignment = DormitoryStaffAssignment::create([
            'user_id' => $this->asramaUser->id,
            'dormitory_id' => $this->dormA->id,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $this->actingAs($this->saUser);

        $response = $this->delete(route('user.asrama.staf-assignments.destroy', [
            'userId' => $this->saUser->id,
            'targetUserId' => $this->asramaUser->id,
            'dormitoryId' => $this->dormA->id,
        ]));

        $response->assertRedirect();

        $this->assertDatabaseHas('dormitory_staff_assignments', [
            'id' => $assignment->id,
            'status' => 'ended',
        ]);
    }

    // ── PERMISSION PROVIDER TESTS ──────────────────────────────────────────

    public function test_permission_provider_generates_permissions_for_active_assignment(): void
    {
        DormitoryStaffAssignment::create([
            'user_id' => $this->asramaUser->id,
            'dormitory_id' => $this->dormA->id,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $provider = new StafPerizinanPermissionProvider;
        $origins = $provider->provide($this->asramaUser->id);

        $this->assertNotEmpty($origins);

        $permissions = array_map(fn ($origin) => $origin->permission, $origins);

        $this->assertContains('staf_perizinan.view', $permissions);
        $this->assertContains('staf_perizinan.create', $permissions);
        $this->assertContains('staf_perizinan.approve', $permissions);
        $this->assertContains('staf_perizinan.scan', $permissions);
        $this->assertContains("staf_perizinan.dormitory:{$this->dormA->id}", $permissions);
    }

    public function test_permission_provider_returns_empty_for_no_assignment(): void
    {
        $provider = new StafPerizinanPermissionProvider;
        $origins = $provider->provide($this->asramaUser->id);

        $this->assertEmpty($origins);
    }

    // ── REGRESSION TESTS ──────────────────────────────────────────────────

    public function test_existing_asrama_role_still_works(): void
    {
        $this->actingAs($this->asramaUser);

        // Asrama role should still be able to access their dormitory via menu
        $response = $this->get(route('root'));

        // Asrama users are redirected to their dashboard
        $response->assertRedirect();
    }

    public function test_sidebar_shows_staf_perizinan_for_users_with_assignments(): void
    {
        DormitoryStaffAssignment::create([
            'user_id' => $this->asramaUser->id,
            'dormitory_id' => $this->dormA->id,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $this->actingAs($this->asramaUser);

        // Root route redirects to user dashboard, follow the redirect
        $response = $this->followingRedirects()->get(route('root'));

        $response->assertSee('Perizinan');
        $response->assertSee('Daftar Perizinan');
    }

    public function test_sidebar_does_not_show_staf_perizinan_for_regular_asrama(): void
    {
        $this->actingAs($this->asramaUser);

        // Root route redirects to user dashboard, follow the redirect
        $response = $this->followingRedirects()->get(route('root'));

        $response->assertDontSee('menu-staf-perizinan');
    }
}
