<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SecuritySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_tab_renders_two_factor_and_notification_controls(): void
    {
        $admin = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get("/{$admin->id}/sa/system-settings?tab=security")
            ->assertOk()
            ->assertSee('Two-Factor Authentication (2FA)')
            ->assertSee('Notifikasi Keamanan Akun')
            ->assertSee('security_2fa_enabled');
    }

    public function test_security_tab_saves_notification_and_two_factor_toggles(): void
    {
        $admin = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);

        $url = "/{$admin->id}/sa/system-settings";

        $this->actingAs($admin)
            ->post($url, [
                'security_settings' => '1',
                'security_2fa_enabled' => '1',
                'security_2fa_scope' => 'super_admin',
                'security_notify_login' => '1',
                'security_notify_password_forgot' => '1',
                'security_notify_password_reset' => '1',
                'otp_expiry_minutes' => '2',
                'password_min_length' => '12',
                'max_login_attempts' => '3',
                'lockout_duration_minutes' => '5',
                'session_lifetime_minutes' => '7',
                // password_changed sengaja tidak dikirim (checkbox unchecked)
            ])
            ->assertRedirect();

        $this->assertSame('1', SystemSetting::get('security_2fa_enabled'));
        $this->assertSame('super_admin', SystemSetting::get('security_2fa_scope'));
        $this->assertSame('1', SystemSetting::get('security_notify_login'));
        $this->assertSame('0', SystemSetting::get('security_notify_password_changed'));
        $this->assertSame('2', SystemSetting::get('otp_expiry_minutes'));
        $this->assertSame('12', SystemSetting::get('password_min_length'));
        $this->assertSame('3', SystemSetting::get('max_login_attempts'));
        $this->assertSame('5', SystemSetting::get('lockout_duration_minutes'));
        $this->assertSame('7', SystemSetting::get('session_lifetime_minutes'));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'SECURITY_SETTINGS_UPDATED',
            'table_name' => 'system_settings',
        ]);

        $this->actingAs($admin)
            ->post($url, ['security_settings' => '1'])
            ->assertRedirect();

        $this->assertSame('0', SystemSetting::get('security_2fa_enabled'));
        $this->assertSame('0', SystemSetting::get('security_notify_login'));
    }

    public function test_test_email_endpoint_reports_success(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('user.sa.system-settings.test-email', ['userId' => $admin->id]), [
                'test_email_to' => 'tujuan@example.test',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Email tes dikirim ke tujuan@example.test. Periksa inbox/spam. Jika tidak masuk, periksa konfigurasi MAIL_*.');
    }

    public function test_test_email_saves_unsaved_smtp_values_before_sending(): void
    {
        Mail::fake();
        $admin = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('user.sa.system-settings.test-email', ['userId' => $admin->id]), [
                'mail_settings' => '1',
                'mail_mailer' => 'smtp',
                'mail_host' => 'smtp.baru.test',
                'mail_port' => '587',
                'mail_username' => 'noreply@baru.test',
                'mail_password' => 'secret',
                'mail_encryption' => 'tls',
                'mail_from_address' => 'noreply@baru.test',
                'mail_from_name' => 'ALIM Baru',
                'test_email_to' => 'tujuan@baru.test',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Email tes dikirim ke tujuan@baru.test. Periksa inbox/spam. Jika tidak masuk, periksa konfigurasi MAIL_*.');

        $this->assertSame('smtp.baru.test', SystemSetting::get('mail_host'));
        $this->assertSame('587', SystemSetting::get('mail_port'));
    }

    public function test_saving_security_tab_does_not_wipe_notification_matrix(): void
    {
        $admin = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);

        SystemSetting::set('notification_channel_matrix', json_encode([
            'login' => ['system' => true, 'email' => false, 'wa' => true],
        ]), 'notifications');
        Cache::forget('system_settings_map');

        $this->actingAs($admin)
            ->post("/{$admin->id}/sa/system-settings", ['security_settings' => '1'])
            ->assertRedirect();

        $matrix = json_decode((string) SystemSetting::get('notification_channel_matrix'), true);

        $this->assertFalse($matrix['login']['email']);
        $this->assertTrue($matrix['login']['wa']);
    }
}
