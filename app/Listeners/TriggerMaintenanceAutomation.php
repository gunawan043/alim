<?php

namespace App\Listeners;

use App\Events\AssetLifecycleEvent;
use App\Models\MaintenenceSchedule;
use App\Services\Sarpras\AutomationSuggestionService;
use Illuminate\Support\Facades\Log;

/**
 * When a repair is completed, automatically schedule the next
 * preventive maintenance for the asset.
 */
class TriggerMaintenanceAutomation
{
    public function __construct(protected AutomationSuggestionService $automation) {}

    public function handle(AssetLifecycleEvent $event): void
    {
        if ($event->eventType !== 'repair_completed') {
            return;
        }

        try {
            $suggestion = $this->automation->suggestMaintenanceSchedule($event->asset);
            if (! $suggestion) {
                return;
            }

            $next = $suggestion['next_due'];
            $intervalDays = (int) ($suggestion['interval_days'] ?? 90);

            MaintenenceSchedule::firstOrCreate(
                [
                    'asset_id' => $event->asset->id,
                    'scheduled_date' => $next,
                    'status' => 'pending',
                ],
                [
                    'maintenance_type' => 'preventive',
                    'priority' => 'medium',
                    'description' => $suggestion['description'] ?? 'Auto-generated preventive maintenance after repair.',
                ]
            );

            Log::info('auto_maintenance_scheduled', [
                'asset_id' => $event->asset->id,
                'next_due' => $next,
                'interval_days' => $intervalDays,
            ]);
        } catch (\Throwable $e) {
            Log::warning('auto_maintenance_failed', [
                'asset_id' => $event->asset->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
