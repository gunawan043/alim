<?php

namespace App\Http\Controllers\Tahfidz;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzStudentTarget;
use App\Services\TahfidzAccess;
use App\Services\TahfidzProgressService;
use Illuminate\Http\Request;

class TahfidzTargetController extends Controller
{
    public function __construct(
        private readonly TahfidzAccess $access,
        private readonly TahfidzProgressService $progress,
    ) {}

    public function index(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $academicYear = AcademicYear::where('is_active', true)->first()
            ?? AcademicYear::orderByDesc('start_date')->first();
        $groups = $this->accessibleGroups($request, $academicYear?->id);
        $groupIds = $groups->pluck('id');
        $inputGroups = $groups->filter(fn ($group) => $this->access->canInputSetoran($request->user(), $group))->values();

        $targets = TahfidzStudentTarget::with(['student:id,name,nis', 'group:id,name'])
            ->whereIn('tahfidz_group_id', $groupIds)
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->when($request->filled('group_id'), fn ($q) => $q->where('tahfidz_group_id', $request->group_id))
            ->when($request->filled('student_id'), fn ($q) => $q->where('student_id', $request->student_id))
            ->when($request->filled('semester'), fn ($q) => $q->where('semester', $request->semester))
            ->orderBy('target_bulan')
            ->get();

        $members = TahfidzGroupMember::with('student:id,name,nis')
            ->whereIn('tahfidz_group_id', $inputGroups->pluck('id'))
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->where('status', 'aktif')
            ->get()
            ->groupBy('tahfidz_group_id')
            ->map(fn ($rows) => $rows->map(fn ($row) => [
                'id' => $row->student?->id,
                'name' => $row->student?->name,
                'nis' => $row->student?->nis,
            ])->filter(fn ($row) => $row['id'])->values());

        $students = TahfidzGroupMember::with('student:id,name,nis')
            ->whereIn('tahfidz_group_id', $groupIds)
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->where('status', 'aktif')
            ->get()->pluck('student')->filter()->unique('id')->sortBy('name')->values();

        $stats = [
            'total' => $targets->count(),
            'students' => $targets->pluck('student_id')->unique()->count(),
            'groups' => $targets->pluck('tahfidz_group_id')->unique()->count(),
            'average_pages' => $targets->whereNotNull('target_halaman')->avg('target_halaman'),
        ];

        return view('tahfidz.targets.index', compact('userId', 'groups', 'inputGroups', 'targets', 'students', 'members', 'stats', 'academicYear'));
    }

    public function store(Request $request, string $userId)
    {
        $group = $this->groupFromRequest($request);
        $this->authorizeInput($request, $group);
        $validated = $this->validateTarget($request, $group);

        TahfidzStudentTarget::updateOrCreate(
            [
                'student_id' => $validated['student_id'],
                'tahfidz_group_id' => $group->id,
                'academic_year_id' => $group->academic_year_id,
                'semester' => $validated['semester'],
                'target_bulan' => $validated['target_bulan'],
            ],
            [
                'juz_start' => $validated['juz_start'] ?? null,
                'juz_end' => $validated['juz_end'] ?? null,
                'target_halaman' => $validated['target_halaman'] ?? null,
                'target_hadits' => $validated['target_hadits'] ?? 0,
                'assigned_by' => $request->user()?->id,
                'notes' => $validated['notes'] ?? null,
            ]
        );

        $this->progress->recalculateStudent($validated['student_id'], $group->academic_year_id, $group->id, $validated['semester']);

        return back()->with('success', 'Target hafalan disimpan.');
    }

    public function update(Request $request, string $userId, string $id)
    {
        $target = TahfidzStudentTarget::findOrFail($id);
        $group = $target->group;
        $this->authorizeInput($request, $group);
        $validated = $this->validateTarget($request, $group);

        $target->update([
            'student_id' => $validated['student_id'],
            'semester' => $validated['semester'],
            'target_bulan' => $validated['target_bulan'],
            'juz_start' => $validated['juz_start'] ?? null,
            'juz_end' => $validated['juz_end'] ?? null,
            'target_halaman' => $validated['target_halaman'] ?? null,
            'target_hadits' => $validated['target_hadits'] ?? 0,
            'assigned_by' => $request->user()?->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->progress->recalculateStudent($target->student_id, $group->academic_year_id, $group->id, $target->semester);

        return back()->with('success', 'Target hafalan diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $target = TahfidzStudentTarget::findOrFail($id);
        $group = $target->group;
        $this->authorizeInput($request, $group);
        $studentId = $target->student_id;
        $semester = $target->semester;
        $target->delete();
        $this->progress->recalculateStudent($studentId, $group->academic_year_id, $group->id, $semester);

        return back()->with('success', 'Target hafalan dihapus.');
    }

    private function validateTarget(Request $request, TahfidzGroup $group): array
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'semester' => 'required|in:ganjil,genap',
            'target_bulan' => 'required|integer|min:1|max:12',
            'juz_start' => 'nullable|integer|min:1|max:30',
            'juz_end' => 'nullable|integer|min:1|max:30|gte:juz_start',
            'target_halaman' => 'nullable|numeric|min:0|max:604',
            'target_hadits' => 'nullable|integer|min:0|max:1000',
            'notes' => 'nullable|string|max:2000',
        ]);

        abort_unless(
            TahfidzGroupMember::where('tahfidz_group_id', $group->id)->where('student_id', $validated['student_id'])->where('status', 'aktif')->exists(),
            422,
            'Santri bukan anggota aktif halaqah ini.'
        );

        return $validated;
    }

    private function groupFromRequest(Request $request): TahfidzGroup
    {
        $groupId = $request->input('tahfidz_group_id');
        $group = TahfidzGroup::findOrFail($groupId);
        $schoolId = $request->attributes->get('schoolContextId') ?: $request->user()?->employment?->school_id;
        abort_unless(
            $this->access->canViewAllSchools($request->user())
            || (string) $group->school_id === (string) $schoolId,
            403
        );

        return $group;
    }

    private function authorizeInput(Request $request, TahfidzGroup $group): void
    {
        abort_unless($this->access->canInputSetoran($request->user(), $group), 403, 'Anda tidak berwenang mengatur target halaqah ini.');
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
            'Anda tidak berwenang melihat target Tahfidz.'
        );
    }
}
