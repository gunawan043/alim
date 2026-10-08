<?php

namespace App\Services;

use App\Models\GradeLevelSubject;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Models\Subject;

/**
 * Resolver JP per minggu — SATU sumber aturan untuk seluruh modul
 * (Jadwal KBM, Pekan Efektif/JP Efektif, ATP, Perangkat).
 *
 * Urutan otoritatif:
 *   teaching_assignments.weekly_hours
 *   → study_group_subjects.weekly_hours
 *   → grade_level_subjects.allocation_hours
 *   → subjects.credit_hours
 *   → default 2 JP.
 */
class TeachingHoursResolver
{
    /** Default JP per minggu bila seluruh sumber kosong. */
    public const DEFAULT_WEEKLY_HOURS = 2;

    /**
     * @param  int|float|string|null  $assignmentHours  JP dari teaching_assignments (bila ada)
     * @param  string|null  $gradeLevelId  Jenjang (bila tidak dipanggil dari konteks kelas)
     */
    public function resolve(
        ?StudyGroup $studyGroup,
        ?Subject $subject,
        int|float|string|null $assignmentHours = null,
        ?string $gradeLevelId = null,
    ): int {
        $hours = (int) $assignmentHours;
        if ($hours > 0) {
            return $hours;
        }

        if (! $subject) {
            return self::DEFAULT_WEEKLY_HOURS;
        }

        if ($studyGroup) {
            $hours = (int) StudyGroupSubject::query()
                ->where('study_group_id', $studyGroup->id)
                ->where('subject_id', $subject->id)
                ->where('is_active', true)
                ->value('weekly_hours');

            if ($hours > 0) {
                return $hours;
            }
        }

        $gradeLevelId = $gradeLevelId ?: $studyGroup?->grade_level_id;

        if ($gradeLevelId) {
            $hours = (int) GradeLevelSubject::query()
                ->where('grade_level_id', $gradeLevelId)
                ->where('subject_id', $subject->id)
                ->where('is_active', true)
                ->value('allocation_hours');

            if ($hours > 0) {
                return $hours;
            }
        }

        $credit = (int) ($subject->credit_hours ?? 0);

        return $credit > 0 ? $credit : self::DEFAULT_WEEKLY_HOURS;
    }
}
