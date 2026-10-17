<?php

namespace App\Listeners;

use App\Events\StudentGraduated;
use App\Events\StudentMutatedIn;
use App\Events\StudentMutatedOut;
use App\Events\StudentPromoted;

class UpdateStudentStatusOnLifecycle
{
    public function handle(object $event): void
    {
        match (true) {
            $event instanceof StudentPromoted => $this->onPromoted($event),
            $event instanceof StudentGraduated => $this->onGraduated($event),
            $event instanceof StudentMutatedOut => $this->onMutatedOut($event),
            $event instanceof StudentMutatedIn => $this->onMutatedIn($event),
        };
    }

    private function onPromoted(StudentPromoted $e): void
    {
        if ($e->student->status === 'graduate') {
            $e->student->forceFill([
                'status' => 'active',
                'graduation_year' => null,
                'graduation_date' => null,
            ])->save();
        }
    }

    private function onGraduated(StudentGraduated $e): void
    {
        $e->student->forceFill([
            'status' => 'graduate',
            'graduation_year' => $e->graduationYear ?? $e->student->graduation_year,
            'graduation_date' => $e->graduationDate,
        ])->save();
    }

    private function onMutatedOut(StudentMutatedOut $e): void
    {
        $newStatus = match ($e->outType) {
            StudentMutatedOut::TYPE_GRADUATION => 'graduate',
            StudentMutatedOut::TYPE_DROPOUT => 'dropped',
            default => 'transfer_out',
        };

        $e->student->forceFill([
            'status' => $newStatus,
            'graduation_date' => $e->leaveDate,
            'graduation_year' => $e->outType === StudentMutatedOut::TYPE_GRADUATION
                ? ($e->leaveDate ? substr($e->leaveDate, 0, 4) : $e->student->graduation_year)
                : $e->student->graduation_year,
        ])->save();
    }

    private function onMutatedIn(StudentMutatedIn $e): void
    {
        $e->student->forceFill([
            'status' => 'active',
            'graduation_year' => null,
            'graduation_date' => null,
        ])->save();
    }
}
