<?php

namespace App\Events\Sarpras;

use App\Models\Asset;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WarrantyExpired
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Asset $asset,
        public readonly int $daysUntilExpiry,
    ) {}
}
