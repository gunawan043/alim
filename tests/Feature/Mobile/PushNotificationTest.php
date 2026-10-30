<?php

namespace Tests\Feature\Mobile;

use App\Jobs\SendUniversalPush;
use App\Models\NotificationUniversal;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Push\PushNotificationService;
use App\Services\Push\WebPushService;
use App\Support\ApnsConfig;
use App\Support\FcmConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PushNotificationTest extends TestCase
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

    private function fcmServiceAccount(): array
    {
        $key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 2048,
        ]);
        openssl_pkey_export($key, $privateKey);

        return [
            'type' => 'service_account',
            'project_id' => 'test-project',
            'client_email' => 'firebase-adminsdk@test-project.iam.gserviceaccount.com',
            'private_key' => $privateKey,
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ];
    }

    private function apnsPrivateKey(): string
    {
        $key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);
        openssl_pkey_export($key, $privateKey);

        return $privateKey;
    }

    private function configureFcm(): void
    {
        FcmConfig::storeProjectId('test-project');
        FcmConfig::storeServiceAccountJson(json_encode($this->fcmServiceAccount(), JSON_THROW_ON_ERROR));
        Cache::flush();
    }

    private function configureApns(): void
    {
        ApnsConfig::storeKeyId('KEYID12345');
        ApnsConfig::storeTeamId('TEAM123456');
        ApnsConfig::storeBundleId('com.alim.app');
        ApnsConfig::storeEnvironment('sandbox');
        ApnsConfig::storePrivateKey($this->apnsPrivateKey());
        Cache::flush();
    }

    public function test_device_registration_stores_fcm_token_and_is_idempotent(): void
    {
        $user = $this->user();
        Sanctum::actingAs($user, [], 'sanctum');

        $payload = [
            'push_token' => str_repeat('f', 152),
            'provider' => 'fcm',
            'platform' => 'android',
            'device_name' => 'Samsung A54',
            'app_version' => '1.0.0',
        ];

        $this->postJson('/api/mobile/v1/devices', $payload)
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.device.provider', 'fcm');

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user->id,
            'push_token' => $payload['push_token'],
            'provider' => 'fcm',
            'platform' => 'android',
        ]);

        $this->postJson('/api/mobile/v1/devices', array_merge($payload, [
            'device_name' => 'Samsung A54 (baru)',
        ]))->assertOk();

        $this->assertSame(1, UserDevice::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user->id,
            'device_name' => 'Samsung A54 (baru)',
        ]);
    }

    public function test_device_registration_rejects_provider_platform_mismatch(): void
    {
        Sanctum::actingAs($this->user(), [], 'sanctum');

        $this->postJson('/api/mobile/v1/devices', [
            'push_token' => str_repeat('a', 64),
            'provider' => 'apns',
            'platform' => 'android',
        ])->assertUnprocessable();
    }

    public function test_user_cannot_revoke_another_users_device(): void
    {
        $owner = $this->user();
        $other = $this->user();

        $device = UserDevice::create([
            'user_id' => $owner->id,
            'provider' => 'apns',
            'push_token' => str_repeat('b', 64),
            'platform' => 'ios',
            'last_seen_at' => now(),
        ]);

        Sanctum::actingAs($other, [], 'sanctum');

        $this->deleteJson('/api/mobile/v1/devices/'.$device->id)
            ->assertNotFound();

        $this->assertNull($device->fresh()->revoked_at);
    }

    public function test_fcm_job_sends_notification_with_normalized_data(): void
    {
        $this->configureFcm();

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'fcm-access-token',
                'expires_in' => 3600,
            ]),
            'https://fcm.googleapis.com/*' => Http::response([
                'name' => 'projects/test-project/messages/123',
            ]),
        ]);

        $user = $this->user();

        UserDevice::create([
            'user_id' => $user->id,
            'provider' => 'fcm',
            'push_token' => 'fcm-device-token',
            'platform' => 'android',
            'last_seen_at' => now(),
        ]);

        $notification = NotificationUniversal::create([
            'user_id' => $user->id,
            'module' => 'academic',
            'type' => 'info',
            'action' => 'nilai_published',
            'title' => 'Nilai Matematika dipublikasikan',
            'message' => 'Nilai untuk Aisyah sudah dapat dilihat.',
            'data' => [
                'screen' => 'grades',
                'student_id' => 'student-1',
                'is_new' => true,
            ],
            'priority' => 'high',
        ]);

        (new SendUniversalPush($user, $notification))->handle(app(PushNotificationService::class), app(WebPushService::class));

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return $request->url() === 'https://fcm.googleapis.com/v1/projects/test-project/messages:send'
                && $request->hasHeader('Authorization', 'Bearer fcm-access-token')
                && $payload['message']['token'] === 'fcm-device-token'
                && $payload['message']['notification']['title'] === 'Nilai Matematika dipublikasikan'
                && $payload['message']['data']['screen'] === 'grades'
                && $payload['message']['data']['student_id'] === 'student-1'
                && $payload['message']['data']['is_new'] === '1'
                && $payload['message']['android']['priority'] === 'HIGH';
        });

        $this->assertTrue($notification->fresh()->is_push_sent);
    }

    public function test_apns_job_sends_notification_with_the_same_data_contract(): void
    {
        $this->configureApns();

        Http::fake([
            'https://api.sandbox.push.apple.com/*' => Http::response('', 200),
        ]);

        $user = $this->user();
        $token = str_repeat('a', 64);

        UserDevice::create([
            'user_id' => $user->id,
            'provider' => 'apns',
            'push_token' => $token,
            'platform' => 'ios',
            'last_seen_at' => now(),
        ]);

        $notification = NotificationUniversal::create([
            'user_id' => $user->id,
            'module' => 'academic',
            'type' => 'info',
            'action' => 'nilai_published',
            'title' => 'Nilai Matematika dipublikasikan',
            'message' => 'Nilai untuk Aisyah sudah dapat dilihat.',
            'data' => [
                'screen' => 'grades',
                'student_id' => 'student-1',
            ],
            'priority' => 'high',
        ]);

        (new SendUniversalPush($user, $notification))->handle(app(PushNotificationService::class), app(WebPushService::class));

        Http::assertSent(function ($request) use ($token) {
            $payload = $request->data();

            return $request->url() === "https://api.sandbox.push.apple.com/3/device/{$token}"
                && $request->hasHeader('apns-topic', 'com.alim.app')
                && $request->hasHeader('apns-push-type', 'alert')
                && $request->hasHeader('apns-priority', '10')
                && $payload['aps']['alert']['title'] === 'Nilai Matematika dipublikasikan'
                && $payload['screen'] === 'grades'
                && $payload['student_id'] === 'student-1';
        });

        $this->assertTrue($notification->fresh()->is_push_sent);
    }

    public function test_invalid_fcm_token_is_revoked(): void
    {
        $this->configureFcm();

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'fcm-access-token',
                'expires_in' => 3600,
            ]),
            'https://fcm.googleapis.com/*' => Http::response([
                'error' => [
                    'status' => 'UNREGISTERED',
                    'message' => 'Requested entity was not found.',
                ],
            ], 404),
        ]);

        $user = $this->user();
        $device = UserDevice::create([
            'user_id' => $user->id,
            'provider' => 'fcm',
            'push_token' => 'invalid-fcm-token',
            'platform' => 'android',
            'last_seen_at' => now(),
        ]);

        $notification = NotificationUniversal::create([
            'user_id' => $user->id,
            'module' => 'academic',
            'type' => 'info',
            'action' => 'nilai_published',
            'title' => 'Nilai dipublikasikan',
            'message' => 'Nilai sudah dapat dilihat.',
        ]);

        (new SendUniversalPush($user, $notification))->handle(app(PushNotificationService::class), app(WebPushService::class));

        $this->assertNotNull($device->fresh()->revoked_at);
        $this->assertFalse($notification->fresh()->is_push_sent);
    }

    public function test_invalid_apns_token_is_revoked(): void
    {
        $this->configureApns();

        Http::fake([
            'https://api.sandbox.push.apple.com/*' => Http::response('Unregistered', 410),
        ]);

        $user = $this->user();
        $device = UserDevice::create([
            'user_id' => $user->id,
            'provider' => 'apns',
            'push_token' => str_repeat('c', 64),
            'platform' => 'ios',
            'last_seen_at' => now(),
        ]);

        $notification = NotificationUniversal::create([
            'user_id' => $user->id,
            'module' => 'academic',
            'type' => 'info',
            'action' => 'nilai_published',
            'title' => 'Nilai dipublikasikan',
            'message' => 'Nilai sudah dapat dilihat.',
        ]);

        (new SendUniversalPush($user, $notification))->handle(app(PushNotificationService::class), app(WebPushService::class));

        $this->assertNotNull($device->fresh()->revoked_at);
        $this->assertFalse($notification->fresh()->is_push_sent);
    }

    public function test_push_is_skipped_without_fcm_or_apns_credentials(): void
    {
        Http::fake();

        $user = $this->user();
        UserDevice::create([
            'user_id' => $user->id,
            'provider' => 'fcm',
            'push_token' => 'fcm-device-token',
            'platform' => 'android',
        ]);

        $notification = NotificationUniversal::create([
            'user_id' => $user->id,
            'module' => 'academic',
            'type' => 'info',
            'action' => 'nilai_published',
            'title' => 'Nilai dipublikasikan',
            'message' => 'Nilai sudah dapat dilihat.',
        ]);

        (new SendUniversalPush($user, $notification))->handle(app(PushNotificationService::class), app(WebPushService::class));

        Http::assertNothingSent();
        $this->assertFalse($notification->fresh()->is_push_sent);
    }
}
