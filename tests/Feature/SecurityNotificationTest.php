<?php

namespace Tests\Feature;

use App\Jobs\SendUniversalEmail;
use App\Jobs\SendUniversalWhatsApp;
use App\Models\NotificationUniversal;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\SecurityNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SecurityNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_login_notification_uses_email_whatsapp_and_system_channels(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'is_active' => true,
            'no_hp' => '081234567890',
        ]);

        app(SecurityNotificationService::class)->login($user, Request::create('/login', 'POST'));

        $this->assertDatabaseHas('notifications_universal', [
            'user_id' => $user->id,
            'action' => 'login',
            'is_archived' => false,
        ]);

        Queue::assertPushed(SendUniversalEmail::class);
        Queue::assertPushed(SendUniversalWhatsApp::class);
    }

    public function test_disabled_security_notification_toggle_skips_delivery(): void
    {
        SystemSetting::set('security_notify_login', '0', 'security');
        Cache::forget('system_settings_map');

        $user = User::factory()->create([
            'is_active' => true,
            'no_hp' => '081234567890',
        ]);

        app(SecurityNotificationService::class)->login($user, Request::create('/login', 'POST'));

        $this->assertDatabaseMissing('notifications_universal', [
            'user_id' => $user->id,
            'action' => 'login',
        ]);
    }

    public function test_new_device_mode_only_notifies_once_per_ip_and_device(): void
    {
        Queue::fake();
        SystemSetting::set('security_login_notify_mode', 'new_device', 'security');
        Cache::forget('system_settings_map');

        $user = User::factory()->create(['is_active' => true, 'no_hp' => '081234567890']);
        $request = Request::create('/login', 'POST');
        $request->server->set('REMOTE_ADDR', '10.10.10.10');
        $request->headers->set('User-Agent', 'ALIM-Test-Device');

        app(SecurityNotificationService::class)->login($user, $request);
        app(SecurityNotificationService::class)->login($user, $request);

        $this->assertSame(1, NotificationUniversal::where('user_id', $user->id)
            ->where('action', 'login')
            ->count());
    }

    public function test_forgot_password_notification_uses_whatsapp_and_system_channels(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'is_active' => true,
            'no_hp' => '081234567890',
        ]);

        app(SecurityNotificationService::class)->passwordForgot(
            $user,
            Request::create('/password/forgot', 'POST'),
        );

        $this->assertDatabaseHas('notifications_universal', [
            'user_id' => $user->id,
            'action' => 'password_forgot',
            'is_archived' => false,
        ]);

        Queue::assertNotPushed(SendUniversalEmail::class);
        Queue::assertPushed(SendUniversalWhatsApp::class);
    }
}
