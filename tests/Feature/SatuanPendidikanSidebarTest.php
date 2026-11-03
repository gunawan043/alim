<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regresi untuk penyempurnaan tingkat satuan pendidikan:
 *  - dropdown Dashboard tidak lagi menampilkan beberapa entri ke halaman yang sama
 *  - route debug tidak lagi tersedia
 *  - gating sidebar keamanan tetap membuka menu untuk role yang berhak
 */
class SatuanPendidikanSidebarTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $roleName, string $jabatan, ?string $principalOf = null): User
    {
        $workUnitId = (string) Str::uuid();

        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit '.$roleName,
            'code' => 'U'.substr(md5($roleName.$jabatan), 0, 4),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '7'.random_int(1000000, 9999999),
            'name' => 'Sekolah '.$roleName,
            'school_level' => 'SMP',
            'is_active' => true,
            'principal_user_id' => $principalOf,
        ]);

        $user = User::create([
            'name' => 'Uji '.$roleName,
            'email' => str(Str::slug($roleName)).'-'.random_int(1000, 9999).'@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_system_admin' => false,
        ]);

        $user->assignRole(Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']));

        // Insert lewat query builder agar observer GtkEmployment tidak menimpa role.
        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'school_id' => $school->id,
            'jabatan' => $jabatan,
            'status_kepegawaian' => 'PTT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($principalOf === null) {
            $school->update(['principal_user_id' => $user->id]);
        }

        return $user->fresh();
    }

    public function test_dropdown_dashboard_tidak_menduplikasi_halaman_yang_sama(): void
    {
        // Kepala (via principal_user_id) yang juga menjabat Wakil → dua opsi
        // dashboard lama, padahal keduanya redirect ke halaman yang sama.
        $user = User::create([
            'name' => 'Uji Rangkap',
            'email' => 'rangkap-'.random_int(1000, 9999).'@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_system_admin' => false,
        ]);

        $this->makeUser('Satuan Pendidikan', 'WAKIL KEPALA SATUAN PENDIDIKAN', $user->id);

        $user->assignRole(Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web']));

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'jabatan' => 'WAKIL KEPALA SATUAN PENDIDIKAN',
            'status_kepegawaian' => 'PTT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user->fresh())
            ->get('/'.$user->id.'/dashboard/satuan-pendidikan');

        $response->assertOk();

        // Tidak boleh ada lagi tautan ke route dashboard lama.
        $response->assertDontSee('/dashboard/kepala-satuan-pendidikan', false);
        $response->assertDontSee('/dashboard/wakil-kepala', false);

        // Hanya satu tautan menu menuju dashboard terpadu.
        preg_match_all(
            '/class="nav-link[^>]*href="[^"]*\/dashboard\/satuan-pendidikan"/s',
            $response->getContent(),
            $matches
        );

        $this->assertCount(
            1,
            $matches[0],
            'Dropdown Dashboard masih menampilkan lebih dari satu entri ke halaman yang sama.'
        );
    }

    public function test_route_debug_sudah_dihapus(): void
    {
        $user = $this->makeUser('Satuan Pendidikan', 'KEPALA SATUAN PENDIDIKAN');

        $this->actingAs($user)
            ->get('/'.$user->id.'/route-test')
            ->assertNotFound();
    }

    public function test_sidebar_keamanan_tetap_tampil_untuk_role_satuan_keamanan(): void
    {
        $user = $this->makeUser('Satuan Keamanan', 'ANGGOTA SATUAN KEAMANAN');

        $response = $this->actingAs($user)
            ->get('/'.$user->id.'/keamanan');

        $response->assertOk();
        $response->assertSee('Pos Gerbang');
        $response->assertSee('Patroli & Ronda', false);
    }
}
