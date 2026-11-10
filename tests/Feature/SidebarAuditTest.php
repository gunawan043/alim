<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Audit sidebar satuan pendidikan: setiap tautan yang DITAMPILKAN ke seorang
 * pengguna harus bisa dibuka — tidak boleh berakhir di HTTP 403.
 *
 * Regresi yang dijaga: sidebar memilih menu berdasarkan jabatan (KSP, Waka,
 * Guru, TU, koordinator), sedangkan controller memeriksa permission. Kalau
 * keduanya tidak sinkron, menu tampil tetapi halaman menolak akses.
 */
class SidebarAuditTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Snapshot izin membuka transaksinya sendiri; jangan bungkus test dengan
     * transaksi agar rebuild tidak gagal ("transaction within a transaction").
     *
     * @var array<int, string>
     */
    protected $connectionsToTransact = [];

    /**
     * RefreshDatabase menganggap database :memory: sudah dimigrasi setelah test
     * pertama, padahal setiap test mendapat koneksi :memory: yang baru. Karena
     * kelas ini tidak memakai transaksi, migrasi harus dipaksa ulang — kalau
     * tidak, test ini gagal dengan "no such table" saat dijalankan setelah test
     * lain yang memakai database.
     */
    protected function setUp(): void
    {
        RefreshDatabaseState::$migrated = false;

        parent::setUp();
    }

    private School $school;

    private AcademicYear $ay;

    private function bootSchool(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Audit', 'code' => 'AUD',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '52010299',
            'name' => 'SMP Audit',
            'school_level' => 'SMP',
            'is_active' => true,
        ]);

        $this->ay = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);
    }

    /** Permission role "Satuan Pendidikan" sesuai PermissionRoleSeeder. */
    private function grantRolePermissions(Role $role): void
    {
        $names = [
            'dashboard_view',
            'menu-satuan-pendidikan-sidebar',
            'menu-gtk-sidebar',
            'menu-wali-kelas-sidebar',
            'menu-coordinator-rumpun-sidebar',
            'menu-waka-kurikulum-sidebar',
            'gtk_view', 'gtk_detail_view', 'gtk_employment_view',
            'gtk-update',
            'satpen_view',
            'school_view',
            'grade_level_view',
            'study_group_view',
            'student_view', 'student_export',
            'students.read',
            'students.write',
            'exam.read',
            'general_teacher.readable',
            'student_teacher.readable',
        ];

        foreach ($names as $name) {
            $permId = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->value('id');

            if (! $permId) {
                $permId = (string) Str::uuid();
                DB::table('permissions')->insert([
                    'id' => $permId, 'name' => $name, 'guard_name' => 'web',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permId, 'role_id' => $role->id,
            ]);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function makeUser(string $jabatan, array $tugas = [], bool $mengajar = false): User
    {
        $role = Role::where('name', 'Satuan Pendidikan')->first();

        if (! $role) {
            $role = Role::create([
                'id' => (string) Str::uuid(),
                'name' => 'Satuan Pendidikan',
                'guard_name' => 'web',
                'level' => 3,
            ]);
            $this->grantRolePermissions($role);
        }

        $user = User::create([
            'name' => 'Uji '.$jabatan,
            'email' => Str::slug($jabatan).'-'.random_int(1000, 9999).'@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_system_admin' => false,
        ]);

        $user->assignRole($role);

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'jabatan' => $jabatan,
            'status_kepegawaian' => 'PTT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('gtk_work_unit')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'work_unit_id' => $this->school->work_unit_id,
            'jabatan' => $jabatan,
            'is_primary' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($tugas as $t) {
            DB::table('gtk_additional_tasks')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'work_unit_id' => $this->school->work_unit_id,
                'nama_tugas' => $t,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (str_contains(strtoupper($jabatan), 'KEPALA SATUAN PENDIDIKAN')) {
            $this->school->update(['principal_user_id' => $user->id]);
        }

        if ($mengajar) {
            $grade = GradeLevel::firstOrCreate(
                ['school_id' => $this->school->id, 'level' => 7],
                ['id' => (string) Str::uuid(), 'name' => 'Kelas 7', 'code' => 'VII', 'fase' => 'D', 'is_active' => true]
            );

            $group = StudyGroup::firstOrCreate(
                ['school_id' => $this->school->id, 'academic_year_id' => $this->ay->id, 'name' => '7A'],
                [
                    'id' => (string) Str::uuid(), 'grade_level_id' => $grade->id,
                    'code' => '7A', 'capacity' => 30, 'is_active' => true,
                ]
            );

            $subject = Subject::firstOrCreate(
                ['school_id' => $this->school->id, 'code' => 'MTK'],
                ['id' => (string) Str::uuid(), 'name' => 'Matematika', 'is_active' => true]
            );

            $decreeId = (string) Str::uuid();
            DB::table('institution_decrees')->insert([
                'id' => $decreeId,
                'decree_number' => 'AUDIT/'.Str::random(6),
                'decree_type' => 'teaching_assignment',
                'title' => 'SK Audit',
                'academic_year_id' => $this->ay->id,
                'issued_date' => now()->format('Y-m-d'),
                'effective_date' => now()->format('Y-m-d'),
                'status' => 'active',
                'created_at' => now(), 'updated_at' => now(),
            ]);

            TeachingAssignment::create([
                'decree_id' => $decreeId,
                'teacher_id' => $user->id,
                'school_id' => $this->school->id,
                'academic_year_id' => $this->ay->id,
                'study_group_id' => $group->id,
                'subject_id' => $subject->id,
                'weekly_hours' => 4,
                'status' => 'active',
            ]);
        }

        return $user->fresh();
    }

    /** @return array<int, string> daftar masalah: tautan 403 atau output buffer yang bocor */
    private function auditLinks(User $user): array
    {
        $page = $this->actingAs($user)->get('/'.$user->id.'/dashboard/satuan-pendidikan');

        $this->assertSame(200, $page->getStatusCode(), 'Dashboard satuan pendidikan gagal dimuat.');

        preg_match_all('/href="([^"#]+)"/', $page->getContent(), $m);

        $links = collect($m[1])
            ->map(fn ($u) => html_entity_decode(trim($u)))
            ->filter(fn ($u) => str_contains($u, '/'.$user->id.'/'))
            ->reject(fn ($u) => str_contains($u, '/logout') || str_contains($u, '/impersonate'))
            ->unique()
            ->values();

        $baseline = ob_get_level();
        $issues = [];

        foreach ($links as $url) {
            $path = parse_url($url, PHP_URL_PATH) ?: $url;
            $res = $this->actingAs($user)->get($path);

            if ($res->baseResponse->getStatusCode() === 403) {
                $issues[] = '403 '.$path;
            }

            // Setiap request harus menutup buffer yang dibukanya. Buffer yang
            // tertinggal (mis. @slot tanpa @endslot) membuat render berikutnya
            // menumpuk dan, pada worker jangka panjang, memakan memori.
            while (ob_get_level() > $baseline) {
                $issues[] = 'output buffer tidak ditutup: '.$path;
                ob_end_clean();
            }
        }

        return $issues;
    }

    public function test_semua_tautan_sidebar_satuan_pendidikan_dapat_dibuka(): void
    {
        $this->bootSchool();

        $cases = [
            'KSP' => $this->makeUser('KEPALA SATUAN PENDIDIKAN'),
            'Waka' => $this->makeUser('WAKIL KEPALA SATUAN PENDIDIKAN', [], true),
            'Guru' => $this->makeUser('GURU UMUM', [], true),
            'Kepala TU' => $this->makeUser('KEPALA TATA USAHA'),
            'Koor Kurikulum' => $this->makeUser('KOORDINATOR KURIKULUM'),
            'Tim Kurikulum' => $this->makeUser('GURU UMUM', ['Tim Kurikulum']),
            'Guru Tahfidz' => $this->makeUser('GURU TAHFIDZ'),
        ];

        $failures = [];

        foreach ($cases as $label => $user) {
            foreach ($this->auditLinks($user) as $issue) {
                $failures[] = "[$label] $issue";
            }
        }

        $this->assertSame(
            [],
            $failures,
            "Tautan sidebar bermasalah (403 atau output buffer bocor):\n".implode("\n", $failures)
        );
    }
}
