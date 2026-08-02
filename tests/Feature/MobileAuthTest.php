<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileAuthTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    private Sekolah $sekolah;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::factory()->create(['name' => 'Test Org']);
        $this->sekolah = Sekolah::factory()->create([
            'organization_id' => $this->org->id,
            'name' => 'Test Sekolah',
            'status' => 'active',
        ]);
    }

    // ── LOGIN ─────────────────────────────────────────────────────────────

    /** @test */
    public function can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/mobile/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'role'],
            ]);
    }

    /** @test */
    public function login_fails_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/mobile/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials']);
    }

    /** @test */
    public function login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/mobile/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    // ── REGISTER ─────────────────────────────────────────────────────────

    /** @test */
    public function can_register_new_user(): void
    {
        $userData = [
            'name' => 'New User',
            'email' => 'new@example.com',
            'phone' => '+6281234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/mobile/v1/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'phone', 'role'],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
    }

    /** @test */
    public function register_requires_password_confirmation_match(): void
    {
        $userData = [
            'name' => 'New User',
            'email' => 'new2@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ];

        $response = $this->postJson('/api/mobile/v1/auth/register', $userData);

        $response->assertStatus(422);
    }

    /** @test */
    public function register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $userData = [
            'name' => 'Duplicate',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/mobile/v1/auth/register', $userData);
        $response->assertStatus(422);
    }

    // ── ME ────────────────────────────────────────────────────────────────

    /** @test */
    public function authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/mobile/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('user.id', $user->getKey())
            ->assertJsonPath('user.email', $user->email);
    }

    /** @test */
    public function unauthenticated_user_cannot_get_profile(): void
    {
        $response = $this->getJson('/api/mobile/v1/auth/me');
        $response->assertStatus(401);
    }

    // ── LOGOUT ────────────────────────────────────────────────────────────

    /** @test */
    public function can_logout_and_revoke_token(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/mobile/v1/auth/logout');

        $response->assertStatus(200);

        // Token should be revoked
        $response2 = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/mobile/v1/auth/me');
        $response2->assertStatus(401);
    }

    // ── RATE LIMITING ────────────────────────────────────────────────────

    /** @test */
    public function login_is_rate_limited(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/mobile/v1/auth/login', [
                'email' => $user->email,
                'password' => 'wrong',
            ]);
        }

        // Should be rate limited after many attempts
        $response = $this->postJson('/api/mobile/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong',
        ]);

        $response->assertStatus(429);
    }

    // ── ENDPOINT AVAILABILITY ─────────────────────────────────────────────

    /** @test */
    public function forgot_password_endpoint_exists(): void
    {
        $response = $this->postJson('/api/mobile/v1/auth/forgot-password', [
            'email' => 'test@example.com',
        ]);
        // May return 200 or 403 depending on 2FA config; just checks it exists
        $response->assertStatus(function ($status) {
            return in_array($status, [200, 202, 403]);
        });
    }
}
