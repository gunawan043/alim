<?php

namespace App\Observers;

use App\Models\AssetRoom;
use App\Models\StudyGroup;
use App\Services\Academic\RombelSubjectProvisioner;
use App\Services\Academic\StudyGroupRoomService;

class StudyGroupObserver
{
    public function __construct(
        private readonly StudyGroupRoomService $roomService,
        private readonly RombelSubjectProvisioner $subjects,
    ) {
    }

    /**
     * Setiap rombel baru langsung membuat ruang kelas di Sarpras dan
     * menyalin struktur mapel dari tingkatnya.
     * Tidak lagi bergantung pada kolom `room` karena nama ruang mengikuti
     * nama rombel.
     */
    public function created(StudyGroup $studyGroup): void
    {
        $this->roomService->sync($studyGroup);
        $this->subjects->provision($studyGroup);
    }

    public function updated(StudyGroup $studyGroup): void
    {
        if ($studyGroup->wasChanged(['grade_level_id', 'academic_year_id', 'peminatan'])) {
            // Rombel berpindah tingkat/TA/jurusan: susun ulang mapel & buku
            // nilai mengikuti struktur tingkat dan jurusan barunya.
            $this->subjects->provision($studyGroup);
        }

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
