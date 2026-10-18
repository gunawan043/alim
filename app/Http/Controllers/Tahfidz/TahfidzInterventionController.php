<?php

namespace App\Http\Controllers\Tahfidz;

use App\Exports\TahfidzInterventionExport;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzIntervention;
use App\Services\TahfidzAccess;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TahfidzInterventionController extends Controller
{
    public function __construct(private readonly TahfidzAccess $access) {}

    public function index(Request $request, string $userId)
    {
        $this->authorizeView($request);
        $academicYear = AcademicYear::where('is_active', true)->first()
            ?? AcademicYear::orderByDesc('start_date')->first();
        $groupIds = $this->accessibleGroupIds($request, $academicYear?->id);

        $query = TahfidzIntervention::with(['student:id,name,nis', 'group:id,name', 'assignedTo:id,name'])
            ->whereIn('tahfidz_group_id', $groupIds)
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('group_id'), fn ($q) => $q->where('tahfidz_group_id', $request->group_id))
            ->when($request->boolean('overdue'), fn ($q) => $q->where('status', 'open')->whereDate('due_date', '<', now()->toDateString()));

        $stats = [
            'open' => (clone $query)->where('status', 'open')->count(),
            'overdue' => (clone $query)->where('status', 'open')->whereDate('due_date', '<', now()->toDateString())->count(),
            'done' => (clone $query)->where('status', 'selesai')->whereBetween('completed_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'total' => (clone $query)->count(),
        ];

        if ($request->boolean('export')) {
            return Excel::download(
                new TahfidzInterventionExport((clone $query)->orderByDesc('created_at')->get()),
                'Tindak-Lanjut-Tahfidz-'.now()->format('Ymd-His').'.xlsx'
            );
        }

        $interventions = $query->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")
            ->orderBy('due_date')
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $groups = TahfidzGroup::whereIn('id', $groupIds)->orderBy('name')->get(['id', 'name']);

        return view('tahfidz.interventions.index', compact('userId', 'interventions', 'groups', 'stats'));
    }

    public function store(Request $request, string $userId, string $studentId)
    {
        $academicYear = AcademicYear::where('is_active', true)->first()
            ?? AcademicYear::orderByDesc('start_date')->first();
        $member = $this->memberFor($request, $studentId, $academicYear?->id);

        $validated = $request->validate([
            'type' => 'required|in:panggilan_wali,bimbingan_tambahan,target_ulang,motivasi,lainnya',
            'notes' => 'nullable|string|max:3000',
            'due_date' => 'nullable|date',
        ]);

        TahfidzIntervention::create([
            'student_id' => $studentId,
            'tahfidz_group_id' => $member->tahfidz_group_id,
            'academic_year_id' => $member->academic_year_id,
            'type' => $validated['type'],
            'title' => $this->title($validated['type']),
            'notes' => $validated['notes'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'status' => 'open',
            'assigned_to' => $request->user()?->id,
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Tindak lanjut berhasil dibuat.');
    }

    public function complete(Request $request, string $userId, string $interventionId)
    {
        $intervention = TahfidzIntervention::with('group')->findOrFail($interventionId);
        $this->authorizeGroup($request, $intervention->group);

        $intervention->update([
            'status' => 'selesai',
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Tindak lanjut ditandai selesai.');
    }

    private function authorizeView(Request $request): void
    {
        $user = $request->user();
        abort_unless(
            $this->access->canViewAllSchools($user)
            || $this->access->isSchoolCoordinator($user, $this->schoolId($request))
            || $this->access->isSchoolLeader($user)
            || $this->access->isTahfidzTeacher($user)
            || (function_exists('canPermission') && canPermission('tahfidz_view')),
            403,
            'Anda tidak berwenang melihat tindak lanjut Tahfidz.'
        );
    }

    private function accessibleGroupIds(Request $request, ?string $academicYearId)
    {
        $user = $request->user();
        $schoolId = $this->schoolId($request);
        $query = TahfidzGroup::query()
            ->where('is_active', true)
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId));

        if (! $this->access->canViewAllSchools($user)) {
            if ($this->access->isSchoolCoordinator($user, $schoolId) || $this->access->isSchoolLeader($user)) {
                $query->where('school_id', $schoolId);
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('teacher_id', $user->id)
                        ->orWhere('coordinator_id', $user->id)
                        ->orWhereHas('teachers', fn ($teacher) => $teacher->where('user_id', $user->id)->where('is_active', true));
                });
            }
        }

        return $query->pluck('id');
    }

    private function schoolId(Request $request): ?string
    {
        return $request->attributes->get('schoolContextId') ?: $request->user()?->employment?->school_id;
    }

    private function memberFor(Request $request, string $studentId, ?string $academicYearId): TahfidzGroupMember
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId') ?: $user?->employment?->school_id;

        $query = TahfidzGroupMember::query()
            ->where('student_id', $studentId)
            ->where('status', 'aktif')
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId));

        if (! $this->access->canViewAllSchools($user)) {
            if ($this->access->isSchoolCoordinator($user, $schoolId) || $this->access->isSchoolLeader($user)) {
                $query->whereHas('group', fn ($group) => $group->where('school_id', $schoolId));
            } else {
                $query->whereHas('group', function ($group) use ($user) {
                    $group->where('teacher_id', $user->id)
                        ->orWhere('coordinator_id', $user->id)
                        ->orWhereHas('teachers', fn ($teacher) => $teacher->where('user_id', $user->id)->where('is_active', true));
                });
            }
        }

        return $query->firstOrFail();
    }

    private function authorizeGroup(Request $request, ?TahfidzGroup $group): void
    {
        abort_unless(
            $group && ($this->access->canViewGroup($request->user(), $group)
                || $this->access->canSubstituteSetoran($request->user(), $group)),
            403,
            'Anda tidak berwenang menindaklanjuti santri ini.'
        );
    }

    private function title(string $type): string
    {
        return match ($type) {
            'panggilan_wali' => 'Panggilan wali santri',
            'bimbingan_tambahan' => 'Bimbingan tahfizh tambahan',
            'target_ulang' => 'Penyesuaian target hafalan',
            'motivasi' => 'Pendampingan motivasi',
            default => 'Tindak lanjut tahfizh',
        };
    }
}
