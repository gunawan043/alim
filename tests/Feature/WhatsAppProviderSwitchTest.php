<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Services\Notifications\WhatsAppGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Berpindah provider WA cukup lewat pengaturan, tanpa mengubah kode:
 * `wa_provider` = fonnte | connect, dengan kredensial masing-masing.
 */
class WhatsAppProviderSwitchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        SystemSetting::set('wa_enabled', '1', 'whatsapp');
        SystemSetting::set('wa_send_gap', '0', 'whatsapp');
        SystemSetting::set('wa_brand_footer', '_Pesan otomatis ALIM_', 'whatsapp');
        Cache::forget('system_settings_map');
    }

    private function useFonnte(): void
    {
        SystemSetting::set('wa_provider', 'fonnte', 'whatsapp');
        SystemSetting::set('wa_api_token', 'token-fonnte', 'whatsapp');
        Cache::forget('system_settings_map');
    }

    private function useConnect(bool $complete = true): void
    {
        SystemSetting::set('wa_provider', 'connect', 'whatsapp');
        SystemSetting::set('connect_base_url', 'https://connect.test', 'integration');
        SystemSetting::set('connect_session_id', 'sess-1', 'integration');

        if ($complete) {
            \App\Support\ConnectApiConfig::storeKey('kunci-uji-yang-cukup-panjang');
        }

        Cache::forget('system_settings_map');
    }

    public function test_provider_fonnte_dipakai_saat_dipilih(): void
    {
        $this->useFonnte();
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $this->assertTrue(app(WhatsAppGatewayService::class)->send('081234567890', 'Halo'));

        Http::assertSent(fn ($r) => str_contains($r->url(), 'api.fonnte.com')
            && $r['target'] === '6281234567890');
    }

    public function test_provider_connect_memakai_api_connect(): void
    {
        $this->useConnect();
        Http::fake(['connect.test/*' => Http::response(['messageId' => 'abc'], 201)]);

        $this->assertTrue(app(WhatsAppGatewayService::class)->send('081234567890', 'Halo'));

        Http::assertSent(fn ($r) => str_contains($r->url(), '/api/sessions/sess-1/messages/send-text')
            && $r['chatId'] === '6281234567890@c.us'
            && str_ends_with($r['text'], '_Pesan otomatis ALIM_'));
    }

    public function test_connect_tanpa_kredensial_gagal_dengan_alasan_jelas(): void
    {
        $this->useConnect(complete: false);
        Http::fake();

        $gateway = app(WhatsAppGatewayService::class);

        $this->assertFalse($gateway->send('081234567890', 'Halo'));
        $this->assertStringContainsString('API key', $gateway->lastResponseDetail());
        Http::assertNothingSent();
    }

    public function test_kembali_ke_fonnte_tanpa_mengubah_kode(): void
    {
        $this->useConnect();
        Http::fake(['connect.test/*' => Http::response(['messageId' => 'abc'], 201)]);
        $this->assertTrue(app(WhatsAppGatewayService::class)->send('081234567890', 'Lewat Connect'));

        // Pindah balik ke Fonnte hanya dengan pengaturan.
        $this->useFonnte();
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);
        $this->assertTrue(app(WhatsAppGatewayService::class)->send('081234567890', 'Lewat Fonnte'));

        Http::assertSent(fn ($r) => str_contains($r->url(), 'api.fonnte.com'));
    }
}
