<?php

namespace App\Events\Sarpras;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssetMoved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Asset $asset,
        public readonly User $mover,
        public readonly ?string $fromLocation,
        public readonly string $toLocation,
    ) {}
}