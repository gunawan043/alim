<?php

namespace App\Services;

use App\Models\GtkEmployment;
use App\Models\GtkWorkUnit;
use App\Models\InstitutionDecree;
use App\Models\School;
use App\Models\StructuralPosition;

/**
 * Menyimpan isi SK Pembagian Tugas dari tiga payload JSON yang dikirim
 * halaman institution-decrees/create (assignments_json, other_tasks_json,
 * positions_json). Dipakai controller dan perintah import agar hasilnya sama.
 */
class InstitutionDecreePayloadService
{
    /**
     * @return array{assignments:int,tasks:int,skipped:int,books:int,messages:array}
     */
    public function saveMatrix(InstitutionDecree $decree, array $assignments, array $tasks, ?string $schoolId): array
    {
        $result = app(InstitutionDecreeMatrixService::class)
            ->sync($decree, $assignments, $tasks, $schoolId, true);

        return [
            'assignments' => $result['assignments_created'] + $result['assignments_updated'],
            'tasks' => $result['tasks_created'] + $result['tasks_updated'],
            'skipped' => $result['assignments_skipped'] + $result['tasks_skipped'],
            'books' => $result['books_created'] ?? 0,
            'messages' => $result['messages'] ?? [],
        ];
    }

    /**
     * Jabatan struktural per guru (positions_json).
     */
    public function savePositions(array $positions, ?string $schoolId): int
    {
        if (empty($positions) || ! $schoolId) {
            return 0;
        }

        $teacherIds = array_keys($positions);
        $workUnitId = School::find($schoolId)?->work_unit_id;

        $workUnitRows = GtkWorkUnit::whereIn('user_id', $teacherIds)
            ->when($workUnitId, fn ($q) => $q->where('work_unit_id', $workUnitId))
            ->get()
            ->keyBy('user_id');

        $employments = GtkEmployment::whereIn('user_id', $teacherIds)
            ->where('school_id', $schoolId)
            ->get()
            ->keyBy('user_id');

        $updated = 0;

        foreach ($positions as $teacherId => $positionId) {
            $position = $positionId ? StructuralPosition::find($positionId) : null;
            $positionName = $position?->name;

            $workUnitRow = $workUnitRows->get($teacherId);
            if ($workUnitRow) {
                $workUnitRow->update(['jabatan' => $positionName]);
                $updated++;

                continue;
            }

            $employment = $employments->get($teacherId);
            if (! $employment) {
                continue;
            }

            $employment->update([
                'jabatan_id' => $position?->id,
                'jabatan' => $positionName,
            ]);
            $updated++;
        }

        return $updated;
    }
}
