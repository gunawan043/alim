<?php

namespace App\Events;

use App\Models\VendorAuditTrail;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VendorAuditRecorded
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public VendorAuditTrail $audit
    ) {}
}
