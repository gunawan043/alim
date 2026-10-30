<?php

namespace Tests\Feature;

use App\Models\GtkContact;
use App\Models\GtkEmployment;
use App\Models\GtkProfile;
use App\Models\GtkWorkUnit;
use App\Models\JenisGtk;
use App\Models\Role;
use App\Models\School;
use App\Models\StructuralPosition;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WorkUnit;
use App\Support\RecruitmentApiConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * API integrasi recruitment (/api/recruitment/v1) — data referensi Personalia.
 */
class RecruitmentApiTest extends TestCase
{
    use RefreshDatabase;

    private const API_KEY = 'test-recruitment-key';

    private const API_HEADER = 'X-ALIM-RECRUITMENT-KEY';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.recruitment.api_key' => self::API_KEY]);
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(): array
    {
        return [self::API_HEADER => self::API_KEY];
    }

    private function createWorkUnit(string $name = 'Unit Test'): WorkUnit
    {
        return WorkUnit::factory()->create([
            'name' => $name,
            'type' => 'Unit Akademik',
            'is_active' => true,
        ]);
    }

    private function createEmployee(WorkUnit $workUnit, array $attrs = []): User
    {
        $user = User::factory()->create(array_merge([
            'name' => 'Budi Santoso',
            'email' => 'budi+'.Str::lower(Str::random(6)).'@example.test',
            'username' => 'budi'.Str::lower(Str::random(6)),
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

        // Observer kepegawaian mengganti role saat GtkEmployment dibuat.
        $user->assignRole('Staff');

        return $user;
    }

    public function test_rejects_request_without_api_key(): void
    {
        $this->getJson('/api/recruitment/v1/pegawai')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_accepts_bearer_token_as_alternative(): void
    {
        $this->getJson('/api/recruitment/v1/ping', [
            'Authorization' => 'Bearer '.self::API_KEY,
        ])->assertOk()->assertJsonPath('success', true);
    }

    public function test_ping_returns_connection_summary(): void
    {
        $workUnit = $this->createWorkUnit('Unit Ping');
        $this->createEmployee($workUnit);

        $this->getJson('/api/recruitment/v1/ping', $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.jumlah_pegawai', 1)
            ->assertJsonPath('data.jumlah_satuan_kerja', 1)
            ->assertJsonStructure([
                'data' => [
                    'app', 'server_time', 'jumlah_pegawai', 'jumlah_satuan_kerja',
                    'jumlah_sekolah', 'jumlah_role', 'endpoint_tersedia',
                ],
            ]);
    }

    public function test_pegawai_index_returns_fields_for_recruitment(): void
    {
        $workUnit = $this->createWorkUnit('Unit Recruitment');
        $this->createEmployee($workUnit, [
            'email' => 'budi@example.test',
            'username' => 'budi',
        ]);

        $response = $this->getJson('/api/recruitment/v1/pegawai', $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.0.nama', 'Budi Santoso')
            ->assertJsonPath('data.0.email', 'budi@example.test')
            ->assertJsonPath('data.0.username', 'budi')
            ->assertJsonPath('data.0.no_whatsapp_normalized', '6281234567890')
            ->assertJsonPath('data.0.satuan_kerja.nama', 'Unit Recruitment')
            ->assertJsonPath('data.0.status_kepegawaian_text', 'Guru Tetap Yayasan')
            ->assertJsonPath('data.0.roles.0', 'Staff');

        $item = $response->json('data.0');

        foreach (['nik', 'no_kk', 'npwp', 'nama_ibu_kandung', 'nupy', 'nomor_sk', 'kontak_darurat'] as $sensitiveKey) {
            $this->assertArrayNotHasKey($sensitiveKey, $item, "Field sensitif {$sensitiveKey} tidak boleh ikut terkirim.");
        }
    }

    public function test_pegawai_can_be_filtered_by_unit_and_role(): void
    {
        $firstUnit = $this->createWorkUnit('Unit Pertama');
        $secondUnit = $this->createWorkUnit('Unit Kedua');

        $first = $this->createEmployee($firstUnit, ['name' => 'Pegawai Pertama']);
        $this->createEmployee($secondUnit, ['name' => 'Pegawai Kedua']);

        $role = Role::firstOrCreate(['name' => 'Humas Personalia', 'guard_name' => 'web']);
        $first->assignRole($role);

        $this->getJson('/api/recruitment/v1/pegawai?satuan_kerja='.$firstUnit->id, $this->authHeaders())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama', 'Pegawai Pertama');

        $this->getJson('/api/recruitment/v1/pegawai?role=Humas%20Personalia', $this->authHeaders())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama', 'Pegawai Pertama');
    }

    public function test_pegawai_show_returns_detail_and_404_for_unknown_id(): void
    {
        $workUnit = $this->createWorkUnit('Unit Detail');
        $employee = $this->createEmployee($workUnit);

        $this->getJson('/api/recruitment/v1/pegawai/'.$employee->id, $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.id', $employee->id)
            ->assertJsonPath('data.nama', 'Budi Santoso');

        $this->getJson('/api/recruitment/v1/pegawai/'.Str::uuid(), $this->authHeaders())
            ->assertStatus(404);
    }

    public function test_reference_endpoints_return_master_data(): void
    {
        $workUnit = $this->createWorkUnit('Unit Referensi');

        JenisGtk::create(['nama' => 'Pendidik / Guru', 'urutan' => 1, 'is_active' => true]);

        StructuralPosition::create([
            'code' => 'guru-referensi',
            'name' => 'Guru Referensi',
            'level' => 'unit',
            'hierarchy_level' => 1,
            'urutan' => 1,
            'is_active' => true,
        ]);

        School::factory()->create([
            'name' => 'Sekolah Referensi',
            'work_unit_id' => $workUnit->id,
            'is_active' => true,
        ]);

        Role::firstOrCreate(['name' => 'Pewawancara', 'guard_name' => 'web']);

        $this->getJson('/api/recruitment/v1/satuan-kerja', $this->authHeaders())
            ->assertOk()->assertJsonPath('data.0.nama', 'Unit Referensi');

        $this->getJson('/api/recruitment/v1/jabatan', $this->authHeaders())
            ->assertOk()->assertJsonPath('data.0.nama', 'Guru Referensi');

        $this->getJson('/api/recruitment/v1/jenis-gtk', $this->authHeaders())
            ->assertOk()->assertJsonPath('data.0.nama', 'Pendidik / Guru');

        $this->getJson('/api/recruitment/v1/sekolah', $this->authHeaders())
            ->assertOk()->assertJsonPath('data.0.nama', 'Sekolah Referensi');

        $roles = $this->getJson('/api/recruitment/v1/role', $this->authHeaders())
            ->assertOk()
            ->json('data');

        $this->assertTrue(
            collect($roles)->pluck('nama')->contains('Pewawancara'),
            'Role Pewawancara harus tersedia di daftar role.'
        );
    }

    public function test_can_be_disabled_from_system_settings(): void
    {
        SystemSetting::set('recruitment_api_enabled', '0', 'integration');
        Cache::forget('system_settings_map');

        $this->getJson('/api/recruitment/v1/ping', $this->authHeaders())
            ->assertStatus(503)
            ->assertJsonPath('success', false);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);
    }

    private function saveUrl(User $admin): string
    {
        return "/{$admin->id}/sa/system-settings";
    }

    public function test_admin_can_save_recruitment_settings_and_token(): void
    {
        config(['services.recruitment.api_key' => '']);

        $admin = $this->admin();
        $url = $this->saveUrl($admin);

        $response = $this->actingAs($admin)->from($url)->post($url, [
            'recruitment_tab' => '1',
            'recruitment_api_enabled' => '1',
            'recruitment_api_header' => 'X-KARIR-TOKEN',
            'recruitment_api_allowed_ips' => ' 103.10.20.30 , 10.0.0.0/8 , ',
        ]);

        $response->assertRedirect($url);
        $response->assertSessionHas('success');

        $this->assertSame('1', SystemSetting::get('recruitment_api_enabled'));
        $this->assertSame('X-KARIR-TOKEN', SystemSetting::get('recruitment_api_header'));
        $this->assertSame('103.10.20.30,10.0.0.0/8', SystemSetting::get('recruitment_api_allowed_ips'));

        $rawKey = (string) SystemSetting::get('recruitment_api_key');
        $this->assertStringStartsWith('enc:v1:', $rawKey);
        $this->assertSame(64, strlen(RecruitmentApiConfig::key()));
    }

    public function test_saved_recruitment_settings_are_used_immediately(): void
    {
        config(['services.recruitment.api_key' => '']);

        $admin = $this->admin();
        $url = $this->saveUrl($admin);

        $this->actingAs($admin)->post($url, [
            'recruitment_tab' => '1',
            'recruitment_api_enabled' => '1',
            'recruitment_api_header' => 'X-KARIR-TOKEN',
            'recruitment_api_allowed_ips' => '',
        ]);

        Cache::forget('system_settings_map');

        $savedKey = RecruitmentApiConfig::key();

        $this->getJson('/api/recruitment/v1/ping', ['X-KARIR-TOKEN' => $savedKey])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/recruitment/v1/ping', [self::API_HEADER => self::API_KEY])
            ->assertStatus(401);
    }

    public function test_recruitment_tab_renders(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get($this->saveUrl($admin).'?tab=recruitment-api')
            ->assertOk()
            ->assertSee('Integrasi Recruitment')
            ->assertSee('Token API Recruitment')
            ->assertSee('/api/recruitment/v1');
    }
}
