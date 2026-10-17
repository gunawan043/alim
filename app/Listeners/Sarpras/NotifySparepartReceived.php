<?php

namespace App\Listeners\Sarpras;

use App\Events\Sarpras\SparepartReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifySparepartReceived implements ShouldQueue
{
    public function handle(SparepartReceived $event): void
    {
        Log::info('Sparepart received', [
            'sparepart_id' => $event->sparepart->id,
            'part_number' => $event->sparepart->part_number,
            'quantity' => $event->movement->quantity,
            'movement_code' => $event->movement->movement_code,
            'receiver_id' => $event->receiver->id,
        ]);
    }
}
