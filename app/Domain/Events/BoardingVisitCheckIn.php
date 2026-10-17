<?php

namespace App\Domain\Events;

use App\Models\DormitoryVisitLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BoardingVisitCheckIn
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly DormitoryVisitLog $visit) {}
}
