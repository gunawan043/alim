<?php

namespace App\Observers;

use App\Models\StudentClassHistory;
use Illuminate\Support\Facades\Log;

class StudentClassHistoryObserver
{
    /**
     * Warn if multiple active rows exist for the same (student, year) —
     * the unique index would catch it, but the message is cryptic.
     */
    public function created(StudentClassHistory $history): void
    {
        if (! $history->is_active) {
            return;
        }

        $this->guardUniqueActive($history);
    }

    public function updated(StudentClassHistory $history): void
    {
        if (! $history->is_active) {
            return;
        }

        if (! $history->isDirty('is_active')) {
            return;
        }

        $this->guardUniqueActive($history);
    }

    private function guardUniqueActive(StudentClassHistory $history): void
    {
        $count = StudentClassHistory::where('student_id', $history->student_id)
            ->where('academic_year_id', $history->academic_year_id)
            ->where('is_active', true)
            ->where('id', '!=', $history->id)
            ->count();

        if ($count > 0) {
            Log::warning('Multiple active StudentClassHistory detected', [
                'student_id' => $history->student_id,
                'academic_year_id' => $history->academic_year_id,
                'new_history_id' => $history->id,
                'conflicting_count' => $count,
            ]);
        }
    }
}
