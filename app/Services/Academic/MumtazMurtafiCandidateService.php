<?php

namespace App\Services\Academic;

use App\Models\MumtazMurtafiCandidate;
use App\Models\RaporDistributionSetting;
use App\Models\RaportRegistration;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Services\Arsip\RaporPdfService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MumtazMurtafiCandidateService
{
    public const PREDIKAT = "Mumtaz Murtafi'";

    public function __construct(
        private readonly RaporPdfService $raporPdf,
    ) {}

    /**
     * Kumpulkan santri berpredikat Mumtaz Murtafi' setelah rapor diterbitkan.
     *
     * @return Collection<int, MumtazMurtafiCandidate>
     */
    public function syncForPublishedRapor(
        StudyGroup $studyGroup,
        string $academicYearId,
        string $semester,
        ?string $generatedBy = null,
    ): Collection {
        $students = StudentClassHistory::with('student')
            ->where('study_group_id', $studyGroup->id)
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->get()
            ->pluck('student')
            ->filter()
            ->values();

        if ($students->isEmpty()) {
            return collect();
        }

        $availableSubjects = $this->raporPdf->availableSubjects(
            $studyGroup->school_id,
            $studyGroup,
            $academicYearId,
            $semester,
        );

        if ($availableSubjects->isEmpty()) {
            return collect();
        }

        $setting = RaporDistributionSetting::forContext(
            $studyGroup->school_id,
            $academicYearId,
            $semester,
        );

        $reportSubjectIds = $setting?->reportSubjectIds();
        if ($reportSubjectIds) {
            $reportSubjectIds = array_values(array_intersect(
                $reportSubjectIds,
                $availableSubjects->pluck('id')->all()
            ));
        }

        $summaries = $this->raporPdf->summariesForStudents(
            $studyGroup->school_id,
            $studyGroup,
            $academicYearId,
            $semester,
            'sas',
            $students,
            $reportSubjectIds ?: null,
        );

        $qualifyingIds = [];
        $candidates = collect();

        foreach ($summaries as $studentId => $summary) {
            if (($summary['predikat'] ?? null) !== self::PREDIKAT) {
                continue;
            }

            $qualifyingIds[] = $studentId;

            $candidate = MumtazMurtafiCandidate::firstOrNew([
                'student_id' => $studentId,
                'academic_year_id' => $academicYearId,
                'semester' => $semester,
            ]);

            $isPosted = $candidate->exists
                && $candidate->status === MumtazMurtafiCandidate::STATUS_POSTED;

            $candidate->fill([
                'school_id' => $studyGroup->school_id,
                'study_group_id' => $studyGroup->id,
                'average_score' => $summary['nilai'] ?? null,
                'class_rank' => $summary['rank'] ?? null,
                'status' => $isPosted
                    ? MumtazMurtafiCandidate::STATUS_POSTED
                    : MumtazMurtafiCandidate::STATUS_PENDING,
                'generated_by' => $generatedBy,
                'dismissed_at' => null,
                'notes' => 'Terbentuk otomatis dari publikasi rapor.',
            ]);

            $candidate->save();
            $candidates->push($candidate);
        }

        MumtazMurtafiCandidate::query()
            ->where('study_group_id', $studyGroup->id)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->where('status', MumtazMurtafiCandidate::STATUS_PENDING)
            ->when($qualifyingIds !== [], fn ($q) => $q->whereNotIn('student_id', $qualifyingIds))
            ->update([
                'status' => MumtazMurtafiCandidate::STATUS_DISMISSED,
                'dismissed_at' => now(),
                'notes' => 'Tidak lagi memenuhi predikat Mumtaz Murtafi\' pada publikasi rapor terbaru.',
            ]);

        return $candidates;
    }

    /**
     * Sinkronisasi untuk rapor yang sudah terbit sebelum fitur kandidat aktif.
     *
     * @return array{groups: int, candidates: int, failed: int}
     */
    public function syncPublishedRapors(
        ?string $schoolId = null,
        ?string $academicYearId = null,
        ?string $generatedBy = null,
    ): array {
        $rows = RaportRegistration::query()
            ->where('is_published', true)
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->when($schoolId, fn ($q) => $q->whereHas(
                'studyGroup',
                fn ($groupQuery) => $groupQuery->where('school_id', $schoolId)
            ))
            ->select('study_group_id', 'academic_year_id', 'semester')
            ->distinct()
            ->get();

        $processed = 0;
        $failed = 0;

        foreach ($rows as $row) {
            $studyGroup = StudyGroup::find($row->study_group_id);
            if (! $studyGroup) {
                continue;
            }

            try {
                $processed += $this->syncForPublishedRapor(
                    $studyGroup,
                    (string) $row->academic_year_id,
                    (string) $row->semester,
                    $generatedBy,
                )->count();
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('Sinkronisasi kandidat Mumtaz Murtafi gagal: '.$e->getMessage(), [
                    'study_group_id' => $row->study_group_id,
                    'academic_year_id' => $row->academic_year_id,
                    'semester' => $row->semester,
                ]);
            }
        }

        return [
            'groups' => $rows->count(),
            'candidates' => $processed,
            'failed' => $failed,
        ];
    }

    public function dismissForUnpublishedRapor(
        StudyGroup $studyGroup,
        string $academicYearId,
        string $semester,
    ): int {
        return MumtazMurtafiCandidate::query()
            ->where('study_group_id', $studyGroup->id)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->where('status', MumtazMurtafiCandidate::STATUS_PENDING)
            ->update([
                'status' => MumtazMurtafiCandidate::STATUS_DISMISSED,
                'dismissed_at' => now(),
                'notes' => 'Publikasi rapor dibatalkan.',
            ]);
    }

    public function pendingCount(?string $schoolId = null): int
    {
        try {
            return MumtazMurtafiCandidate::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where('status', MumtazMurtafiCandidate::STATUS_PENDING)
                ->count();
        } catch (\Throwable $e) {
            Log::warning('Gagal menghitung kandidat Mumtaz Murtafi: '.$e->getMessage());

            return 0;
        }
    }
}
