<?php

namespace App\Listeners;

use App\Events\StudentGraduated;
use App\Events\StudentMutatedOut;
use App\Events\StudentPromoted;
use App\Models\StudentClassHistory;
use Illuminate\Support\Carbon;

class ClosePreviousClassHistoryOnLifecycle
{
    public function handle(object $event): void
    {
        $leaveDate = match (true) {
            $event instanceof StudentPromoted => $event->promotionDate,
            $event instanceof StudentGraduated => $event->graduationDate,
            $event instanceof StudentMutatedOut => $event->leaveDate ?? Carbon::now()->toDateString(),
            default => null,
        };

        if ($leaveDate === null) {
            return;
        }

        $academicYearId = match (true) {
            $event instanceof StudentPromoted => $event->fromAcademicYear->id,
            $event instanceof StudentGraduated => $event->fromAcademicYear->id,
            $event instanceof StudentMutatedOut => $this->activeAcademicYearId($event->student->id),
            default => null,
        };

        if ($academicYearId === null) {
            return;
        }

        StudentClassHistory::where('student_id', $event->student->id)
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'leave_date' => $leaveDate,
            ]);
    }

    private function activeAcademicYearId(?string $studentId): ?string
    {
        if ($studentId === null) {
            return null;
        }

        return StudentClassHistory::where('student_id', $studentId)
            ->where('is_active', true)
            ->value('academic_year_id');
    }
}
