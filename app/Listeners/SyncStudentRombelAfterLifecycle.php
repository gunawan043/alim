<?php

namespace App\Listeners;

use App\Events\StudentAssignedToRombel;
use App\Events\StudentMutatedIn;
use App\Events\StudentPromoted;
use App\Models\StudentClassHistory;
use Illuminate\Support\Carbon;

class SyncStudentRombelAfterLifecycle
{
    public function handle(object $event): void
    {
        match (true) {
            $event instanceof StudentPromoted => $this->onPromoted($event),
            $event instanceof StudentMutatedIn => $this->onMutatedIn($event),
        };
    }

    private function onPromoted(StudentPromoted $e): void
    {
        $exists = StudentClassHistory::where('student_id', $e->student->id)
            ->where('academic_year_id', $e->toAcademicYear->id)
            ->exists();

        if ($exists) {
            return;
        }

        $history = StudentClassHistory::create([
            'student_id' => $e->student->id,
            'study_group_id' => $e->toStudyGroup->id,
            'academic_year_id' => $e->toAcademicYear->id,
            'is_active' => true,
            'join_date' => $e->promotionDate,
            'attendance_number' => $this->nextAttendance(
                $e->toStudyGroup->id,
                $e->toAcademicYear->id
            ),
        ]);

        StudentAssignedToRombel::dispatch($history);
    }

    private function onMutatedIn(StudentMutatedIn $e): void
    {
        if (! $e->enrollInStudyGroup || ! $e->enrollInAcademicYear) {
            return;
        }

        $exists = StudentClassHistory::where('student_id', $e->student->id)
            ->where('academic_year_id', $e->enrollInAcademicYear->id)
            ->exists();

        if ($exists) {
            return;
        }

        $history = StudentClassHistory::create([
            'student_id' => $e->student->id,
            'study_group_id' => $e->enrollInStudyGroup->id,
            'academic_year_id' => $e->enrollInAcademicYear->id,
            'is_active' => true,
            'join_date' => $e->joinDate ?? Carbon::now()->toDateString(),
            'attendance_number' => $this->nextAttendance(
                $e->enrollInStudyGroup->id,
                $e->enrollInAcademicYear->id
            ),
        ]);

        StudentAssignedToRombel::dispatch($history);
    }

    private function nextAttendance(string $studyGroupId, string $academicYearId): int
    {
        return (int) StudentClassHistory::where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $academicYearId)
            ->count() + 1;
    }
}
