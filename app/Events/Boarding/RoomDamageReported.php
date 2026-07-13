<?php

namespace App\Events\Boarding;

use App\Models\DormitoryRoom;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a room damage is reported. Triggers a Sarpras
 * maintenance ticket creation through an integration listener.
 */
class RoomDamageReported
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public DormitoryRoom $room,
        public string $damageType,
        public string $description,
        public ?string $reporterId = null,
        public int $severity = 1,
    ) {}
}