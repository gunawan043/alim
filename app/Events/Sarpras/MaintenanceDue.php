<?php

namespace App\Events\Sarpras;

use App\Models\Asset;
use App\Models\MaintenanceHistory;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MaintenanceDue
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Asset $asset,
        public readonly MaintenanceHistory $history,
    ) {}
}