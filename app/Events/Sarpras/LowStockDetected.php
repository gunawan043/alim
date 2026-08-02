<?php

namespace App\Events\Sarpras;

use App\Models\Sparepart;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowStockDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Sparepart $sparepart,
        public readonly float $recommendedQuantity,
    ) {}
}
