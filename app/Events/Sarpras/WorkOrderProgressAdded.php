<?php

namespace App\Events\Sarpras;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderProgress;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkOrderProgressAdded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly WorkOrder $workOrder,
        public readonly WorkOrderProgress $progress,
        public readonly User $author,
    ) {}
}