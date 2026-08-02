<?php

namespace App\Events\Sarpras;

use App\Models\Sparepart;
use App\Models\SparepartStockMovement;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SparepartReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Sparepart $sparepart,
        public readonly SparepartStockMovement $movement,
        public readonly User $receiver,
    ) {}
}
