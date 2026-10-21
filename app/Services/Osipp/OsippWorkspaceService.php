<?php

namespace App\Services\Osipp;

use App\Models\AcademicYear;
use App\Models\Osipp\OsippActivity;
use App\Models\Osipp\OsippCoaching;
use App\Models\Osipp\OsippDocument;
use App\Models\Osipp\OsippEvaluation;
use App\Models\Osipp\OsippMember;
use App\Models\Osipp\OsippPeriod;
use App\Models\Osipp\OsippProgram;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;

class OsippWorkspaceService
{
    public function __construct(private readonly OsippAccess $access) {}

    /**
     * Konteks workspace OSIPP: sekolah, tahun ajaran, daftar periode, dan
     * periode yang sedang dipilih. Periode tidak wajib ada agar dashboard
     * tetap dapat menampilkan langkah awal pembuatan kepengurusan.
     *
     * @return array<string, mixed>
     */
    public function context(Request $request, ?string $periodId = null): array
    {
        $user = $request->user();
        $schoolId = $this->access->schoolId($user);

        if (! $schoolId) {
            abort(403, 'Konteks satuan pendidikan belum tersedia untuk workspace OSIPP.');
        }

        $school = School::find($schoolId);
        $activeYearId = $this->access->academicYearId($schoolId);
        $periods = OsippPeriod::query()
            ->with('academicYear')
            ->where('school_id', $schoolId)
            ->withCount([
                'members',
                'programs',
                'activities',
                'documents',
                'coachings',
                'evaluations',
            ])
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderByDesc('start_date')
            ->get();

        $selectedId = $periodId ?: $request->input('period_id');
        $period = ($selectedId ? $periods->firstWhere('id', $selectedId) : null)
            ?: $periods->firstWhere('status', OsippPeriod::STATUS_ACTIVE)
            ?: $periods->first();

        return [
            'user' => $user,
            'userId' => $user?->id,
            'school' => $school,
            'schoolId' => $schoolId,
            'activeYearId' => $activeYearId,
            'periods' => $periods,
            'period' => $period,
            'canManage' => $this->access->canManage($user, $schoolId, $period?->academic_year_id ?? $activeYearId),
            'canVerify' => $this->access->canVerify($user, $schoolId, $period?->academic_year_id ?? $activeYearId),
            'canEvaluate' => $this->access->canEvaluate($user, $schoolId, $period?->academic_year_id ?? $activeYearId),
        ];
    }

    public function ensurePeriod(array $context): OsippPeriod
    {
        abort_unless($context['period'] instanceof OsippPeriod, 404, 'Belum ada periode kepengurusan OSIPP. Buat periode terlebih dahulu.');

        return $context['period'];
    }

    public function ensurePeriodAccess(User $user, OsippPeriod $period): void
    {
        abort_unless($this->access->canAccessPeriod($user, $period), 403, 'Anda tidak berwenang mengakses periode OSIPP ini.');
    }

    /**
     * @return array<string, int|float>
     */
    public function statsForPeriod(OsippPeriod $period): array
    {
        $memberQuery = OsippMember::query()->where('period_id', $period->id);
        $programQuery = OsippProgram::query()->where('period_id', $period->id);
        $activityQuery = OsippActivity::query()->where('period_id', $period->id);
        $documentQuery = OsippDocument::query()->where('period_id', $period->id);
        $coachingQuery = OsippCoaching::query()->where('period_id', $period->id);
        $evaluation = OsippEvaluation::query()
            ->where('period_id', $period->id)
            ->where('status', OsippEvaluation::STATUS_FINAL)
            ->latest('evaluation_date')
            ->first();

        return [
            'members' => (clone $memberQuery)->where('status', OsippMember::STATUS_ACTIVE)->count(),
            'core_members' => (clone $memberQuery)->where('status', OsippMember::STATUS_ACTIVE)
                ->where('member_role', OsippMember::ROLE_PENGURUS)->count(),
            'programs' => (clone $programQuery)->count(),
            'programs_running' => (clone $programQuery)->where('status', OsippProgram::STATUS_RUNNING)->count(),
            'programs_completed' => (clone $programQuery)->where('status', OsippProgram::STATUS_COMPLETED)->count(),
            'activities' => (clone $activityQuery)->count(),
            'activities_pending' => (clone $activityQuery)->where('status', OsippActivity::STATUS_SUBMITTED)->count(),
            'activities_completed' => (clone $activityQuery)->where('status', OsippActivity::STATUS_COMPLETED)->count(),
            'documents' => (clone $documentQuery)->count(),
            'documents_pending' => (clone $documentQuery)->where('status', OsippDocument::STATUS_SUBMITTED)->count(),
            'coachings' => (clone $coachingQuery)->count(),
            'evaluation_score' => $evaluation ? (float) $evaluation->total_score : 0.0,
            'evaluation_grade' => $evaluation?->grade,
        ];
    }

    public function memberOptions(OsippPeriod $period)
    {
        return OsippMember::query()
            ->with('student:id,name,nis,nisn')
            ->where('period_id', $period->id)
            ->where('status', OsippMember::STATUS_ACTIVE)
            ->orderBy('sort_order')
            ->orderBy('position')
            ->get();
    }

    public function studentQuery(string $schoolId, string $search = '')
    {
        return Student::query()
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('nis', 'like', '%'.$search.'%')
                        ->orWhere('nisn', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name');
    }

    public function academicYearOptions(string $schoolId)
    {
        return AcademicYear::query()
            ->orderByDesc('is_active')
            ->orderByDesc('start_date')
            ->get();
    }
}
