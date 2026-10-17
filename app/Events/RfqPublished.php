<?php

namespace App\Events;

use App\Models\RfqRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RfqPublished
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public RfqRequest $rfq) {}
}
