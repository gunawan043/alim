<?php

namespace Tests\Feature\SuperAdmin;

use App\Mail\PasswordChangedMail;
use App\Models\GtkEmployment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_reset_password_with_manual_value(): void
    {
        Mail::fake();

        $admin = $this->admin();
        $target = User::factory()->create(['is_active' => true]);

        DB::table('sessions')->insert([
            'id' => 'target-web-session',
            'user_id' => $target->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => '',
            'last_activity' => time(),
        ]);

        $target->createToken('mobile-test');

        $response = $this->actingAs($admin)
            ->post(route('user.sa.users.reset-password', ['userId' => $admin->id, 'id' => $target->id]), [
                'mode' => 'manual',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ]);
        $response->assertRedirect()->assertSessionHas('success');

        $target->refresh();

        $this->assertTrue(Hash::check('new-password-123', $target->password));
        $this->assertTrue($target->must_change_password);
        $this->assertDatabaseMissing('sessions', ['id' => 'target-web-session']);
        $this->assertSame(0, $target->tokens()->count());
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'ADMIN_PASSWORD_RESET',
            'record_id' => $target->id,
        ]);
        Mail::assertSent(PasswordChangedMail::class, fn (PasswordChangedMail $mail) => $mail->hasTo($target->email));
    }

    public function test_super_admin_can_reset_password_to_at_nupy(): void
    {
        Mail::fake();

        $admin = $this->admin();
        $target = User::factory()->create(['is_active' => true]);
        GtkEmployment::create([
            'user_id' => $target->id,
            'status_kepegawaian' => 'GTT',
            'nupy' => '19740120141164',
        ]);

        $this->actingAs($admin)
            ->post(route('user.sa.users.reset-password', ['userId' => $admin->id, 'id' => $target->id]), [
                'mode' => 'nupy',
            ])
            ->assertRedirect()
            ->assertSessionHas('generated_password', '@19740120141164');

        $this->assertTrue(Hash::check('@19740120141164', $target->refresh()->password));
        $this->assertTrue($target->must_change_password);
    }

    public function test_super_admin_cannot_use_nupy_if_target_has_no_nupy(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->post(route('user.sa.users.reset-password', ['userId' => $admin->id, 'id' => $target->id]), [
                'mode' => 'nupy',
            ])
            ->assertSessionHas('error');
    }

    public function test_super_admin_can_generate_temporary_password_once(): void
    {
        Mail::fake();

        $admin = $this->admin();
        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->post(route('user.sa.users.reset-password', ['userId' => $admin->id, 'id' => $target->id]), [
                'mode' => 'generate',
            ])
            ->assertRedirect()
            ->assertSessionHas('generated_password');

        $generated = session('generated_password');

        $this->assertNotNull($generated);
        $this->assertTrue(Hash::check($generated, $target->refresh()->password));
        $this->assertTrue($target->must_change_password);
    }

    public function test_super_admin_cannot_reset_own_password_through_user_management(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('user.sa.users.reset-password', ['userId' => $admin->id, 'id' => $admin->id]), [
                'mode' => 'generate',
            ])
            ->assertSessionHas('error');
    }

    public function test_user_actions_target_the_selected_user_not_the_admin(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create(['is_active' => true, 'name' => 'Target User']);

        $this->actingAs($admin)
            ->post(route('user.sa.users.toggle-status', ['userId' => $admin->id, 'id' => $target->id]), [], ['Accept' => 'application/json'])
            ->assertJson(['success' => true]);

        $this->assertFalse($target->refresh()->is_active);
        $this->assertTrue($admin->refresh()->is_active);

        $this->actingAs($admin)->get(route('user.sa.users.edit', ['userId' => $admin->id, 'id' => $target->id]))
            ->assertOk()
            ->assertSee('Target User');

        $this->actingAs($admin)->delete(route('user.sa.users.destroy', ['userId' => $admin->id, 'id' => $target->id]))
            ->assertRedirect();

        $this->assertSoftDeleted('users', ['id' => $target->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_reset_password_respects_minimum_length(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->post(route('user.sa.users.reset-password', ['userId' => $admin->id, 'id' => $target->id]), [
                'mode' => 'manual',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertSessionHasErrors('password');
    }

    private function admin(): User
    {
        return User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);
    }
}
