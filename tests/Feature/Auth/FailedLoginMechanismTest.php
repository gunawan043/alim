<?php

namespace Tests\Feature\Auth;

use App\Mail\AccountCompromisedMail;
use App\Mail\AccountLockedMail;
use App\Models\FailedLoginAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FailedLoginMechanismTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private array $credentials;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        // Clear any stale failed login attempts from previous tests
        FailedLoginAttempt::query()->delete();

        $this->user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $this->credentials = [
            'identity' => $this->user->email,
            'password' => 'wrong-password',
        ];
    }

    // ── Attempt 1-4: No cooldown ─────────────────────────────────────────

    public function test_attempt_1_no_cooldown(): void
    {
        $response = $this->post('/login', $this->credentials);

        $response->assertRedirect('/login');
        $this->assertGuest();

        $this->user->refresh();
        $this->assertEquals(1, $this->user->failed_login_attempts);
        $this->assertFalse($this->user->isLocked());

        $ipRecord = FailedLoginAttempt::where('ip_address', '127.0.0.1')->first();
        $this->assertNotNull($ipRecord);
        $this->assertEquals(1, $ipRecord->attempts);
        $this->assertNull($ipRecord->locked_until);
    }

    public function test_attempt_2_no_cooldown(): void
    {
        $this->post('/login', $this->credentials);
        $response = $this->post('/login', $this->credentials);

        $response->assertRedirect('/login');
        $this->assertGuest();

        $this->user->refresh();
        $this->assertEquals(2, $this->user->failed_login_attempts);
        $this->assertFalse($this->user->isLocked());
    }

    public function test_attempt_3_no_cooldown(): void
    {
        $this->post('/login', $this->credentials);
        $this->post('/login', $this->credentials);
        $response = $this->post('/login', $this->credentials);

        $response->assertRedirect('/login');
        $this->assertGuest();

        $this->user->refresh();
        $this->assertEquals(3, $this->user->failed_login_attempts);
        $this->assertFalse($this->user->isLocked());
    }

    public function test_attempt_4_no_cooldown(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->post('/login', $this->credentials);
        }

        $this->user->refresh();
        $this->assertEquals(4, $this->user->failed_login_attempts);
        $this->assertFalse($this->user->isLocked());
    }

    // ── Attempt 5: IP cooldown ───────────────────────────────────────────

    public function test_attempt_5_triggers_ip_cooldown(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->post('/login', $this->credentials);
        }

        $response = $this->post('/login', $this->credentials);

        $response->assertRedirect('/login');
        $this->assertGuest();

        $this->user->refresh();
        $this->assertEquals(5, $this->user->failed_login_attempts);
        $this->assertFalse($this->user->isLocked());

        $ipRecord = FailedLoginAttempt::where('ip_address', '127.0.0.1')->first();
        $this->assertNotNull($ipRecord);
        $this->assertTrue($ipRecord->attempts >= 5);
    }

    // ── Attempt 6: Compromised warning ───────────────────────────────────

    public function test_attempt_6_triggers_compromised_warning(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', $this->credentials);
        }

        $response = $this->post('/login', $this->credentials);

        $response->assertRedirect('/login');
        $this->assertGuest();

        $this->user->refresh();
        $this->assertEquals(6, $this->user->failed_login_attempts);
        $this->assertFalse($this->user->isLocked());

        Mail::assertQueued(AccountCompromisedMail::class);
    }

    // ── Attempt 7-8: Normal failures ─────────────────────────────────────

    public function test_attempt_7_no_lock(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', $this->credentials);
        }

        $response = $this->post('/login', $this->credentials);

        $response->assertRedirect('/login');
        $this->assertGuest();

        $this->user->refresh();
        $this->assertEquals(7, $this->user->failed_login_attempts);
        $this->assertFalse($this->user->isLocked());
    }

    public function test_attempt_8_no_lock(): void
    {
        for ($i = 0; $i < 7; $i++) {
            $this->post('/login', $this->credentials);
        }

        $response = $this->post('/login', $this->credentials);

        $response->assertRedirect('/login');
        $this->assertGuest();

        $this->user->refresh();
        $this->assertEquals(8, $this->user->failed_login_attempts);
        $this->assertFalse($this->user->isLocked());
    }

    // ── Attempt 9: Account lock ──────────────────────────────────────────

    public function test_attempt_9_locks_account(): void
    {
        for ($i = 0; $i < 8; $i++) {
            $this->post('/login', $this->credentials);
        }

        $response = $this->post('/login', $this->credentials);

        $response->assertRedirect('/login');
        $this->assertGuest();
        $response->assertSessionHasErrors('account_locked');

        $this->user->refresh();
        $this->assertEquals(9, $this->user->failed_login_attempts);
        $this->assertTrue($this->user->isLocked());
        $this->assertNotNull($this->user->locked_until);

        Mail::assertSent(AccountLockedMail::class, 2); // user + super admins
    }

    // ── Attempt 10: Account locked rejection ─────────────────────────────

    public function test_attempt_10_rejected_because_account_locked(): void
    {
        for ($i = 0; $i < 9; $i++) {
            $this->post('/login', $this->credentials);
        }

        $response = $this->post('/login', $this->credentials);

        $response->assertRedirect('/login');
        $this->assertGuest();
        $response->assertSessionHasErrors('account_locked');

        $this->user->refresh();
        $this->assertTrue($this->user->isLocked());
    }

    // ── Successful login resets counters ─────────────────────────────────

    public function test_successful_login_resets_counters(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->post('/login', $this->credentials);
        }

        $successCredentials = [
            'identity' => $this->user->email,
            'password' => 'password',
        ];

        $response = $this->post('/login', $successCredentials);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($this->user);

        $this->user->refresh();
        $this->assertEquals(0, $this->user->failed_login_attempts);
        $this->assertNull($this->user->locked_until);

        $ipRecord = FailedLoginAttempt::where('ip_address', '127.0.0.1')->first();
        if ($ipRecord) {
            $this->assertEquals(0, $ipRecord->attempts);
        }
    }

    // ── Different user, same IP ──────────────────────────────────────────

    public function test_different_user_same_ip_does_not_share_counters(): void
    {
        $otherUser = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        for ($i = 0; $i < 4; $i++) {
            $this->post('/login', [
                'identity' => $this->user->email,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post('/login', [
            'identity' => $otherUser->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');

        $this->user->refresh();
        $otherUser->refresh();

        $this->assertEquals(4, $this->user->failed_login_attempts);
        $this->assertEquals(1, $otherUser->failed_login_attempts);
    }

    // ── Non-existent user ────────────────────────────────────────────────

    public function test_non_existent_user_increments_ip_counter(): void
    {
        $this->post('/login', [
            'identity' => 'nonexistent@example.com',
            'password' => 'wrong-password',
        ]);

        $ipRecord = FailedLoginAttempt::where('ip_address', '127.0.0.1')->first();
        $this->assertNotNull($ipRecord);
        $this->assertEquals(1, $ipRecord->attempts);
    }

    // ── Cooldown does not break user counter ─────────────────────────────

    public function test_ip_cooldown_does_not_break_user_counter(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->post('/login', $this->credentials);
        }

        $ipRecord = FailedLoginAttempt::where('ip_address', '127.0.0.1')->first();
        $originalAttempts = $ipRecord->attempts;

        $this->post('/login', $this->credentials);

        $this->user->refresh();
        $this->assertEquals(5, $this->user->failed_login_attempts);
    }
}
