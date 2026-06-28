<?php

namespace App\Services;

use App\Models\JadwalKbm;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\SubjectKktp;
use App\Models\TeachingAssignment;
use Illuminate\Support\Collection;

class JadwalGeneratorService
{
    public const MAX_PERIODS_PER_DAY = 12;

    public const DAYS_OF_WEEK = [1, 2, 3, 4, 5, 6];

    public const DEFAULT_PERIOD_MINUTES = 45;

    public const DEFAULT_START_HOUR = 7;

    public const DEFAULT_BREAK_AFTER = 4;

    public const DEFAULT_BREAK_MINUTES = 30;

    /**
     * Generate jadwal for multiple study groups.
     * Strategy: place heaviest subjects first, then iterate days×slots to find
     * slot that doesn't conflict with guru or rombel.
     */
    public function generateBulk(array $studyGroupIds, string $academicYearId, string $semester): Collection
    {
        $results = collect();

        foreach ($studyGroupIds as $sgId) {
            try {
                $created = $this->generateForStudyGroup($sgId, $academicYearId, $semester);
                $results->push([
                    'study_group_id' => $sgId,
                    'generated' => $created->count(),
                    'conflicts' => null,
                ]);
            } catch (\Throwable $e) {
                $results->push([
                    'study_group_id' => $sgId,
                    'generated' => 0,
                    'conflicts' => [$e->getMessage()],
                ]);
            }
        }

        return $results;
    }

    /**
     * Generate jadwal for a single study group.
     */
    public function generateForStudyGroup(string $studyGroupId, string $academicYearId, string $semester): Collection
    {
        $studyGroup = StudyGroup::findOrFail($studyGroupId);

        $assignments = TeachingAssignment::with(['subject', 'teacher'])
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->get();

        if ($assignments->isEmpty()) {
            return collect([]);
        }

        $subjectHours = $this->resolveSubjectHours($assignments, $studyGroup->school_id);

        $created = collect();

        foreach ($subjectHours as $subjectId => $data) {
            $teacherId = $data['teacher_id'];
            $hours = $data['hours'];

            for ($i = 0; $i < $hours; $i++) {
                $slot = $this->findFreeSlot(
                    $studyGroupId,
                    $teacherId,
                    $subjectId,
                    $academicYearId,
                    $semester
                );

                if ($slot === null) {
                    continue;
                }

                $jadwal = JadwalKbm::create([
                    'school_id' => $studyGroup->school_id,
                    'academic_year_id' => $academicYearId,
                    'study_group_id' => $studyGroupId,
                    'subject_id' => $subjectId,
                    'teacher_id' => $teacherId,
                    'day_of_week' => $slot['day'],
                    'slot_index' => $slot['slot_index'],
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'room' => $studyGroup->room,
                    'is_active' => true,
                ]);

                $created->push($jadwal);
            }
        }

        return $created;
    }

    /**
     * Find first free slot for (study_group, teacher, subject) tuple.
     * Returns null if no slot available.
     */
    protected function findFreeSlot(
        string $studyGroupId,
        ?string $teacherId,
        string $subjectId,
        string $academicYearId,
        string $semester
    ): ?array {
        foreach (self::DAYS_OF_WEEK as $day) {
            for ($slot = 1; $slot <= self::MAX_PERIODS_PER_DAY; $slot++) {
                $times = $this->resolveSlotTimes($slot, $day);

                if ($this->isTeacherBusy($teacherId, $day, $slot, $academicYearId, $semester)) {
                    continue;
                }

                if ($this->isStudyGroupBusy($studyGroupId, $day, $slot, $academicYearId, $semester)) {
                    continue;
                }

                return [
                    'day' => $day,
                    'slot_index' => $slot,
                    'start_time' => $times['start'],
                    'end_time' => $times['end'],
                ];
            }
        }

        return null;
    }

    /**
     * Resolve start/end times for a slot in a given day.
     * Honors configurable periods: 45min per slot, break after slot 4 (30min).
     */
    public function resolveSlotTimesPublic(int $slot, int $day): array
    {
        return $this->resolveSlotTimes($slot, $day);
    }

    protected function resolveSlotTimes(int $slot, int $day): array
    {
        $periodMinutes = self::DEFAULT_PERIOD_MINUTES;
        $breakAfter = self::DEFAULT_BREAK_AFTER;
        $breakMinutes = self::DEFAULT_BREAK_MINUTES;

        $startMinutes = (self::DEFAULT_START_HOUR * 60) + (($slot - 1) * $periodMinutes);
        if ($slot > $breakAfter) {
            $startMinutes += $breakMinutes;
        }

        $endMinutes = $startMinutes + $periodMinutes;

        return [
            'start' => sprintf('%02d:%02d:00', intdiv($startMinutes, 60), $startMinutes % 60),
            'end' => sprintf('%02d:%02d:00', intdiv($endMinutes, 60), $endMinutes % 60),
        ];
    }

    protected function isTeacherBusy(
        ?string $teacherId,
        int $day,
        int $slot,
        string $academicYearId,
        string $semester
    ): bool {
        if (! $teacherId) {
            return false;
        }

        return JadwalKbm::where('teacher_id', $teacherId)
            ->where('day_of_week', $day)
            ->where('slot_index', $slot)
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->exists();
    }

    protected function isStudyGroupBusy(
        string $studyGroupId,
        int $day,
        int $slot,
        string $academicYearId,
        string $semester
    ): bool {
        return JadwalKbm::where('study_group_id', $studyGroupId)
            ->where('day_of_week', $day)
            ->where('slot_index', $slot)
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Build subject × hours × teacher map.
     * KKTP hours_per_week override default.
     */
    protected function resolveSubjectHours(Collection $assignments, string $schoolId): array
    {
        $map = [];

        foreach ($assignments as $a) {
            if (! $a->subject) {
                continue;
            }

            $hours = $a->subject->hours_per_week ?? 4;

            $kktp = SubjectKktp::where('subject_id', $a->subject_id)
                ->where('school_id', $schoolId)
                ->where('is_active', true)
                ->first();

            if ($kktp && $kktp->hours_per_week > 0) {
                $hours = $kktp->hours_per_week;
            }

            $map[$a->subject_id] = [
                'teacher_id' => $a->teacher_id,
                'hours' => $hours,
            ];
        }

        return $map;
    }
}
