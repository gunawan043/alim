<?php

namespace Tests\Feature\Dormitory;

use App\Models\Dormitory;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DormitoryProfileAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function asramaUser(): User
    {
        $permission = Permission::firstOrCreate(['name' => 'dormitory_view', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'Asrama', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);

        $user = User::factory()->create(['name' => 'Kepala Asrama']);
        $user->assignRole($role);

        return $user;
    }

    private function dormitory(?string $headId = null): Dormitory
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Asrama',
            'code' => 'WU-'.Str::random(6),
        ]);

        $schoolId = (string) Str::uuid();
        DB::table('schools')->insert([
            'id' => $schoolId,
            'work_unit_id' => $workUnitId,
            'school_code' => 'SK-'.Str::random(5),
            'npsn' => (string) (1000000000 + rand(0, 899999999)),
            'nss' => (string) rand(100000000000, 999999999999),
            'name' => 'Sekolah Asrama',
            'address' => 'Jl. Asrama',
            'is_active' => true,
        ]);

        return Dormitory::create([
            'id' => (string) Str::uuid(),
            'work_unit_id' => $workUnitId,
            'school_id' => $schoolId,
            'code' => 'DS-'.Str::random(4),
            'name' => 'Asrama '.Str::random(4),
            'gender' => 'putra',
            'capacity' => 20,
            'total_rooms' => 2,
            'total_wings' => 1,
            'head_id' => $headId,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function kepala_asrama_dapat_membuka_profil_asrama(): void
    {
        $user = $this->asramaUser();
        $dormitory = $this->dormitory($user->id);

        $this->actingAs($user)
            ->get("/{$user->id}/asrama/profil-saya")
            ->assertRedirect("/{$user->id}/asrama/{$dormitory->id}");

        $this->actingAs($user)
            ->get("/{$user->id}/asrama/{$dormitory->id}")
            ->assertOk();
    }

    /** @test */
    public function kepala_asrama_tidak_dapat_membuka_asrama_lain(): void
    {
        $user = $this->asramaUser();
        $this->dormitory($user->id);
        $other = $this->dormitory();

        $this->actingAs($user)
            ->get("/{$user->id}/asrama/{$other->id}")
            ->assertForbidden();
    }

    /** @test */
    public function pengguna_tanpa_izin_asrama_ditolak(): void
    {
        $user = User::factory()->create();
        $dormitory = $this->dormitory();

        $this->actingAs($user)
            ->get("/{$user->id}/asrama/profil-saya")
            ->assertForbidden();
    }
}
