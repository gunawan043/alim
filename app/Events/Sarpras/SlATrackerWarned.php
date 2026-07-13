<?php

namespace App\Events\Sarpras;

use App\Models\SlaTracker;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SlATrackerWarned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly SlaTracker $tracker,
        public readonly int $remainingMinutes,
    ) {}
}