<?php

namespace Tests\Feature\Auth;

use App\Mail\LoginOtpMail;
use App\Models\LoginOtp;
use App\Models\SystemSetting;
use App\Models\TwoFactorRecoveryCode;
use App\Models\User;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Cache::forget('system_settings_map');

        parent::tearDown();
    }

    public function test_two_factor_is_disabled_by_default(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);

        $this->post('/login', [
            'identity' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('system.dashboard'));

        $this->assertAuthenticatedAs($user);
        Mail::assertNotSent(LoginOtpMail::class);
    }

    public function test_enabled_two_factor_requires_email_otp_before_login(): void
    {
        Mail::fake();
        $this->enableTwoFactor();

        $user = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);

        $response = $this->post('/login', [
            'identity' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();

        $otp = null;
        Mail::assertSent(LoginOtpMail::class, function (LoginOtpMail $mail) use ($user, &$otp) {
            $this->assertTrue($mail->hasTo($user->email));
            $otp = $mail->otp;

            return true;
        });

        $this->assertNotNull($otp);
        $this->assertDatabaseHas('login_otps', ['user_id' => $user->id]);

        $this->post(route('two-factor.verify'), ['otp' => $otp])
            ->assertRedirect(route('system.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('login_otps', 0);
    }

    public function test_wrong_two_factor_code_does_not_log_in(): void
    {
        Mail::fake();
        $this->enableTwoFactor();

        $user = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);

        $this->post('/login', [
            'identity' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.challenge'));

        LoginOtp::where('user_id', $user->id)->update([
            'otp_hash' => Hash::make('123456'),
        ]);

        $this->from(route('two-factor.challenge'))
            ->post(route('two-factor.verify'), ['otp' => '000000'])
            ->assertRedirect(route('two-factor.challenge'))
            ->assertSessionHasErrors('otp');

        $this->assertGuest();
        $this->assertSame(1, LoginOtp::where('user_id', $user->id)->firstOrFail()->attempts);
    }

    public function test_recovery_code_can_complete_login_once(): void
    {
        Mail::fake();
        $this->enableTwoFactor();

        $user = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);

        $codes = TwoFactorRecoveryCode::generateFor($user);

        $this->post('/login', [
            'identity' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.challenge'));

        $this->post(route('two-factor.recovery'), ['recovery_code' => $codes[0]])
            ->assertRedirect(route('system.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertFalse(TwoFactorRecoveryCode::verifyFor($user, $codes[0]));
    }

    public function test_totp_authenticator_can_complete_login(): void
    {
        Mail::fake();
        $this->enableTwoFactor();

        $user = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);
        $secret = TotpService::generateSecret();
        $user->setTotpSecret($secret, true);

        $this->post('/login', [
            'identity' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.challenge'));

        $this->post(route('two-factor.totp'), [
            'totp_code' => TotpService::currentCode($secret),
        ])->assertRedirect(route('system.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_two_factor_scope_super_admin_skips_regular_users(): void
    {
        Mail::fake();
        $this->enableTwoFactor('super_admin');

        $user = User::factory()->create(['is_active' => true]);

        $this->post('/login', [
            'identity' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('user.dashboard', ['userId' => $user->id]));

        $this->assertAuthenticatedAs($user);
        Mail::assertNotSent(LoginOtpMail::class);
    }

    private function enableTwoFactor(string $scope = 'all'): void
    {
        SystemSetting::set('security_2fa_enabled', '1', 'security');
        SystemSetting::set('security_2fa_scope', $scope, 'security');
        Cache::forget('system_settings_map');
    }
}
