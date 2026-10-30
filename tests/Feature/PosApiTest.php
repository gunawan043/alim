<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Divisi;
use App\Models\GradeLevel;
use App\Models\GtkContact;
use App\Models\GtkEmployment;
use App\Models\GtkProfile;
use App\Models\GtkWorkUnit;
use App\Models\JenisGtk;
use App\Models\School;
use App\Models\StructuralPosition;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * POS / Kasir integration API (/api/pos/v1) — static API key auth,
 * read-only master data (satuan kerja, kepegawaian, divisi, jabatan,
 * jenis GTK, sekolah, santri).
 */
class PosApiTest extends TestCase
{
    use RefreshDatabase;

    private const API_KEY = 'test-pos-api-key';

    private const API_HEADER = 'X-ALIM-POS-KEY';

    protected function setUp(): void
    {
        parent::setUp();

        // SQLite menyimpan FK lama gtk_employments → positions meskipun tabel
        // `positions` sudah di-merge/di-drop oleh migrasi. Buat stub agar
        // insert fixture ke gtk_employments tidak gagal.
        if (DB::connection()->getDriverName() === 'sqlite'
            && Schema::hasTable('gtk_employments')
            && ! Schema::hasTable('positions')) {
            Schema::create('positions', function ($table) {
                $table->uuid('id')->primary();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }

        config(['services.pos.api_key' => self::API_KEY]);
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(): array
    {
        return [self::API_HEADER => self::API_KEY];
    }

    // ── Fixtures ───────────────────────────────────────────────────────────

    private function createWorkUnit(string $name = 'Unit Test', ?string $divisiId = null): WorkUnit
    {
        return WorkUnit::factory()->create([
            'name' => $name,
            'type' => 'Unit Akademik',
            'divisi_id' => $divisiId,
            'is_active' => true,
        ]);
    }

    private function createEmployee(WorkUnit $workUnit, array $attrs = []): User
    {
        $user = User::factory()->create(array_merge([
            'name' => 'Budi Santoso',
            'is_active' => true,
        ], $attrs));

        GtkProfile::create([
            'user_id' => $user->id,
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Mataram',
            'tanggal_lahir' => '1990-05-12',
        ]);

        GtkContact::create([
            'user_id' => $user->id,
            'no_hp' => '081234567890',
            'no_whatsapp' => '081234567890',
        ]);

        GtkEmployment::create([
            'user_id' => $user->id,
            'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Pendidik / Guru',
            'jabatan' => 'GURU TAHFIDZ',
        ]);

        GtkWorkUnit::create([
            'user_id' => $user->id,
            'work_unit_id' => $workUnit->id,
            'jabatan' => 'GURU TAHFIDZ',
            'is_primary' => true,
        ]);

        return $user;
    }

    /**
     * @return array{student: Student, school: School, studyGroup: StudyGroup}
     */
    private function createStudentWithClass(string $name = 'Santri Test'): array
    {
        $workUnit = $this->createWorkUnit('Yayasan Santri');
        $school = School::factory()->create([
            'name' => 'SD Test',
            'work_unit_id' => $workUnit->id,
            'is_active' => true,
        ]);

        $academicYear = AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2026/2027',
            'is_active' => true,
        ]);

        $gradeLevel = GradeLevel::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'level' => 7,
            'name' => 'Kelas 7',
            'is_active' => true,
        ]);

        $studyGroup = StudyGroup::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => '7A',
            'code' => '7A',
            'is_active' => true,
        ]);

        $student = Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'nis' => '12345',
            'nisn' => '0012345678',
            'name' => $name,
            'gender' => 'L',
            'birth_place' => 'Mataram',
            'birth_date' => '2013-01-15',
            'status' => 'active',
        ]);

        StudentClassHistory::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'study_group_id' => $studyGroup->id,
            'academic_year_id' => $academicYear->id,
            'is_active' => true,
        ]);

        return ['student' => $student, 'school' => $school, 'studyGroup' => $studyGroup];
    }

    // ── Authentication ─────────────────────────────────────────────────────

    public function test_rejects_request_without_api_key(): void
    {
        $this->getJson('/api/pos/v1/kepegawaian')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_rejects_request_with_invalid_api_key(): void
    {
        Log::spy();

        $this->getJson('/api/pos/v1/kepegawaian', [self::API_HEADER => 'wrong-key'])
            ->assertStatus(401)
            ->assertJsonPath('success', false);

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message) => str_contains($message, 'API key'))
            ->once();
    }

    public function test_rejects_generic_x_api_key_header(): void
    {
        $this->getJson('/api/pos/v1/kepegawaian', ['X-API-KEY' => self::API_KEY])
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_returns_503_when_integration_is_not_configured(): void
    {
        config(['services.pos.api_key' => '']);

        $this->getJson('/api/pos/v1/divisi', $this->authHeaders())
            ->assertStatus(503)
            ->assertJsonPath('success', false);
    }

    public function test_accepts_bearer_token_as_alternative_to_api_key_header(): void
    {
        $this->getJson('/api/pos/v1/divisi', [
            'Authorization' => 'Bearer '.self::API_KEY,
        ])->assertOk()->assertJsonPath('success', true);
    }

    public function test_api_key_header_name_is_configurable(): void
    {
        config(['services.pos.header' => 'X-KASIR-SECRET']);

        $this->getJson('/api/pos/v1/divisi', $this->authHeaders())
            ->assertStatus(401);

        $this->getJson('/api/pos/v1/divisi', ['X-KASIR-SECRET' => self::API_KEY])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_rejects_ip_outside_allowlist(): void
    {
        Log::spy();

        config(['services.pos.allowed_ips' => ['203.0.113.9']]);

        $this->getJson('/api/pos/v1/divisi', $this->authHeaders())
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message) => str_contains($message, 'IP'))
            ->once();
    }

    public function test_allows_ip_inside_allowlist_including_cidr(): void
    {
        config(['services.pos.allowed_ips' => ['203.0.113.0/24']]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->getJson('/api/pos/v1/divisi', $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_uses_token_from_system_settings_when_present(): void
    {
        SystemSetting::set('pos_api_key', 'db-stored-key', 'pos_api');
        Cache::forget('system_settings_map');

        $this->getJson('/api/pos/v1/divisi', $this->authHeaders())
            ->assertStatus(401);

        $this->getJson('/api/pos/v1/divisi', [self::API_HEADER => 'db-stored-key'])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_can_be_disabled_from_system_settings(): void
    {
        SystemSetting::set('pos_api_enabled', '0', 'pos_api');
        Cache::forget('system_settings_map');

        $this->getJson('/api/pos/v1/divisi', $this->authHeaders())
            ->assertStatus(503)
            ->assertJsonPath('success', false);
    }

    public function test_header_and_allowed_ips_from_system_settings_are_used(): void
    {
        SystemSetting::set('pos_api_key', self::API_KEY, 'pos_api');
        SystemSetting::set('pos_api_header', 'X-KASIR-TOKEN', 'pos_api');
        SystemSetting::set('pos_api_allowed_ips', '203.0.113.0/24', 'pos_api');
        Cache::forget('system_settings_map');

        $this->getJson('/api/pos/v1/divisi', ['X-KASIR-TOKEN' => self::API_KEY])
            ->assertStatus(403);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
            ->getJson('/api/pos/v1/divisi', ['X-KASIR-TOKEN' => self::API_KEY])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_empty_allowlist_in_system_settings_overrides_env_whitelist(): void
    {
        config(['services.pos.allowed_ips' => ['203.0.113.9']]);

        SystemSetting::set('pos_api_allowed_ips', '', 'pos_api');
        Cache::forget('system_settings_map');

        $this->getJson('/api/pos/v1/divisi', $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    // ── Tes Koneksi (Ping) ────────────────────────────────────────────────

    public function test_ping_endpoint_returns_connection_summary(): void
    {
        $workUnit = $this->createWorkUnit('Unit Ping');
        $this->createEmployee($workUnit);

        $this->getJson('/api/pos/v1/ping', $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.jumlah_pegawai', 1)
            ->assertJsonPath('data.jumlah_satuan_kerja', 1)
            ->assertJsonStructure([
                'data' => [
                    'app', 'server_time', 'jumlah_pegawai', 'jumlah_satuan_kerja',
                    'jumlah_santri', 'endpoint_tersedia',
                ],
            ]);
    }

    public function test_ping_endpoint_requires_api_key(): void
    {
        $this->getJson('/api/pos/v1/ping')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    // ── Security hardening ─────────────────────────────────────────────────

    public function test_repeated_failed_authentication_is_rate_limited(): void
    {
        config(['services.pos.auth_fail_limit' => 3]);
        Cache::flush();

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->getJson('/api/pos/v1/divisi', [self::API_HEADER => 'wrong-key'])
                ->assertStatus(401);
        }

        $this->getJson('/api/pos/v1/divisi', [self::API_HEADER => 'wrong-key'])
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    public function test_requires_https_in_production(): void
    {
        $this->app['env'] = 'production';
        config(['services.pos.require_https' => true]);

        $this->getJson('/api/pos/v1/divisi', $this->authHeaders())
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_api_responses_are_not_cacheable(): void
    {
        $this->getJson('/api/pos/v1/ping', $this->authHeaders())
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_show_endpoint_rejects_non_uuid_id(): void
    {
        $response = $this->getJson('/api/pos/v1/kepegawaian/not-a-uuid', $this->authHeaders());

        // Constraint UUID membuat request tidak pernah masuk ke controller POS;
        // aplikasi jatuh ke fallback web (401) atau 404. Yang penting bukan 200.
        $this->assertContains($response->status(), [401, 404]);
        $this->assertNotSame(200, $response->status());
    }

    // ── Satuan Kerja ───────────────────────────────────────────────────────

    public function test_satuan_kerja_index_returns_paginated_list(): void
    {
        $divisi = Divisi::create([
            'nama' => 'Pengasuhan',
            'kode' => 'PAH-PNG',
            'is_active' => true,
        ]);

        $workUnit = $this->createWorkUnit('Asrama Putra', $divisi->id);
        $this->createEmployee($workUnit);

        $response = $this->getJson('/api/pos/v1/satuan-kerja', $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.nama', 'Asrama Putra')
            ->assertJsonPath('data.0.divisi.nama', 'Pengasuhan')
            ->assertJsonPath('data.0.jumlah_pegawai', 1)
            ->assertJsonStructure([
                'success', 'message', 'data', 'errors',
                'meta' => ['pagination' => ['page', 'per_page', 'total', 'last_page']],
            ]);
    }

    public function test_satuan_kerja_show_returns_404_for_unknown_id(): void
    {
        $this->getJson('/api/pos/v1/satuan-kerja/'.Str::uuid(), $this->authHeaders())
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    // ── Kepegawaian ────────────────────────────────────────────────────────

    public function test_kepegawaian_index_exposes_required_fields_and_hides_sensitive_data(): void
    {
        $workUnit = $this->createWorkUnit('Unit Kepegawaian');
        $this->createEmployee($workUnit);

        $response = $this->getJson('/api/pos/v1/kepegawaian', $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.nama', 'Budi Santoso')
            ->assertJsonPath('data.0.no_whatsapp', '081234567890')
            ->assertJsonPath('data.0.no_whatsapp_normalized', '6281234567890')
            ->assertJsonPath('data.0.satuan_kerja.nama', 'Unit Kepegawaian')
            ->assertJsonPath('data.0.jabatan', 'GURU TAHFIDZ')
            ->assertJsonPath('data.0.status_kepegawaian', 'GTY')
            ->assertJsonPath('data.0.status_kepegawaian_text', 'Guru Tetap Yayasan')
            ->assertJsonPath('data.0.jenis_gtk', 'Pendidik / Guru')
            ->assertJsonPath('data.0.jenis_kelamin_text', 'Laki-laki');

        $item = $response->json('data.0');

        foreach (['nik', 'no_kk', 'npwp', 'nama_ibu_kandung', 'nupy', 'nomor_sk', 'kontak_darurat'] as $sensitiveKey) {
            $this->assertArrayNotHasKey($sensitiveKey, $item, "Field sensitif {$sensitiveKey} tidak boleh ikut terkirim.");
        }
    }

    public function test_kepegawaian_can_be_filtered_by_satuan_kerja(): void
    {
        $firstUnit = $this->createWorkUnit('Unit Pertama');
        $secondUnit = $this->createWorkUnit('Unit Kedua');

        $this->createEmployee($firstUnit, ['name' => 'Pegawai Pertama']);
        $this->createEmployee($secondUnit, ['name' => 'Pegawai Kedua']);

        $response = $this->getJson(
            '/api/pos/v1/kepegawaian?satuan_kerja='.$firstUnit->id,
            $this->authHeaders()
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama', 'Pegawai Pertama');
    }

    public function test_kepegawaian_defaults_to_active_only_but_supports_all(): void
    {
        $workUnit = $this->createWorkUnit('Unit Aktif');
        $this->createEmployee($workUnit, ['name' => 'Pegawai Aktif']);
        $this->createEmployee($workUnit, ['name' => 'Pegawai Nonaktif', 'is_active' => false]);

        $this->getJson('/api/pos/v1/kepegawaian', $this->authHeaders())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama', 'Pegawai Aktif');

        $this->getJson('/api/pos/v1/kepegawaian?is_active=all', $this->authHeaders())
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_kepegawaian_show_returns_detail(): void
    {
        $workUnit = $this->createWorkUnit('Unit Detail');
        $employee = $this->createEmployee($workUnit);

        $this->getJson('/api/pos/v1/kepegawaian/'.$employee->id, $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.id', $employee->id)
            ->assertJsonPath('data.nama', 'Budi Santoso')
            ->assertJsonPath('data.satuan_kerja.nama', 'Unit Detail');
    }

    public function test_kepegawaian_show_returns_404_for_unknown_id(): void
    {
        $this->getJson('/api/pos/v1/kepegawaian/'.Str::uuid(), $this->authHeaders())
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    // ── Master organisasi ──────────────────────────────────────────────────

    public function test_divisi_index_returns_list(): void
    {
        Divisi::create(['nama' => 'Tahfidz', 'kode' => 'PAH-TAH', 'is_active' => true]);

        $this->getJson('/api/pos/v1/divisi', $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.0.nama', 'Tahfidz')
            ->assertJsonPath('data.0.kode', 'PAH-TAH');
    }

    public function test_jabatan_index_returns_positions_with_jenis_gtk(): void
    {
        $jenisGtk = JenisGtk::create(['nama' => 'Pendidik / Guru', 'urutan' => 1, 'is_active' => true]);

        StructuralPosition::create([
            'jenis_gtk_id' => $jenisGtk->id,
            'code' => 'guru_tahfidz',
            'name' => 'Guru Tahfidz',
            'level' => 'unit',
            'hierarchy_level' => 2,
            'urutan' => 3,
            'is_active' => true,
        ]);

        $this->getJson('/api/pos/v1/jabatan', $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.0.nama', 'Guru Tahfidz')
            ->assertJsonPath('data.0.jenis_gtk.nama', 'Pendidik / Guru');
    }

    public function test_jenis_gtk_index_returns_list(): void
    {
        JenisGtk::create(['nama' => 'Tenaga Kependidikan', 'urutan' => 7, 'is_active' => true]);

        $this->getJson('/api/pos/v1/jenis-gtk', $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.0.nama', 'Tenaga Kependidikan');
    }

    // ── Sekolah ────────────────────────────────────────────────────────────

    public function test_sekolah_index_returns_school_profile(): void
    {
        $workUnit = $this->createWorkUnit('Unit Sekolah');

        School::factory()->create([
            'name' => 'SMP IT Test',
            'work_unit_id' => $workUnit->id,
            'school_level' => 'smp',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/pos/v1/sekolah', $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.0.nama', 'SMP IT Test')
            ->assertJsonPath('data.0.jenjang', 'smp')
            ->assertJsonPath('data.0.satuan_kerja.nama', 'Unit Sekolah')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'npsn', 'nss', 'nama', 'jenjang', 'jenjang_text',
                        'gender', 'status', 'status_text', 'alamat',
                        'provinsi', 'kota', 'kecamatan', 'desa', 'kode_pos',
                        'telepon', 'email', 'website', 'akreditasi',
                        'tahun_akreditasi', 'kepala_sekolah', 'satuan_kerja',
                        'logo_url', 'is_active', 'diperbarui_pada',
                    ],
                ],
            ]);
    }

    // ── Santri ─────────────────────────────────────────────────────────────

    public function test_santri_index_returns_identity_class_and_school(): void
    {
        $fixture = $this->createStudentWithClass();

        $this->getJson('/api/pos/v1/santri', $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.0.nama', 'Santri Test')
            ->assertJsonPath('data.0.nis', '12345')
            ->assertJsonPath('data.0.nisn', '0012345678')
            ->assertJsonPath('data.0.kelas.nama', '7A')
            ->assertJsonPath('data.0.kelas.tingkat', 7)
            ->assertJsonPath('data.0.sekolah.nama', 'SD Test')
            ->assertJsonPath('data.0.status', 'active');
    }

    public function test_santri_can_be_filtered_by_sekolah_and_kelas(): void
    {
        $fixture = $this->createStudentWithClass();

        $this->getJson('/api/pos/v1/santri?sekolah='.$fixture['school']->id, $this->authHeaders())
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/pos/v1/santri?kelas='.$fixture['studyGroup']->id, $this->authHeaders())
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/pos/v1/santri?sekolah='.Str::uuid(), $this->authHeaders())
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_santri_show_returns_detail_and_404_for_unknown_id(): void
    {
        $fixture = $this->createStudentWithClass();

        $this->getJson('/api/pos/v1/santri/'.$fixture['student']->id, $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.nama', 'Santri Test');

        $this->getJson('/api/pos/v1/santri/'.Str::uuid(), $this->authHeaders())
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}
