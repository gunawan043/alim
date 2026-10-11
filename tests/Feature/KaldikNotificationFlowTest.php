<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Uji end-to-end: membuat Kaldik via HTTP → notifikasi GTK tercipta.
 *  - Kaldik pondok (kategori kaldik)  → GLOBAL (semua GTK)
 *  - Agenda satuan kerja              → hanya satuan terkait (+ pembuat)
 */
class KaldikNotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    private string $unitA;

    private string $unitB;

    private User $superAdmin;

    private User $guruA;

    private User $guruB;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('positions')) {
            Schema::create('positions', function ($table) {
                $table->uuid('id')->primary();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }

        $this->unitA = (string) Str::uuid();
        $this->unitB = (string) Str::uuid();

        foreach ([['A', $this->unitA], ['B', $this->unitB]] as [$code, $id]) {
            DB::table('work_units')->insert([
                'id' => $id, 'name' => 'Unit '.$code, 'code' => 'KNF-'.$code,
                'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $superRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web'], ['level' => 1]);
        $guruRole = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);
        \App\Models\Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);

        $this->superAdmin = $this->makeUser('Super Admin Uji', 'sa.kaldik@test.local', $this->unitA, $superRole);
        $this->guruA = $this->makeUser('Guru Unit A', 'guru.a.kaldik@test.local', $this->unitA, $guruRole);
        $this->guruB = $this->makeUser('Guru Unit B', 'guru.b.kaldik@test.local', $this->unitB, $guruRole);
    }

    public function test_kaldik_pondok_mengirim_notifikasi_global_semua_gtk(): void
    {
        $response = $this->actingAs($this->superAdmin)->post("/{$this->superAdmin->id}/kaldik", [
            'name' => 'Libur Nasional Uji',
            'category' => 'kaldik',
            'type' => 'libur',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'is_active' => 1,
        ]);

        $response->assertOk();

        // Semua GTK (semua satuan kerja) menerima, termasuk Super Admin.
        foreach ([$this->superAdmin, $this->guruA, $this->guruB] as $user) {
            $this->assertTrue(
                DB::table('notifications_universal')
                    ->where('user_id', $user->id)
                    ->where('module', 'kaldik')
                    ->where('title', 'like', '%Libur Nasional Uji%')
                    ->exists(),
                'GTK '.$user->name.' menerima notifikasi Kaldik pondok.'
            );
        }
    }

    public function test_agenda_satuan_kerja_hanya_ke_satuan_terkait_plus_pembuat(): void
    {
        $response = $this->actingAs($this->superAdmin)->post("/{$this->superAdmin->id}/kaldik", [
            'name' => 'Agenda Unit B',
            'category' => 'agenda',
            'type' => 'kegiatan',
            'work_unit_id' => $this->unitB,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'is_active' => 1,
        ]);

        $response->assertOk();

        // Guru Unit B menerima (satuan terkait).
        $this->assertTrue(
            DB::table('notifications_universal')->where('user_id', $this->guruB->id)
                ->where('title', 'like', '%Agenda Unit B%')->exists()
        );

        // Pembuat (Super Admin) juga menerima sebagai konfirmasi.
        $this->assertTrue(
            DB::table('notifications_universal')->where('user_id', $this->superAdmin->id)
                ->where('title', 'like', '%Agenda Unit B%')->exists(),
            'Pembuat selalu menerima konfirmasi.'
        );

        // Guru Unit A tidak menerima (bukan satuan terkait).
        $this->assertFalse(
            DB::table('notifications_universal')->where('user_id', $this->guruA->id)
                ->where('title', 'like', '%Agenda Unit B%')->exists(),
            'Satuan lain tidak menerima agenda satuan B.'
        );
    }

    private function makeUser(string $name, string $email, string $workUnitId, Role $role): User
    {
        $user = User::create([
            'name' => $name, 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
        $user->assignRole($role);

        DB::table('gtk_work_unit')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'work_unit_id' => $workUnitId,
            'is_primary' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('permission_snapshots')->insert([
            'user_id' => $user->id,
            'scope_key' => 'test|'.$user->id,
            'scope_school_id' => null,
            'fingerprint' => hash('sha256', $user->id),
            'permissions' => json_encode(['jadwalkbm.read']),
            'revoked' => json_encode([]),
            'is_current' => 1,
            'created_at' => now(),
            'archived_at' => null,
        ]);

        return $user->fresh();
    }
}
