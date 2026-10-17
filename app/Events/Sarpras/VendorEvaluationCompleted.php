<?php

namespace App\Events\Sarpras;

use App\Models\Vendor;
use App\Models\VendorEvaluation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VendorEvaluationCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Vendor $vendor,
        public readonly VendorEvaluation $evaluation,
    ) {}
}
