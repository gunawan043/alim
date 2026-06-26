<?php

namespace App\Events;

use App\Models\StudentClassHistory;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event yang dipicu ketika seorang siswa keluar dari sebuah rombel
 * (StudentClassHistory di-set is_active = false atau di-delete).
 *
 * Mirrors StudentAssignedToRombel: kontrak data saja, tanpa logic bisnis.
 * Listener DeactivateStudentAcademicRecordsListener akan
 * men-deactivate record akademik terkait (student_absences, raport_registrations,
 * placeholder nilai sumatif) untuk menjaga konsistensi data.
 */
class StudentExitedFromRombel
{
    use Dispatchable, SerializesModels;

    public string $classHistoryId;

    public string $studentId;

    public string $studyGroupId;

    public string $academicYearId;

    public ?string $leaveDate;

    public string $reason;

    /**
     * @param  string  $reason  'moved' | 'graduated' | 'dropped' | 'deleted'
     */
    public function __construct(
        StudentClassHistory $classHistory,
        string $reason = 'moved'
    ) {
        $this->classHistoryId = (string) $classHistory->id;
        $this->studentId = (string) $classHistory->student_id;
        $this->studyGroupId = (string) $classHistory->study_group_id;
        $this->academicYearId = (string) $classHistory->academic_year_id;
        $this->leaveDate = optional($classHistory->leave_date)->toDateString()
            ?? optional($classHistory->updated_at)->toDateString()
            ?? now()->toDateString();
        $this->reason = $reason;
    }
}
