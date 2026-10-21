<?php

namespace Tests\Feature;

use App\Models\Dormitory;
use App\Models\DormitoryPermit;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\Boarding\VisitWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Eksekusi kebijakan asrama:
 *  - Staf Perizinan lintas asrama sesuai gender (saling backup).
 *  - Kebijakan kunjungan PER ASRAMA (bukan global).
 *  - State machine status izin yang terstruktur.
 */
class AsramaPolicyExecutionTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Dormitory $dormPutra;

    private Dormitory $dormPutri;

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

        $this->seedFixture();
    }

    public function test_staf_perizinan_lintas_asrama_sesuai_gender(): void
    {
        $stafPutri = $this->makeUser('Staf Perizinan Putri', 'staf.perizinan.putri@test.local', 'Staf Perizinan Putri');
        $stafPutra = $this->makeUser('Staf Perizinan Putra', 'staf.perizinan.putra@test.local', 'Staf Perizinan Putra');

        // Putri: akses semua asrama putri (SMP/SMA dsb.), bukan putra.
        $this->assertTrue($stafPutri->canAccessDormitory($this->dormPutri->id));
        $this->assertFalse($stafPutri->canAccessDormitory($this->dormPutra->id));
        $this->assertContains($this->dormPutri->id, $stafPutri->accessibleDormitoryIds());
        $this->assertNotContains($this->dormPutra->id, $stafPutri->accessibleDormitoryIds());

        // Putra: kebalikannya.
        $this->assertTrue($stafPutra->canAccessDormitory($this->dormPutra->id));
        $this->assertFalse($stafPutra->canAccessDormitory($this->dormPutri->id));

        // Bukan staf perizinan → tidak dapat akses lintas.
        $guru = $this->makeUser('Guru Biasa', 'guru.biasa@test.local', 'Guru Mapel');
        $this->assertFalse($guru->canAccessDormitory($this->dormPutri->id));
    }

    public function test_kebijakan_kunjungan_per_asrama_disimpan_dan_tidak_duplikat(): void
    {
        $service = app(VisitWorkflowService::class);

        $policy = $service->saveDormitoryPolicy($this->dormPutri->id, [
            'visit_strategy' => 'quota',
            'visit_quota' => 2,
            'visit_quota_period' => 'monthly',
            'max_visitors_per_visit' => 5,
        ]);

        $this->assertSame('quota', $policy->visit_strategy);
        $this->assertSame(2, (int) $policy->visit_quota);

        // Resolver menemukan kebijakan asrama ini (bukan global).
        $resolved = $service->resolvePolicyForDormitory($this->dormPutri->id);
        $this->assertSame($policy->id, $resolved->id);

        // Update kedua tidak membuat duplikat.
        $updated = $service->saveDormitoryPolicy($this->dormPutri->id, [
            'visit_strategy' => 'quota',
            'visit_quota' => 4,
            'visit_quota_period' => 'monthly',
            'max_visitors_per_visit' => 6,
        ]);

        $this->assertSame($policy->id, $updated->id);
        $this->assertSame(4, (int) $updated->visit_quota);

        $assignments = DB::table('dormitory_policy_assignments')
            ->where('target_id', $this->dormPutri->id)
            ->where('boarding_policy_id', $policy->id)
            ->count();

        $this->assertSame(1, $assignments, 'Assignment kebijakan asrama tidak boleh duplikat.');
    }

    public function test_state_machine_status_izin_terstruktur(): void
    {
        $permit = new DormitoryPermit(['status' => DormitoryPermit::STATUS_PENDING]);

        // pending hanya boleh ke approved/rejected.
        $this->assertTrue($permit->canTransitionTo(DormitoryPermit::STATUS_APPROVED));
        $this->assertFalse($permit->canTransitionTo(DormitoryPermit::STATUS_PICKED_UP));
        $this->assertSame('menunggu', $permit->phase());

        $permit->status = DormitoryPermit::STATUS_APPROVED;
        $this->assertTrue($permit->canTransitionTo(DormitoryPermit::STATUS_PICKED_UP));
        $this->assertSame('belum_keluar', $permit->phase());

        $permit->status = DormitoryPermit::STATUS_PICKED_UP;
        $this->assertTrue($permit->canTransitionTo(DormitoryPermit::STATUS_RETURNED));
        $this->assertSame('sedang_keluar', $permit->phase());

        // returned terminal.
        $permit->status = DormitoryPermit::STATUS_RETURNED;
        $this->assertFalse($permit->canTransitionTo(DormitoryPermit::STATUS_APPROVED));
        $this->assertSame('kembali', $permit->phase());

        // Idempoten: status sama selalu boleh.
        $this->assertTrue($permit->canTransitionTo(DormitoryPermit::STATUS_RETURNED));
    }

    // ─────────────────────────────────────────────────────────────

    private function makeUser(string $name, string $email, string $jabatan): User
    {
        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);

        foreach (['impersonate_role', 'teacher-attendance_manual', 'teacher-attendance_view'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $user->assignRole($role);

        DB::table('permission_snapshots')->insert([
            'user_id' => $user->id,
            'scope_key' => 'test|'.$user->id,
            'scope_school_id' => $this->school->id,
            'fingerprint' => hash('sha256', $user->id.'scope'),
            'permissions' => json_encode(['jadwalkbm.read']),
            'revoked' => json_encode([]),
            'is_current' => 1,
            'created_at' => now(),
            'archived_at' => null,
        ]);

        Schema::disableForeignKeyConstraints();
        try {
            DB::table('gtk_employments')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'school_id' => $this->school->id,
                'status_kepegawaian' => 'GTY',
                'jenis_gtk' => 'Pendidik / Guru',
                'jabatan' => $jabatan,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        return $user->fresh();
    }

    private function seedFixture(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Uji Kebijakan Asrama',
            'code' => 'UU-KBJ',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '89898989',
            'name' => 'Sekolah Uji Kebijakan',
            'boarding_type' => 'boarding',
        ]);

        $this->dormPutra = Dormitory::create([
            'work_unit_id' => $workUnitId,
            'school_id' => $this->school->id,
            'code' => 'ASR-PA',
            'name' => 'Asrama Putra Uji',
            'gender' => 'putra',
            'is_active' => true,
        ]);

        $this->dormPutri = Dormitory::create([
            'work_unit_id' => $workUnitId,
            'school_id' => $this->school->id,
            'code' => 'ASR-PI',
            'name' => 'Asrama Putri Uji',
            'gender' => 'putri',
            'is_active' => true,
        ]);
    }
}
