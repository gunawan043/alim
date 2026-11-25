<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use App\Support\ConnectApiConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Uji kirim WhatsApp lewat ALIM Connect, di dalam tab WhatsApp Connect
 * pada Pengaturan Sistem (satu tempat dengan konfigurasi SSO).
 *
 * Yang dijaga: key terenkripsi dan tidak tampil balik, key kosong tidak
 * menimpa, prasyarat yang kurang disebut sekaligus, dan kegagalan
 * diterjemahkan menjadi langkah perbaikan.
 */
class ConnectWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_system_admin' => true, 'is_active' => true]);
    }

    private function configure(?string $key = 'key-oper-123'): void
    {
        ConnectApiConfig::storeBaseUrl('https://connect.test');
        ConnectApiConfig::storeSessionId('sess-1');

        if ($key !== null) {
            ConnectApiConfig::storeKey($key);
        }

        Cache::forget('system_settings_map');
    }

    private function updateUrl(): string
    {
        return route('user.sa.system-settings.update', ['userId' => $this->admin->id]);
    }

    private function tabUrl(): string
    {
        return route('user.sa.system-settings.index', ['userId' => $this->admin->id, 'tab' => 'connect-sso']);
    }

    private function saveOutbound(array $extra = [])
    {
        return $this->actingAs($this->admin)
            ->from($this->tabUrl())
            ->post($this->updateUrl(), array_merge([
                'connect_outbound_tab' => '1',
                'connect_base_url' => 'https://connect.test/',
                'connect_session_id' => 'sess-1',
                'connect_api_key_input' => 'rahasia-operator-123',
            ], $extra));
    }

    public function test_key_disimpan_terenkripsi_dan_tidak_ditampilkan_di_halaman(): void
    {
        $this->saveOutbound()->assertRedirect();

        $stored = (string) SystemSetting::query()->where('key', 'connect_api_key')->value('value');

        $this->assertStringStartsWith('enc:v1:', $stored);
        $this->assertStringNotContainsString('rahasia-operator-123', $stored);
        $this->assertSame('rahasia-operator-123', ConnectApiConfig::apiKey());
        $this->assertSame('https://connect.test', ConnectApiConfig::baseUrl(), 'Garis miring akhir dibuang.');

        $this->actingAs($this->admin)
            ->get($this->tabUrl())
            ->assertOk()
            ->assertDontSee('rahasia-operator-123')
            ->assertSee('Tersimpan terenkripsi');
    }

    public function test_key_kosong_tidak_menimpa_key_yang_sudah_tersimpan(): void
    {
        $this->configure('key-lama-999');

        $this->saveOutbound(['connect_api_key_input' => ''])->assertRedirect();

        $this->assertSame('key-lama-999', ConnectApiConfig::apiKey());
    }

    public function test_hapus_key_hanya_lewat_centang_eksplisit(): void
    {
        $this->configure('key-oper-123');

        $this->saveOutbound([
            'connect_api_key_input' => '',
            'clear_connect_api_key' => '1',
        ])->assertRedirect();

        $this->assertFalse(ConnectApiConfig::hasStoredKey());
        $this->assertSame('', ConnectApiConfig::apiKey());
    }

    public function test_origin_http_ditolak_di_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->assertNull(
            ConnectApiConfig::originOf('http://connect.test'),
            'Origin http harus ditolak saat aplikasi berjalan di production.'
        );

        $this->app->detectEnvironment(fn () => 'testing');
        $this->assertSame('https://connect.test', ConnectApiConfig::originOf('https://connect.test/panel'));
    }

    public function test_base_url_tidak_valid_ditolak_saat_disimpan(): void
    {
        $this->saveOutbound(['connect_base_url' => 'ftp://connect.test'])
            ->assertSessionHas('error');
    }

    public function test_prasyarat_yang_kurang_disebut_semua_sekaligus(): void
    {
        $missing = ConnectApiConfig::missing();

        $this->assertCount(3, $missing);

        $this->actingAs($this->admin)
            ->get($this->tabUrl())
            ->assertOk()
            ->assertSee('Base URL Connect belum diisi')
            ->assertSee('Session ID belum diisi')
            ->assertSee('API key Connect');
    }

    public function test_cek_sesi_readonly_membaca_status_nyata(): void
    {
        $this->configure();

        Http::fake([
            'connect.test/api/sessions/sess-1' => Http::response([
                'id' => 'sess-1',
                'name' => 'ALIM Pusat',
                'status' => 'ready',
                'phone' => '628111222333',
                'lastActive' => '2026-11-25T10:00:00Z',
            ], 200),
        ]);

        $this->actingAs($this->admin)
            ->from($this->tabUrl())
            ->post($this->updateUrl(), ['connect_check_session' => '1'])
            ->assertRedirect()
            ->assertSessionHas('connect_outbound_result');

        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->hasHeader('X-API-Key', 'key-oper-123'));

        $this->actingAs($this->admin)
            ->get($this->tabUrl())
            ->assertOk()
            ->assertSee('Sesi WhatsApp siap menerima pengiriman.')
            ->assertSee('628111222333')
            ->assertDontSee('key-oper-123');
    }

    public function test_kirim_pesan_memakai_jid_c_us_dan_mencatat_riwayat(): void
    {
        $this->configure();

        Http::fake([
            'connect.test/api/sessions/sess-1/messages/send-text' => Http::response([
                'messageId' => 'true_628111222333@c.us_ABC',
                'timestamp' => 1706868000,
            ], 201),
        ]);

        $this->actingAs($this->admin)
            ->from($this->tabUrl())
            ->post($this->updateUrl(), [
                'connect_send_test' => '1',
                'connect_test_phone' => '0811-1222-333',
                'connect_test_text' => 'Uji dari ALIM',
            ])
            ->assertRedirect();

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/messages/send-text')
            && $request['chatId'] === '628111222333@c.us'
            && $request['text'] === 'Uji dari ALIM'
            && $request->hasHeader('X-API-Key', 'key-oper-123'));

        $this->assertDatabaseHas('integration_event_log', [
            'event_name' => 'wa.connect_test',
            'status' => 'processed',
            'aggregate_id' => '628111222333@c.us',
        ]);

        $this->actingAs($this->admin)
            ->get($this->tabUrl())
            ->assertOk()
            ->assertSee('Connect menerima pesan untuk dikirim ke 628111222333@c.us');
    }

    public function test_sesi_belum_siap_diterjemahkan_menjadi_langkah_perbaikan(): void
    {
        $this->configure();

        Http::fake(['connect.test/*' => Http::response(['message' => 'Session not active'], 400)]);

        $this->actingAs($this->admin)
            ->from($this->tabUrl())
            ->post($this->updateUrl(), [
                'connect_send_test' => '1',
                'connect_test_phone' => '08111222333',
                'connect_test_text' => 'Uji',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('integration_event_log', [
            'event_name' => 'wa.connect_test',
            'status' => 'failed',
        ]);

        $this->actingAs($this->admin)
            ->get($this->tabUrl())
            ->assertOk()
            ->assertSee('sesi belum aktif')
            ->assertSee('pastikan sesi WhatsApp sudah terhubung');
    }

    public function test_key_ditolak_menyebut_pemeriksaan_key(): void
    {
        $this->configure();

        Http::fake(['connect.test/*' => Http::response(['message' => 'Unauthorized'], 401)]);

        $this->actingAs($this->admin)->from($this->tabUrl())
            ->post($this->updateUrl(), ['connect_check_session' => '1'])
            ->assertRedirect();

        $this->actingAs($this->admin)->get($this->tabUrl())
            ->assertSee('API key ditolak Connect');
    }

    public function test_connect_tidak_bisa_dihubungi_tidak_membuat_halaman_error(): void
    {
        $this->configure();

        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection refused'));

        $this->actingAs($this->admin)->from($this->tabUrl())
            ->post($this->updateUrl(), ['connect_check_session' => '1'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin)->get($this->tabUrl())
            ->assertOk()
            ->assertSee('Connect tidak bisa dihubungi');
    }

    public function test_nomor_tanpa_isi_pesan_ditolak(): void
    {
        $this->configure();

        $this->actingAs($this->admin)->from($this->tabUrl())
            ->post($this->updateUrl(), ['connect_send_test' => '1', 'connect_test_phone' => '08111222333'])
            ->assertSessionHas('error');

        Http::assertNothingSent();
    }

    public function test_hanya_super_admin_yang_bisa_membuka_tab(): void
    {
        $biasa = User::factory()->create(['is_active' => true]);

        $this->actingAs($biasa)
            ->get(route('user.sa.system-settings.index', ['userId' => $biasa->id, 'tab' => 'connect-sso']))
            ->assertForbidden();
    }
}
