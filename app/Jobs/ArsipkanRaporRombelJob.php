<?php

namespace App\Jobs;

use App\Models\AcademicYear;
use App\Models\ArchiveDocument;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\User;
use App\Services\Arsip\DocumentArchiveService;
use App\Services\Arsip\RaporPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * ARSIP DOKUMEN: arsipkan rapor satu rombel (satu PDF per santri per varian)
 * ke registri `archive_documents` — termasuk anggota rombel yang sudah
 * pindah/naik kelas (arsip periode).
 *
 * Varian: jenis (STS/SAS) × bahasa (Indonesia/Arab) — idempoten per
 * santri+semester+jenis+bahasa.
 */
class ArsipkanRaporRombelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $studyGroupId,
        public readonly string $academicYearId,
        public readonly string $semester,
        public readonly string $jenis = 'sas',
        public readonly ?string $userId = null,
        public readonly string $bahasa = 'id',
    ) {}

    /**
     * @return array{created: int, skipped: int}
     */
    public function handle(RaporPdfService $pdfService, DocumentArchiveService $documents): array
    {
        $studyGroup = StudyGroup::with(['gradeLevel', 'school'])->find($this->studyGroupId);
        $academicYear = AcademicYear::find($this->academicYearId);

        if (! $studyGroup || ! $academicYear) {
            return ['created' => 0, 'skipped' => 0];
        }

        $user = $this->userId ? User::find($this->userId) : null;

        $histories = StudentClassHistory::with('student')
            ->where('study_group_id', $studyGroup->id)
            ->where('academic_year_id', $academicYear->id)
            ->orderByDesc('is_active')
            ->orderBy('attendance_number')
            ->get();

        $jenisList = $this->jenis === 'all' ? ['sts', 'sas'] : [$this->jenis === 'sts' ? 'sts' : 'sas'];
        $bahasaList = $this->bahasa === 'all' ? ['id', 'ar'] : [$this->bahasa === 'ar' ? 'ar' : 'id'];

        $created = 0;
        $skipped = 0;

        foreach ($histories as $history) {
            $student = $history->student;

            if (! $student) {
                continue;
            }

            foreach ($jenisList as $jenis) {
                foreach ($bahasaList as $bahasa) {
                    $exists = ArchiveDocument::query()
                        ->where('type', 'rapor')
                        ->where('academic_year_id', $academicYear->id)
                        ->where('study_group_id', $studyGroup->id)
                        ->where('student_id', $student->id)
                        ->where('semester', $this->semester)
                        ->where('jenis', $jenis)
                        ->where('language', $bahasa)
                        ->exists();

                    if ($exists) {
                        $skipped++;

                        continue;
                    }

                    $bytes = $pdfService->renderSingle(
                        $studyGroup->school_id,
                        $studyGroup,
                        $academicYear,
                        (string) $academicYear->id,
                        $this->semester,
                        $jenis,
                        $student,
                        $bahasa,
                    );

                    $bahasaLabel = $bahasa === 'ar' ? 'Arab' : 'Indonesia';

                    $documents->store([
                        'school_id' => $studyGroup->school_id,
                        'academic_year_id' => $academicYear->id,
                        'semester' => $this->semester,
                        'jenis' => $jenis,
                        'language' => $bahasa,
                        'type' => 'rapor',
                        'title' => sprintf(
                            'Rapor %s (%s) — %s — %s — Semester %s %s',
                            strtoupper($jenis),
                            $bahasaLabel,
                            $student->name,
                            $studyGroup->name,
                            ucfirst($this->semester),
                            $academicYear->name
                        ),
                        'student_id' => $student->id,
                        'study_group_id' => $studyGroup->id,
                        'generated_by' => $user?->id,
                        'notes' => "Jenis: {$jenis} · Bahasa: {$bahasaLabel} · Diarsipkan dari halaman Inventaris Arsip.",
                    ], $bytes, 'pdf');

                    $created++;
                }
            }
        }

        return ['created' => $created, 'skipped' => $skipped];
    }
}
