<?php

namespace App\Listeners;

use App\Events\VendorRated;
use App\Services\Vendor\AuditTrailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;

class RecordVendorRatingTransition implements ShouldQueue
{
    public string $queue = 'vendor-events';

    public function __construct(protected AuditTrailService $auditTrail) {}

    public function handle(VendorRated $event): void
    {
        $vendor = $event->vendor;
        $perf = $event->performance;

        $this->auditTrail->recordAction(
            entityType: 'vendor',
            entityId: $vendor->id,
            action: 'rated',
            payload: [
                'score' => $perf->score ?? null,
                'rating_period' => $perf->period ?? null,
            ],
        );

        Cache::tags(['vendors', "vendor:{$vendor->id}"])->flush();
    }
}
