<?php

namespace App\Events\Sarpras;

use App\Models\RepairRequest;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RepairRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly RepairRequest $repair,
        public readonly User $rejecter,
        public readonly string $reason,
    ) {}
}