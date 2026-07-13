<?php

namespace App\Listeners;

use App\Events\AssetLifecycleEvent;
use Illuminate\Support\Facades\Log;

/**
 * For critical asset events (damage reports, status changes to "broken/disposed"),
 * dispatch a notification to admins.
 *
 * The implementation logs as a queued notification; the actual
 * Notification model can be replaced or routed through channels.
 */
class NotifyOnCriticalAssetEvents
{
    public function handle(AssetLifecycleEvent $event): void
    {
        $critical = ['damage_reported', 'asset_status_changed', 'repair_rejected'];

        if (! in_array($event->eventType, $critical, true)) {
            return;
        }

        try {
            // Higher-priority for asset_status_changed to "broken" or worse
            $priority = match (true) {
                $event->eventType === 'damage_reported'                       => 'high',
                ($event->detail['to'] ?? null) === 'broken'                   => 'high',
                ($event->detail['to'] ?? null) === 'disposed'                 => 'critical',
                default                                                       => 'normal',
            };

            Log::channel('stack')->info('asset_critical_notification', [
                'asset_id'   => $event->asset->id,
                'asset_code' => $event->asset->asset_code,
                'event'      => $event->eventType,
                'priority'   => $priority,
                'detail'     => $event->detail,
                'actor_id'   => $event->actorId,
            ]);
        } catch (\Throwable $e) {
            Log::warning('asset_notification_failed', [
                'asset_id' => $event->asset->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}