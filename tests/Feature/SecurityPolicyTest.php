<?php

namespace Tests\Feature;

use App\Models\PasswordOtp;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SecurityPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('system_settings_map');
    }

    protected function tearDown(): void
    {
        Cache::forget('system_settings_map');

        parent::tearDown();
    }

    public function test_login_lockout_uses_configured_attempts_and_duration(): void
    {
        Mail::fake();
        $this->setSetting('max_login_attempts', '3');
        $this->setSetting('lockout_duration_minutes', '5');

        $user = User::factory()->create(['is_active' => true]);

        $this->post('/login', ['identity' => $user->email, 'password' => 'wrong-1']);
        $this->post('/login', ['identity' => $user->email, 'password' => 'wrong-2']);
        $this->post('/login', ['identity' => $user->email, 'password' => 'wrong-3'])
            ->assertSessionHasErrors('account_locked');

        $user->refresh();

        $this->assertSame(3, $user->failed_login_attempts);
        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->locked_until->between(now()->addMinutes(4), now()->addMinutes(6)));
    }

    public function test_otp_expiry_uses_security_setting(): void
    {
        Mail::fake();
        $this->setSetting('otp_expiry_minutes', '2');

        $user = User::factory()->create(['is_active' => true]);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.otp.form'));

        $otp = PasswordOtp::where('user_id', $user->id)->firstOrFail();

        $this->assertTrue($otp->expires_at->between(
            now()->addMinutes(1)->addSeconds(50),
            now()->addMinutes(2)->addSeconds(10),
        ));
    }

    public function test_session_lifetime_middleware_applies_setting(): void
    {
        $this->setSetting('session_lifetime_minutes', '7');

        $this->get('/login')->assertOk();

        $this->assertSame(7, (int) config('session.lifetime'));
    }

    public function test_admin_ip_allowlist_blocks_other_ips(): void
    {
        $this->setSetting('security_admin_ip_allowlist', '10.10.10.10');

        $admin = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);

        $this->actingAs($admin)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('user.sa.system-settings.index', ['userId' => $admin->id]))
            ->assertForbidden();

        $this->actingAs($admin)
            ->withServerVariables(['REMOTE_ADDR' => '10.10.10.10'])
            ->get(route('user.sa.system-settings.index', ['userId' => $admin->id]))
            ->assertOk();
    }

    public function test_password_minimum_length_is_enforced_from_setting(): void
    {
        $this->setSetting('password_min_length', '12');

        $user = User::factory()->create(['password' => bcrypt('password')]);

        $this->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'short-pass1',
                'password_confirmation' => 'short-pass1',
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'password');

        $this->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'valid-password-123',
                'password_confirmation' => 'valid-password-123',
            ])
            ->assertSessionHasNoErrors();
    }

    private function setSetting(string $key, string $value): void
    {
        SystemSetting::set($key, $value, 'security');
        Cache::forget('system_settings_map');
    }
}
