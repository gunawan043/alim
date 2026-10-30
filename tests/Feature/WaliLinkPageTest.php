<?php

namespace Tests\Feature;

use App\Http\Middleware\BindOrganizationContext;
use App\Http\Middleware\DenyStudentOperatorAi;
use App\Http\Middleware\EnsureDormitoryStudentOperatorAccess;
use App\Http\Middleware\EnsureEmployeeAccess;
use App\Http\Middleware\EnsureRoleAccess;
use App\Http\Middleware\EnsureStudentPasswordChanged;
use App\Http\Middleware\RestrictAdminIp;
use App\Http\Middleware\SchoolContextMiddleware;
use App\Models\GtkEmployment;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Models\WaliSantri;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Halaman admin Penghubungan Wali (Super Admin + Tata Usaha).
 */
class WaliLinkPageTest extends TestCase
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

    private function tataUsaha(?School $school = null): User
    {
        $user = User::factory()->create([
            'name' => 'Petugas Tata Usaha',
            'is_active' => true,
        ]);

        GtkEmployment::create([
            'user_id' => $user->id,
            'school_id' => $school?->id,
            'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Tenaga Kependidikan',
            'jabatan' => 'Kepala Tata Usaha',
        ]);

        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web']);
        $user->assignRole($role);

        return $user->fresh();
    }

    private function pendingLink(School $school, Student $student, User $wali): WaliSantri
    {
        return WaliSantri::create([
            'user_id' => $wali->id,
            'student_id' => $student->id,
            'school_id' => $school->id,
            'role' => 'wali',
            'is_primary' => false,
            'status' => 'pending',
        ]);
    }

    /**
     * @return array<int, class-string>
     */
    private function routeGuards(): array
    {
        return [
            EnsureEmployeeAccess::class,
            EnsureRoleAccess::class,
            SchoolContextMiddleware::class,
            BindOrganizationContext::class,
            RestrictAdminIp::class,
            EnsureStudentPasswordChanged::class,
            DenyStudentOperatorAi::class,
            EnsureDormitoryStudentOperatorAccess::class,
        ];
    }

    private function digits(int $length): string
    {
        $number = '';
        for ($i = 0; $i < $length; $i++) {
            $number .= (string) random_int(0, 9);
        }

        return $number;
    }

    public function test_tata_usaha_can_open_page_and_see_pending_link(): void
    {
        $school = $this->school();
        $student = $this->student($school, ['name' => 'Santri Halaman']);
        $wali = $this->wali('Wali Halaman');
        $tu = $this->tataUsaha($school);
        $this->pendingLink($school, $student, $wali);

        $this->withoutMiddleware($this->routeGuards())
            ->actingAs($tu)
            ->get("/{$tu->id}/wali-links")
            ->assertOk()
            ->assertSee('Penghubungan Wali')
            ->assertSee('Santri Halaman')
            ->assertSee('Wali Halaman');
    }

    public function test_regular_wali_cannot_open_page(): void
    {
        $wali = $this->wali();

        $this->withoutMiddleware($this->routeGuards())
            ->actingAs($wali)
            ->get("/{$wali->id}/wali-links")
            ->assertForbidden();
    }

    public function test_tata_usaha_can_approve_pending_link_from_page(): void
    {
        $school = $this->school();
        $student = $this->student($school);
        $wali = $this->wali();
        $tu = $this->tataUsaha($school);
        $link = $this->pendingLink($school, $student, $wali);

        $response = $this->withoutMiddleware($this->routeGuards())
            ->actingAs($tu)
            ->post("/{$tu->id}/wali-links/{$link->id}/approve")
            ->assertRedirect();

        $this->assertDatabaseHas('wali_santri', [
            'id' => $link->id,
            'status' => 'active',
            'verified_by' => $tu->id,
        ]);
    }

    public function test_super_admin_can_open_page_for_selected_school(): void
    {
        $schoolA = $this->school('Sekolah Super Admin A');
        $schoolB = $this->school('Sekolah Super Admin B');

        $this->pendingLink($schoolA, $this->student($schoolA, ['name' => 'Santri Sekolah A']), $this->wali('Wali A'));
        $this->pendingLink($schoolB, $this->student($schoolB, ['name' => 'Santri Sekolah B']), $this->wali('Wali B'));

        $admin = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);

        $this->withoutMiddleware($this->routeGuards())
            ->actingAs($admin)
            ->get("/{$admin->id}/wali-links?school_id={$schoolA->id}")
            ->assertOk()
            ->assertSee('Penghubungan Wali')
            ->assertSee('Santri Sekolah A')
            ->assertDontSee('Santri Sekolah B');
    }

    public function test_super_admin_sees_all_schools_without_filter(): void
    {
        $schoolA = $this->school('Sekolah Semua A');
        $schoolB = $this->school('Sekolah Semua B');

        $this->pendingLink($schoolA, $this->student($schoolA, ['name' => 'Santri Semua A']), $this->wali('Wali A'));
        $this->pendingLink($schoolB, $this->student($schoolB, ['name' => 'Santri Semua B']), $this->wali('Wali B'));

        $admin = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);

        $this->withoutMiddleware($this->routeGuards())
            ->actingAs($admin)
            ->get("/{$admin->id}/wali-links")
            ->assertOk()
            ->assertSee('Santri Semua A')
            ->assertSee('Santri Semua B')
            ->assertSee('Semua Satuan Pendidikan');
    }

    public function test_tu_cannot_manage_link_from_another_school(): void
    {
        $schoolA = $this->school('Sekolah A');
        $schoolB = $this->school('Sekolah B');
        $tu = $this->tataUsaha($schoolA);
        $studentB = $this->student($schoolB);
        $wali = $this->wali();
        $linkB = $this->pendingLink($schoolB, $studentB, $wali);

        $this->withoutMiddleware($this->routeGuards())
            ->actingAs($tu)
            ->post("/{$tu->id}/wali-links/{$linkB->id}/approve")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('wali_santri', [
            'id' => $linkB->id,
            'status' => 'pending',
        ]);
    }

    public function test_page_only_shows_links_from_tu_school(): void
    {
        $schoolA = $this->school('Sekolah A');
        $schoolB = $this->school('Sekolah B');

        $studentA = $this->student($schoolA, ['name' => 'Santri Sekolah A']);
        $studentB = $this->student($schoolB, ['name' => 'Santri Sekolah B']);

        $tu = $this->tataUsaha($schoolA);

        $this->pendingLink($schoolA, $studentA, $this->wali('Wali A'));
        $this->pendingLink($schoolB, $studentB, $this->wali('Wali B'));

        $this->withoutMiddleware($this->routeGuards())
            ->actingAs($tu)
            ->get("/{$tu->id}/wali-links")
            ->assertOk()
            ->assertSee('Santri Sekolah A')
            ->assertDontSee('Santri Sekolah B');
    }

    public function test_super_admin_can_approve_link_from_any_school_without_filter(): void
    {
        $schoolA = $this->school('Sekolah Bebas A');
        $schoolB = $this->school('Sekolah Bebas B');
        $linkB = $this->pendingLink($schoolB, $this->student($schoolB), $this->wali('Wali B'));

        $admin = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);

        $this->withoutMiddleware($this->routeGuards())
            ->actingAs($admin)
            ->post("/{$admin->id}/wali-links/{$linkB->id}/approve")
            ->assertRedirect();

        $this->assertDatabaseHas('wali_santri', [
            'id' => $linkB->id,
            'status' => 'active',
        ]);
    }
}
