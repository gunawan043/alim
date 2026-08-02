<?php

namespace App\Observers;

use App\Models\AssetRoom;
use App\Models\StudyGroup;

class StudyGroupObserver
{
    /**
     * Auto-sync: setiap StudyGroup dibuat → buat AssetRoom dengan room_type='kelas'
     */
    public function created(StudyGroup $studyGroup): void
    {
        if (blank($studyGroup->room)) {
            return;
        }

        $this->createOrUpdateAssetRoom($studyGroup);
    }

    /**
     * Auto-sync: setiap StudyGroup diupdate → update AssetRoom terkait
     */
    public function updated(StudyGroup $studyGroup): void
    {
        $wasFilled = filled($studyGroup->getOriginal('room'));
        $isFilled = filled($studyGroup->room);

        // room di-nullify → hapus AssetRoom terkait
        if ($wasFilled && ! $isFilled) {
            AssetRoom::where('study_group_id', $studyGroup->id)->delete();

            return;
        }

        // room baru atau berubah → upsert AssetRoom
        if ($isFilled) {
            $this->createOrUpdateAssetRoom($studyGroup);
        }
    }

    /**
     * Auto-sync: setiap StudyGroup dihapus → hapus AssetRoom terkait
     */
    public function deleted(StudyGroup $studyGroup): void
    {
        AssetRoom::where('study_group_id', $studyGroup->id)->delete();
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function createOrUpdateAssetRoom(StudyGroup $studyGroup): void
    {
        if (! $studyGroup->school_id) {
            return;
        }

        $school = $studyGroup->school;
        $roomName = $studyGroup->full_name; // "X IPA 1" dst.

        $data = [
            'school_id' => $studyGroup->school_id,
            'work_unit_id' => $school?->work_unit_id,
            'study_group_id' => $studyGroup->id,
            'room_name' => $roomName,
            'room_type' => 'kelas',
            'capacity' => $studyGroup->capacity,
            'condition' => 'baik',
            'is_bookable' => false,
            'is_active' => $studyGroup->is_active,
            'notes' => 'Ruang Kelas — Rombongan Belajar',
        ];

        AssetRoom::updateOrCreate(
            ['study_group_id' => $studyGroup->id],
            $data
        );
    }
}
