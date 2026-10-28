<?php

namespace App\Observers;

use App\Models\AssetRoom;
use App\Models\StudyGroup;
use App\Services\Academic\StudyGroupRoomService;

class StudyGroupObserver
{
    public function __construct(private readonly StudyGroupRoomService $roomService)
    {
    }

    /**
     * Setiap rombel baru langsung membuat ruang kelas di Sarpras.
     * Tidak lagi bergantung pada kolom `room` karena nama ruang mengikuti
     * nama rombel.
     */
    public function created(StudyGroup $studyGroup): void
    {
        $this->roomService->sync($studyGroup);
    }

    public function updated(StudyGroup $studyGroup): void
    {
        if (! $studyGroup->wasChanged([
            'school_id',
            'name',
            'capacity',
            'homeroom_teacher_id',
            'is_active',
        ])) {
            return;
        }

        $this->roomService->sync($studyGroup);
    }

    public function deleted(StudyGroup $studyGroup): void
    {
        AssetRoom::where('study_group_id', $studyGroup->id)->delete();
    }
}
