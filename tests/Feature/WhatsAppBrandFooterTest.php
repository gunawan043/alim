<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Services\Notifications\WhatsAppGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Footer branding WA dipasang di satu tempat (WhatsAppGatewayService::send)
 * supaya semua jalur — OTP, keamanan, wali, antrean job — mendapatkannya.
 */
class WhatsAppBrandFooterTest extends TestCase
{
    use RefreshDatabase;

    private const FOOTER = '_Pesan otomatis dari aplikasi ALIM | Ponpes Abu Hurairah_';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('system_settings_map');

        SystemSetting::set('wa_enabled', '1', 'whatsapp');
        SystemSetting::set('wa_provider', 'fonnte', 'whatsapp');
        SystemSetting::set('wa_api_token', 'token-uji', 'whatsapp');
        SystemSetting::set('wa_brand_footer', self::FOOTER, 'whatsapp');

        Cache::forget('system_settings_map');
    }

    private function gateway(): WhatsAppGatewayService
    {
        return app(WhatsAppGatewayService::class);
    }

    public function test_pesan_teks_mendapat_footer(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true, 'id' => 1], 200)]);

        $this->assertTrue($this->gateway()->send('081234567890', 'Halo wali santri.'));

        Http::assertSent(function ($request) {
            $message = $request['message'] ?? '';

            return $request['target'] === '6281234567890'
                && str_ends_with($message, self::FOOTER)
                && str_starts_with($message, 'Halo wali santri.');
        });
    }

    public function test_pesan_otp_juga_mendapat_footer(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true, 'id' => 2], 200)]);

        $this->gateway()->send('081234567890', 'Kode verifikasi ALIM: 123456', [
            'event' => 'login_otp',
        ]);

        Http::assertSent(fn ($request) => str_contains($request['message'] ?? '', '123456')
            && str_ends_with($request['message'], self::FOOTER));
    }

    public function test_footer_tidak_ditambahkan_dua_kali(): void
    {
        $once = $this->gateway()->withBrandFooter('Pesan.');
        $twice = $this->gateway()->withBrandFooter($once);

        $this->assertSame($once, $twice);
        $this->assertSame(1, substr_count($twice, self::FOOTER));
    }

    public function test_footer_kosong_tidak_mengubah_pesan(): void
    {
        SystemSetting::set('wa_brand_footer', '', 'whatsapp');
        Cache::forget('system_settings_map');

        $this->assertSame('Pesan biasa.', $this->gateway()->withBrandFooter('Pesan biasa.'));
    }

    public function test_footer_dipisahkan_baris_kosong(): void
    {
        $this->assertSame(
            "Baris terakhir.\n\n".self::FOOTER,
            $this->gateway()->withBrandFooter("Baris terakhir.\n\n")
        );
    }
}
