<?php

namespace App\Events\Sarpras;

use App\Models\Asset;
use App\Models\MaintenanceHistory;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MaintenanceOverdue
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Asset $asset,
        public readonly MaintenanceHistory $history,
        public readonly int $overdueDays,
    ) {}
}
