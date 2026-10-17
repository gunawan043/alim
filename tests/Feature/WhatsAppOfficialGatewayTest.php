<?php

namespace Tests\Feature;

use App\Http\Controllers\NotificationPreferenceController;
use App\Models\NotificationPreference;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Notifications\WhatsAppGatewayService;
use App\Services\NotificationUniversalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * WhatsApp Cloud API resmi (Meta), webhook, dan preferensi notifikasi.
 */
class WhatsAppOfficialGatewayTest extends TestCase
{
    use RefreshDatabase;

    private function configureMeta(bool $template = true): void
    {
        SystemSetting::set('wa_enabled', '1', 'notifications');
        SystemSetting::set('wa_provider', 'meta', 'notifications');
        SystemSetting::set('wa_meta_phone_number_id', '123456789012345', 'notifications');
        SystemSetting::set('wa_meta_access_token', 'EAAG-test-token', 'notifications');
        SystemSetting::set('wa_meta_api_version', 'v20.0', 'notifications');
        SystemSetting::set('wa_meta_template_enabled', $template ? '1' : '0', 'notifications');
        SystemSetting::set('wa_meta_template_name', 'alim_lesson_reminder', 'notifications');
        SystemSetting::set('wa_meta_template_language', 'id', 'notifications');
        Cache::forget('system_settings_map');
    }

    public function test_meta_cloud_api_mengirim_template_resmi(): void
    {
        $this->configureMeta(true);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.TEST-123']],
            ], 200),
        ]);

        $gateway = app(WhatsAppGatewayService::class);

        $sent = $gateway->send('08123456789', 'Pengingat Matematika 7A pukul 07:00', [
            'event' => 'lesson_reminder',
            'template_params' => ['Matematika', '7A', '07:00'],
        ]);

        $this->assertTrue($sent);
        $this->assertSame('wamid.TEST-123', $gateway->lastProviderMessageId());

        Http::assertSent(function ($request) {
            $data = $request->data();

            return str_contains($request->url(), '/123456789012345/messages')
                && $request->hasHeader('Authorization', 'Bearer EAAG-test-token')
                && ($data['messaging_product'] ?? null) === 'whatsapp'
                && ($data['to'] ?? null) === '628123456789'
                && ($data['type'] ?? null) === 'template'
                && ($data['template']['name'] ?? null) === 'alim_lesson_reminder'
                && ($data['template']['language']['code'] ?? null) === 'id'
                && count($data['template']['components'][0]['parameters'] ?? []) === 3;
        });

        $this->assertDatabaseHas('wa_message_logs', [
            'provider' => 'meta',
            'provider_message_id' => 'wamid.TEST-123',
            'status' => 'sent',
        ]);
    }

    public function test_meta_cloud_api_fallback_pesan_teks(): void
    {
        $this->configureMeta(false);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.TEXT']]], 200),
        ]);

        $sent = app(WhatsAppGatewayService::class)->send('08123456789', 'Pesan teks biasa');

        $this->assertTrue($sent);

        Http::assertSent(fn ($request) => ($request->data()['type'] ?? null) === 'text'
            && ($request->data()['text']['body'] ?? null) === 'Pesan teks biasa');
    }

    public function test_webhook_verifikasi_dan_status_pengiriman(): void
    {
        SystemSetting::set('wa_meta_verify_token', 'verify-secret', 'notifications');
        Cache::forget('system_settings_map');

        $this->get('/webhook/whatsapp?hub_mode=subscribe&hub_verify_token=verify-secret&hub_challenge=abc123')
            ->assertOk()
            ->assertSee('abc123');

        $this->get('/webhook/whatsapp?hub_mode=subscribe&hub_verify_token=salah&hub_challenge=abc123')
            ->assertForbidden();

        DB::table('wa_message_logs')->insert([
            'id' => (string) Str::uuid(),
            'phone' => '628123456789',
            'provider' => 'meta',
            'provider_message_id' => 'wamid.STATUS-1',
            'status' => 'sent',
            'message' => 'Tes',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/webhook/whatsapp', [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'statuses' => [[
                            'id' => 'wamid.STATUS-1',
                            'status' => 'failed',
                            'errors' => [['code' => 131047, 'title' => 'Re-engagement']],
                        ]],
                    ],
                ]],
            ]],
        ])->assertOk();

        $this->assertDatabaseHas('wa_message_logs', [
            'provider_message_id' => 'wamid.STATUS-1',
            'status' => 'failed',
        ]);
    }

    public function test_matriks_channel_global_menonaktifkan_email_dan_wa(): void
    {
        $user = User::create([
            'name' => 'Guru Matriks', 'email' => 'guru.matriks@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        SystemSetting::set('notification_channel_matrix', json_encode([
            'teacher_late' => ['system' => true, 'email' => false, 'wa' => false, 'push' => false],
        ]), 'notifications');
        Cache::forget('system_settings_map');

        Queue::fake();
        Http::fake();

        $notification = app(NotificationUniversalService::class)->send($user->id, [
            'module' => 'teacher-attendance',
            'action' => 'teacher_late',
            'type' => 'warning',
            'title' => 'Terlambat',
            'message' => 'Anda terlambat 5 menit.',
            'send_email' => true,
            'send_whatsapp' => true,
            'send_push' => true,
        ]);

        $this->assertNotNull($notification);
        $this->assertFalse((bool) $notification->is_archived);
        Queue::assertNothingPushed();
        Http::assertNothingSent();

        // Channel Sistem dimatikan → notifikasi diarsipkan (tidak tampil di lonceng).
        SystemSetting::set('notification_channel_matrix', json_encode([
            'teacher_late' => ['system' => false, 'email' => true, 'wa' => false, 'push' => false],
        ]), 'notifications');
        Cache::forget('system_settings_map');

        $archived = app(NotificationUniversalService::class)->send($user->id, [
            'module' => 'teacher-attendance',
            'action' => 'teacher_late',
            'type' => 'warning',
            'title' => 'Terlambat lagi',
            'message' => 'Anda terlambat 7 menit.',
        ]);

        $this->assertTrue((bool) $archived->is_archived);
        $this->assertNotNull($archived->archived_at);
    }

    public function test_preferensi_notifikasi_dapat_diubah(): void
    {
        $user = User::create([
            'name' => 'Guru Preferensi',
            'email' => 'guru.pref@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $request = Request::create('/notifikasi/preferensi', 'PUT', [
            'email_enabled' => 1,
            'whatsapp_enabled' => 0,
            'push_enabled' => 1,
            'lesson_reminder' => 1,
            'late_notification' => 0,
        ]);
        $request->setUserResolver(fn () => $user);

        app(NotificationPreferenceController::class)->update($request, $user->id);

        $prefs = NotificationPreference::forUser($user->fresh());

        $this->assertTrue($prefs->email_enabled);
        $this->assertFalse($prefs->whatsapp_enabled);
        $this->assertTrue($prefs->push_enabled);
        $this->assertTrue($prefs->lesson_reminder);
        $this->assertFalse($prefs->late_notification);
    }
}
