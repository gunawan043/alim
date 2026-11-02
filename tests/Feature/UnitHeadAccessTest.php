<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\GtkEmployment;
use App\Models\Role;
use App\Models\User;
use App\Support\UnitHeadAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitHeadAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_hanya_personalia_dan_super_admin_yang_dapat_mengelola_kepala_satuan_kerja(): void
    {
        $superAdmin = $this->userWithRole('Super Admin');
        $personalia = $this->userWithRole('Personalia');
        $administrator = $this->userWithRole('Administrator');
        $asrama = $this->userWithRole('Asrama');

        $this->assertTrue(UnitHeadAccess::canManage($superAdmin));
        $this->assertTrue(UnitHeadAccess::canManage($personalia));
        $this->assertFalse(UnitHeadAccess::canManage($administrator));
        $this->assertFalse(UnitHeadAccess::canManage($asrama));
    }

    public function test_jabatan_personalia_tetap_dikenali_walau_role_utama_bukan_personalia(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->syncRoles([Role::firstOrCreate(['name' => 'Staff', 'guard_name' => 'web'])]);

        GtkEmployment::create([
            'user_id' => $user->id,
            'status_kepegawaian' => 'PTY',
            'jenis_gtk' => 'Tendik',
            'jabatan' => 'Kepala Personalia',
        ]);

        $this->assertTrue(UnitHeadAccess::canManage($user->fresh()));
    }

    private function userWithRole(string $roleName): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->syncRoles([Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web'])]);

        return $user->fresh();
    }
}
