<?php

namespace App\Jobs;

use App\Services\StudyGroupSubjectProvisioner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job: Provision academic structure for a Subject ↔ StudyGroup binding.
 *
 * Triggered by SubjectAssignedToStudyGroup via the dedicated listener.
 * The job runs on the 'academic-provision' queue.
 *
 * Idempotent: re-running for the same (study_group, subject, year, semester)
 * will not create duplicate admin_books or nilai placeholders.
 */
class ProvisionStudyGroupSubjectAcademicStructureJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $studyGroupSubjectId;

    public string $studyGroupId;

    public string $subjectId;

    public ?string $teacherId;

    public ?string $schoolId;

    public ?string $academicYearId;

    public ?string $gradeLevelId;

    public string $changeType;

    public string $queue = 'academic-provision';

    public int $tries = 3;

    public int $backoff = 30;

    public int $timeout = 300;

    public function __construct(
        string $studyGroupSubjectId,
        string $studyGroupId,
        string $subjectId,
        ?string $teacherId = null,
        ?string $schoolId = null,
        ?string $academicYearId = null,
        ?string $gradeLevelId = null,
        string $changeType = 'created',
    ) {
        $this->studyGroupSubjectId = $studyGroupSubjectId;
        $this->studyGroupId = $studyGroupId;
        $this->subjectId = $subjectId;
        $this->teacherId = $teacherId;
        $this->schoolId = $schoolId;
        $this->academicYearId = $academicYearId;
        $this->gradeLevelId = $gradeLevelId;
        $this->changeType = $changeType;
    }

    public function handle(): void
    {
        $service = new StudyGroupSubjectProvisioner(
            $this->studyGroupSubjectId,
            $this->studyGroupId,
            $this->subjectId,
            $this->teacherId,
            $this->schoolId,
            $this->academicYearId,
            $this->gradeLevelId,
            $this->changeType,
        );

        $result = $service->provision();

        Log::info('ProvisionStudyGroupSubjectAcademicStructureJob selesai', [
            'sgs_id' => $this->studyGroupSubjectId,
            'study_group_id' => $this->studyGroupId,
            'subject_id' => $this->subjectId,
            'change_type' => $this->changeType,
            'result' => $result,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('ProvisionStudyGroupSubjectAcademicStructureJob gagal permanen', [
            'sgs_id' => $this->studyGroupSubjectId,
            'study_group_id' => $this->studyGroupId,
            'subject_id' => $this->subjectId,
            'change_type' => $this->changeType,
            'error' => $exception->getMessage(),
        ]);
    }
}
