<?php

namespace App\Events;

use App\Models\Asset;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssetLifecycleEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Asset $asset,
        public string $eventType,
        public array $detail = [],
        public ?int $actorId = null
    ) {}

    public static function dispatched(Asset $asset, array $detail = [], ?int $actorId = null): void
    {
        event(new self($asset, 'dispatched', $detail, $actorId));
    }
}
