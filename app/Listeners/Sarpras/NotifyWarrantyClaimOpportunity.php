<?php

namespace App\Listeners\Sarpras;

use App\Events\Sarpras\WarrantyClaimOpportunity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifyWarrantyClaimOpportunity implements ShouldQueue
{
    public function handle(WarrantyClaimOpportunity $event): void
    {
        Log::info('Warranty claim opportunity', [
            'warranty_id' => $event->warranty->id,
            'asset_id' => $event->warranty->asset_id,
            'vendor_id' => $event->warranty->vendor_id,
            'priority' => $event->priority,
            'days_to_expiry' => $event->warranty->daysToExpiry(),
        ]);
    }
}