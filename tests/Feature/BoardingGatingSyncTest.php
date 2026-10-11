<?php

namespace Tests\Feature;

use App\Authorization\ValueObjects\ScopeKey;
use App\Models\DormitoryResident;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Observers\DormitoryResidentObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Gating modul asrama per tipe satuan pendidikan (boarding_type) dan
 * sinkronisasi otomatis boarding_status dari data penghuni asrama.
 */
class BoardingGatingSyncTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private Student $student;

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

    public function test_akses_modul_asrama_ditolak_untuk_satuan_non_boarding(): void
    {
        // Lewat HTTP: permintaan nyata ditolak untuk satuan non-boarding.
        $this->actingAs($this->admin)
            ->get("/{$this->admin->id}/asrama")
            ->assertStatus(403);

        // Uji middleware secara langsung agar tidak bercampur guard izin lain.
        $middleware = app(\App\Http\Middleware\EnsureBoardingSchool::class);
        $request = \Illuminate\Http\Request::create('/asrama', 'GET');
        $request->attributes->set('schoolContextId', $this->school->id);

        try {
            $middleware->handle($request, fn () => response('ok'));
            $this->fail('Satuan non-boarding seharusnya ditolak.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        // Tipe both/boarding → diteruskan.
        $this->school->forceFill(['boarding_type' => 'both'])->save();

        $response = $middleware->handle($request, fn () => response('ok'));
        $this->assertSame('ok', $response->getContent(), 'Satuan both harus diteruskan middleware.');
    }

    public function test_sinkronisasi_boarding_status_dari_penghuni_asrama(): void
    {
        // Hanya berlaku untuk satuan boarding/both.
        $this->school->forceFill(['boarding_type' => 'both'])->save();
        $this->student->forceFill(['boarding_status' => null])->save();

        $observer = app(DormitoryResidentObserver::class);

        // Ditempatkan di kamar (penghuni aktif) → boarding.
        $resident = new DormitoryResident([
            'student_id' => $this->student->id,
            'is_active' => true,
        ]);
        $observer->saved($resident);
        $this->assertSame('boarding', $this->student->fresh()->boarding_status);

        // Keluar asrama → non_boarding.
        $resident->is_active = false;
        $observer->saved($resident);
        $this->assertSame('non_boarding', $this->student->fresh()->boarding_status);

        // Data penghuni dihapus → non_boarding.
        $this->student->forceFill(['boarding_status' => 'boarding'])->save();
        $observer->deleted($resident);
        $this->assertSame('non_boarding', $this->student->fresh()->boarding_status);

        // Satuan non-boarding: status tidak diubah oleh sinkronisasi.
        $this->school->forceFill(['boarding_type' => 'non_boarding'])->save();
        $this->student->forceFill(['boarding_status' => null])->save();

        $observer->saved($resident);
        $this->assertNull($this->student->fresh()->boarding_status, 'Non-boarding tidak disinkronkan.');
    }

    private function makeUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);

        foreach (['impersonate_role', 'teacher-attendance_manual', 'teacher-attendance_view'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $user = User::create([
            'name' => 'Admin Asrama Uji',
            'email' => 'admin.asrama@boarding.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $user->assignRole($role);

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

        Schema::disableForeignKeyConstraints();
        try {
            DB::table('gtk_employments')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'school_id' => $this->school->id,
                'status_kepegawaian' => 'GTY',
                'jenis_gtk' => 'Pendidik / Guru',
                'jabatan' => 'Guru Mapel',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        return $user;
    }

    private function seedFixture(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Uji Gating',
            'code' => 'UU-GATE',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '56565656',
            'name' => 'Sekolah Uji Gating',
        ]);

        $this->admin = $this->makeUser();

        $this->student = Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'nisn' => '4445556667',
            'name' => 'Santri Gating',
            'gender' => 'L',
        ]);
    }
}
