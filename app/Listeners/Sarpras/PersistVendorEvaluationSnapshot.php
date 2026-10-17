<?php

namespace App\Listeners\Sarpras;

use App\Events\Sarpras\VendorEvaluationCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class PersistVendorEvaluationSnapshot implements ShouldQueue
{
    public function handle(VendorEvaluationCompleted $event): void
    {
        Log::info('Vendor evaluation completed', [
            'vendor_id' => $event->vendor->id,
            'grade' => $event->evaluation->grade,
            'on_time_pct' => $event->evaluation->on_time_pct,
        ]);
    }
}
