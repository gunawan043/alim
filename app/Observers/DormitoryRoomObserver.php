<?php

namespace App\Observers;

use App\Models\AssetRoom;
use App\Models\DormitoryRoom;
use App\Services\Asrama\DormitoryRoomAssetService;

/**
 * Setiap kamar asrama otomatis tercatat sebagai ruang di Sarpras —
 * perilaku yang sama dengan rombel → ruang kelas.
 */
class DormitoryRoomObserver
{
    public function __construct(private readonly DormitoryRoomAssetService $roomAssetService) {}

    public function created(DormitoryRoom $room): void
    {
        $this->roomAssetService->sync($room);
    }

    public function updated(DormitoryRoom $room): void
    {
        if (! $room->wasChanged([
            'dormitory_id',
            'wing_id',
            'code',
            'name',
            'floor',
            'capacity',
            'is_active',
        ])) {
            return;
        }

        $this->roomAssetService->sync($room);
    }

    public function deleted(DormitoryRoom $room): void
    {
        AssetRoom::where('dormitory_room_id', $room->id)->delete();
    }
}
