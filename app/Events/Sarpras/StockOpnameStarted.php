<?php

namespace App\Events\Sarpras;

use App\Models\StockOpnameSession;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockOpnameStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly StockOpnameSession $session,
        public readonly User $organizer,
    ) {}
}