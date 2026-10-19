<?php

namespace App\Http\Controllers\Tahfidz;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzJuzProgress;
use App\Models\TahfidzProgressRecap;
use App\Services\TahfidzAccess;
use Illuminate\Http\Request;

class TahfidzProgressController extends Controller
{
    public function __construct(private readonly TahfidzAccess $access) {}

    public function index(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $academicYear = AcademicYear::where('is_active', true)->first()
            ?? AcademicYear::orderByDesc('start_date')->first();
        $groups = $this->accessibleGroups($request, $academicYear?->id);
        $groupIds = $groups->pluck('id');

        $members = TahfidzGroupMember::with(['student:id,name,nis', 'group:id,name'])
            ->whereIn('tahfidz_group_id', $groupIds)
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->where('status', 'aktif')
            ->when($request->filled('group_id'), fn ($q) => $q->where('tahfidz_group_id', $request->group_id))
            ->when($request->filled('student_id'), fn ($q) => $q->where('student_id', $request->student_id))
            ->get();

        $studentIds = $members->pluck('student_id')->unique();
        $progress = TahfidzJuzProgress::whereIn('student_id', $studentIds)
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->get()
            ->groupBy('student_id');
        $recaps = TahfidzProgressRecap::whereIn('student_id', $studentIds)
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->orderByDesc('semester')
            ->get()
            ->keyBy('student_id');

        $rows = $members->map(function ($member) use ($progress, $recaps) {
            $rows = $progress[$member->student_id] ?? collect();
            $recap = $recaps[$member->student_id] ?? null;

            return [
                'member' => $member,
                'completed' => $rows->whereIn('status', ['selesai_ziyadah', 'sudah_tasmi'])->count(),
                'total_juz' => $rows->count(),
                'halaman' => $recap?->total_halaman_ziyadah,
                'average' => $recap?->rata_rata_nilai,
                'target' => $recap?->pencapaian_target_persen,
                'last_juz' => $recap?->last_position_juz,
                'rows' => $rows,
            ];
        })->sortByDesc('completed')->values();

        $students = $members->pluck('student')->filter()->unique('id')->sortBy('name')->values();

        $stats = [
            'students' => $rows->count(),
            'juz' => $rows->sum('completed'),
            'average' => $rows->whereNotNull('average')->avg('average'),
            'target' => $rows->whereNotNull('target')->avg('target'),
        ];

        return view('tahfidz.progress.index', compact('userId', 'groups', 'students', 'rows', 'stats', 'academicYear'));
    }

    private function accessibleGroups(Request $request, ?string $academicYearId)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId') ?: $user?->employment?->school_id;
        $query = TahfidzGroup::query()->where('is_active', true)
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId));

        if (! $this->access->canViewAllSchools($user)) {
            if ($this->access->isSchoolCoordinator($user, $schoolId) || $this->access->isSchoolLeader($user)) {
                $query->where('school_id', $schoolId);
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('teacher_id', $user->id)->orWhere('coordinator_id', $user->id)
                        ->orWhereHas('teachers', fn ($teacher) => $teacher->where('user_id', $user->id)->where('is_active', true));
                });
            }
        }

        return $query->orderBy('name')->get();
    }

    private function authorizeView(Request $request): void
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId') ?: $user?->employment?->school_id;

        abort_unless(
            $this->access->canViewAllSchools($user)
            || $this->access->isSchoolCoordinator($user, $schoolId)
            || $this->access->isSchoolLeader($user)
            || $this->access->isTahfidzTeacher($user)
            || (function_exists('canPermission') && canPermission('tahfidz_view')),
            403,
            'Anda tidak berwenang melihat progres Tahfidz.'
        );
    }
}
