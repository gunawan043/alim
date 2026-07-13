<?php

namespace App\Events\Sarpras;

use App\Models\RepairCostHistory;
use App\Models\WorkOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RepairCostRecorded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly WorkOrder $workOrder,
        public readonly RepairCostHistory $cost,
    ) {}
}