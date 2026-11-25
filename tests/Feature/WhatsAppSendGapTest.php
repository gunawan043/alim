<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Services\Notifications\WhatsAppGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Jeda kirim melindungi nomor WhatsApp dari pemblokiran akibat pengiriman
 * massal. Diuji tanpa benar-benar menunggu: waktu digeser lewat cache.
 */
class WhatsAppSendGapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        SystemSetting::set('wa_enabled', '1', 'whatsapp');
        SystemSetting::set('wa_provider', 'log', 'whatsapp');
        SystemSetting::set('wa_send_gap', '0', 'whatsapp');
        SystemSetting::set('wa_send_burst', '10', 'whatsapp');
        SystemSetting::set('wa_send_window', '60', 'whatsapp');

        Cache::forget('system_settings_map');
    }

    private function gateway(): WhatsAppGatewayService
    {
        // Instance baru sengaja dibuat agar terbukti hitungan dibagi lewat
        // cache, bukan disimpan di properti objek.
        return new WhatsAppGatewayService;
    }

    /** Panggil respectSendGap langsung, tanpa lewat provider. */
    private function tick(WhatsAppGatewayService $gateway): float
    {
        $method = new \ReflectionMethod($gateway, 'respectSendGap');
        $method->setAccessible(true);

        $start = microtime(true);
        $method->invoke($gateway);

        return microtime(true) - $start;
    }

    public function test_jeda_nol_tidak_membatasi_apa_pun(): void
    {
        $gateway = $this->gateway();
        $start = microtime(true);

        for ($i = 0; $i < 30; $i++) {
            $this->tick($gateway);
        }

        $this->assertLessThan(2.0, microtime(true) - $start, 'Jeda 0 harus benar-benar tanpa menunggu.');
        $this->assertNull(
            Cache::get('wa:send-gap:count'),
            'Saat jeda 0, hitungan tidak boleh dibuat sama sekali.'
        );
    }

    public function test_jeda_aktif_hanya_setelah_kuota_awal_habis(): void
    {
        SystemSetting::set('wa_send_gap', '5', 'whatsapp');
        SystemSetting::set('wa_send_burst', '2', 'whatsapp');
        Cache::forget('system_settings_map');

        $gateway = $this->gateway();

        $this->tick($gateway);
        $this->assertSame(1, (int) Cache::get('wa:send-gap:count'));
        $this->assertNotNull(Cache::get('wa:send-gap:last'));

        // Kiriman kedua masih dalam kuota awal: tidak menunggu.
        $this->assertLessThan(1.0, $this->tick($gateway));

        // Kiriman ketiga melewati kuota: harus menunggu 5 detik.
        $waited = $this->tick($gateway);

        $this->assertGreaterThanOrEqual(4.0, $waited, 'Pesan ke-3 harus menunggu jeda penuh.');
        $this->assertSame(3, (int) Cache::get('wa:send-gap:count'));
    }

    public function test_jeda_dibatasi_agar_tidak_diisi_berlebihan(): void
    {
        SystemSetting::set('wa_send_gap', '9999', 'whatsapp');
        SystemSetting::set('wa_send_burst', '1', 'whatsapp');
        Cache::forget('system_settings_map');

        $gateway = $this->gateway();

        $this->tick($gateway);
        Cache::put('wa:send-gap:count', 5, 600);
        Cache::put('wa:send-gap:last', time() - 130, 3600);

        // Jeda 9999 dipotong ke 120; kiriman terakhir sudah 130 detik lalu,
        // jadi tidak boleh ada penantian panjang.
        $this->assertLessThan(2.0, $this->tick($gateway));
    }

    public function test_hitungan_dipakai_bersama_antar_instance(): void
    {
        SystemSetting::set('wa_send_gap', '3', 'whatsapp');
        SystemSetting::set('wa_send_burst', '1', 'whatsapp');
        Cache::forget('system_settings_map');

        $this->tick(new WhatsAppGatewayService);
        Cache::put('wa:send-gap:last', time() - 10, 3600);

        // Instance berbeda tetap melihat hitungan yang sama dan langsung
        // menunggu sisa jeda (3 detik dikurangi 10 detik lalu = 0).
        $this->assertLessThan(1.0, $this->tick(new WhatsAppGatewayService));
        $this->assertSame(2, (int) Cache::get('wa:send-gap:count'));
    }

    public function test_rentetan_baru_setelah_idle_kembali_tanpa_jeda(): void
    {
        SystemSetting::set('wa_send_gap', '5', 'whatsapp');
        SystemSetting::set('wa_send_burst', '1', 'whatsapp');
        SystemSetting::set('wa_send_window', '60', 'whatsapp');
        Cache::forget('system_settings_map');

        $gateway = $this->gateway();
        $this->tick($gateway);

        // Broadcast kedua terjadi setelah idle lebih lama dari periode: kuota
        // tanpa jeda pulih, jadi tidak ikut menunggu sisa broadcast pertama.
        Cache::put('wa:send-gap:last', time() - 61, 3600);

        $this->assertLessThan(1.0, $this->tick($gateway));
        $this->assertSame(1, (int) Cache::get('wa:send-gap:count'));
    }
}
