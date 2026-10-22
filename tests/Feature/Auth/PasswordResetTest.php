<?php

namespace Tests\Feature\Auth;

use App\Mail\PasswordChangedMail;
use App\Mail\ResetPasswordOtpMail;
use App\Models\PasswordOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $this->get(route('password.request'))->assertOk();
    }

    public function test_otp_is_sent_for_registered_email(): void
    {
        Mail::fake();

        $user = User::factory()->create(['is_active' => true]);
        $otp = null;

        $response = $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.otp.form'));

        Mail::assertSent(ResetPasswordOtpMail::class, function (ResetPasswordOtpMail $mail) use ($user, &$otp) {
            $this->assertTrue($mail->hasTo($user->email));
            $otp = $mail->otp;

            return true;
        });

        $this->assertNotNull($otp);
        $response->assertSessionHas('reset_user_uuid', $user->id);
        $response->assertSessionHas('reset_email', $user->email);
        $this->assertDatabaseHas('password_otps', ['user_id' => $user->id]);
        $this->assertSame(0, PasswordOtp::where('user_id', $user->id)->firstOrFail()->attempts);
        $this->assertDatabaseHas('notifications_universal', [
            'user_id' => $user->id,
            'action' => 'password_forgot',
            'is_archived' => false,
        ]);
    }

    public function test_unknown_email_is_not_disclosed(): void
    {
        Mail::fake();

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'tidak-ada@example.test'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status');

        $this->assertDatabaseCount('password_otps', 0);
        Mail::assertNothingSent();
    }

    public function test_otp_can_be_verified_and_password_can_be_reset(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'is_active' => true,
            'password' => bcrypt('old-password'),
        ]);

        $this->post(route('password.email'), ['email' => $user->email])->assertRedirect(route('password.otp.form'));

        $this->get(route('password.otp.form'))
            ->assertOk()
            ->assertSee('***@');

        DB::table('sessions')->insert([
            'id' => 'other-device-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => '',
            'last_activity' => time(),
        ]);

        $otp = null;
        Mail::assertSent(ResetPasswordOtpMail::class, function (ResetPasswordOtpMail $mail) use (&$otp) {
            $otp = $mail->otp;

            return true;
        });

        $this->post(route('password.otp.verify'), ['otp' => '000000'])
            ->assertSessionHasErrors('otp');

        $this->assertSame(1, PasswordOtp::where('user_id', $user->id)->firstOrFail()->attempts);

        $this->post(route('password.otp.verify'), ['otp' => $otp])
            ->assertRedirect(route('password.reset.form'))
            ->assertSessionHas('otp_verified', true);

        $this->get(route('password.reset.form'))->assertOk();

        $this->post(route('password.reset.update'), [
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(Hash::check('new-password-123', $user->password));
        $this->assertDatabaseCount('password_otps', 0);
        $this->assertDatabaseMissing('sessions', ['id' => 'other-device-session']);
        $this->assertNull(session('reset_user_uuid'));
        Mail::assertSent(PasswordChangedMail::class, fn (PasswordChangedMail $mail) => $mail->hasTo($user->email));
        $this->assertDatabaseHas('notifications_universal', [
            'user_id' => $user->id,
            'action' => 'password_reset',
            'is_archived' => false,
        ]);
    }

    public function test_reset_requires_verified_otp(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'password' => bcrypt('old-password'),
        ]);

        $this->withSession(['reset_user_uuid' => $user->id])
            ->post(route('password.reset.update'), [
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect(route('password.request'));

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_expired_otp_is_rejected(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $otp = '123456';

        PasswordOtp::create([
            'user_id' => $user->id,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->subMinute(),
            'attempts' => 0,
        ]);

        $this->withSession(['reset_user_uuid' => $user->id])
            ->post(route('password.otp.verify'), ['otp' => $otp])
            ->assertSessionHasErrors('otp');

        $this->assertFalse(session('otp_verified', false));
    }

    public function test_otp_is_locked_after_five_failed_attempts(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $otp = '123456';

        PasswordOtp::create([
            'user_id' => $user->id,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->withSession(['reset_user_uuid' => $user->id])
                ->post(route('password.otp.verify'), ['otp' => '000000'])
                ->assertSessionHasErrors('otp');
        }

        $this->assertDatabaseMissing('password_otps', ['user_id' => $user->id]);

        $this->withSession(['reset_user_uuid' => $user->id])
            ->post(route('password.otp.verify'), ['otp' => $otp])
            ->assertSessionHasErrors('otp');
    }
}
