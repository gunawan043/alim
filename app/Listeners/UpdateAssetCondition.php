<?php

namespace App\Listeners;

use App\Events\AssetLifecycleEvent;
use App\Models\MaintenanceHistory;
use Carbon\Carbon;

class UpdateAssetCondition
{
    public function handle(AssetLifecycleEvent $event): void
    {
        if (! in_array($event->eventType, ['maintenance_completed', 'repair_completed'])) {
            return;
        }

        $detail = $event->detail;
        $condition = $detail['condition_after'] ?? $event->asset->condition;

        if ($condition && $condition !== $event->asset->condition) {
            $event->asset->update([
                'condition' => $condition,
                'last_condition_update' => Carbon::now(),
            ]);
        }

        // Save maintenance history
        MaintenanceHistory::create([
            'asset_id' => $event->asset->id,
            'maintenance_type' => $event->eventType === 'maintenance_completed' ? 'preventive' : 'corrective',
            'performed_date' => $detail['performed_date'] ?? Carbon::today(),
            'performed_by_name' => $detail['performed_by'] ?? '',
            'condition_before' => $detail['condition_before'] ?? null,
            'condition_after' => $condition,
            'work_description' => $detail['work_description'] ?? null,
            'cost' => $detail['cost'] ?? 0,
            'next_due_date' => $detail['next_due_date'] ?? null,
        ]);
    }
}
