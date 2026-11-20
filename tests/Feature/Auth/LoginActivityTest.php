<?php

namespace Tests\Feature\Auth;

use App\Models\FailedLoginAttempt;
use App\Models\LoginActivity;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LoginActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        SystemSetting::set('security_2fa_enabled', '0', 'security');
        FailedLoginAttempt::query()->delete();
    }

    public function test_login_berhasil_dicatat(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->post('/login', [
            'identity' => $user->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertDatabaseHas('login_activities', [
            'user_id' => $user->id,
            'event' => 'login_success',
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_percobaan_login_gagal_berulang_ditandai_mencurigakan(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        foreach (range(1, 3) as $attempt) {
            $this->post('/login', [
                'identity' => $user->email,
                'password' => 'password-salah',
            ])->assertRedirect('/login');

            $this->assertSame(
                $attempt,
                LoginActivity::where('user_id', $user->id)->where('event', 'login_failed')->count()
            );

            $this->assertSame(
                $attempt >= 3,
                LoginActivity::where('user_id', $user->id)
                    ->where('event', 'login_failed')
                    ->where('is_suspicious', true)
                    ->exists()
            );
        }
    }

    public function test_super_admin_dapat_melihat_dan_mengelola_aktivitas_login(): void
    {
        $admin = User::factory()->create(['is_system_admin' => true, 'is_active' => true]);

        $lockedUser = User::factory()->create([
            'is_active' => true,
            'failed_login_attempts' => 9,
            'locked_at' => now(),
            'locked_until' => now()->addHour(),
        ]);

        LoginActivity::create([
            'user_id' => $lockedUser->id,
            'identity' => $lockedUser->email,
            'email' => $lockedUser->email,
            'event' => 'account_locked',
            'reason' => 'Batas percobaan login tercapai',
            'ip_address' => '10.0.0.9',
            'user_agent' => 'Feature Test',
            'is_suspicious' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('user.sa.login-activities.index', ['userId' => $admin->id]))
            ->assertOk()
            ->assertSee('Aktivitas Login')
            ->assertSee($lockedUser->email);

        $this->actingAs($admin)
            ->post(route('user.sa.login-activities.users.unlock', [
                'userId' => $admin->id,
                'targetId' => $lockedUser->id,
            ]))
            ->assertRedirect();

        $this->assertNull($lockedUser->fresh()->locked_until);
        $this->assertSame(0, $lockedUser->fresh()->failed_login_attempts);

        $this->actingAs($admin)
            ->post(route('user.sa.login-activities.ips.block', ['userId' => $admin->id]), [
                'ip_address' => '10.0.0.9',
                'minutes' => 30,
                'reason' => 'Uji blokir',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('failed_login_attempts', [
            'ip_address' => '10.0.0.9',
            'locked_reason' => 'ip',
        ]);

        $this->actingAs($admin)
            ->post(route('user.sa.login-activities.ips.unblock', ['userId' => $admin->id]), [
                'ip_address' => '10.0.0.9',
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('failed_login_attempts', [
            'ip_address' => '10.0.0.9',
        ]);
    }

    public function test_super_admin_dapat_membuka_log_sistem(): void
    {
        $admin = User::factory()->create(['is_system_admin' => true, 'is_active' => true]);

        $this->actingAs($admin)
            ->get(route('user.sa.system-logs.index', ['userId' => $admin->id]))
            ->assertOk()
            ->assertSee('Log Error');
    }
}
