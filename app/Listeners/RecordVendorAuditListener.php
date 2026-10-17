<?php

namespace App\Listeners;

use App\Events\VendorAuditRecorded;
use Illuminate\Contracts\Queue\ShouldQueue;

class RecordVendorAuditListener implements ShouldQueue
{
    public function handle(VendorAuditRecorded $event): void
    {
        // Persisted synchronously via AuditTrailService::record()
        // Listener exposes a decoupled hook for downstream notification/reporting
    }
}
