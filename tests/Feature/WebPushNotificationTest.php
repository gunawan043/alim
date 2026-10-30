<?php

namespace Tests\Feature;

use App\Http\Controllers\WebPushSubscriptionController;
use App\Jobs\SendUniversalPush;
use App\Models\NotificationUniversal;
use App\Models\User;
use App\Models\WebPushSubscription;
use App\Services\Push\PushNotificationService;
use App\Services\Push\WebPushService;
use App\Support\WebPushConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WebPushNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function user(): User
    {
        return User::factory()->create([
            'is_active' => true,
            'is_wali' => true,
        ]);
    }

    private function configureWebPush(): void
    {
        WebPushConfig::generateKeys();
    }

    public function test_public_key_endpoint_returns_vapid_key(): void
    {
        $this->configureWebPush();
        $user = $this->user();
        $this->actingAs($user);

        $request = Request::create('/'.$user->id.'/notifications/web-push/public-key');
        $request->setUserResolver(fn () => $user);

        $response = app(WebPushSubscriptionController::class)->publicKey($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['data']['enabled']);
        $this->assertSame(WebPushConfig::vapidPublicKey(), $response->getData(true)['data']['public_key']);
    }

    public function test_browser_subscription_can_be_stored_and_revoked(): void
    {
        $this->configureWebPush();
        $user = $this->user();
        $this->actingAs($user);

        $endpoint = 'https://push.example.test/subscription/abc';

        $subscribe = Request::create('/'.$user->id.'/notifications/web-push/subscribe', 'POST', [
            'endpoint' => $endpoint,
            'keys' => [
                'p256dh' => str_repeat('p', 87),
                'auth' => str_repeat('a', 22),
            ],
            'content_encoding' => 'aes128gcm',
        ]);
        $subscribe->setUserResolver(fn () => $user);

        $response = app(WebPushSubscriptionController::class)->subscribe($subscribe);

        $this->assertSame(201, $response->getStatusCode());

        $this->assertDatabaseHas('web_push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => $endpoint,
            'content_encoding' => 'aes128gcm',
        ]);

        $unsubscribe = Request::create('/'.$user->id.'/notifications/web-push/unsubscribe', 'POST', [
            'endpoint' => $endpoint,
        ]);
        $unsubscribe->setUserResolver(fn () => $user);

        $this->assertSame(200, app(WebPushSubscriptionController::class)->unsubscribe($unsubscribe)->getStatusCode());

        $this->assertNotNull(WebPushSubscription::where('endpoint', $endpoint)->first()->revoked_at);
    }

    public function test_web_push_job_can_mark_notification_as_sent(): void
    {
        $user = $this->user();
        $notification = NotificationUniversal::create([
            'user_id' => $user->id,
            'module' => 'academic',
            'type' => 'info',
            'action' => 'nilai_updated',
            'title' => 'Nilai diperbarui',
            'message' => 'Nilai sudah diperbarui.',
        ]);

        $fakeWebPush = new class extends WebPushService
        {
            public function isConfigured(): bool
            {
                return true;
            }

            public function sendToUser(User $user, NotificationUniversal $notification): array
            {
                return ['sent' => 1, 'failed' => 0, 'expired' => 0];
            }
        };

        $this->app->instance(WebPushService::class, $fakeWebPush);

        (new SendUniversalPush($user, $notification))->handle(
            app(PushNotificationService::class),
            app(WebPushService::class),
        );

        $this->assertTrue($notification->fresh()->is_push_sent);
    }
}
