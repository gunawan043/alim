<?php

namespace Tests\Feature\Sarpras;

use App\Jobs\SendUniversalWhatsApp;
use App\Models\CleaningArea;
use App\Models\NotificationPreference;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Sarpras\Automation\SarprasNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Penugasan Sarpras (work order perbaikan, area kebersihan) wajib masuk
 * WhatsApp — bukan hanya lonceng in-app.
 */
class SarprasWhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_assignment_notification_is_delivered_via_whatsapp(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'is_active' => true,
            'no_hp' => '081234567890',
        ]);

        app(SarprasNotificationService::class)->deliver($user->id, [
            'event_type' => 'WorkOrderAssigned',
            'module' => 'sarpras',
            'type' => 'info',
            'title' => 'Work Order baru: WO-001',
            'message' => 'Anda ditugaskan pada work order WO-001.',
            'action_url' => '/'.$user->id.'/sarpras/teknisi',
        ]);

        $this->assertDatabaseHas('notifications_universal', [
            'user_id' => $user->id,
            'action' => 'WorkOrderAssigned',
        ]);

        Queue::assertPushed(SendUniversalWhatsApp::class);
    }

    public function test_whatsapp_is_skipped_when_receiver_has_no_phone_number(): void
    {
        Queue::fake();

        $user = User::factory()->create(['is_active' => true, 'no_hp' => null]);

        app(SarprasNotificationService::class)->deliver($user->id, [
            'event_type' => 'WorkOrderAssigned',
            'module' => 'sarpras',
            'title' => 'Work Order baru',
            'message' => 'Anda ditugaskan pada work order.',
        ]);

        $this->assertDatabaseHas('notifications_universal', ['user_id' => $user->id]);
        Queue::assertNotPushed(SendUniversalWhatsApp::class);
    }

    public function test_whatsapp_is_skipped_when_global_toggle_is_off(): void
    {
        Queue::fake();

        SystemSetting::set('whatsapp_notifications', '0', 'notifications');
        Cache::forget('system_settings_map');
        Cache::forget('system_settings');

        $user = User::factory()->create([
            'is_active' => true,
            'no_hp' => '081234567890',
        ]);

        app(SarprasNotificationService::class)->deliver($user->id, [
            'event_type' => 'WorkOrderAssigned',
            'module' => 'sarpras',
            'title' => 'Work Order baru',
            'message' => 'Anda ditugaskan pada work order.',
        ]);

        Queue::assertNotPushed(SendUniversalWhatsApp::class);
    }

    public function test_cleaning_area_assignment_dispatches_whatsapp(): void
    {
        Queue::fake([SendUniversalWhatsApp::class]);

        $admin = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
            'no_hp' => '081234567890',
        ]);

        $this->actingAs($admin)
            ->post("/{$admin->id}/sarpras/kebersihan/area", [
                'name' => 'Koridor Gedung B',
                'area_type' => 'jalan',
            ])
            ->assertRedirect();

        $area = CleaningArea::where('name', 'Koridor Gedung B')->firstOrFail();

        $this->actingAs($admin)
            ->post("/{$admin->id}/sarpras/kebersihan/area/{$area->id}/assignment", [
                'user_id' => $admin->id,
                'role' => 'petugas',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cleaning_assignments', [
            'area_id' => $area->id,
            'user_id' => $admin->id,
        ]);

        Queue::assertPushed(SendUniversalWhatsApp::class);
    }

    public function test_user_can_opt_out_of_whatsapp_for_assignments(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'is_active' => true,
            'no_hp' => '081234567890',
        ]);

        NotificationPreference::create([
            'user_id' => $user->id,
            'email_enabled' => true,
            'whatsapp_enabled' => false,
            'push_enabled' => true,
        ]);

        app(SarprasNotificationService::class)->deliver($user->id, [
            'event_type' => 'WorkOrderAssigned',
            'module' => 'sarpras',
            'title' => 'Work Order baru',
            'message' => 'Anda ditugaskan pada work order.',
        ]);

        Queue::assertNotPushed(SendUniversalWhatsApp::class);
    }
}
