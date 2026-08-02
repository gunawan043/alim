<?php

namespace App\Events\Sarpras;

use App\Models\RepairRequest;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RepairApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly RepairRequest $repair,
        public readonly User $approver,
    ) {}
}
