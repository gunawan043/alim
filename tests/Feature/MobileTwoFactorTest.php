<?php

namespace Tests\Feature;

use App\Mail\LoginOtpMail;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MobileTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('system_settings_map');
    }

    public function test_mobile_login_requires_email_otp_when_enabled(): void
    {
        Mail::fake();
        $this->enableMobileTwoFactor();

        $user = User::factory()->create([
            'is_active' => true,
            'is_wali' => true,
        ]);

        $login = $this->postJson('/api/mobile/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();

        $login->assertJson([
            'success' => true,
            'two_factor_required' => true,
        ]);

        $challenge = $login->json('challenge_token');
        $this->assertNotNull($challenge);

        $otp = null;
        Mail::assertSent(LoginOtpMail::class, function (LoginOtpMail $mail) use ($user, &$otp) {
            $this->assertTrue($mail->hasTo($user->email));
            $otp = $mail->otp;

            return true;
        });

        $this->postJson('/api/mobile/v1/auth/two-factor/verify', [
            'challenge_token' => $challenge,
            'code' => $otp,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['access_token', 'user']]);

        $this->assertDatabaseCount('login_otps', 0);
    }

    public function test_mobile_login_accepts_totp_authenticator(): void
    {
        Mail::fake();
        $this->enableMobileTwoFactor();

        $user = User::factory()->create([
            'is_active' => true,
            'is_wali' => true,
        ]);
        $secret = TotpService::generateSecret();
        $user->setTotpSecret($secret, true);

        $challenge = $this->postJson('/api/mobile/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()->json('challenge_token');

        $this->postJson('/api/mobile/v1/auth/two-factor/verify', [
            'challenge_token' => $challenge,
            'totp_code' => TotpService::currentCode($secret),
        ])->assertOk()->assertJsonPath('success', true);
    }

    private function enableMobileTwoFactor(): void
    {
        SystemSetting::set('security_2fa_mobile_enabled', '1', 'security');
        SystemSetting::set('security_2fa_scope', 'all', 'security');
        Cache::forget('system_settings_map');
    }
}
