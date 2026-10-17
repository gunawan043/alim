<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi keamanan: pengguna tidak boleh membuka halaman milik user lain
 * hanya dengan mengganti {userId} pada URL.
 */
class UserIdScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_tidak_bisa_mengakses_url_user_lain(): void
    {
        \App\Models\Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'Guru', 'guard_name' => 'web', 'level' => 12]);

        $a = User::create([
            'name' => 'Guru A', 'email' => 'guru.a@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
        $b = User::create([
            'name' => 'Guru B', 'email' => 'guru.b@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
        $a->assignRole($role);
        $b->assignRole($role);

        $this->actingAs($a);

        $this->get('/'.$b->id.'/notifications')->assertForbidden();
        $this->get('/'.$a->id.'/notifications')->assertOk();
    }
}
