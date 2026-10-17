<?php

namespace App\Jobs;

use App\Models\AcademicYear;
use App\Models\ArchiveDocument;
use App\Models\StudyGroup;
use App\Models\User;
use App\Services\Arsip\DocumentArchiveService;
use App\Services\Arsip\LegerPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * ARSIP DOKUMEN: arsipkan Leger satu rombel (PDF landscape) ke registri
 * `archive_documents` — idempoten per TA+rombel+semester+jenis.
 */
class ArsipkanLegerRombelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $studyGroupId,
        public readonly string $academicYearId,
        public readonly string $semester,
        public readonly string $jenis = 'sas',
        public readonly ?string $userId = null,
    ) {}

    /**
     * @return array{created: int, skipped: int}
     */
    public function handle(LegerPdfService $pdfService, DocumentArchiveService $documents): array
    {
        $studyGroup = StudyGroup::with(['gradeLevel', 'school'])->find($this->studyGroupId);
        $academicYear = AcademicYear::find($this->academicYearId);

        if (! $studyGroup || ! $academicYear) {
            return ['created' => 0, 'skipped' => 0];
        }

        $exists = ArchiveDocument::query()
            ->where('type', 'leger')
            ->where('academic_year_id', $academicYear->id)
            ->where('study_group_id', $studyGroup->id)
            ->where('semester', $this->semester)
            ->where('jenis', $this->jenis)
            ->exists();

        if ($exists) {
            return ['created' => 0, 'skipped' => 1];
        }

        $bytes = $pdfService->render(
            $studyGroup->school_id,
            $studyGroup,
            $academicYear,
            (string) $academicYear->id,
            $this->semester,
            $this->jenis,
        );

        $user = $this->userId ? User::find($this->userId) : null;

        $documents->store([
            'school_id' => $studyGroup->school_id,
            'academic_year_id' => $academicYear->id,
            'semester' => $this->semester,
            'jenis' => $this->jenis,
            'type' => 'leger',
            'title' => sprintf(
                'Leger %s — %s — Semester %s %s',
                strtoupper($this->jenis),
                $studyGroup->name,
                ucfirst($this->semester),
                $academicYear->name
            ),
            'study_group_id' => $studyGroup->id,
            'generated_by' => $user?->id,
            'notes' => 'Diarsipkan dari halaman Inventaris Arsip.',
        ], $bytes, 'pdf');

        return ['created' => 1, 'skipped' => 0];
    }
}
