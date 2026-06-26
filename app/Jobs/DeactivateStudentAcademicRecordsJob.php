<?php

namespace App\Jobs;

use App\Models\AcademicYear;
use App\Services\AcademicProvisionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job untuk menonaktifkan record akademik ketika siswa keluar dari rombel.
 *
 * Mirrors ProvisionStudentAcademicDataJob: listener
 * DeactivateStudentAcademicRecordsListener men-dispatch job ini,
 * AcademicProvisionService::deactivate() melakukan idempotent UPDATE.
 *
 * Field yang disentuh:
 *   - student_absences : enrollment_status = 'inactive'
 *   - raport_registrations : status = 'withdrawn' (hanya yang draft/in_progress)
 *   - admin_nilai_sumatif placeholder : tidak disentuh — nilai historis tetap
 *     untuk audit & konsistensi rapor yang sudah diterbitkan.
 */
class DeactivateStudentAcademicRecordsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $studentId;

    public string $studyGroupId;

    public string $academicYearId;

    public string $leaveDate;

    public ?string $semester;

    public string $classHistoryId;

    public string $reason;

    public string $queue = 'academic-provision';

    public int $tries = 3;

    public int $backoff = 30;

    public int $timeout = 300;

    public function __construct(
        string $studentId,
        string $studyGroupId,
        string $academicYearId,
        string $leaveDate,
        ?string $semester = null,
        ?string $classHistoryId = null,
        string $reason = 'moved'
    ) {
        $this->studentId = $studentId;
        $this->studyGroupId = $studyGroupId;
        $this->academicYearId = $academicYearId;
        $this->leaveDate = $leaveDate;
        $this->semester = $semester;
        $this->classHistoryId = $classHistoryId ?? '';
        $this->reason = $reason;
    }

    public function handle(): void
    {
        // Resolve semester if missing — perlu untuk filter di service
        if ($this->semester === null) {
            $this->semester = AcademicYear::whereKey($this->academicYearId)->value('semester') ?? 'ganjil';
        }

        $service = new AcademicProvisionService(
            $this->studentId,
            $this->studyGroupId,
            $this->academicYearId,
            $this->leaveDate,
            $this->semester,
        );

        $result = $service->deactivate();

        Log::info('DeactivateStudentAcademicRecordsJob selesai', [
            'class_history_id' => $this->classHistoryId,
            'student_id' => $this->studentId,
            'study_group_id' => $this->studyGroupId,
            'reason' => $this->reason,
            'result' => $result,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('DeactivateStudentAcademicRecordsJob gagal permanen', [
            'class_history_id' => $this->classHistoryId,
            'student_id' => $this->studentId,
            'study_group_id' => $this->studyGroupId,
            'academic_year_id' => $this->academicYearId,
            'reason' => $this->reason,
            'error' => $exception->getMessage(),
        ]);
    }
}
