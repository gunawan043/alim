<?php

namespace App\Domain\Events;

use App\Models\DormitoryPermit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BoardingPermitSubmitted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly DormitoryPermit $permit) {}
}
