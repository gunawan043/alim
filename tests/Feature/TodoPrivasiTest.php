<?php

namespace Tests\Feature;

use App\Authorization\ValueObjects\ScopeKey;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Privasi & kejelasan penugasan To-do List:
 * - Tugas pribadi hanya terlihat pemiliknya.
 * - Tugas yang didelegasikan terlihat pemberi tugas (delegator) dan penerima (owner).
 * - Tugas pribadi GTK lain tidak bocor.
 */
class TodoPrivasiTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $userA;

    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Uji Todo', 'code' => 'UUT',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '99988877',
            'name' => 'Sekolah Uji Todo',
        ]);

        $this->userA = $this->makeUser('User Todo A', 'todo.a@test.local');
        $this->userB = $this->makeUser('User Todo B', 'todo.b@test.local');

        // Tugas pribadi A.
        Todo::create([
            'owner_id' => $this->userA->id,
            'created_by' => $this->userA->id,
            'work_unit_id' => $this->school->work_unit_id,
            'title' => 'Tugas Pribadi A',
            'priority' => 'sedang',
            'is_private' => 1,
        ]);

        // Delegasi A → B.
        Todo::create([
            'owner_id' => $this->userB->id,
            'created_by' => $this->userA->id,
            'delegated_by' => $this->userA->id,
            'delegated_at' => now(),
            'work_unit_id' => $this->school->work_unit_id,
            'title' => 'Delegasi A ke B',
            'priority' => 'tinggi',
        ]);

        // Tugas pribadi B.
        Todo::create([
            'owner_id' => $this->userB->id,
            'created_by' => $this->userB->id,
            'work_unit_id' => $this->school->work_unit_id,
            'title' => 'Tugas Pribadi B',
            'priority' => 'sedang',
            'is_private' => 1,
        ]);
    }

    public function test_tugas_pribadi_dan_delegasi_tidak_bocor_ke_gtk_lain(): void
    {
        // A melihat: tugas pribadinya + tugas yang ia delegasikan (ke B).
        $titlesA = collect($this->actingAs($this->userA)
            ->getJson("/{$this->userA->id}/todos")
            ->assertOk()
            ->json('todos'))
            ->pluck('title')
            ->all();

        $this->assertContains('Tugas Pribadi A', $titlesA, 'Pemilik melihat tugas pribadinya.');
        $this->assertContains('Delegasi A ke B', $titlesA, 'Delegator melihat tugas yang ia delegasikan.');
        $this->assertNotContains('Tugas Pribadi B', $titlesA, 'Tugas pribadi GTK lain tidak boleh bocor.');

        // B melihat: tugas miliknya (termasuk delegasi dari A) — bukan tugas pribadi A.
        $titlesB = collect($this->actingAs($this->userB)
            ->getJson("/{$this->userB->id}/todos")
            ->assertOk()
            ->json('todos'))
            ->pluck('title')
            ->all();

        $this->assertContains('Tugas Pribadi B', $titlesB);
        $this->assertContains('Delegasi A ke B', $titlesB, 'Penerima tugas melihat delegasinya.');
        $this->assertNotContains('Tugas Pribadi A', $titlesB, 'Tugas pribadi GTK lain tidak boleh bocor.');
    }

    private function makeUser(string $name, string $email): User
    {
        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $user->assignRole($role);
        $this->seedSnapshot($user);

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Pendidik / Guru',
            'jabatan' => 'Guru Umum',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('gtk_work_unit')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'work_unit_id' => $this->school->work_unit_id,
            'jabatan' => 'Guru Umum',
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    private function seedSnapshot(User $user): void
    {
        $roleDimension = implode(',', $user->fresh()->effectiveRoles()) ?: 'default';

        $scopeKey = ScopeKey::fromComponents(
            schoolId: $this->school->id,
            academicYearId: 'global',
            roleDimension: $roleDimension,
            tenantId: 'local',
        )->value;

        DB::table('permission_snapshots')->insert([
            'user_id' => $user->id,
            'scope_key' => $scopeKey,
            'scope_school_id' => $this->school->id,
            'fingerprint' => hash('sha256', $user->id.$scopeKey),
            'permissions' => json_encode(['jadwalkbm.read']),
            'revoked' => json_encode([]),
            'is_current' => 1,
            'created_at' => now(),
            'archived_at' => null,
        ]);
    }
}
