<?php

namespace App\Http\Controllers\Tahfidz;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\TahfidzCurriculum;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzGroupTeacher;
use App\Models\TahfidzPackage;
use App\Models\User;
use App\Services\TahfidzAccess;
use Illuminate\Http\Request;

class HalaqahController extends Controller
{
    public function __construct(private readonly TahfidzAccess $access) {}

    public function index(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $schoolId = $this->schoolId($request);
        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $activeYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $academicYearId = $request->input('academic_year_id', $activeYear?->id);

        $query = TahfidzGroup::with(['school:id,name', 'studyGroup:id,name', 'package:id,name', 'teacher:id,name', 'coordinator:id,name'])
            ->withCount('members')
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->when($request->filled('school_id'), fn ($q) => $q->where('school_id', $request->school_id))
            ->when($request->filled('study_group_id'), fn ($q) => $q->where('study_group_id', $request->study_group_id));

        $this->scopeGroups($query, $request, $schoolId, $academicYearId);

        $groups = $query->orderBy('name')->get();
        $schools = $this->access->canViewAllSchools($request->user()) ? School::orderBy('name')->get(['id', 'name']) : collect();
        $canManage = $this->access->isSuperAdmin($request->user())
            || $this->access->isSchoolCoordinator($request->user(), $schoolId, $academicYearId);

        return view('tahfidz.halaqah.index', compact('userId', 'groups', 'academicYears', 'academicYearId', 'schools', 'canManage'));
    }

    public function create(Request $request, string $userId)
    {
        $this->authorizeManage($request);

        return view('tahfidz.halaqah.form', $this->formData($request));
    }

    public function store(Request $request, string $userId)
    {
        $this->authorizeManage($request);
        $validated = $this->validateGroup($request);
        $school = School::findOrFail($validated['school_id']);

        $group = TahfidzGroup::create([
            'work_unit_id' => $school->work_unit_id,
            'school_id' => $school->id,
            'study_group_id' => $validated['study_group_id'] ?? null,
            'academic_year_id' => $validated['academic_year_id'],
            'tahfidz_package_id' => $validated['tahfidz_package_id'] ?? null,
            'curriculum_id' => $validated['curriculum_id'] ?? null,
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'gender' => $validated['gender'],
            'teacher_id' => $validated['teacher_id'],
            'coordinator_id' => $validated['coordinator_id'] ?? null,
            'max_members' => $validated['max_members'] ?? null,
            'room' => $validated['room'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'notes' => $validated['notes'] ?? null,
        ]);

        TahfidzGroupTeacher::create([
            'tahfidz_group_id' => $group->id,
            'user_id' => $group->teacher_id,
            'role' => 'guru_utama',
            'can_input_setoran' => true,
            'is_active' => true,
        ]);

        if ($group->coordinator_id && (string) $group->coordinator_id !== (string) $group->teacher_id) {
            TahfidzGroupTeacher::create([
                'tahfidz_group_id' => $group->id,
                'user_id' => $group->coordinator_id,
                'role' => 'koordinator',
                'can_input_setoran' => true,
                'is_active' => true,
            ]);
        }

        return redirect()->route('user.tahfidz.halaqah.show', ['userId' => $userId, 'id' => $group->id])
            ->with('success', 'Halaqah berhasil dibuat.');
    }

    public function show(Request $request, string $userId, string $id)
    {
        $group = TahfidzGroup::with(['school', 'studyGroup', 'package', 'curriculum', 'teacher', 'coordinator', 'members.student', 'teachers.user', 'setorans' => fn ($q) => $q->with(['student:id,name', 'teacher:id,name', 'verifier:id,name'])->orderByDesc('setoran_date')->limit(30)])->findOrFail($id);
        $this->authorizeGroup($request, $group);

        $activeStudentIds = TahfidzGroupMember::query()
            ->where('academic_year_id', $group->academic_year_id)
            ->where('status', 'aktif')
            ->pluck('student_id');

        $availableStudents = Student::query()
            ->where('school_id', $group->school_id)
            ->where('status', 'active')
            ->whereNotIn('id', $activeStudentIds)
            ->orderBy('name')
            ->limit(300)
            ->get(['id', 'name', 'nis']);

        $canManage = $this->access->canManageGroup($request->user(), $group);

        return view('tahfidz.halaqah.show', compact('userId', 'group', 'availableStudents', 'canManage'));
    }

    public function edit(Request $request, string $userId, string $id)
    {
        $group = TahfidzGroup::findOrFail($id);
        $this->authorizeGroupManage($request, $group);

        return view('tahfidz.halaqah.form', $this->formData($request, $group));
    }

    public function update(Request $request, string $userId, string $id)
    {
        $group = TahfidzGroup::findOrFail($id);
        $this->authorizeGroupManage($request, $group);
        $validated = $this->validateGroup($request);
        $school = School::findOrFail($validated['school_id']);

        $group->update([
            'work_unit_id' => $school->work_unit_id,
            'school_id' => $school->id,
            'study_group_id' => $validated['study_group_id'] ?? null,
            'academic_year_id' => $validated['academic_year_id'],
            'tahfidz_package_id' => $validated['tahfidz_package_id'] ?? null,
            'curriculum_id' => $validated['curriculum_id'] ?? null,
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'gender' => $validated['gender'],
            'teacher_id' => $validated['teacher_id'],
            'coordinator_id' => $validated['coordinator_id'] ?? null,
            'max_members' => $validated['max_members'] ?? null,
            'room' => $validated['room'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('user.tahfidz.halaqah.show', ['userId' => $userId, 'id' => $group->id])
            ->with('success', 'Halaqah diperbarui.');
    }

    public function addMembers(Request $request, string $userId, string $id)
    {
        $group = TahfidzGroup::findOrFail($id);
        $this->authorizeGroupManage($request, $group);
        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
        ]);

        $added = 0;
        $skipped = 0;
        foreach ($request->input('student_ids') as $studentId) {
            $student = Student::where('school_id', $group->school_id)->find($studentId);
            if (! $student) {
                $skipped++;

                continue;
            }

            $active = TahfidzGroupMember::where('student_id', $studentId)
                ->where('academic_year_id', $group->academic_year_id)
                ->where('status', 'aktif')
                ->exists();

            if ($active) {
                $skipped++;

                continue;
            }

            TahfidzGroupMember::create([
                'tahfidz_group_id' => $group->id,
                'student_id' => $studentId,
                'academic_year_id' => $group->academic_year_id,
                'join_date' => now()->toDateString(),
                'status' => 'aktif',
            ]);
            $added++;
        }

        return back()->with('success', $added.' santri ditambahkan'.($skipped ? ', '.$skipped.' dilewati karena sudah punya halaqah aktif.' : '.'));
    }

    public function removeMember(Request $request, string $userId, string $id, string $memberId)
    {
        $group = TahfidzGroup::findOrFail($id);
        $this->authorizeGroupManage($request, $group);
        $member = TahfidzGroupMember::where('tahfidz_group_id', $group->id)->findOrFail($memberId);
        $member->update(['status' => 'pindah_halaqah', 'leave_date' => now()->toDateString()]);

        return back()->with('success', 'Santri dikeluarkan dari halaqah.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $group = TahfidzGroup::findOrFail($id);
        $this->authorizeGroupManage($request, $group);
        abort_if($group->setorans()->exists(), 422, 'Halaqah memiliki setoran; nonaktifkan saja atau pindahkan setoran terlebih dahulu.');
        $group->members()->delete();
        $group->teachers()->delete();
        $group->delete();

        return redirect()->route('user.tahfidz.halaqah.index', ['userId' => $userId])->with('success', 'Halaqah dihapus.');
    }

    private function formData(Request $request, ?TahfidzGroup $group = null): array
    {
        $schoolId = $group?->school_id ?? $this->schoolId($request);
        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $schools = $this->access->canViewAllSchools($request->user()) ? School::orderBy('name')->get(['id', 'name']) : School::whereKey($schoolId)->get(['id', 'name']);
        $studyGroups = StudyGroup::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->orderBy('name')->get(['id', 'name', 'academic_year_id']);
        $packages = TahfidzPackage::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $curricula = TahfidzCurriculum::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('is_active', true)->orderByDesc('created_at')->get(['id', 'grade_level_id', 'academic_year_id', 'semester']);
        $teachers = User::query()
            ->when($schoolId, fn ($q) => $q->whereHas('employment', fn ($e) => $e->where('school_id', $schoolId)))
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($teachers->isEmpty()) {
            $teachers = User::orderBy('name')->limit(300)->get(['id', 'name']);
        }

        return [
            'userId' => $request->route('userId'),
            'group' => $group,
            'academicYears' => $academicYears,
            'schools' => $schools,
            'studyGroups' => $studyGroups,
            'packages' => $packages,
            'curricula' => $curricula,
            'teachers' => $teachers,
            'schoolId' => $schoolId,
        ];
    }

    private function validateGroup(Request $request): array
    {
        return $request->validate([
            'school_id' => 'required|exists:schools,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'study_group_id' => 'nullable|exists:study_groups,id',
            'tahfidz_package_id' => 'nullable|exists:tahfidz_packages,id',
            'curriculum_id' => 'nullable|exists:tahfidz_curriculums,id',
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:20',
            'gender' => 'required|in:putra,putri',
            'teacher_id' => 'required|exists:users,id',
            'coordinator_id' => 'nullable|exists:users,id',
            'max_members' => 'nullable|integer|min:1|max:100',
            'room' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
        ]);
    }

    private function scopeGroups($query, Request $request, ?string $schoolId, ?string $academicYearId): void
    {
        $user = $request->user();
        if ($this->access->canViewAllSchools($user)) {
            return;
        }

        if ($this->access->isSchoolCoordinator($user, $schoolId, $academicYearId)) {
            $query->where('school_id', $schoolId);

            return;
        }

        $query->where(function ($q) use ($user) {
            $q->where('teacher_id', $user->id)
                ->orWhere('coordinator_id', $user->id)
                ->orWhereHas('teachers', fn ($t) => $t->where('user_id', $user->id)->where('is_active', true));
        })->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
    }

    private function authorizeView(Request $request): void
    {
        abort_unless(
            $this->access->canViewAllSchools($request->user())
            || $this->access->isSchoolCoordinator($request->user(), $this->schoolId($request))
            || (function_exists('canPermission') && canPermission('tahfidz_view')),
            403,
            'Anda tidak berwenang melihat halaqah tahfidz.'
        );
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless(
            $this->access->isSuperAdmin($request->user())
            || $this->access->isSchoolCoordinator($request->user(), $this->schoolId($request)),
            403,
            'Hanya koordinator sekolah yang dapat mengelola halaqah.'
        );
    }

    private function authorizeGroup(Request $request, TahfidzGroup $group): void
    {
        abort_unless($this->access->canViewGroup($request->user(), $group), 403, 'Anda tidak berwenang melihat halaqah ini.');
    }

    private function authorizeGroupManage(Request $request, TahfidzGroup $group): void
    {
        abort_unless(
            $this->access->canManageGroup($request->user(), $group),
            403,
            'Anda tidak berwenang mengelola halaqah ini.'
        );
    }

    private function schoolId(Request $request): ?string
    {
        return $request->attributes->get('schoolContextId') ?: $request->user()?->employment?->school_id;
    }
}
