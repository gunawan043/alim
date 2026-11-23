<?php

namespace Tests\Feature;

use App\Authorization\ValueObjects\ScopeKey;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\InstitutionDecree;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeacherAdminBook;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class InstitutionDecreeActivationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private StudyGroup $group;

    private Subject $subject;

    private User $teacher;

    private User $waka;

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

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Uji Aktivasi SK',
            'code' => 'UAKT',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '88888888',
            'name' => 'Sekolah Uji Aktivasi SK',
        ]);

        $this->ay = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $grade = GradeLevel::create([
            'school_id' => $this->school->id,
            'level' => 7,
            'name' => 'Kelas 7',
            'code' => 'VII',
            'is_active' => true,
        ]);

        $this->group = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $grade->id,
            'name' => '7A',
            'code' => '7A',
            'is_active' => true,
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'MAT',
            'name' => 'Matematika',
            'credit_hours' => 6,
            'is_active' => true,
        ]);

        $this->teacher = User::create([
            'name' => 'Guru Aktivasi',
            'email' => 'guru.aktivasi@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->waka = User::create([
            'name' => 'Waka Kurikulum',
            'email' => 'waka.aktivasi@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);

        // Izin global tingkat sistem harus ada agar pemeriksaan canPermission()
        // tidak melempar PermissionDoesNotExist (403) saat render halaman.
        Permission::firstOrCreate(['name' => 'institution-decree-all-access', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'teaching-assignment-all-access', 'guard_name' => 'web']);

        $this->waka->assignRole($role);

        $this->seedPermissionSnapshot($this->waka);
        $this->seedPermissionSnapshot($this->teacher);

        // Kepegawaian menentukan konteks sekolah (SchoolContextMiddleware);
        // tanpa itu assertDecreeAccess() menolak SK milik sekolah ini.
        foreach ([
            [$this->teacher, 'Pendidik / Guru', 'Guru Mapel', '9876543210'],
            [$this->waka, 'Pimpinan & Struktural Pendidikan', 'Wakil Kepala Satuan Pendidikan', null],
        ] as [$user, $jenis, $jabatan, $nupy]) {
            DB::table('gtk_employments')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'school_id' => $this->school->id,
                'status_kepegawaian' => 'GTY',
                'nupy' => $nupy,
                'jenis_gtk' => $jenis,
                'jabatan' => $jabatan,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedPermissionSnapshot(User $user): void
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
            'permissions' => json_encode(['jadwalkbm.read', 'jadwalkbm.write', 'jadwalkbm.publish']),
            'revoked' => json_encode([]),
            'is_current' => 1,
            'created_at' => now(),
            'archived_at' => null,
        ]);
    }

    public function test_mengaktifkan_sk_mengarsipkan_sk_lain_pada_sekolah_dan_ta_yang_sama(): void
    {
        $old = $this->decree('010/SK/2026', InstitutionDecree::STATUS_ACTIVE);
        $new = $this->decree('011/SK/2026', InstitutionDecree::STATUS_DRAFT);

        $this->actingAs($this->waka)
            ->post("/{$this->waka->id}/institution-decrees/{$new->id}/activate")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(InstitutionDecree::STATUS_ACTIVE, $new->refresh()->status);
        $this->assertSame(InstitutionDecree::STATUS_ARCHIVED, $old->refresh()->status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'INSTITUTION_DECREE_ACTIVATED',
            'record_id' => $new->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'INSTITUTION_DECREE_DEACTIVATED',
            'record_id' => $old->id,
        ]);
    }

    public function test_menonaktifkan_sk_memindahkannya_ke_arsip_dan_tidak_menghapus_penugasan(): void
    {
        $decree = $this->decree('012/SK/2026', InstitutionDecree::STATUS_ACTIVE);

        $this->actingAs($this->waka)
            ->post("/{$this->waka->id}/institution-decrees/{$decree->id}/deactivate")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(InstitutionDecree::STATUS_ARCHIVED, $decree->refresh()->status);
        $this->assertDatabaseHas('teaching_assignments', [
            'decree_id' => $decree->id,
            'teacher_id' => $this->teacher->id,
        ]);
    }

    public function test_sk_yang_sudah_aktif_tidak_diaktifkan_ulang(): void
    {
        $decree = $this->decree('013/SK/2026', InstitutionDecree::STATUS_ACTIVE);

        $this->actingAs($this->waka)
            ->post("/{$this->waka->id}/institution-decrees/{$decree->id}/activate")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(InstitutionDecree::STATUS_ACTIVE, $decree->refresh()->status);
    }

    public function test_sk_yang_tidak_aktif_tidak_bisa_dinonaktifkan(): void
    {
        $decree = $this->decree('014/SK/2026', InstitutionDecree::STATUS_DRAFT);

        $this->actingAs($this->waka)
            ->post("/{$this->waka->id}/institution-decrees/{$decree->id}/deactivate")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(InstitutionDecree::STATUS_DRAFT, $decree->refresh()->status);
    }

    public function test_jenis_sk_lain_tidak_saling_mengarsipkan(): void
    {
        $a = $this->decree('015/SK/2026', InstitutionDecree::STATUS_ACTIVE, 'SK Penugasan Pengawas');
        $b = $this->decree('016/SK/2026', InstitutionDecree::STATUS_DRAFT, 'SK Penugasan Pengawas');

        $this->actingAs($this->waka)
            ->post("/{$this->waka->id}/institution-decrees/{$b->id}/activate")
            ->assertRedirect();

        $this->assertSame(InstitutionDecree::STATUS_ACTIVE, $a->refresh()->status);
        $this->assertSame(InstitutionDecree::STATUS_ACTIVE, $b->refresh()->status);
    }

    public function test_daftar_sk_menampilkan_tombol_aktifkan_dan_nonaktifkan(): void
    {
        $active = $this->decree('017/SK/2026', InstitutionDecree::STATUS_ACTIVE);
        $draft = $this->decree('017B/SK/2026', InstitutionDecree::STATUS_DRAFT);

        $html = $this->actingAs($this->waka)
            ->get("/{$this->waka->id}/institution-decrees")
            ->assertOk()
            ->assertSee('Nonaktifkan')
            ->assertSee('Aktifkan')
            ->getContent();

        // Baris aktif -> Nonaktifkan, baris draft -> Aktifkan (tombol langsung,
        // bukan di dalam dropdown agar terlihat tanpa hover).
        $this->assertStringContainsString('data-id="'.$active->id.'"', $html);
        $this->assertStringContainsString('data-action="deactivate"', $html);
        $this->assertStringContainsString('data-id="'.$draft->id.'"', $html);
        $this->assertStringContainsString('data-action="activate"', $html);
        $this->assertStringContainsString('btn-soft-success toggle-s', $html);
    }

    public function test_halaman_detail_menampilkan_tombol_status(): void
    {
        $decree = $this->decree('018/SK/2026', InstitutionDecree::STATUS_DRAFT);

        $this->actingAs($this->waka)
            ->get("/{$this->waka->id}/institution-decrees/{$decree->id}")
            ->assertOk()
            ->assertSee('Aktifkan');
    }

    public function test_form_edit_tidak_bisa_mengaktifkan_sk_secara_langsung(): void
    {
        $decree = $this->decree('019/SK/2026', InstitutionDecree::STATUS_DRAFT);

        $this->actingAs($this->waka)
            ->put("/{$this->waka->id}/institution-decrees/{$decree->id}", [
                'decree_number' => '019/SK/2026',
                'decree_type' => 'SK Pembagian Tugas',
                'title' => 'Pembagian Tugas Mengajar',
                'academic_year_id' => $this->ay->id,
                'issued_date' => '2026-07-01',
                'effective_date' => '2026-07-01',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertSame(InstitutionDecree::STATUS_DRAFT, $decree->refresh()->status);
    }

    private function decree(string $number, string $status, string $type = 'SK Pembagian Tugas'): InstitutionDecree
    {
        $decree = InstitutionDecree::create([
            'decree_number' => $number,
            'decree_type' => $type,
            'title' => 'Pembagian Tugas Mengajar',
            'academic_year_id' => $this->ay->id,
            'school_id' => $this->school->id,
            'issued_date' => '2026-07-01',
            'effective_date' => '2026-07-01',
            'status' => $status,
        ]);

        if ($type === 'SK Pembagian Tugas') {
            TeachingAssignment::create([
                'decree_id' => $decree->id,
                'teacher_id' => $this->teacher->id,
                'school_id' => $this->school->id,
                'academic_year_id' => $this->ay->id,
                'study_group_id' => $this->group->id,
                'subject_id' => $this->subject->id,
                'role' => 'guru_mapel',
                'weekly_hours' => 6,
                'status' => 'active',
            ]);

            // Satu buku admin per konteks (mapel + rombel + TA + semester).
            TeacherAdminBook::updateOrCreate(
                [
                    'subject_id' => $this->subject->id,
                    'study_group_id' => $this->group->id,
                    'academic_year_id' => $this->ay->id,
                    'semester' => 'ganjil',
                ],
                [
                    'teacher_id' => $this->teacher->id,
                    'school_id' => $this->school->id,
                    'is_active' => true,
                ]
            );
        }

        return $decree;
    }
}
