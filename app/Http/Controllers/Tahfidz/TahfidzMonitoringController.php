<?php

namespace App\Http\Controllers\Tahfidz;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\TahfidzCertificate;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzJuzProgress;
use App\Models\TahfidzMutabaah;
use App\Models\TahfidzProgressRecap;
use App\Models\TahfidzSetoran;
use App\Models\TahfidzStudentTarget;
use App\Models\TahfidzTasmianParticipant;
use App\Services\TahfidzAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class TahfidzMonitoringController extends Controller
{
    public function __construct(private readonly TahfidzAccess $access) {}

    public function index(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $activeYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $academicYearId = $request->input('academic_year_id', $activeYear?->id);
        $schoolId = $request->input('school_id', $this->schoolId($request));
        $dateFrom = $request->input('date_from', now()->subDays(30)->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $canViewAll = $this->access->canViewAllSchools($request->user());

        $groups = $this->accessibleGroups($request, $academicYearId);
        if ($schoolId) {
            $groups = $groups->where('school_id', $schoolId)->values();
        }
        $groupIds = $groups->pluck('id');
        $schools = $canViewAll ? School::orderBy('name')->get(['id', 'name']) : School::whereKey($this->schoolId($request))->get(['id', 'name']);

        $members = TahfidzGroupMember::with([
            'student:id,name,nis,school_id',
            'group:id,name,school_id,teacher_id',
        ])
            ->whereIn('tahfidz_group_id', $groupIds)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'aktif')
            ->get();

        $studentIds = $members->pluck('student_id')->unique()->values();
        $verifiedInPeriod = TahfidzSetoran::query()
            ->whereIn('student_id', $studentIds)
            ->where('academic_year_id', $academicYearId)
            ->where('verification_status', 'verified')
            ->whereBetween('setoran_date', [$dateFrom, $dateTo])
            ->get()
            ->groupBy('student_id');

        $lastVerified = TahfidzSetoran::query()
            ->whereIn('student_id', $studentIds)
            ->where('academic_year_id', $academicYearId)
            ->where('verification_status', 'verified')
            ->selectRaw('student_id, MAX(setoran_date) as last_date')
            ->groupBy('student_id')
            ->pluck('last_date', 'student_id');

        $pendingQuery = TahfidzSetoran::with(['student:id,name,nis', 'group:id,name'])
            ->whereIn('tahfidz_group_id', $groupIds)
            ->where('verification_status', 'pending');
        $pendingCount = (clone $pendingQuery)->count();
        $pendingSetorans = (clone $pendingQuery)->orderBy('setoran_date')->limit(15)->get();

        $progressByStudent = TahfidzJuzProgress::query()
            ->whereIn('student_id', $studentIds)
            ->where('academic_year_id', $academicYearId)
            ->get()
            ->groupBy('student_id');
        $recaps = TahfidzProgressRecap::query()
            ->whereIn('student_id', $studentIds)
            ->where('academic_year_id', $academicYearId)
            ->get()
            ->keyBy('student_id');
        $targets = TahfidzStudentTarget::query()
            ->whereIn('student_id', $studentIds)
            ->where('academic_year_id', $academicYearId)
            ->get()
            ->groupBy('student_id');
        $lastMutabaah = TahfidzMutabaah::query()
            ->whereIn('student_id', $studentIds)
            ->where('academic_year_id', $academicYearId)
            ->selectRaw('student_id, MAX(record_date) as last_date')
            ->groupBy('student_id')
            ->pluck('last_date', 'student_id');

        $inactiveSetoranDays = (int) config('tahfidz.monitoring.inactive_setoran_days', 14);
        $inactiveMutabaahDays = (int) config('tahfidz.monitoring.inactive_mutabaah_days', 7);
        $lowTargetPercent = (float) config('tahfidz.monitoring.low_target_percent', 50);

        $memberRows = $members->map(function (TahfidzGroupMember $member) use (
            $verifiedInPeriod, $lastVerified, $progressByStudent, $recaps, $targets,
            $lastMutabaah, $inactiveSetoranDays, $inactiveMutabaahDays, $lowTargetPercent
        ) {
            $studentId = $member->student_id;
            $periodRows = $verifiedInPeriod[$studentId] ?? collect();
            $rows = $progressByStudent[$studentId] ?? collect();
            $recap = $recaps[$studentId] ?? null;
            $lastSetoran = $lastVerified[$studentId] ?? null;
            $lastMutabaahDate = $lastMutabaah[$studentId] ?? null;
            $setoranDays = $lastSetoran ? Carbon::parse($lastSetoran)->diffInDays(now()) : null;
            $mutabaahDays = $lastMutabaahDate ? Carbon::parse($lastMutabaahDate)->diffInDays(now()) : null;

            $reasons = [];
            if (! $lastSetoran) {
                $reasons[] = 'Belum ada setoran terverifikasi';
            } elseif ($setoranDays > $inactiveSetoranDays) {
                $reasons[] = 'Tidak setoran '.$setoranDays.' hari';
            }
            if (! isset($targets[$studentId])) {
                $reasons[] = 'Target belum diatur';
            } elseif ($recap?->pencapaian_target_persen !== null && (float) $recap->pencapaian_target_persen < $lowTargetPercent) {
                $reasons[] = 'Capaian target '.number_format((float) $recap->pencapaian_target_persen, 0).'%';
            }
            if (! $lastMutabaahDate) {
                $reasons[] = 'Belum ada mutaba’ah';
            } elseif ($mutabaahDays > $inactiveMutabaahDays) {
                $reasons[] = 'Mutaba’ah '.$mutabaahDays.' hari lalu';
            }

            return [
                'member' => $member,
                'student' => $member->student,
                'group' => $member->group,
                'setoran_count' => $periodRows->count(),
                'avg_nilai' => $periodRows->whereNotNull('nilai_setoran')->avg('nilai_setoran'),
                'last_setoran' => $lastSetoran,
                'setoran_days' => $setoranDays,
                'juz_completed' => $rows->whereIn('status', ['selesai_ziyadah', 'sudah_tasmi'])->count(),
                'target_percent' => $recap?->pencapaian_target_persen,
                'last_mutabaah' => $lastMutabaahDate,
                'last_mutabaah_days' => $mutabaahDays,
                'reasons' => $reasons,
            ];
        })->sortByDesc(fn ($row) => count($row['reasons']))->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 25;
        $memberPaginator = new LengthAwarePaginator(
            $memberRows->forPage($page, $perPage)->values(),
            $memberRows->count(),
            $perPage,
            $page,
            ['path' => route('user.tahfidz.monitoring.index', ['userId' => $userId]), 'query' => $request->query()]
        );

        $certificateCount = TahfidzCertificate::query()
            ->when(! $canViewAll, fn ($q) => $q->where('school_id', $this->schoolId($request)))
            ->count();

        $tasmiPending = TahfidzTasmianParticipant::with([
            'student:id,name,nis', 'session:id,session_name,school_id,session_date', 'score',
        ])
            ->whereHas('score')
            ->whereDoesntHave('certificate')
            ->when(! $canViewAll, fn ($q) => $q->whereHas('session', fn ($session) => $session->where('school_id', $this->schoolId($request))))
            ->latest()
            ->limit(10)
            ->get();

        $stats = [
            'halaqah' => $groups->count(),
            'members' => $memberRows->count(),
            'verified' => $verifiedInPeriod->flatten(1)->count(),
            'pending' => $pendingCount,
            'behind' => $memberRows->filter(fn ($row) => ! empty($row['reasons']))->count(),
            'certificates' => $certificateCount,
        ];

        return view('tahfidz.monitoring.index', compact(
            'userId', 'groups', 'schools', 'academicYears', 'academicYearId', 'schoolId',
            'dateFrom', 'dateTo', 'memberPaginator', 'pendingSetorans', 'tasmiPending', 'stats'
        ));
    }

    private function accessibleGroups(Request $request, ?string $academicYearId)
    {
        $user = $request->user();
        $schoolId = $this->schoolId($request);

        $query = TahfidzGroup::query()
            ->with(['school:id,name', 'teacher:id,name'])
            ->where('is_active', true)
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId));

        if (! $this->access->canViewAllSchools($user)) {
            if ($this->access->isSchoolCoordinator($user, $schoolId)) {
                $query->where('school_id', $schoolId);
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('teacher_id', $user->id)
                        ->orWhere('coordinator_id', $user->id)
                        ->orWhereHas('teachers', fn ($teacher) => $teacher->where('user_id', $user->id)->where('is_active', true));
                });
            }
        }

        return $query->orderBy('name')->get();
    }

    private function authorizeView(Request $request): void
    {
        $user = $request->user();
        $hasAssignment = TahfidzGroup::query()
            ->where('is_active', true)
            ->where(function ($q) use ($user) {
                $q->where('teacher_id', $user->id)
                    ->orWhere('coordinator_id', $user->id)
                    ->orWhereHas('teachers', fn ($teacher) => $teacher->where('user_id', $user->id)->where('is_active', true));
            })
            ->exists();

        abort_unless(
            $this->access->canViewAllSchools($user)
            || $this->access->isSchoolCoordinator($user, $this->schoolId($request))
            || $hasAssignment
            || (function_exists('canPermission') && canPermission('tahfidz_view')),
            403,
            'Anda tidak berwenang melihat monitoring tahfidz.'
        );
    }

    private function schoolId(Request $request): ?string
    {
        return $request->attributes->get('schoolContextId') ?: $request->user()?->employment?->school_id;
    }
}
