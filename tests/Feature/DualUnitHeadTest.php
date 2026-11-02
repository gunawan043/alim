<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\AsramaDashboardController;
use App\Http\Controllers\Dashboard\SatuanPendidikanDashboardController;
use App\Models\Dormitory;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class DualUnitHeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_yang_merangkap_kepala_asrama_dan_kepala_sekolah_mendapat_pemilih_dashboard(): void
    {
        [$user, $dormitory, $school] = $this->dualHeadUser();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('user.dashboard.choose', ['userId' => $user->id]));

        $this->actingAs($user)
            ->get(route('user.dashboard.choose', ['userId' => $user->id]))
            ->assertOk()
            ->assertSee('Anda memegang dua jabatan kepala')
            ->assertSee($dormitory->name)
            ->assertSee($school->name)
            ->assertSee('Kepala Asrama')
            ->assertSee('Kepala Satuan Pendidikan');
    }

    public function test_dashboard_asrama_dan_satuan_pendidikan_sama_sama_dapat_diakses(): void
    {
        [$user] = $this->dualHeadUser();

        $asrama = app(AsramaDashboardController::class);
        $satuan = app(SatuanPendidikanDashboardController::class);

        $asramaAccess = new ReflectionMethod($asrama, 'canAccessDashboard');
        $asramaAccess->setAccessible(true);

        $satuanAccess = new ReflectionMethod($satuan, 'canAccessDashboard');
        $satuanAccess->setAccessible(true);

        $this->assertTrue($asramaAccess->invoke($asrama, $user));
        $this->assertTrue($satuanAccess->invoke($satuan, $user));
    }

    public function test_halaman_dashboard_asrama_dan_satuan_pendidikan_dapat_dibuka(): void
    {
        [$user] = $this->dualHeadUser();

        $this->actingAs($user)
            ->get(route('user.dashboard.asrama', ['userId' => $user->id]))
            ->assertOk()
            ->assertSee('Asrama')
            ->assertSee('Tugas Harian')
            ->assertSee('Dashboard Kepala');

        $this->actingAs($user)
            ->get(route('user.dashboard.satuan-pendidikan', ['userId' => $user->id]))
            ->assertOk()
            ->assertSee('Satuan Pendidikan')
            ->assertSee('Pilih Dashboard');

        $this->actingAs($user)
            ->get(route('user.schools.index', ['userId' => $user->id]))
            ->assertOk();
    }

    /**
     * @return array{0: User, 1: Dormitory, 2: School}
     */
    private function dualHeadUser(): array
    {
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::firstOrCreate(['name' => 'Asrama', 'guard_name' => 'web']);
        $permission = \App\Models\Permission::firstOrCreate(['name' => 'school-create', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $user->syncRoles([$role]);

        $dormitory = Dormitory::factory()
            ->withHead($user->id)
            ->create(['is_active' => true, 'gender' => 'putra']);

        $school = School::factory()->create([
            'principal_user_id' => $user->id,
            'principal_name' => $user->name,
            'boarding_type' => 'both',
            'is_active' => true,
        ]);

        return [$user, $dormitory, $school];
    }
}
