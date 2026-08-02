<?php

namespace App\Events\Sarpras;

use App\Models\Asset;
use App\Models\QrScanHistory;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssetQrScanned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Asset $asset,
        public readonly QrScanHistory $scan,
        public readonly User $scanner,
    ) {}
}
