<?php

namespace App\Listeners;

use App\Events\AssetLifecycleEvent;
use App\Models\AssetEventLog;

class PersistAssetEventLog
{
    public function handle(AssetLifecycleEvent $event): void
    {
        AssetEventLog::create([
            'asset_id' => $event->asset->id,
            'event_type' => $event->eventType,
            'event_detail' => $event->detail,
            'actor_id' => $event->actorId ?? auth()->id(),
        ]);
    }
}
