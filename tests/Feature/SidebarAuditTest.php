<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryRoom;
use App\Models\DormitoryWing;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\RoomSupervisor;
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

    /**
     * Setelah kelas ini selesai, database :memory: milik kelas test berikutnya
     * belum bermigrasi — jadi tandai ulang agar mereka memigrasi sendiri.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        RefreshDatabaseState::$migrated = false;
    }

    private School $school;

    private AcademicYear $ay;

    private ?Dormitory $dormitory = null;

    private ?DormitoryWing $dormitoryWing = null;

    private ?DormitoryRoom $dormitoryRoom = null;

    private function bootSchool(): void
    {
        // Katalog permission harus ada: banyak view memanggil
        // hasPermissionTo('...') yang melempar PermissionDoesNotExist bila
        // namanya belum terdaftar (di produksi diisi PermissionSeeder).
        $this->seed(\Database\Seeders\PermissionSeeder::class);

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

    /**
     * @param  array<int, string>  $tugas
     * @param  array{placement_jabatan?: string, homeroom?: bool, room?: bool, wing?: bool, task_source?: string}  $options
     */
    private function makeUser(string $jabatan, array $tugas = [], bool $mengajar = false, array $options = []): User
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
            'email' => Str::slug($jabatan).'-'.Str::uuid().'@test.local',
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
            'jabatan' => $options['placement_jabatan'] ?? $jabatan,
            'is_primary' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Tugas tambahan tersimpan di dua tabel berbeda di data nyata:
        // gtk_additional_tasks (nama_tugas) dan other_teacher_tasks (task_name).
        // Keduanya harus dibaca sidebar, jadi audit menguji keduanya.
        $taskSource = $options['task_source'] ?? 'gtk';

        foreach ($tugas as $t) {
            if ($taskSource === 'other') {
                DB::table('other_teacher_tasks')->insert([
                    'id' => (string) Str::uuid(),
                    'teacher_id' => $user->id,
                    'school_id' => $this->school->id,
                    'work_unit_id' => $this->school->work_unit_id,
                    'academic_year_id' => $this->ay->id,
                    'task_name' => $t,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                continue;
            }

            DB::table('gtk_additional_tasks')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'work_unit_id' => $this->school->work_unit_id,
                'nama_tugas' => $t,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (($options['room'] ?? false) || ($options['wing'] ?? false)) {
            $this->assignDormitoryScope($user, $options);
        }

        if (str_contains(strtoupper($jabatan), 'KEPALA SATUAN PENDIDIKAN')) {
            $this->school->update(['principal_user_id' => $user->id]);
        }

        $needGroup = $mengajar || ($options['homeroom'] ?? false);

        if ($needGroup) {
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

            if ($options['homeroom'] ?? false) {
                $group->update(['homeroom_teacher_id' => $user->id]);
            }
        }

        if ($mengajar) {
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
            $status = $res->baseResponse->getStatusCode();

            // Tautan sidebar harus terbuka. 403 = hak akses tidak sinkron;
            // 404/500 = tautan menunjuk halaman yang tidak ada/rusak.
            if ($status >= 400) {
                $issues[] = $status.' '.$path;
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

    /**
     * Asrama + gedung + kamar untuk tugas yang berlingkup asrama
     * (Wali Kamar mengikuti room_supervisors, Wali Asrama mengikuti
     * dormitory_wings.supervisor_user_id).
     *
     * @param  array{room?: bool, wing?: bool}  $options
     */
    private function assignDormitoryScope(User $user, array $options): void
    {
        if ($this->dormitory === null) {
            $dormWorkUnitId = (string) Str::uuid();
            DB::table('work_units')->insert([
                'id' => $dormWorkUnitId, 'name' => 'Asrama Audit', 'code' => 'ASR-AUD',
                'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);

            $this->dormitory = Dormitory::create([
                'work_unit_id' => $dormWorkUnitId,
                'school_id' => $this->school->id,
                'name' => 'Asrama Audit',
                'gender' => 'putra',
                'is_active' => true,
            ]);

            // Sekolah yang punya asrama otomatis mendukung modul boarding —
            // sama dengan aturan backfill di migrasi boarding_type.
            $this->school->update(['boarding_type' => 'boarding']);

            $this->dormitoryWing = DormitoryWing::create([
                'id' => (string) Str::uuid(),
                'dormitory_id' => $this->dormitory->id,
                'code' => 'GD-A',
                'name' => 'Gedung A',
                'gender' => 'putra',
                'is_active' => true,
            ]);

            $this->dormitoryRoom = DormitoryRoom::create([
                'id' => (string) Str::uuid(),
                'dormitory_id' => $this->dormitory->id,
                'wing_id' => $this->dormitoryWing->id,
                'code' => 'A-01',
                'name' => 'Kamar A-01',
                'gender' => 'putra',
                'capacity' => 6,
                'is_active' => true,
            ]);
        }

        if ($options['wing'] ?? false) {
            $this->dormitoryWing->update(['supervisor_user_id' => $user->id]);
        }

        if ($options['room'] ?? false) {
            RoomSupervisor::updateOrCreate(
                [
                    'room_id' => $this->dormitoryRoom->id,
                    'academic_year_id' => $this->ay->id,
                    'status' => 'active',
                ],
                [
                    'user_id' => $user->id,
                    'dormitory_id' => $this->dormitory->id,
                    'start_date' => now()->subMonth()->toDateString(),
                ],
            );
        }
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

            // Rangkap tiga: Waka (tugas tambahan) + Wali Kelas + Guru Mapel.
            // Ini kasus nyata yang dulu tidak menampilkan menu jabatan.
            'Waka + Walkes + Guru Mapel' => $this->makeUser(
                'GURU UMUM',
                ['Waka Kurikulum', 'Wali Kelas'],
                true,
                ['homeroom' => true],
            ),

            // Waka yang tersimpan pada penempatan satuan kerja, bukan
            // gtk_employments.jabatan.
            'Waka via penempatan' => $this->makeUser(
                'GURU UMUM',
                [],
                true,
                ['placement_jabatan' => 'Wakil Kepala Satuan Pendidikan'],
            ),

            // Kepala TU yang tercatat sebagai tugas tambahan.
            'Kepala TU via tugas' => $this->makeUser('GURU UMUM', ['Kepala Tata Usaha']),
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

    /**
     * Seluruh tugas tambahan pada katalog (`additional_task_types`) — bukan
     * hanya Waka/Wali Kelas/Guru Mapel — harus memunculkan menu yang sesuai
     * dan setiap tautannya dapat dibuka.
     *
     * `expect` adalah teks yang wajib muncul di sidebar; daftar kosong berarti
     * tugas tersebut memang belum punya modul (mis. Staf Penitipan Barang) —
     * yang diuji hanya tidak adanya tautan rusak.
     *
     * @return array<string, array{tugas: array<int, string>, expect: array<int, string>, options?: array<string, bool>}>
     */
    private function tugasTambahanCases(): array
    {
        return [
            // ── Kesiswaan ────────────────────────────────────────────────
            'Wali Kelas' => [
                'tugas' => ['Wali Kelas'],
                'expect' => ['Men Walkes'],
                'options' => ['homeroom' => true],
            ],
            'Koordinator Kesiswaan' => [
                'tugas' => ['Koordinator Kesiswaan'],
                'expect' => ['Kesiswaan'],
            ],
            'Tim Kesiswaan' => [
                'tugas' => ['Tim Kesiswaan'],
                'expect' => ['Kesiswaan'],
            ],
            'Koordinator Alumni' => [
                'tugas' => ['Koordinator Alumni'],
                'expect' => ['Data Alumni'],
            ],
            'Pembina Ekstrakurikuler' => [
                'tugas' => ['Pembina Ekstrakurikuler'],
                'expect' => ['Ekstrakurikuler'],
            ],
            'Koordinator Ekstrakurikuler' => [
                'tugas' => ['Koordinator Ekstrakurikuler'],
                'expect' => ['Ekstrakurikuler'],
            ],

            // ── Kurikulum ────────────────────────────────────────────────
            'Koordinator Kurikulum' => [
                'tugas' => ['Koordinator Kurikulum'],
                'expect' => ['Kurikulum'],
            ],
            'Tim Kurikulum' => [
                'tugas' => ['Tim Kurikulum'],
                'expect' => ['Kurikulum'],
            ],
            'Koordinator Guru Umum' => [
                'tugas' => ['Koordinator Guru Umum'],
                'expect' => ['Nilai & Rapor'],
            ],
            'Koordinator Guru Agama' => [
                'tugas' => ['Koordinator Guru Agama'],
                'expect' => ['Nilai & Rapor'],
            ],
            'Koordinator Guru Hadits' => [
                'tugas' => ['Koordinator Guru Hadits'],
                'expect' => ['Nilai & Rapor'],
            ],
            'Koordinator Guru Bahasa Arab' => [
                'tugas' => ['Koordinator Guru Bahasa Arab'],
                'expect' => ['Nilai & Rapor'],
            ],
            'Koordinator Guru Tahfidz' => [
                'tugas' => ['Koordinator Guru Tahfidz'],
                'expect' => ['Nilai & Rapor'],
            ],
            'Koordinator Laboratorium' => [
                'tugas' => ['Koordinator Laboratorium'],
                'expect' => ['Dashboard Laboratorium'],
            ],

            // ── Sarana prasarana & keuangan ──────────────────────────────
            'Koordinator Sarpras Satuan Pendidikan' => [
                'tugas' => ['Koordinator Sarpras Satuan Pendidikan'],
                'expect' => ['Sarpras'],
            ],
            'Bendahara Sekolah' => [
                'tugas' => ['Bendahara Sekolah'],
                'expect' => ['Keuangan'],
            ],

            // ── Asrama ───────────────────────────────────────────────────
            'Wali Kamar' => [
                'tugas' => ['Wali Kamar'],
                'expect' => ['Wali Kamar', 'Kamar Saya'],
                'options' => ['room' => true],
            ],
            'Wali Asrama' => [
                'tugas' => ['Wali Asrama'],
                'expect' => ['Wali Asrama'],
                'options' => ['wing' => true],
            ],
            'Staf Asrama' => [
                'tugas' => ['Staf Asrama'],
                'expect' => ['Staf Asrama'],
            ],

            // ── Unit lain ────────────────────────────────────────────────
            'Admin UKS Putra' => [
                'tugas' => ['Admin UKS Putra'],
                'expect' => ['Admin UKS Putra'],
            ],
            'Admin UKS Putri' => [
                'tugas' => ['Admin UKS Putri'],
                'expect' => ['Admin UKS Putri'],
            ],
            'Guru Koordinator OSIPP' => [
                'tugas' => ['Guru Koordinator OSIPP'],
                'expect' => ['OSIPP'],
            ],

            // Belum ada modulnya — cukup dipastikan tidak ada tautan rusak.
            'Staf Penitipan Barang' => [
                'tugas' => ['Staf Penitipan Barang'],
                'expect' => [],
            ],
        ];
    }

    public function test_semua_tugas_tambahan_mendapat_menu_dan_tidak_403(): void
    {
        $this->bootSchool();

        $failures = [];
        $index = 0;

        foreach ($this->tugasTambahanCases() as $label => $case) {
            $user = $this->makeUser(
                'GURU UMUM',
                $case['tugas'],
                false,
                array_merge(
                    ['task_source' => $index % 2 === 0 ? 'gtk' : 'other'],
                    $case['options'] ?? [],
                ),
            );
            $index++;

            $page = $this->actingAs($user)->get('/'.$user->id.'/dashboard/satuan-pendidikan');

            if ($page->getStatusCode() !== 200) {
                $failures[] = "[$label] dashboard status ".$page->getStatusCode();

                continue;
            }

            $html = $page->getContent();

            foreach ($case['expect'] as $marker) {
                if (! str_contains($html, $marker)) {
                    $failures[] = "[$label] menu '{$marker}' tidak muncul di sidebar";
                }
            }

            foreach ($this->auditLinks($user) as $issue) {
                $failures[] = "[$label] $issue";
            }
        }

        $this->assertSame(
            [],
            $failures,
            "Sidebar tugas tambahan bermasalah:\n".implode("\n", $failures)
        );
    }

    /**
     * Rangkap tugas tambahan: menu tidak boleh saling menutup atau dobel.
     */
    public function test_kombinasi_tugas_tambahan_tidak_saling_menutup(): void
    {
        $this->bootSchool();

        $cases = [
            'Kurikulum + Tim Kurikulum' => [
                'tugas' => ['Koordinator Kurikulum', 'Tim Kurikulum'],
                'expect' => ['Kurikulum'],
            ],
            'Kesiswaan + Alumni' => [
                'tugas' => ['Koordinator Kesiswaan', 'Koordinator Alumni'],
                'expect' => ['Kesiswaan', 'Data Alumni'],
            ],
            'Bendahara + Sarpras' => [
                'tugas' => ['Bendahara Sekolah', 'Koordinator Sarpras Satuan Pendidikan'],
                'expect' => ['Keuangan', 'Sarpras'],
            ],
            'Wali Kelas + Wali Kamar + Pembina Ekskul' => [
                'tugas' => ['Wali Kelas', 'Wali Kamar', 'Pembina Ekstrakurikuler'],
                'expect' => ['Men Walkes', 'Wali Kamar', 'Ekstrakurikuler'],
                'options' => ['homeroom' => true, 'room' => true],
            ],
            'Admin UKS + Wali Kamar' => [
                'tugas' => ['Admin UKS Putra', 'Wali Kamar'],
                'expect' => ['Admin UKS Putra', 'Wali Kamar'],
                'options' => ['room' => true],
            ],
            'Koordinator Mapel + Tim Kurikulum' => [
                'tugas' => ['Koordinator Guru Tahfidz', 'Tim Kurikulum'],
                'expect' => ['Nilai & Rapor', 'Kurikulum'],
            ],
        ];

        $failures = [];

        foreach ($cases as $label => $case) {
            $user = $this->makeUser('GURU UMUM', $case['tugas'], false, $case['options'] ?? []);

            $page = $this->actingAs($user)->get('/'.$user->id.'/dashboard/satuan-pendidikan');

            if ($page->getStatusCode() !== 200) {
                $failures[] = "[$label] dashboard status ".$page->getStatusCode();

                continue;
            }

            $html = $page->getContent();

            foreach ($case['expect'] as $marker) {
                if (! str_contains($html, $marker)) {
                    $failures[] = "[$label] menu '{$marker}' tidak muncul";
                }
            }

            foreach ($this->auditLinks($user) as $issue) {
                $failures[] = "[$label] $issue";
            }
        }

        $this->assertSame(
            [],
            $failures,
            "Sidebar kombinasi tugas tambahan bermasalah:\n".implode("\n", $failures)
        );
    }



    /**
     * Pemisahan topi: Mengajar, Men Walkes, Jabatan, Tugas Tambahan.
     */
    public function test_topi_mengajar_walkes_dan_jabatan_terpisah(): void
    {
        $this->bootSchool();

        $waka = $this->makeUser(
            'GURU UMUM',
            ['Waka Kurikulum', 'Wali Kelas'],
            true,
            ['homeroom' => true],
        );

        $page = $this->actingAs($waka)->get('/'.$waka->id.'/dashboard/satuan-pendidikan');
        $page->assertOk();
        $html = $page->getContent();

        $this->assertStringContainsString('Mengajar · Guru Mapel', $html);
        $this->assertStringContainsString('Mapel Saya', $html);
        $this->assertStringContainsString('Kurikulum', $html);
        $this->assertStringContainsString('Asesmen', $html);
        $this->assertStringContainsString('Men Walkes', $html);
        $this->assertStringContainsString('Jabatan ·', $html);

        $guru = $this->makeUser('GURU UMUM', [], true);
        $guruPage = $this->actingAs($guru)->get('/'.$guru->id.'/dashboard/satuan-pendidikan');
        $guruPage->assertOk();
        $this->assertStringContainsString('Mengajar · Guru Mapel', $guruPage->getContent());
    }


    /**
     * Rangkap jabatan + tugas tambahan: dua topi tampil, tidak saling menutup.
     */
    public function test_jabatan_dan_tugas_tambahan_punya_topi_sendiri(): void
    {
        $this->bootSchool();

        $dual = $this->makeUser('GURU UMUM', ['Waka Kurikulum', 'Koordinator Kesiswaan'], false);
        $dualPage = $this->actingAs($dual)->get('/'.$dual->id.'/dashboard/satuan-pendidikan');
        $dualPage->assertOk();
        $dualHtml = $dualPage->getContent();
        $this->assertStringContainsString('Jabatan ·', $dualHtml);
        $this->assertStringContainsString('Tugas Tambahan · Koordinator Kesiswaan', $dualHtml);
        $this->assertStringContainsString('Dashboard Kesiswaan', $dualHtml);

        $taskOnly = $this->makeUser('GURU UMUM', ['Koordinator Kesiswaan'], false);
        $taskPage = $this->actingAs($taskOnly)->get('/'.$taskOnly->id.'/dashboard/satuan-pendidikan');
        $taskPage->assertOk();
        $taskHtml = $taskPage->getContent();
        $this->assertStringContainsString('Tugas Tambahan · Koordinator Kesiswaan', $taskHtml);
        $this->assertStringNotContainsString('Jabatan ·', $taskHtml);
    }

    public function test_tmp_probe(): void
    {
        $this->withoutExceptionHandling();
        $this->bootSchool();
        $u = $this->makeUser('GURU UMUM', ['Wali Kamar'], false, ['room' => true]);
        $dorm = \App\Models\Dormitory::query()->first();
        $room = \App\Models\DormitoryRoom::query()->first();

        foreach ([
            '/'.$u->id.'/violation-points/dashboard',
            '/'.$u->id.'/asrama/'.$dorm->id.'/kamar/'.$room->id,
        ] as $path) {
            try {
                $r = $this->actingAs($u)->get($path);
                fwrite(STDOUT, "\nOK $path ".$r->getStatusCode()."\n");
            } catch (\Throwable $e) {
                fwrite(STDOUT, "\nEXC $path: ".get_class($e).' — '.$e->getMessage()."\n".$e->getFile().':'.$e->getLine()."\n");
            }
        }

        $this->assertTrue(true);
    }

    /**
     * Satu GTK menjadi Kepala Satuan Pendidikan sekaligus Kepala Asrama.
     * Kedua sidebar harus tampil dan seluruh tautannya dapat dibuka.
     */
    public function test_dual_head_dapat_membuka_menu_satuan_pendidikan_dan_asrama(): void
    {
        $this->bootSchool();

        $asramaRole = Role::firstOrCreate(
            ['name' => 'Asrama'],
            ['id' => (string) Str::uuid(), 'guard_name' => 'web', 'level' => 3],
        );

        // Role Satuan Pendidikan sudah ada di produksi beserta permission-nya.
        $satuanRole = Role::firstOrCreate(
            ['name' => 'Satuan Pendidikan'],
            ['id' => (string) Str::uuid(), 'guard_name' => 'web', 'level' => 3],
        );
        $this->grantRolePermissions($satuanRole);

        $user = User::create([
            'name' => 'Kepala Rangkap',
            'email' => 'kepala-rangkap@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_system_admin' => false,
        ]);

        $user->assignRole($asramaRole);

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'jabatan' => 'Kepala Asrama',
            'status_kepegawaian' => 'PTT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dormWorkUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $dormWorkUnitId,
            'name' => 'Asrama Audit',
            'code' => 'ASR-AUD',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('gtk_work_unit')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'work_unit_id' => $dormWorkUnitId,
            'jabatan' => 'Kepala Asrama',
            'is_primary' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \App\Models\Dormitory::create([
            'work_unit_id' => $dormWorkUnitId,
            'school_id' => $this->school->id,
            'name' => 'Asrama Audit',
            'gender' => 'putra',
            'head_id' => $user->id,
            'is_active' => true,
        ]);

        $this->school->update(['principal_user_id' => $user->id]);

        // Penetapan kepala sekolah menambahkan role Satuan Pendidikan tanpa
        // menghapus role Asrama.
        \App\Support\UnitHeadAccess::ensureSatuanPendidikanRole($user);

        $user = $user->fresh();
        $this->assertTrue($user->hasRole('Asrama'), 'Role Asrama harus tetap ada.');
        $this->assertTrue($user->hasRole('Satuan Pendidikan'), 'Role Satuan Pendidikan harus ditambahkan.');

        $issues = $this->auditLinks($user);

        $this->assertSame([], $issues, "Tautan bermasalah untuk kepala rangkap:\n".implode("\n", $issues));
    }
}
