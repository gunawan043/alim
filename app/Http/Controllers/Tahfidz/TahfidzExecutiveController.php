<?php

namespace App\Http\Controllers\Tahfidz;

use App\Exports\TahfidzExecutiveExport;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\TahfidzCertificate;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzIntervention;
use App\Models\TahfidzJuzProgress;
use App\Models\TahfidzProgressRecap;
use App\Models\TahfidzSetoran;
use App\Services\TahfidzAccess;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TahfidzExecutiveController extends Controller
{
    public function __construct(private readonly TahfidzAccess $access) {}

    public function index(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $academicYear = AcademicYear::where('is_active', true)->first()
            ?? AcademicYear::orderByDesc('start_date')->first();
        $schoolId = $this->schoolId($request);
        $groupQuery = TahfidzGroup::query()
            ->with('school:id,name')
            ->where('is_active', true)
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id));

        if (! $this->access->canViewAllSchools($request->user())) {
            $groupQuery->where('school_id', $schoolId);
        }

        $groups = $groupQuery->get();
        $schoolIds = $groups->pluck('school_id')->filter()->unique();
        $schools = School::whereIn('id', $schoolIds)->orderBy('name')->get();

        $rows = $schools->map(function (School $school) use ($groups, $academicYear) {
            $groupIds = $groups->where('school_id', $school->id)->pluck('id');
            $memberIds = TahfidzGroupMember::whereIn('tahfidz_group_id', $groupIds)
                ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
                ->where('status', 'aktif')
                ->pluck('student_id')
                ->unique();
            $verified = TahfidzSetoran::whereIn('tahfidz_group_id', $groupIds)
                ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
                ->where('verification_status', 'verified')
                ->get();
            $progress = TahfidzJuzProgress::whereIn('student_id', $memberIds)
                ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
                ->get();
            $recaps = TahfidzProgressRecap::whereIn('student_id', $memberIds)
                ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
                ->get();
            $recentStudents = $verified
                ->filter(fn ($setoran) => $setoran->setoran_date && $setoran->setoran_date->gte(now()->subDays(30)))
                ->pluck('student_id')
                ->unique();
            $inactive = $memberIds->diff($recentStudents)->count();
            $targetAvg = $recaps->whereNotNull('pencapaian_target_persen')->avg('pencapaian_target_persen');
            $activePercent = $memberIds->count() > 0 ? (($memberIds->count() - $inactive) / $memberIds->count()) * 100 : 0;
            $health = round((($targetAvg ?? 0) * 0.5) + ($activePercent * 0.5));

            return [
                'school' => $school,
                'halaqah' => $groupIds->count(),
                'members' => $memberIds->count(),
                'verified_setoran' => $verified->count(),
                'avg_nilai' => $verified->whereNotNull('nilai_setoran')->avg('nilai_setoran'),
                'juz_completed' => $progress->whereIn('status', ['selesai_ziyadah', 'sudah_tasmi'])->count(),
                'target_avg' => $targetAvg,
                'inactive_members' => $inactive,
                'certificates' => TahfidzCertificate::where('school_id', $school->id)->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))->count(),
                'open_interventions' => TahfidzIntervention::whereIn('tahfidz_group_id', $groupIds)->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))->where('status', 'open')->count(),
                'health_score' => min(100, $health),
            ];
        })->sortByDesc('health_score')->values();

        $stats = [
            'schools' => $rows->count(),
            'halaqah' => $rows->sum('halaqah'),
            'members' => $rows->sum('members'),
            'setoran' => $rows->sum('verified_setoran'),
            'avg_nilai' => $rows->whereNotNull('avg_nilai')->avg('avg_nilai'),
            'juz' => $rows->sum('juz_completed'),
            'certificates' => $rows->sum('certificates'),
            'interventions' => $rows->sum('open_interventions'),
        ];

        if ($request->boolean('export')) {
            return Excel::download(new TahfidzExecutiveExport($rows), 'Laporan-Eksekutif-Tahfidz-'.now()->format('Ymd-His').'.xlsx');
        }

        return view('tahfidz.executive.index', compact('userId', 'rows', 'stats', 'academicYear'));
    }

    private function authorizeView(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $this->access->canViewAllSchools($user)
            || $this->access->isSchoolCoordinator($user, $this->schoolId($request))
            || $this->access->isSchoolLeader($user)
            || (function_exists('canPermission') && canPermission('tahfidz_view')),
            403,
            'Anda tidak berwenang melihat laporan eksekutif Tahfidz.'
        );
    }

    private function schoolId(Request $request): ?string
    {
        return $request->attributes->get('schoolContextId') ?: $request->user()?->employment?->school_id;
    }
}
