<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Models\TwoFactorRecoveryCode;
use App\Models\User;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProfileSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('system_settings_map');
    }

    public function test_security_page_can_be_rendered_for_owner(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('user.profile.security', ['userId' => $user->id]))
            ->assertOk()
            ->assertSee('Keamanan Akun')
            ->assertSee('Sesi Web Aktif');
    }

    public function test_login_history_is_shown(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'LOGIN_SUCCESS',
            'table_name' => 'users',
            'record_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Browser',
        ]);

        $this->actingAs($user)
            ->get(route('user.profile.security', ['userId' => $user->id]))
            ->assertOk()
            ->assertSee('Riwayat Login Terakhir')
            ->assertSee('Login Berhasil');
    }

    public function test_recovery_codes_can_be_generated_when_two_factor_is_enabled(): void
    {
        SystemSetting::set('security_2fa_enabled', '1', 'security');
        Cache::forget('system_settings_map');

        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->post(route('user.profile.security.recovery-codes', ['userId' => $user->id]))
            ->assertRedirect()
            ->assertSessionHas('recovery_codes');

        $this->assertSame(8, TwoFactorRecoveryCode::where('user_id', $user->id)->count());
        $this->assertSame(0, TwoFactorRecoveryCode::where('user_id', $user->id)->whereNotNull('used_at')->count());
    }

    public function test_totp_can_be_setup_and_confirmed(): void
    {
        SystemSetting::set('security_2fa_enabled', '1', 'security');
        Cache::forget('system_settings_map');

        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->post(route('user.profile.security.totp.setup', ['userId' => $user->id]))
            ->assertRedirect();

        $user->refresh();
        $secret = $user->getTotpSecret();

        $this->assertNotNull($secret);
        $this->assertNull($user->two_factor_confirmed_at);

        $this->actingAs($user)
            ->get(route('user.profile.security', ['userId' => $user->id]))
            ->assertOk();

        $this->actingAs($user)
            ->post(route('user.profile.security.totp.confirm', ['userId' => $user->id]), [
                'totp_code' => TotpService::currentCode($secret),
            ])
            ->assertRedirect();

        $this->assertTrue($user->refresh()->hasTotpEnabled());
    }

    public function test_other_web_session_can_be_revoked(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        DB::table('sessions')->insert([
            'id' => 'other-web-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => '',
            'last_activity' => time(),
        ]);

        $this->actingAs($user)
            ->delete(route('user.profile.security.sessions.revoke', [
                'userId' => $user->id,
                'sessionId' => 'other-web-session',
            ]))
            ->assertRedirect();

        $this->assertDatabaseMissing('sessions', ['id' => 'other-web-session']);
    }

    public function test_mobile_session_can_be_revoked(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $token = $user->createToken('mobile-test')->accessToken;

        $this->actingAs($user)
            ->delete(route('user.profile.security.mobile-sessions.revoke', [
                'userId' => $user->id,
                'tokenId' => $token->id,
            ]))
            ->assertRedirect();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
    }

    public function test_other_users_security_page_is_forbidden(): void
    {
        $owner = User::factory()->create(['is_active' => true]);
        $other = User::factory()->create(['is_active' => true]);

        $this->actingAs($other)
            ->get(route('user.profile.security', ['userId' => $owner->id]))
            ->assertForbidden();
    }
}
