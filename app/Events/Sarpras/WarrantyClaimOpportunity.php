<?php

namespace App\Events\Sarpras;

use App\Models\VendorWarranty;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WarrantyClaimOpportunity
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly VendorWarranty $warranty,
        public readonly string $priority,
    ) {}
}