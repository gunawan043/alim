<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\GtkAnalysisRun;
use App\Models\GtkGapSummary;
use App\Models\OtherTeacherTask;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Engine that computes teacher workload & gap analysis.
 *
 * The engine is intentionally side-effecting via a single run record
 * (GtkAnalysisRun) so it can be re-run, audited, and diffed over time.
 * The heavy lifting happens inside a transaction and writes to
 * gtk_gap_summaries with a per-dimension status.
 */
class GtkAnalysisEngine
{
    public const IDEAL_MIN_HOURS_PER_GURU = 18.0;

    public const IDEAL_MAX_HOURS_PER_GURU = 40.0;

    public const HOVERLOAD_THRESHOLD = 32.0;

    public const UNDERLOAD_THRESHOLD = 12.0;

    public function __construct() {}

    /**
     * Run the analysis for a given scope.
     *
     * @param array{
     *   school_id?: ?string,
     *   academic_year_id?: ?string,
     *   scope?: string,
     *   trigger_source?: string,
     *   trigger_ref_id?: string,
     *   context?: array,
     * } $options
     */
    public function run(array $options = []): GtkAnalysisRun
    {
        $run = GtkAnalysisRun::create([
            'school_id' => $options['school_id'] ?? null,
            'academic_year_id' => $options['academic_year_id'] ?? $this->resolveActiveAcademicYearId(),
            'scope' => $options['scope'] ?? GtkAnalysisRun::SCOPE_SCHOOL,
            'trigger_source' => $options['trigger_source'] ?? 'manual',
            'trigger_ref_id' => $options['trigger_ref_id'] ?? null,
            'context' => $options['context'] ?? [],
            'status' => GtkAnalysisRun::STATUS_PROCESSING,
            'started_at' => now(),
        ]);

        try {
            $summary = DB::transaction(function () use ($run) {
                $this->purgeSummaries($run->id);

                $schoolId = $run->school_id;
                $academicYearId = $run->academic_year_id;

                $perSubject = $this->computeSubjectGaps($schoolId, $academicYearId, $run->id);
                $perTeacher = $this->computeTeacherWorkloads($schoolId, $academicYearId, $run->id);
                $perGroup = $this->computeGroupCoverage($schoolId, $academicYearId, $run->id);

                return [
                    'subject_rows' => count($perSubject),
                    'teacher_rows' => count($perTeacher),
                    'group_rows' => count($perGroup),
                    'total_guru' => $this->countActiveGuru($schoolId),
                    'total_kebutuhan' => array_sum(array_column($perSubject, 'hours_needed')),
                    'total_tersedia' => array_sum(array_column($perSubject, 'hours_available')),
                    'total_gap' => array_sum(array_column($perSubject, 'hours_gap')),
                    'subject_deficit' => count(array_filter($perSubject, fn ($r) => $r['status'] === GtkGapSummary::STATUS_DEFICIT)),
                    'subject_surplus' => count(array_filter($perSubject, fn ($r) => $r['status'] === GtkGapSummary::STATUS_SURPLUS)),
                    'subject_uncovered' => count(array_filter($perSubject, fn ($r) => $r['status'] === GtkGapSummary::STATUS_UNCOVERED)),
                    'guru_overload' => count(array_filter($perTeacher, fn ($r) => ($r['hours_gap'] ?? 0) > 0 && ($r['hours_available'] ?? 0) >= self::HOVERLOAD_THRESHOLD)),
                    'guru_underload' => count(array_filter($perTeacher, fn ($r) => ($r['hours_available'] ?? 0) < self::UNDERLOAD_THRESHOLD)),
                ];
            });

            $run->update([
                'status' => GtkAnalysisRun::STATUS_COMPLETED,
                'summary' => $summary,
                'finished_at' => now(),
            ]);

            return $run->fresh('summaries');
        } catch (\Throwable $e) {
            Log::error('GTK analysis failed', [
                'run_id' => $run->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $run->update([
                'status' => GtkAnalysisRun::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Per-subject gap: berapa jam yang dibutuhkan vs tersedia.
     */
    protected function computeSubjectGaps(?string $schoolId, ?string $academicYearId, string $runId): array
    {
        $neededBySubject = $this->aggregateSubjectNeeds($schoolId, $academicYearId);
        $assignedBySubject = $this->aggregateAssignedHours($schoolId, $academicYearId);

        $rows = [];
        $subjectIds = array_unique(array_merge(array_keys($neededBySubject), array_keys($assignedBySubject)));

        foreach ($subjectIds as $subjectId) {
            $needed = (float) ($neededBySubject[$subjectId] ?? 0);
            $available = (float) ($assignedBySubject[$subjectId] ?? 0);
            $gap = $available - $needed;

            $teacherCount = TeachingAssignment::query()
                ->where('subject_id', $subjectId)
                ->where('status', 'active')
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
                ->distinct('teacher_id')
                ->count('teacher_id');

            $assignmentCount = TeachingAssignment::query()
                ->where('subject_id', $subjectId)
                ->where('status', 'active')
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
                ->count();

            $status = $this->classifySubjectStatus($needed, $available, $teacherCount);

            $subject = Subject::find($subjectId);
            $rows[] = GtkGapSummary::create([
                'analysis_run_id' => $runId,
                'school_id' => $schoolId,
                'academic_year_id' => $academicYearId,
                'subject_id' => $subjectId,
                'teacher_id' => null,
                'study_group_id' => null,
                'dimension' => GtkGapSummary::DIMENSION_SUBJECT,
                'dimension_label' => $subject?->name,
                'hours_needed' => $needed,
                'hours_available' => $available,
                'hours_gap' => $gap,
                'teacher_count' => $teacherCount,
                'assignment_count' => $assignmentCount,
                'group_count' => 0,
                'status' => $status,
                'ideal_min_hours' => $needed,
                'ideal_max_hours' => $needed,
            ])->toArray();
        }

        return $rows;
    }

    /**
     * Per-teacher workload: total assigned teaching hours + tugas tambahan.
     */
    protected function computeTeacherWorkloads(?string $schoolId, ?string $academicYearId, string $runId): array
    {
        $teachingHours = $this->aggregateTeachingHoursByTeacher($schoolId, $academicYearId);
        $additionalHours = $this->aggregateAdditionalHoursByTeacher($schoolId, $academicYearId);
        $teacherIds = array_unique(array_merge(array_keys($teachingHours), array_keys($additionalHours)));

        $rows = [];
        foreach ($teacherIds as $teacherId) {
            $teach = (float) ($teachingHours[$teacherId] ?? 0);
            $add = (float) ($additionalHours[$teacherId] ?? 0);
            $total = $teach + $add;

            $assignmentCount = TeachingAssignment::query()
                ->where('teacher_id', $teacherId)
                ->where('status', 'active')
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
                ->count();

            $subjectCount = TeachingAssignment::query()
                ->where('teacher_id', $teacherId)
                ->where('status', 'active')
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
                ->distinct('subject_id')
                ->count('subject_id');

            $idealMin = self::IDEAL_MIN_HOURS_PER_GURU;
            $idealMax = self::IDEAL_MAX_HOURS_PER_GURU;
            $gap = $total - $idealMin;

            $teacher = User::find($teacherId);
            $rows[] = GtkGapSummary::create([
                'analysis_run_id' => $runId,
                'school_id' => $schoolId,
                'academic_year_id' => $academicYearId,
                'subject_id' => null,
                'teacher_id' => $teacherId,
                'study_group_id' => null,
                'dimension' => GtkGapSummary::DIMENSION_TEACHER,
                'dimension_label' => $teacher?->name,
                'hours_needed' => $idealMin,
                'hours_available' => $total,
                'hours_gap' => $gap,
                'teacher_count' => 1,
                'assignment_count' => $assignmentCount,
                'group_count' => 0,
                'status' => $this->classifyTeacherStatus($total),
                'ideal_min_hours' => $idealMin,
                'ideal_max_hours' => $idealMax,
                'details' => [
                    'teaching_hours' => $teach,
                    'additional_hours' => $add,
                    'subject_count' => $subjectCount,
                ],
            ])->toArray();
        }

        return $rows;
    }

    /**
     * Per-rombel coverage: setiap kelas punya guru untuk mapel yang diajarkan di sana?
     */
    protected function computeGroupCoverage(?string $schoolId, ?string $academicYearId, string $runId): array
    {
        $groups = StudyGroup::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->where('is_active', true)
            ->get();

        $rows = [];
        foreach ($groups as $group) {
            $planned = StudyGroupSubject::query()
                ->where('study_group_id', $group->id)
                ->where('is_active', true)
                ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
                ->get();

            $plannedHours = (float) $planned->sum('weekly_hours');

            $assignedHours = (float) TeachingAssignment::query()
                ->where('study_group_id', $group->id)
                ->where('status', 'active')
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
                ->sum('weekly_hours');

            $coveredSubjects = TeachingAssignment::query()
                ->where('study_group_id', $group->id)
                ->where('status', 'active')
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
                ->distinct('subject_id')
                ->count('subject_id');

            $gap = $assignedHours - $plannedHours;
            $status = $gap >= 0 ? GtkGapSummary::STATUS_BALANCED : GtkGapSummary::STATUS_DEFICIT;

            $rows[] = GtkGapSummary::create([
                'analysis_run_id' => $runId,
                'school_id' => $group->school_id,
                'academic_year_id' => $group->academic_year_id,
                'subject_id' => null,
                'teacher_id' => null,
                'study_group_id' => $group->id,
                'dimension' => GtkGapSummary::DIMENSION_STUDY_GROUP,
                'dimension_label' => $group->full_name,
                'hours_needed' => $plannedHours,
                'hours_available' => $assignedHours,
                'hours_gap' => $gap,
                'teacher_count' => 0,
                'assignment_count' => (int) $coveredSubjects,
                'group_count' => 1,
                'status' => $status,
                'ideal_min_hours' => $plannedHours,
                'ideal_max_hours' => $plannedHours,
                'details' => [
                    'planned_subjects' => $planned->count(),
                    'covered_subjects' => (int) $coveredSubjects,
                ],
            ])->toArray();
        }

        return $rows;
    }

    /**
     * Hitung kebutuhan per mapel = sum(weekly_hours StudyGroupSubject) untuk subject itu.
     */
    protected function aggregateSubjectNeeds(?string $schoolId, ?string $academicYearId): array
    {
        $query = DB::table('study_group_subjects as sgs')
            ->join('study_groups as sg', 'sg.id', '=', 'sgs.study_group_id')
            ->where('sgs.is_active', true)
            ->where('sg.is_active', true)
            ->when($schoolId, fn ($q) => $q->where('sg.school_id', $schoolId))
            ->when($academicYearId, fn ($q) => $q->where('sgs.academic_year_id', $academicYearId))
            ->groupBy('sgs.subject_id')
            ->selectRaw('sgs.subject_id, SUM(sgs.weekly_hours) as total_hours');

        return $query->pluck('total_hours', 'subject_id')->map(fn ($v) => (float) $v)->all();
    }

    /**
     * Hitung jam tersedia per mapel = sum(weekly_hours TeachingAssignment) aktif.
     */
    protected function aggregateAssignedHours(?string $schoolId, ?string $academicYearId): array
    {
        $query = TeachingAssignment::query()
            ->where('status', 'active')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->groupBy('subject_id')
            ->selectRaw('subject_id, SUM(weekly_hours) as total_hours');

        return $query->pluck('total_hours', 'subject_id')->map(fn ($v) => (float) $v)->all();
    }

    protected function aggregateTeachingHoursByTeacher(?string $schoolId, ?string $academicYearId): array
    {
        $query = TeachingAssignment::query()
            ->where('status', 'active')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->groupBy('teacher_id')
            ->selectRaw('teacher_id, SUM(weekly_hours) as total_hours');

        return $query->pluck('total_hours', 'teacher_id')->map(fn ($v) => (float) $v)->all();
    }

    protected function aggregateAdditionalHoursByTeacher(?string $schoolId, ?string $academicYearId): array
    {
        $query = OtherTeacherTask::query()
            ->where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->groupBy('teacher_id')
            ->selectRaw('teacher_id, SUM(weekly_hours) as total_hours');

        return $query->pluck('total_hours', 'teacher_id')->map(fn ($v) => (float) $v)->all();
    }

    protected function classifySubjectStatus(float $needed, float $available, int $teacherCount): string
    {
        if ($needed <= 0) {
            return $teacherCount > 0
                ? GtkGapSummary::STATUS_SURPLUS
                : GtkGapSummary::STATUS_BALANCED;
        }

        if ($teacherCount === 0) {
            return GtkGapSummary::STATUS_UNCOVERED;
        }

        $ratio = $available / $needed;
        if ($ratio < 0.85) {
            return GtkGapSummary::STATUS_DEFICIT;
        }
        if ($ratio > 1.15) {
            return GtkGapSummary::STATUS_SURPLUS;
        }

        return GtkGapSummary::STATUS_BALANCED;
    }

    protected function classifyTeacherStatus(float $totalHours): string
    {
        if ($totalHours <= 0) {
            return GtkGapSummary::STATUS_UNCOVERED;
        }
        if ($totalHours > self::IDEAL_MAX_HOURS_PER_GURU) {
            return GtkGapSummary::STATUS_SURPLUS;
        }
        if ($totalHours < self::IDEAL_MIN_HOURS_PER_GURU) {
            return GtkGapSummary::STATUS_DEFICIT;
        }

        return GtkGapSummary::STATUS_BALANCED;
    }

    protected function countActiveGuru(?string $schoolId): int
    {
        $teacherIds = usersHavingPermission('general_teacher.readable');
        $query = User::query()
            ->where('is_active', true)
            ->whereIn('id', $teacherIds);

        if ($schoolId) {
            $query->whereHas('employments', fn ($q) => $q->where('school_id', $schoolId));
        }

        return $query->count();
    }

    protected function resolveActiveAcademicYearId(): ?string
    {
        $ay = AcademicYear::query()
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();

        return $ay?->id;
    }

    protected function purgeSummaries(string $runId): void
    {
        GtkGapSummary::where('analysis_run_id', $runId)->delete();
    }
}
