<?php

namespace App\Services\Academic;

use App\Models\AssetRoom;
use App\Models\School;
use App\Models\StudyGroup;

/**
 * Sinkronisasi rombel → ruang Sarpras.
 *
 * Satu rombel memiliki satu AssetRoom bertipe kelas. Nama ruang selalu
 * mengikuti nama rombel, sehingga perubahan nama rombel ikut memperbarui
 * ruang tanpa memutus relasi aset yang sudah tercatat di ruang tersebut.
 */
class StudyGroupRoomService
{
    public function sync(StudyGroup $studyGroup): ?AssetRoom
    {
        if (! $studyGroup->school_id) {
            return null;
        }

        $school = School::find($studyGroup->school_id);
        if (! $school?->work_unit_id) {
            return null;
        }

        return AssetRoom::updateOrCreate(
            ['study_group_id' => $studyGroup->id],
            [
                'school_id' => $studyGroup->school_id,
                'work_unit_id' => $school->work_unit_id,
                'room_name' => $studyGroup->full_name ?: $studyGroup->name,
                'room_type' => 'kelas',
                'capacity' => $studyGroup->capacity,
                'condition' => 'baik',
                'is_bookable' => false,
                'booking_requires_approval' => true,
                'responsible_user_id' => $studyGroup->homeroom_teacher_id,
                'is_active' => $studyGroup->is_active,
                'notes' => 'Ruang Kelas — Rombongan Belajar',
            ],
        );
    }

    /**
     * Backfill/repair semua rombel yang belum punya ruang.
     *
     * @return array{created:int,updated:int}
     */
    public function syncAll(?string $schoolId = null): array
    {
        $created = 0;
        $updated = 0;

        StudyGroup::withoutGlobalScope('school_context')
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->orderBy('id')
            ->chunkById(100, function ($groups) use (&$created, &$updated) {
                foreach ($groups as $group) {
                    $exists = AssetRoom::where('study_group_id', $group->id)->exists();
                    $room = $this->sync($group);

                    if (! $room) {
                        continue;
                    }

                    $exists ? $updated++ : $created++;
                }
            });

        return ['created' => $created, 'updated' => $updated];
    }
}
