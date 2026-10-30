<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\SystemSetting;
use App\Models\User;
use App\Support\ApnsConfig;
use App\Support\FcmConfig;
use App\Support\WebPushConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PushSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function admin(): User
    {
        return User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);
    }

    private function url(User $admin): string
    {
        return "/{$admin->id}/sa/system-settings";
    }

    private function fcmAccount(): array
    {
        $key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 2048,
        ]);
        openssl_pkey_export($key, $privateKey);

        return [
            'project_id' => 'test-project',
            'client_email' => 'firebase-adminsdk@test-project.iam.gserviceaccount.com',
            'private_key' => $privateKey,
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ];
    }

    private function apnsKey(): string
    {
        $key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);
        openssl_pkey_export($key, $privateKey);

        return $privateKey;
    }

    public function test_push_tab_renders_without_exposing_secrets(): void
    {
        $admin = $this->admin();

        FcmConfig::storeServiceAccountJson(json_encode($this->fcmAccount()));
        ApnsConfig::storePrivateKey($this->apnsKey());

        $this->actingAs($admin)
            ->get($this->url($admin).'?tab=push')
            ->assertOk()
            ->assertSee('FCM — Android')
            ->assertSee('APNs — iOS')
            ->assertSee('Web Push — Browser')
            ->assertSee('Service Account JSON')
            ->assertDontSee('firebase-adminsdk@test-project.iam.gserviceaccount.com')
            ->assertDontSee('BEGIN EC PRIVATE KEY');
    }

    public function test_vapid_keys_can_be_generated_and_private_key_is_encrypted(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post($this->url($admin), [
                'web_push_vapid_subject' => 'mailto:admin@alim.test',
                'generate_web_push_keys' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $rawPrivate = (string) SystemSetting::get('web_push_vapid_private_key');

        $this->assertTrue(WebPushConfig::isConfigured());
        $this->assertNotEmpty(WebPushConfig::vapidPublicKey());
        $this->assertStringStartsWith('enc:v1:', $rawPrivate);
        $this->assertSame('mailto:admin@alim.test', WebPushConfig::subject());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'WEB_PUSH_VAPID_KEYS_GENERATED',
            'table_name' => 'system_settings',
        ]);
    }

    public function test_invalid_vapid_subject_is_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post($this->url($admin), [
                'web_push_vapid_subject' => 'not-a-mailto-or-url',
            ])
            ->assertSessionHasErrors('web_push_vapid_subject');
    }

    public function test_push_settings_are_saved_and_secrets_are_encrypted(): void
    {
        $admin = $this->admin();
        $account = $this->fcmAccount();
        $apnsKey = $this->apnsKey();

        $this->actingAs($admin)
            ->post($this->url($admin), [
                'fcm_project_id' => 'test-project',
                'fcm_service_account_json' => json_encode($account),
                'apns_key_id' => 'KEYID12345',
                'apns_team_id' => 'TEAM123456',
                'apns_bundle_id' => 'com.alim.app',
                'apns_environment' => 'sandbox',
                'apns_private_key' => $apnsKey,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $rawFcm = (string) SystemSetting::get('fcm_service_account_json');
        $rawApns = (string) SystemSetting::get('apns_private_key');

        $this->assertStringStartsWith('enc:v1:', $rawFcm);
        $this->assertStringStartsWith('enc:v1:', $rawApns);
        $this->assertNotSame(json_encode($account), $rawFcm);
        $this->assertNotSame($apnsKey, $rawApns);
        $this->assertTrue(FcmConfig::isConfigured());
        $this->assertTrue(ApnsConfig::isConfigured());
        $this->assertSame('sandbox', ApnsConfig::environment());
        $this->assertSame('com.alim.app', ApnsConfig::bundleId());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'PUSH_SETTINGS_UPDATED',
            'table_name' => 'system_settings',
        ]);
    }

    public function test_invalid_service_account_is_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post($this->url($admin), [
                'fcm_project_id' => 'test-project',
                'fcm_service_account_json' => '{"client_email":""}',
            ])
            ->assertSessionHasErrors('fcm_service_account_json');

        $this->assertFalse(FcmConfig::hasStoredServiceAccount());
    }

    public function test_blank_secrets_are_preserved_and_clear_checkboxes_remove_them(): void
    {
        $admin = $this->admin();

        FcmConfig::storeProjectId('test-project');
        FcmConfig::storeServiceAccountJson(json_encode($this->fcmAccount()));
        ApnsConfig::storePrivateKey($this->apnsKey());

        $this->actingAs($admin)
            ->post($this->url($admin), [
                'fcm_project_id' => 'test-project',
                'fcm_service_account_json' => '',
                'apns_key_id' => 'KEYID12345',
                'apns_team_id' => 'TEAM123456',
                'apns_bundle_id' => 'com.alim.app',
                'apns_private_key' => '',
            ])
            ->assertRedirect();

        $this->assertTrue(FcmConfig::hasStoredServiceAccount());
        $this->assertTrue(ApnsConfig::hasStoredPrivateKey());

        $this->actingAs($admin)
            ->post($this->url($admin), [
                'fcm_service_account_clear' => '1',
                'apns_private_key_clear' => '1',
            ])
            ->assertRedirect();

        $this->assertFalse(FcmConfig::hasStoredServiceAccount());
        $this->assertFalse(ApnsConfig::hasStoredPrivateKey());
    }
}
