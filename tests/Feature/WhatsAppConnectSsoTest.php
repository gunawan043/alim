<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ConnectSsoHandoff;
use App\Models\Permission;
use App\Models\User;
use App\Services\Connect\ConnectHandoffService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WhatsAppConnectSsoTest extends TestCase
{
    use RefreshDatabase;

    private const SSO_KEY = 'test-connect-sso-key';

    private const CSRF_TOKEN = 'test-csrf-token';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.connect_sso.api_key' => self::SSO_KEY,
            'services.connect_sso.header' => 'X-ALIM-CONNECT-SSO-KEY',
            'services.connect_sso.scopes' => ['sso:exchange'],
            'services.connect_sso.handoff_url' => 'https://connect.test/sso/handoff',
            'services.connect_sso.frame_origin' => 'https://connect.test',
            'services.connect_sso.handoff_ttl' => 60,
            'services.connect_sso.allowed_ips' => [],
            'services.connect_sso.require_https' => false,
        ]);
    }

    private function userWithPermission(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $permission = Permission::firstOrCreate([
            'name' => 'whatsapp_connect_view',
            'guard_name' => 'web',
        ]);
        $user->givePermissionTo($permission);

        return $user->fresh();
    }

    private function issueHandoff(User $user): array
    {
        return $this->actingAs($user)
            ->withSession(['_token' => self::CSRF_TOKEN])
            ->withHeader('X-CSRF-TOKEN', self::CSRF_TOKEN)
            ->postJson(route('user.whatsapp-connect.handoff', ['userId' => $user->id]))
            ->assertOk()
            ->json('data');
    }

    private function exchange(array $payload, array $headers = [])
    {
        return $this->postJson('/api/connect/v1/sso/exchange', $payload, array_merge([
            'Accept' => 'application/json',
            'X-ALIM-CONNECT-SSO-KEY' => self::SSO_KEY,
        ], $headers));
    }

    private function exchangePayload(string $code): array
    {
        return [
            'code' => $code,
            'audience' => 'connect',
            'scope' => 'whatsapp.embed',
        ];
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $user = $this->userWithPermission();

        $this->get(route('user.whatsapp-connect.index', ['userId' => $user->id]))
            ->assertRedirect('/login');
    }

    public function test_user_without_permission_gets_403(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('user.whatsapp-connect.index', ['userId' => $user->id]))
            ->assertStatus(403);
    }

    public function test_user_with_permission_can_open_page_and_csp_is_scoped(): void
    {
        $user = $this->userWithPermission();

        $response = $this->actingAs($user)
            ->get(route('user.whatsapp-connect.index', ['userId' => $user->id]));

        $response->assertOk()
            ->assertSee('WhatsApp Connect')
            ->assertSee('Buka WhatsApp Connect')
            ->assertDontSee('id="connect-frame"', false);
        // URL diterbitkan sebagai literal JSON di skrip, jadi "/" tampil ter-escape.
        $this->assertStringContainsString(
            json_encode('https://connect.test/sso/handoff'),
            $response->getContent()
        );

        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("frame-src 'self' https://connect.test", $csp);
        $this->assertStringNotContainsString('*', $csp);
        $this->assertStringNotContainsString(' http://', $csp);
    }

    public function test_page_reports_which_setting_is_missing_instead_of_a_generic_hint(): void
    {
        $user = $this->userWithPermission();
        config(['services.connect_sso.frame_origin' => '']);

        $response = $this->actingAs($user)
            ->get(route('user.whatsapp-connect.index', ['userId' => $user->id]));

        $response->assertOk()
            ->assertSee('CONNECT_SSO_FRAME_ORIGIN belum berisi origin Connect', false)
            ->assertDontSee('id="connect-frame"', false)
            ->assertDontSee('connect-handoff-form', false);

        $this->assertStringNotContainsString('frame-src', (string) $response->headers->get('Content-Security-Policy'));
    }

    public function test_plain_http_origin_is_refused_outside_development(): void
    {
        $user = $this->userWithPermission();
        config(['services.connect_sso.frame_origin' => 'http://connect.test']);

        app()->detectEnvironment(fn () => 'production');

        try {
            $response = $this->actingAs($user)
                ->get(route('user.whatsapp-connect.index', ['userId' => $user->id]));

            $response->assertOk()
                ->assertSee('CONNECT_SSO_FRAME_ORIGIN belum berisi origin Connect', false);
            $this->assertStringNotContainsString('http://connect.test', (string) $response->headers->get('Content-Security-Policy'));
        } finally {
            app()->detectEnvironment(fn () => 'testing');
        }
    }

    /**
     * Produksi tidak punya jalur .env yang aman untuk diaktifkan operator, jadi
     * nilai alamat dibaca dari system_settings lebih dulu. Test ini memastikan
     * baris database benar-benar mengalahkan config/services.php.
     */
    public function test_database_settings_override_config_for_handoff_url_and_frame_origin(): void
    {
        config([
            'services.connect_sso.handoff_url' => 'https://from-env.test/sso/handoff',
            'services.connect_sso.frame_origin' => 'https://from-env.test',
        ]);

        \App\Models\SystemSetting::set('connect_sso_handoff_url', 'https://from-db.test/sso/handoff', 'integration');
        \App\Models\SystemSetting::set('connect_sso_frame_origin', 'https://from-db.test', 'integration');

        $user = $this->userWithPermission();
        $data = $this->issueHandoff($user);

        $this->assertSame('https://from-db.test/sso/handoff', $data['connect_sso_url']);

        $response = $this->actingAs($user)
            ->get(route('user.whatsapp-connect.index', ['userId' => $user->id]));

        $this->assertStringContainsString(
            "frame-src 'self' https://from-db.test",
            (string) $response->headers->get('Content-Security-Policy')
        );
    }

    public function test_stored_frame_origin_with_path_is_reduced_to_an_origin(): void
    {
        \App\Support\ConnectSsoConfig::storeFrameOrigin('https://connect.test/dashboard?x=1');

        $this->assertSame('https://connect.test', \App\Support\ConnectSsoConfig::frameOrigin());
    }

    public function test_sso_api_key_can_come_from_database_for_the_exchange(): void
    {
        \App\Support\ConnectSsoConfig::storeKey('stored-sso-key-123');

        $data = $this->issueHandoff($this->userWithPermission());

        $this->exchange($this->exchangePayload($data['code']), [
            'X-ALIM-CONNECT-SSO-KEY' => 'stored-sso-key-123',
        ])->assertOk();

        // Key dari config/services.php tidak lagi diterima begitu database punya key.
        $this->exchange($this->exchangePayload('kode-lain'))->assertStatus(401);
    }

    public function test_user_cannot_open_another_users_handoff_page(): void
    {
        $user = $this->userWithPermission();
        $other = $this->userWithPermission();

        $this->actingAs($user)
            ->get(route('user.whatsapp-connect.index', ['userId' => $other->id]))
            ->assertStatus(403);
    }

    public function test_handoff_issues_hashed_code_and_audit_without_raw_code(): void
    {
        $user = $this->userWithPermission();
        $data = $this->issueHandoff($user);

        $this->assertNotEmpty($data['code']);
        $this->assertSame('connect', $data['audience']);
        $this->assertSame('whatsapp.embed', $data['scope']);

        $handoff = ConnectSsoHandoff::firstOrFail();
        $this->assertSame(hash('sha256', $data['code']), $handoff->code_hash);
        $this->assertNotSame($data['code'], $handoff->code_hash);
        $this->assertTrue($handoff->expires_at->isFuture());

        $audit = AuditLog::where('action', 'WHATSAPP_CONNECT_HANDOFF_ISSUED')->firstOrFail();
        $this->assertStringNotContainsString($data['code'], json_encode($audit->metadata));
    }

    public function test_exchange_returns_minimum_identity_and_marks_code_used(): void
    {
        $user = $this->userWithPermission();
        $data = $this->issueHandoff($user);

        $response = $this->exchange($this->exchangePayload($data['code']));

        $response->assertOk()
            ->assertJsonPath('data.core_user_id', $user->id)
            ->assertJsonPath('data.name', $user->name)
            ->assertJsonPath('data.audience', 'connect')
            ->assertJsonPath('data.scope', 'whatsapp.embed');

        $body = $response->json('data');
        $this->assertArrayNotHasKey('roles', $body);
        $this->assertArrayNotHasKey('permissions', $body);
        $this->assertArrayNotHasKey('units', $body);

        $this->assertNotNull(ConnectSsoHandoff::firstOrFail()->used_at);
    }

    public function test_code_is_one_time_only(): void
    {
        $user = $this->userWithPermission();
        $data = $this->issueHandoff($user);

        $this->exchange($this->exchangePayload($data['code']))->assertOk();

        $second = $this->exchange($this->exchangePayload($data['code']));
        $second->assertStatus(409)
            ->assertJsonPath('errors.code', 'code_already_used');
    }

    public function test_expired_code_is_rejected(): void
    {
        $user = $this->userWithPermission();
        $data = $this->issueHandoff($user);

        ConnectSsoHandoff::query()->update(['expires_at' => now()->subMinute()]);

        $this->exchange($this->exchangePayload($data['code']))
            ->assertStatus(410)
            ->assertJsonPath('errors.code', 'code_expired');
    }

    public function test_wrong_audience_and_scope_are_rejected(): void
    {
        $user = $this->userWithPermission();
        $data = $this->issueHandoff($user);

        $this->exchange([
            'code' => $data['code'],
            'audience' => 'other',
            'scope' => 'whatsapp.embed',
        ])->assertStatus(403)->assertJsonPath('errors.code', 'audience_mismatch');

        $this->exchange([
            'code' => $data['code'],
            'audience' => 'connect',
            'scope' => 'other',
        ])->assertStatus(403)->assertJsonPath('errors.code', 'scope_mismatch');
    }

    public function test_manipulated_code_is_not_found(): void
    {
        $this->exchange($this->exchangePayload('not-a-real-code'))
            ->assertStatus(404)
            ->assertJsonPath('errors.code', 'code_not_found');
    }

    public function test_inactive_user_is_rejected_at_exchange(): void
    {
        $user = $this->userWithPermission();
        $data = $this->issueHandoff($user);

        $user->update(['is_active' => false]);

        $this->exchange($this->exchangePayload($data['code']))
            ->assertStatus(403)
            ->assertJsonPath('errors.code', 'user_inactive');
    }

    public function test_revoked_permission_is_rejected_at_exchange(): void
    {
        $user = $this->userWithPermission();
        $data = $this->issueHandoff($user);

        $user->revokePermissionTo('whatsapp_connect_view');

        $this->exchange($this->exchangePayload($data['code']))
            ->assertStatus(403)
            ->assertJsonPath('errors.code', 'permission_revoked');
    }

    public function test_invalid_sso_key_is_rejected(): void
    {
        $user = $this->userWithPermission();
        $data = $this->issueHandoff($user);

        $this->exchange($this->exchangePayload($data['code']), [
            'X-ALIM-CONNECT-SSO-KEY' => 'wrong-key',
        ])->assertStatus(401);
    }

    public function test_sso_key_without_scope_is_rejected(): void
    {
        config(['services.connect_sso.scopes' => ['other:scope']]);

        $user = $this->userWithPermission();
        $data = $this->issueHandoff($user);

        $this->exchange($this->exchangePayload($data['code']))
            ->assertStatus(403)
            ->assertJsonPath('errors.code', 'insufficient_scope');
    }

    public function test_handoff_route_uses_csrf_protected_web_group(): void
    {
        // Laravel disables CSRF enforcement while running PHPUnit, so assert the
        // route is in the web group (which contains VerifyCsrfToken) instead.
        $route = Route::getRoutes()->getByName('user.whatsapp-connect.handoff');

        $this->assertNotNull($route);
        $this->assertContains('web', $route->gatherMiddleware());
    }

    public function test_handoff_is_rate_limited(): void
    {
        $user = $this->userWithPermission();

        for ($i = 0; $i < 10; $i++) {
            $this->issueHandoff($user);
        }

        $this->actingAs($user)
            ->withSession(['_token' => self::CSRF_TOKEN])
            ->withHeader('X-CSRF-TOKEN', self::CSRF_TOKEN)
            ->postJson(route('user.whatsapp-connect.handoff', ['userId' => $user->id]))
            ->assertStatus(429);
    }

    public function test_sso_handoff_migration_can_roll_back_and_up(): void
    {
        $path = database_path('migrations/2026_11_22_000003_create_connect_sso_handoffs_table.php');
        $migration = require $path;

        $this->assertTrue(Schema::hasTable('connect_sso_handoffs'));

        $migration->down();
        $this->assertFalse(Schema::hasTable('connect_sso_handoffs'));

        $migration->up();
        $this->assertTrue(Schema::hasTable('connect_sso_handoffs'));
    }

    public function test_exchange_failure_is_audited_without_raw_code(): void
    {
        $user = $this->userWithPermission();
        $data = $this->issueHandoff($user);

        $this->exchange($this->exchangePayload('not-a-real-code'))
            ->assertStatus(404);

        $failed = AuditLog::where('action', 'WHATSAPP_CONNECT_SSO_REDEEM_FAILED')->first();
        $this->assertNotNull($failed);
        $this->assertStringNotContainsString('not-a-real-code', json_encode($failed->metadata));
    }

    // ── Kontrak API exchange (untuk staging) ──────────────────────────────

    public function test_exchange_core_user_id_konsisten_saat_replay_gagal(): void
    {
        $user = $this->userWithPermission();
        $data = $this->issueHandoff($user);

        // Redemption pertama mengembalikan identitas; redemption kedua (kode
        // terpakai) tidak boleh lagi mengembalikan identitas apa pun.
        $this->exchange($this->exchangePayload($data['code']))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.core_user_id', $user->id);

        $this->exchange($this->exchangePayload($data['code']))
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data', null)
            ->assertJsonPath('errors.code', 'code_already_used');
    }

    public function test_success_envelope_kompatibel_dengan_connect(): void
    {
        $user = $this->userWithPermission();
        $data = $this->issueHandoff($user);

        $body = $this->exchange($this->exchangePayload($data['code']))
            ->assertOk()
            ->json();

        $this->assertTrue($body['success']);
        $this->assertNull($body['errors']);
        $this->assertArrayHasKey('meta', $body);
        $this->assertArrayHasKey('timestamp', $body['meta']);

        foreach (['core_user_id', 'name', 'audience', 'scope', 'issued_at', 'redeemed_at'] as $field) {
            $this->assertArrayHasKey($field, $body['data'], "Field {$field} wajib ada.");
        }
    }

    public function test_error_envelope_selalu_punya_code_dan_context(): void
    {
        $body = $this->exchange($this->exchangePayload('kode-tidak-ada'))
            ->assertStatus(404)
            ->json();

        $this->assertFalse($body['success']);
        $this->assertNull($body['data']);
        $this->assertSame('code_not_found', $body['errors']['code']);
        $this->assertArrayHasKey('context', $body['errors']);
    }

    public function test_kode_kosong_ditolak_422(): void
    {
        $this->exchange(['code' => '', 'audience' => 'connect', 'scope' => 'whatsapp.embed'])
            ->assertStatus(422);
    }

    public function test_audience_dan_scope_wajib_sesuai_kontrak(): void
    {
        $user = $this->userWithPermission();
        $data = $this->issueHandoff($user);

        // Audience/scope salah tidak boleh menghabiskan kode handoff.
        $this->exchange([
            'code' => $data['code'],
            'audience' => 'core',
            'scope' => 'whatsapp.embed',
        ])->assertStatus(403)->assertJsonPath('errors.code', 'audience_mismatch');

        $this->assertNull(ConnectSsoHandoff::firstOrFail()->used_at);

        $this->exchange($this->exchangePayload($data['code']))->assertOk();
    }

    public function test_kode_expired_tidak_dapat_ditukar_setelah_ttl_habis(): void
    {
        $user = $this->userWithPermission();
        $data = $this->issueHandoff($user);

        $this->travel((int) config('services.connect_sso.handoff_ttl') + 1)->seconds();

        $this->exchange($this->exchangePayload($data['code']))
            ->assertStatus(410)
            ->assertJsonPath('errors.code', 'code_expired');

        $this->assertNull(ConnectSsoHandoff::firstOrFail()->used_at);
    }

    public function test_pengguna_nonaktif_ditolak_saat_exchange(): void
    {
        $user = $this->userWithPermission();
        $data = $this->issueHandoff($user);

        $user->update(['is_active' => false]);

        $this->exchange($this->exchangePayload($data['code']))
            ->assertStatus(403)
            ->assertJsonPath('errors.code', 'user_inactive');
    }

    public function test_kegagalan_tak_terduga_dibalas_500_tanpa_detail(): void
    {
        $user = $this->userWithPermission();
        $data = $this->issueHandoff($user);

        // Paksa kegagalan di luar ConnectSsoException: service dibungkus
        // sehingga setiap Throwable lain jatuh ke cabang 500.
        $this->mock(ConnectHandoffService::class, function ($mock) {
            $mock->shouldReceive('redeem')->andThrow(new \RuntimeException('db down'));
        });

        $response = $this->exchange($this->exchangePayload($data['code']))
            ->assertStatus(500)
            ->assertJsonPath('errors.code', 'core_error');

        $this->assertStringNotContainsString('db down', $response->getContent());
        $this->assertStringNotContainsString('Exception', (string) $response->getContent());
    }
}
