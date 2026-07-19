<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AbilityRegistry;
use App\Support\TokenExpiration;
use App\Support\TokenName;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

/**
 * Sprint 2 — Mobile Auth Feature Tests.
 *
 * Covers ADR-018 contract:
 *  - register / login / google / me / logout
 *  - logout-all / sessions list / update current / revoke others
 *  - expired token 401, missing token 401, invalid token 401
 *  - ability assignment from roles
 *  - token expiration wired from TokenExpiration
 *  - brute force lockout (Sprint 2 fix)
 *  - token name format from TokenName
 */
class MobileAuthSprint2Test extends TestCase
{
    protected static bool $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! static::$migrated) {
            Artisan::call('migrate:fresh', ['--force' => true]);
            static::$migrated = true;
        }

        $this->beginTransaction();
    }

    protected function beginTransaction(): void
    {
        $connection = DB::connection();
        $connection->beginTransaction();

        $this->beforeApplicationDestroyed(function () use ($connection) {
            $connection->rollBack();
        });
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    private function createWali(array $attrs = []): User
    {
        $email = $attrs['email'] ?? $this->faker->unique()->safeEmail();

        $user = User::create(array_merge([
            'id' => (string) Str::uuid(),
            'name' => 'Wali Test',
            'email' => $email,
            'password' => Hash::make('password123'),
            'is_wali' => true,
            'is_active' => true,
        ], $attrs));

        $role = \App\Models\Role::firstOrCreate(
            ['name' => 'wali', 'guard_name' => 'web']
        );

        if (method_exists($user, 'assignRole')) {
            $user->assignRole($role);
        }

        return $user;
    }

    private function makeBearer(User $user): string
    {
        $new = $user->createToken('test:user:password:unknown:fp_unit', ['profile.read']);

        return $new->plainTextToken;
    }

    // ── REGISTER ────────────────────────────────────────────────────────────

    public function test_register_succeeds_and_returns_sanctum_token(): void
    {
        $payload = [
            'name' => 'Ahmad Wali',
            'email' => 'new-wali@test.id',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'no_kk' => '7301010000000001',
            'nik_wali' => '7301011508900001',
            'no_hp' => '081234567890',
            'hubungan' => 'ayah',
        ];

        $response = $this->postJson('/api/mobile/v1/auth/register', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure([
                'success', 'message', 'data' => [
                    'user', 'access_token', 'token_type',
                    'expires_in', 'expires_at', 'abilities',
                ],
            ]);

        $this->assertNotEmpty($response->json('data.access_token'));

        $row = PersonalAccessToken::query()->latest('created_at')->first();
        $this->assertNotNull($row);
        $this->assertStringStartsWith('mobile:user:password:', $row->name);
    }

    public function test_register_validation_fails_on_missing_email(): void
    {
        $response = $this->postJson('/api/mobile/v1/auth/register', [
            'name' => 'Ahmad',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_validation_fails_on_duplicate_email(): void
    {
        $this->createWali(['email' => 'dup@test.id']);

        $response = $this->postJson('/api/mobile/v1/auth/register', [
            'name' => 'Dup',
            'email' => 'dup@test.id',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    // ── LOGIN ───────────────────────────────────────────────────────────────

    public function test_login_succeeds_with_correct_credentials(): void
    {
        $wali = $this->createWali(['email' => 'login-ok@test.id']);

        $response = $this->postJson('/api/mobile/v1/auth/login', [
            'email' => 'login-ok@test.id',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer');

        $token = $response->json('data.access_token');
        $this->assertNotEmpty($token);

        $row = PersonalAccessToken::query()->where('tokenable_id', $wali->id)->latest('created_at')->first();
        $this->assertNotNull($row);
        $this->assertEquals('mobile:user:password:unknown:fp_ip_'.substr(md5((string) $this->app['request']->ip()), 0, 12), $row->name);
    }

    public function test_login_with_wrong_password_returns_validation_error(): void
    {
        $this->createWali(['email' => 'wrong@test.id']);

        $response = $this->postJson('/api/mobile/v1/auth/login', [
            'email' => 'wrong@test.id',
            'password' => 'NOT-correct',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_increments_failed_attempts_until_lock(): void
    {
        $wali = $this->createWali(['email' => 'lockout@test.id']);

        for ($i = 0; $i < 9; $i++) {
            $this->postJson('/api/mobile/v1/auth/login', [
                'email' => 'lockout@test.id',
                'password' => 'wrong',
            ])->assertStatus(422);
        }

        $wali->refresh();
        $this->assertNotNull($wali->locked_until);
        $this->assertTrue($wali->locked_until->isFuture());

        $response = $this->postJson('/api/mobile/v1/auth/login', [
            'email' => 'lockout@test.id',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_inactive_account_returns_validation_error(): void
    {
        $this->createWali(['email' => 'inactive@test.id', 'is_active' => false]);

        $response = $this->postJson('/api/mobile/v1/auth/login', [
            'email' => 'inactive@test.id',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    // ── GOOGLE LOGIN ────────────────────────────────────────────────────────

    public function test_google_login_returns_sanctum_token_for_new_user(): void
    {
        $response = $this->postJson('/api/mobile/v1/auth/google', [
            'google_id' => 'gid-'.Str::random(12),
            'email' => 'g-new@test.id',
            'name' => 'Google Wali',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_new_user', true)
            ->assertJsonPath('data.token_type', 'Bearer');

        $row = PersonalAccessToken::query()->where('name', 'like', 'mobile:user:google:%')->latest('created_at')->first();
        $this->assertNotNull($row);
    }

    public function test_google_login_links_existing_user(): void
    {
        $wali = $this->createWali(['email' => 'g-existing@test.id']);

        $response = $this->postJson('/api/mobile/v1/auth/google', [
            'google_id' => 'gid-link',
            'email' => 'g-existing@test.id',
            'name' => 'Existing Wali',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.is_new_user', false);

        $wali->refresh();
        $this->assertEquals('gid-link', $wali->google_id);
    }

    public function test_google_login_validation_fails(): void
    {
        $response = $this->postJson('/api/mobile/v1/auth/google', [
            'google_id' => 'gid-1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'name']);
    }

    // ── ME ──────────────────────────────────────────────────────────────────

    public function test_me_requires_token(): void
    {
        $this->getJson('/api/mobile/v1/auth/me')->assertStatus(401);
    }

    public function test_me_returns_user_profile_with_token(): void
    {
        $wali = $this->createWali(['email' => 'me@test.id']);
        $token = $this->makeBearer($wali);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->getJson('/api/mobile/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'me@test.id')
            ->assertJsonStructure(['data' => ['user', 'students']]);
    }

    // ── LOGOUT ──────────────────────────────────────────────────────────────

    public function test_logout_revokes_current_token(): void
    {
        $wali = $this->createWali(['email' => 'logout@test.id']);
        $token = $this->makeBearer($wali);

        $tokenId = $this->tokenIdFromBearer($token, $wali);
        $this->assertNotNull($tokenId);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->postJson('/api/mobile/v1/auth/logout');

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }

    public function test_logout_without_token_returns_401(): void
    {
        $this->postJson('/api/mobile/v1/auth/logout')->assertStatus(401);
    }

    // ── LOGOUT-ALL / SESSIONS / UPDATE / OTHERS (Sprint 2) ─────────────────

    public function test_sessions_endpoint_lists_all_tokens(): void
    {
        $wali = $this->createWali(['email' => 'sessions@test.id']);

        $t1 = $this->makeBearer($wali);
        $t2 = $wali->createToken('mobile:user:password:android:fp_other', ['profile.read'])->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$t1,
            'Accept' => 'application/json',
        ])->getJson('/api/mobile/v1/auth/sessions');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.sessions');

        $sessions = $response->json('data.sessions');
        $current = array_values(array_filter($sessions, fn ($s) => $s['current_device'] === true));
        $this->assertCount(1, $current);

        $others = array_values(array_filter($sessions, fn ($s) => $s['current_device'] === false));
        $this->assertCount(1, $others);
    }

    public function test_sessions_requires_token(): void
    {
        $this->getJson('/api/mobile/v1/auth/sessions')->assertStatus(401);
    }

    public function test_update_current_session_changes_label(): void
    {
        $wali = $this->createWali(['email' => 'label@test.id']);
        $token = $this->makeBearer($wali);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->patchJson('/api/mobile/v1/auth/sessions/current', [
            'device_label' => 'HP Pak Kades',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.session.device_label', 'HP Pak Kades');
    }

    public function test_update_current_session_validation_requires_label(): void
    {
        $wali = $this->createWali(['email' => 'label2@test.id']);
        $token = $this->makeBearer($wali);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->patchJson('/api/mobile/v1/auth/sessions/current', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['device_label']);
    }

    public function test_revoke_others_deletes_other_sessions_only(): void
    {
        $wali = $this->createWali(['email' => 'others@test.id']);

        $current = $this->makeBearer($wali);
        $currentId = $this->tokenIdFromBearer($current, $wali);

        $other1 = $wali->createToken('mobile:user:password:android:fp_a', ['profile.read'])->plainTextToken;
        $other2 = $wali->createToken('mobile:user:password:ios:fp_b', ['profile.read'])->plainTextToken;

        $otherId1 = $this->tokenIdFromBearer($other1, $wali);
        $otherId2 = $this->tokenIdFromBearer($other2, $wali);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$current,
            'Accept' => 'application/json',
        ])->deleteJson('/api/mobile/v1/auth/sessions/others');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.revoked', 2);

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $currentId]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $otherId1]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $otherId2]);
    }

    public function test_revoke_others_requires_token(): void
    {
        $this->deleteJson('/api/mobile/v1/auth/sessions/others')->assertStatus(401);
    }

    // ── 401 / 403 / TOKEN ABILITIES ─────────────────────────────────────────

    public function test_invalid_bearer_returns_401(): void
    {
        $this->withHeaders([
            'Authorization' => 'Bearer this-is-not-a-real-token',
            'Accept' => 'application/json',
        ])->getJson('/api/mobile/v1/auth/me')->assertStatus(401);
    }

    public function test_expired_token_returns_401(): void
    {
        $wali = $this->createWali(['email' => 'expired@test.id']);

        $new = $wali->createToken(
            'mobile:user:password:android:fp_exp',
            ['profile.read'],
            now()->subMinute()
        );

        $this->withHeaders([
            'Authorization' => 'Bearer '.$new->plainTextToken,
            'Accept' => 'application/json',
        ])->getJson('/api/mobile/v1/auth/me')->assertStatus(401);
    }

    public function test_login_response_abilities_match_role(): void
    {
        $wali = $this->createWali(['email' => 'abilities@test.id']);

        $response = $this->postJson('/api/mobile/v1/auth/login', [
            'email' => 'abilities@test.id',
            'password' => 'password123',
        ])->assertOk();

        $expected = AbilityRegistry::forRoles($wali->effectiveRoles());
        $this->assertEqualsCanonicalizing($expected, $response->json('data.abilities'));
    }

    public function test_login_response_expiration_matches_helper(): void
    {
        $this->createWali(['email' => 'exp@test.id']);

        $response = $this->postJson('/api/mobile/v1/auth/login', [
            'email' => 'exp@test.id',
            'password' => 'password123',
        ])->assertOk();

        $minutes = TokenExpiration::mobileDefaultMinutes();
        $expectedSeconds = $minutes === null ? null : $minutes * 60;

        $this->assertSame($expectedSeconds, $response->json('data.expires_in'));
    }

    public function test_token_name_format_helper(): void
    {
        $name = TokenName::mobile('user', 'password', 'android', 'fp_abc');
        $this->assertEquals('mobile:user:password:android:fp_abc', $name);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function tokenIdFromBearer(string $bearer, User $user): ?string
    {
        $hash = hash('sha256', explode('|', $bearer)[1] ?? '');
        if ($hash === '') {
            return null;
        }

        $row = PersonalAccessToken::query()
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $user->getKey())
            ->where('token', $hash)
            ->first();

        return $row?->getKey();
    }
}
