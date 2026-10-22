<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\SystemSetting;
use App\Models\User;
use App\Services\SystemMailSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class EmailSettingsTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_email_tab_renders(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get($this->url($admin).'?tab=email')
            ->assertOk()
            ->assertSee('Konfigurasi Email Sistem')
            ->assertSee('Email Tujuan Uji Coba')
            ->assertSee('URL Logo Email')
            ->assertSee('Password SMTP');
    }

    public function test_email_settings_are_saved_and_password_is_encrypted(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post($this->url($admin), [
                'mail_settings' => '1',
                'mail_mailer' => 'smtp',
                'mail_host' => 'smtp.mail.test',
                'mail_port' => '587',
                'mail_username' => 'noreply@mail.test',
                'mail_password' => 'super-secret-smtp',
                'mail_encryption' => 'tls',
                'mail_from_address' => 'noreply@mail.test',
                'mail_from_name' => 'ALIM Test',
                'mail_logo_url' => 'https://cdn.example.test/alim-logo.png',
            ])
            ->assertRedirect();

        $encrypted = (string) SystemSetting::get('mail_password');

        $this->assertNotSame('super-secret-smtp', $encrypted);
        $this->assertSame('super-secret-smtp', Crypt::decryptString($encrypted));
        $this->assertSame('smtp.mail.test', SystemSetting::get('mail_host'));
        $this->assertSame('587', SystemSetting::get('mail_port'));
        $this->assertSame('tls', SystemSetting::get('mail_encryption'));
        $this->assertSame('noreply@mail.test', SystemSetting::get('mail_from_address'));
        $this->assertSame('https://cdn.example.test/alim-logo.png', SystemSetting::get('mail_logo_url'));

        app(SystemMailSettingsService::class)->apply();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.mail.test', config('mail.mailers.smtp.host'));
        $this->assertSame(587, config('mail.mailers.smtp.port'));
        $this->assertSame('super-secret-smtp', config('mail.mailers.smtp.password'));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'EMAIL_SETTINGS_UPDATED',
            'table_name' => 'system_settings',
        ]);
    }

    public function test_blank_password_does_not_overwrite_saved_password(): void
    {
        $admin = $this->admin();

        SystemSetting::set('mail_password', Crypt::encryptString('keep-me'), 'mail');

        $this->actingAs($admin)
            ->post($this->url($admin), [
                'mail_settings' => '1',
                'mail_mailer' => 'smtp',
                'mail_host' => 'smtp.mail.test',
                'mail_port' => '587',
                'mail_password' => '',
            ])
            ->assertRedirect();

        $this->assertSame('keep-me', Crypt::decryptString((string) SystemSetting::get('mail_password')));
    }
}
