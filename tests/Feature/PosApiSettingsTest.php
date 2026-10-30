<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use App\Support\PosApiConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Admin page behaviour for POS API settings
 * (Super Admin → Pengaturan Sistem → tab Integrasi POS).
 */
class PosApiSettingsTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_saving_pos_tab_stores_settings_and_generates_token(): void
    {
        $admin = $this->admin();
        $url = $this->saveUrl($admin);

        $response = $this->actingAs($admin)
            ->from($url)
            ->post($url, [
                'pos_tab' => '1',
                'pos_api_enabled' => '1',
                'pos_api_header' => 'X-KASIR-TOKEN',
                'pos_api_allowed_ips' => ' 103.10.20.30 , 10.0.0.0/8 , ',
            ]);

        $response->assertRedirect($url);
        $response->assertSessionHas('success');

        $this->assertSame('1', SystemSetting::get('pos_api_enabled'));
        $this->assertSame('X-KASIR-TOKEN', SystemSetting::get('pos_api_header'));
        $this->assertSame('103.10.20.30,10.0.0.0/8', SystemSetting::get('pos_api_allowed_ips'));

        $rawKey = (string) SystemSetting::get('pos_api_key');
        $this->assertStringStartsWith('enc:v1:', $rawKey, 'Token harus disimpan terenkripsi.');
        $this->assertTrue(PosApiConfig::isEncrypted($rawKey));

        $plainKey = PosApiConfig::key();
        $this->assertSame(64, strlen($plainKey), 'Token POS harus 64 karakter hex.');
        $this->assertNotSame($rawKey, $plainKey);
        $this->assertNotNull(SystemSetting::get('pos_api_key_generated_at'));
    }

    public function test_header_falls_back_to_default_when_left_blank(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post($this->saveUrl($admin), [
                'pos_tab' => '1',
                'pos_api_enabled' => '1',
                'pos_api_header' => '   ',
                'pos_api_allowed_ips' => '',
            ]);

        $this->assertSame('X-ALIM-POS-KEY', SystemSetting::get('pos_api_header'));
        $this->assertSame('', SystemSetting::get('pos_api_allowed_ips'));
    }

    public function test_disabled_toggle_is_saved_as_zero(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post($this->saveUrl($admin), [
                'pos_tab' => '1',
                // pos_api_enabled sengaja tidak dikirim (checkbox unchecked)
                'pos_api_header' => 'X-ALIM-POS-KEY',
                'pos_api_allowed_ips' => '',
            ]);

        $this->assertSame('0', SystemSetting::get('pos_api_enabled'));
    }

    public function test_system_settings_are_forbidden_for_regular_users(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->withoutMiddleware()
            ->actingAs($user)
            ->get("/{$user->id}/sa/system-settings?tab=pos-api")
            ->assertForbidden();

        $this->withoutMiddleware()
            ->actingAs($user)
            ->post("/{$user->id}/sa/system-settings", ['pos_tab' => '1'])
            ->assertForbidden();
    }

    public function test_generate_button_rotates_existing_token(): void
    {
        $admin = $this->admin();
        $url = $this->saveUrl($admin);

        $this->actingAs($admin)->post($url, [
            'pos_tab' => '1',
            'pos_api_enabled' => '1',
            'pos_api_header' => 'X-ALIM-POS-KEY',
            'pos_api_allowed_ips' => '',
        ]);

        $firstKey = PosApiConfig::key();
        $this->assertSame(64, strlen($firstKey));

        $this->actingAs($admin)->post($url, [
            'pos_tab' => '1',
            'pos_api_enabled' => '1',
            'pos_api_header' => 'X-ALIM-POS-KEY',
            'pos_api_allowed_ips' => '',
            'regenerate_pos_key' => '1',
        ]);

        $rotatedKey = PosApiConfig::key();
        $this->assertSame(64, strlen($rotatedKey));
        $this->assertNotSame($firstKey, $rotatedKey, 'Token baru harus berbeda dari token lama.');
    }

    public function test_legacy_plaintext_token_is_encrypted_on_next_save(): void
    {
        $admin = $this->admin();
        $url = $this->saveUrl($admin);

        $legacyKey = str_repeat('a', 64);
        SystemSetting::set('pos_api_key', $legacyKey, 'pos_api');
        Cache::forget('system_settings_map');

        $this->actingAs($admin)->post($url, [
            'pos_tab' => '1',
            'pos_api_enabled' => '1',
            'pos_api_header' => 'X-ALIM-POS-KEY',
            'pos_api_allowed_ips' => '',
        ]);

        $raw = (string) SystemSetting::get('pos_api_key');
        $this->assertStringStartsWith('enc:v1:', $raw, 'Token lama harus otomatis dienkripsi.');
        $this->assertSame($legacyKey, PosApiConfig::key(), 'Nilai token tidak boleh berubah saat migrasi.');
    }

    public function test_pos_tab_renders_with_token_and_endpoint_list(): void
    {
        $admin = $this->admin();
        $url = $this->saveUrl($admin);

        $this->actingAs($admin)->post($url, [
            'pos_tab' => '1',
            'pos_api_enabled' => '1',
            'pos_api_header' => 'X-KASIR-TOKEN',
            'pos_api_allowed_ips' => '',
        ]);

        $savedKey = PosApiConfig::key();

        $response = $this->actingAs($admin)->get($url.'?tab=pos-api');

        $response->assertOk()
            ->assertSee('Integrasi POS')
            ->assertSee('Token API')
            ->assertSee('Generate Token Baru')
            ->assertSee('Pengaturan Akses')
            ->assertSee('Alamat API')
            ->assertSee('kepegawaian')
            ->assertSee('/api/pos/v1')
            ->assertSee('terenkripsi')
            ->assertSee($savedKey, false);
    }

    public function test_invalid_ip_allowlist_is_rejected_and_nothing_is_saved(): void
    {
        $admin = $this->admin();
        $url = $this->saveUrl($admin);

        $response = $this->actingAs($admin)
            ->from($url)
            ->post($url, [
                'pos_tab' => '1',
                'pos_api_enabled' => '1',
                'pos_api_header' => 'X-KASIR-TOKEN',
                'pos_api_allowed_ips' => '999.1.2.3, 10.0.0.0/33',
            ]);

        $response->assertRedirect($url);
        $response->assertSessionHas('error');

        $this->assertNull(SystemSetting::get('pos_api_allowed_ips'));
        $this->assertNull(SystemSetting::get('pos_api_header'));
        $this->assertNull(SystemSetting::get('pos_api_key'));
    }
}
