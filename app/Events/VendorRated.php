<?php

namespace App\Events;

use App\Models\Vendor;
use App\Models\VendorPerformance;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VendorRated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Vendor $vendor,
        public VendorPerformance $performance,
    ) {}
}
