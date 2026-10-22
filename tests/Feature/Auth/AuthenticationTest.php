<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create(['is_system_admin' => true]);

        $response = $this->post('/login', [
            'identity' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('system.dashboard'));
    }

    public function test_successful_login_sends_security_notification(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
            'no_hp' => '081234567890',
        ]);

        $this->post('/login', [
            'identity' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('system.dashboard'));

        $this->assertDatabaseHas('notifications_universal', [
            'user_id' => $user->id,
            'action' => 'login',
            'is_archived' => false,
        ]);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'identity' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }
}
