<?php

namespace App\Jobs;

use App\Models\GtkAnalysisRun;
use App\Models\GtkEmployment;
use App\Models\GtkGapSummary;
use App\Models\GtkProfile;
use App\Models\StudyGroupSubject;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Recalculate teacher workload and produce gap analysis rows.
 *
 * Dedup: implements ShouldBeUnique with a per-(scope, ref) lock so bursts of
 * events for the same scope collapse into a single job execution.
 */
class RecalculateTeacherWorkloadJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public int $timeout = 300;

    public function __construct(
        public string $schoolId,
        public string $academicYearId,
        public string $scope = 'school',
        public ?string $triggerSource = null,
        public ?string $triggerRefId = null,
        public ?string $subjectId = null,
        public ?string $teacherId = null,
        public ?string $studyGroupId = null,
    ) {}

    /**
     * Unique lock key — collapse bursts of duplicate dispatches within the
     * same scope/ref window into a single execution.
     */
    public function uniqueId(): string
    {
        return sprintf(
            '%s|%s|%s|%s|%s|%s',
            $this->schoolId,
            $this->academicYearId,
            $this->scope,
            $this->subjectId ?? '*',
            $this->teacherId ?? '*',
            $this->studyGroupId ?? '*',
        );
    }

    public function uniqueFor(): int
    {
        return 60; // seconds — duplicate dispatches inside this window are absorbed
    }

    public function handle(): void
    {
        $runId = (string) Str::uuid();

        GtkAnalysisRun::create([
            'id' => $runId,
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'scope' => $this->scope,
            'trigger_source' => $this->triggerSource,
            'trigger_ref_id' => $this->triggerRefId,
            'status' => 1, // running
            'context' => [
                'subject_id' => $this->subjectId,
                'teacher_id' => $this->teacherId,
                'study_group_id' => $this->studyGroupId,
            ],
            'started_at' => now(),
        ]);

        try {
            $payload = $this->compute($runId);

            GtkAnalysisRun::where('id', $runId)->update([
                'status' => 2, // completed
                'summary' => $payload['summary'],
                'finished_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('gtk-workload.recalc.failed', [
                'run_id' => $runId,
                'school_id' => $this->schoolId,
                'year_id' => $this->academicYearId,
                'error' => $e->getMessage(),
            ]);

            GtkAnalysisRun::where('id', $runId)->update([
                'status' => 3, // failed
                'error_message' => $e->getMessage(),
                'error_trace' => substr($e->getTraceAsString(), 0, 4000),
                'finished_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * @return array{summary: array<string,mixed>}
     */
    private function compute(string $runId): array
    {
        $schoolId = $this->schoolId;
        $academicYearId = $this->academicYearId;

        // 1. Snapshot the relevant slice.
        $assignmentsQ = TeachingAssignment::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'active');

        if ($this->subjectId) {
            $assignmentsQ->where('subject_id', $this->subjectId);
        }
        if ($this->teacherId) {
            $assignmentsQ->where('teacher_id', $this->teacherId);
        }
        if ($this->studyGroupId) {
            $assignmentsQ->where('study_group_id', $this->studyGroupId);
        }

        $assignments = $assignmentsQ->get(['id', 'teacher_id', 'study_group_id', 'subject_id', 'role', 'weekly_hours']);
        if ($assignments->isEmpty()) {
            return [
                'summary' => [
                    'note' => 'no_active_assignments_in_scope',
                    'scope' => $this->scope,
                ],
            ];
        }

        $subjectIds = $assignments->pluck('subject_id')->unique()->values();
        $studyGroupIds = $assignments->pluck('study_group_id')->unique()->values();
        $teacherIds = $assignments->pluck('teacher_id')->unique()->values();

        // 2. Hours needed per (subject × study_group) from curriculum map.
        $sgsMap = StudyGroupSubject::query()
            ->whereIn('subject_id', $subjectIds)
            ->whereIn('study_group_id', $studyGroupIds)
            ->get(['subject_id', 'study_group_id', 'weekly_hours', 'jam_per_minggu'])
            ->groupBy(fn ($r) => $r->subject_id.'|'.$r->study_group_id);

        // 3. Available hours per (teacher × subject) from active assignments.
        $teacherSubjectHours = DB::table('teaching_assignments')
            ->select(
                'teacher_id',
                'subject_id',
                DB::raw('SUM(weekly_hours) as hours_available'),
                DB::raw('COUNT(*) as assignment_count'),
            )
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'active')
            ->when($this->subjectId, fn ($q) => $q->where('subject_id', $this->subjectId))
            ->when($this->teacherId, fn ($q) => $q->where('teacher_id', $this->teacherId))
            ->groupBy('teacher_id', 'subject_id')
            ->get()
            ->keyBy(fn ($r) => $r->teacher_id.'|'.$r->subject_id);

        $teacherTotals = DB::table('teaching_assignments')
            ->select('teacher_id', DB::raw('SUM(weekly_hours) as total_hours'))
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'active')
            ->whereIn('teacher_id', $teacherIds)
            ->groupBy('teacher_id')
            ->pluck('total_hours', 'teacher_id');

        // 4. Ideal hours per teacher (GtkProfile) — sourced from employment if present.
        $profileByUser = GtkProfile::query()
            ->whereIn('user_id', $teacherIds)
            ->get(['id', 'user_id'])
            ->keyBy('user_id');

        $employmentByUser = GtkEmployment::query()
            ->whereIn('user_id', $teacherIds)
            ->where('school_id', $schoolId)
            ->get(['user_id', 'min_weekly_hours', 'max_weekly_hours', 'ideal_min_hours', 'ideal_max_hours'])
            ->keyBy('user_id');

        // 5. Build gap rows.
        $rows = [];
        $bySubject = [];
        $byTeacher = [];
        $byGroup = [];
        $deficitSubj = [];

        foreach ($assignments as $a) {
            $keySgs = $a->subject_id.'|'.$a->study_group_id;
            $sgs = $sgsMap->get($keySgs)?->first();
            $needed = (float) ($sgs->weekly_hours ?? $sgs->jam_per_minggu ?? 0);

            $keyTs = $a->teacher_id.'|'.$a->subject_id;
            $available = (float) ($teacherSubjectHours[$keyTs]->hours_available ?? 0);
            $gap = $available - $needed;

            $emp = $employmentByUser[$a->teacher_id] ?? null;
            $idealMin = $emp?->ideal_min_hours !== null ? (float) $emp->ideal_min_hours : ($emp?->min_weekly_hours !== null ? (float) $emp->min_weekly_hours : null);
            $idealMax = $emp?->ideal_max_hours !== null ? (float) $emp->ideal_max_hours : ($emp?->max_weekly_hours !== null ? (float) $emp->max_weekly_hours : null);

            $status = match (true) {
                $gap < -0.01 => 'deficit',
                $gap > 0.01 => 'surplus',
                default => 'balanced',
            };

            // Teacher-level dimension (per teacher × subject — current workload).
            $rows[] = [
                'id' => (string) Str::uuid(),
                'analysis_run_id' => $runId,
                'school_id' => $schoolId,
                'academic_year_id' => $academicYearId,
                'subject_id' => $a->subject_id,
                'study_group_id' => $a->study_group_id,
                'teacher_id' => $a->teacher_id,
                'dimension' => 'teacher_subject',
                'dimension_label' => 'Beban Guru-Mapel',
                'hours_needed' => round($needed, 2),
                'hours_available' => round($available, 2),
                'hours_gap' => round($gap, 2),
                'teacher_count' => 1,
                'assignment_count' => 1,
                'group_count' => 1,
                'status' => $status,
                'ideal_min_hours' => $idealMin,
                'ideal_max_hours' => $idealMax,
                'details' => json_encode([
                    'role' => $a->role,
                    'assignment_id' => $a->id,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $bySubject[$a->subject_id] = ($bySubject[$a->subject_id] ?? ['needed' => 0, 'available' => 0]);
            $bySubject[$a->subject_id]['needed'] += $needed;
            $bySubject[$a->subject_id]['available'] += $available;

            $byGroup[$a->study_group_id] = ($byGroup[$a->study_group_id] ?? ['needed' => 0, 'available' => 0]);
            $byGroup[$a->study_group_id]['needed'] += $needed;
            $byGroup[$a->study_group_id]['available'] += $available;

            $byTeacher[$a->teacher_id] = ($byTeacher[$a->teacher_id] ?? ['total_hours' => 0, 'ideal_min' => $idealMin, 'ideal_max' => $idealMax]);
            $byTeacher[$a->teacher_id]['total_hours'] = (float) ($teacherTotals[$a->teacher_id] ?? 0);
        }

        // 6. Subject dimension — aggregate by subject_id.
        foreach ($bySubject as $subjectId => $agg) {
            $gap = $agg['available'] - $agg['needed'];
            $status = match (true) {
                $gap < -0.01 => 'deficit',
                $gap > 0.01 => 'surplus',
                default => 'balanced',
            };
            $rows[] = [
                'id' => (string) Str::uuid(),
                'analysis_run_id' => $runId,
                'school_id' => $schoolId,
                'academic_year_id' => $academicYearId,
                'subject_id' => $subjectId,
                'study_group_id' => null,
                'teacher_id' => null,
                'dimension' => 'subject',
                'dimension_label' => 'Kebutuhan per Mata Pelajaran',
                'hours_needed' => round($agg['needed'], 2),
                'hours_available' => round($agg['available'], 2),
                'hours_gap' => round($gap, 2),
                'teacher_count' => $assignments->where('subject_id', $subjectId)->pluck('teacher_id')->unique()->count(),
                'assignment_count' => $assignments->where('subject_id', $subjectId)->count(),
                'group_count' => $assignments->where('subject_id', $subjectId)->pluck('study_group_id')->unique()->count(),
                'status' => $status,
                'ideal_min_hours' => null,
                'ideal_max_hours' => null,
                'details' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($status === 'deficit') {
                $deficitSubj[$subjectId] = true;
            }
        }

        // 7. Group dimension.
        foreach ($byGroup as $groupId => $agg) {
            $gap = $agg['available'] - $agg['needed'];
            $status = match (true) {
                $gap < -0.01 => 'deficit',
                $gap > 0.01 => 'surplus',
                default => 'balanced',
            };
            $rows[] = [
                'id' => (string) Str::uuid(),
                'analysis_run_id' => $runId,
                'school_id' => $schoolId,
                'academic_year_id' => $academicYearId,
                'subject_id' => null,
                'study_group_id' => $groupId,
                'teacher_id' => null,
                'dimension' => 'study_group',
                'dimension_label' => 'Kebutuhan per Rombel',
                'hours_needed' => round($agg['needed'], 2),
                'hours_available' => round($agg['available'], 2),
                'hours_gap' => round($gap, 2),
                'teacher_count' => $assignments->where('study_group_id', $groupId)->pluck('teacher_id')->unique()->count(),
                'assignment_count' => $assignments->where('study_group_id', $groupId)->count(),
                'group_count' => 1,
                'status' => $status,
                'ideal_min_hours' => null,
                'ideal_max_hours' => null,
                'details' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // 8. Teacher dimension — total weekly hours vs ideal.
        foreach ($byTeacher as $teacherUserId => $agg) {
            $totalHours = (float) $agg['total_hours'];
            $idealMin = $agg['ideal_min'];
            $idealMax = $agg['ideal_max'];

            $status = 'balanced';
            if ($idealMin !== null && $totalHours < (float) $idealMin) {
                $status = 'underload';
            } elseif ($idealMax !== null && $totalHours > (float) $idealMax) {
                $status = 'overload';
            }

            $rows[] = [
                'id' => (string) Str::uuid(),
                'analysis_run_id' => $runId,
                'school_id' => $schoolId,
                'academic_year_id' => $academicYearId,
                'subject_id' => null,
                'study_group_id' => null,
                'teacher_id' => $teacherUserId,
                'dimension' => 'teacher',
                'dimension_label' => 'Total Beban Guru',
                'hours_needed' => round((float) ($idealMin ?? 0), 2),
                'hours_available' => round($totalHours, 2),
                'hours_gap' => round($totalHours - (float) ($idealMin ?? 0), 2),
                'teacher_count' => 1,
                'assignment_count' => $assignments->where('teacher_id', $teacherUserId)->count(),
                'group_count' => $assignments->where('teacher_id', $teacherUserId)->pluck('study_group_id')->unique()->count(),
                'status' => $status,
                'ideal_min_hours' => $idealMin,
                'ideal_max_hours' => $idealMax,
                'details' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // 9. Atomic bulk insert (single transaction — no partial rows).
        DB::transaction(function () use ($rows) {
            // Wipe any prior run rows for this scope to avoid stale duplicates
            // when re-running for the same (school, year, ref) tuple.
            GtkGapSummary::query()
                ->where('school_id', $this->schoolId)
                ->where('academic_year_id', $this->academicYearId)
                ->where('dimension', '!=', 'historical')
                ->delete();

            foreach (array_chunk($rows, 200) as $chunk) {
                GtkGapSummary::insert($chunk);
            }
        });

        // 10. Subject shortage detection — subjects with no teacher at all.
        $assignedSubjects = $assignments->pluck('subject_id')->unique()->values();
        $expectedSubjects = StudyGroupSubject::query()
            ->whereIn('study_group_id', $studyGroupIds)
            ->pluck('subject_id')
            ->unique()
            ->values();

        $shortages = $expectedSubjects->diff($assignedSubjects)->values();

        $summary = [
            'assignment_count' => $assignments->count(),
            'teacher_count' => $teacherIds->count(),
            'subject_count' => $subjectIds->count(),
            'group_count' => $studyGroupIds->count(),
            'deficit_subjects' => array_keys($deficitSubj),
            'unassigned_subjects' => $shortages->all(),
        ];

        return ['summary' => $summary];
    }
}
