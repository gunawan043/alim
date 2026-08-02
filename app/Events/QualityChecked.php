<?php

namespace App\Events;

use App\Models\QualityCheck;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QualityChecked
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public QualityCheck $qualityCheck) {}
}
