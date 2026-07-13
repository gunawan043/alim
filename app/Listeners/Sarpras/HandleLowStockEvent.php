<?php

namespace App\Listeners\Sarpras;

use App\Events\Sarpras\LowStockDetected;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class HandleLowStockEvent implements ShouldQueue
{
    public function handle(LowStockDetected $event): void
    {
        Log::warning('Low stock detected', [
            'sparepart_id' => $event->sparepart->id,
            'part_number' => $event->sparepart->part_number,
            'current_stock' => (float) $event->sparepart->stock,
            'reorder_point' => (float) $event->sparepart->reorder_point,
            'recommended_quantity' => $event->recommendedQuantity,
        ]);
    }
}