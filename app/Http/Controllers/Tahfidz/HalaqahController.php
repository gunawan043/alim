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
use App\Models\TahfidzJuzProgress;
use App\Models\TahfidzPackage;
use App\Models\TahfidzSchedule;
use App\Models\TahfidzProgressRecap;
use App\Models\TahfidzStudentTarget;
use App\Models\User;
use App\Services\TahfidzAccess;
use App\Services\TahfidzProgressService;
use Illuminate\Http\Request;

class HalaqahController extends Controller
{
    public function __construct(
        private readonly TahfidzAccess $access,
        private readonly TahfidzProgressService $progress,
    ) {}

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
        $stats = [
            'total' => $groups->count(),
            'active' => $groups->where('is_active', true)->count(),
            'members' => $groups->sum('members_count'),
            'teachers' => $groups->pluck('teacher_id')->filter()->unique()->count(),
        ];
        $form = $this->formData($request);

        return view('tahfidz.halaqah.index', compact('userId', 'groups', 'academicYears', 'academicYearId', 'schools', 'canManage', 'stats', 'form'));
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
        $group = TahfidzGroup::with(['school', 'studyGroup', 'package', 'curriculum', 'teacher', 'coordinator', 'members.student', 'teachers.user', 'schedules' => fn ($q) => $q->orderBy('day_of_week')->orderBy('time_start'), 'setorans' => fn ($q) => $q->with(['student:id,name', 'teacher:id,name', 'verifier:id,name'])->orderByDesc('setoran_date')->limit(30)])->findOrFail($id);
        $this->authorizeGroup($request, $group);
        $group->loadCount([
            'members as active_members_count' => fn ($query) => $query->where('status', 'aktif'),
            'setorans',
        ]);

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
        $canTarget = $this->access->canInputSetoran($request->user(), $group)
            || $canManage;
        $canSchedule = $canTarget;
        $activeMembers = $group->members->where('status', 'aktif')->values();
        $memberIds = $activeMembers->pluck('student_id');

        $targets = TahfidzStudentTarget::with('student:id,name,nis')
            ->where('tahfidz_group_id', $group->id)
            ->where('academic_year_id', $group->academic_year_id)
            ->orderBy('target_bulan')
            ->get();

        $juzProgress = TahfidzJuzProgress::query()
            ->whereIn('student_id', $memberIds)
            ->where('academic_year_id', $group->academic_year_id)
            ->orderBy('juz_number')
            ->get()
            ->groupBy('student_id');

        $recaps = TahfidzProgressRecap::query()
            ->whereIn('student_id', $memberIds)
            ->where('academic_year_id', $group->academic_year_id)
            ->get()
            ->keyBy('student_id');

        return view('tahfidz.halaqah.show', compact(
            'userId', 'group', 'availableStudents', 'canManage', 'canTarget',
            'activeMembers', 'targets', 'juzProgress', 'recaps', 'canSchedule'
        ));
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

    public function storeTarget(Request $request, string $userId, string $id)
    {
        $group = TahfidzGroup::findOrFail($id);
        $this->authorizeTarget($request, $group);
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

        $this->progress->recalculateStudent(
            $validated['student_id'],
            $group->academic_year_id,
            $group->id,
            $validated['semester'],
        );

        return back()->with('success', 'Target hafalan santri disimpan.');
    }

    public function updateTarget(Request $request, string $userId, string $id, string $targetId)
    {
        $group = TahfidzGroup::findOrFail($id);
        $this->authorizeTarget($request, $group);
        $target = TahfidzStudentTarget::where('tahfidz_group_id', $group->id)->findOrFail($targetId);
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

        $this->progress->recalculateStudent(
            $target->student_id,
            $group->academic_year_id,
            $group->id,
            $target->semester,
        );

        return back()->with('success', 'Target hafalan diperbarui.');
    }

    public function destroyTarget(Request $request, string $userId, string $id, string $targetId)
    {
        $group = TahfidzGroup::findOrFail($id);
        $this->authorizeTarget($request, $group);
        $target = TahfidzStudentTarget::where('tahfidz_group_id', $group->id)->findOrFail($targetId);
        $studentId = $target->student_id;
        $semester = $target->semester;
        $target->delete();

        $this->progress->recalculateStudent($studentId, $group->academic_year_id, $group->id, $semester);

        return back()->with('success', 'Target hafalan dihapus.');
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

    public function storeSchedule(Request $request, string $userId, string $id)
    {
        $group = TahfidzGroup::findOrFail($id);
        $this->authorizeSchedule($request, $group);
        $validated = $this->validateSchedule($request);

        TahfidzSchedule::create([
            'tahfidz_group_id' => $group->id,
            'academic_year_id' => $group->academic_year_id,
            'day_of_week' => $validated['day_of_week'],
            'time_start' => $validated['time_start'],
            'time_end' => $validated['time_end'],
            'room' => $validated['room'] ?? null,
            'schedule_type' => $validated['schedule_type'],
            'is_active' => $request->boolean('is_active', true),
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Jadwal halaqah ditambahkan.');
    }

    public function updateSchedule(Request $request, string $userId, string $id, string $scheduleId)
    {
        $group = TahfidzGroup::findOrFail($id);
        $this->authorizeSchedule($request, $group);
        $schedule = TahfidzSchedule::where('tahfidz_group_id', $group->id)->findOrFail($scheduleId);
        $validated = $this->validateSchedule($request);

        $schedule->update([
            'day_of_week' => $validated['day_of_week'],
            'time_start' => $validated['time_start'],
            'time_end' => $validated['time_end'],
            'room' => $validated['room'] ?? null,
            'schedule_type' => $validated['schedule_type'],
            'is_active' => $request->boolean('is_active', true),
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Jadwal halaqah diperbarui.');
    }

    public function destroySchedule(Request $request, string $userId, string $id, string $scheduleId)
    {
        $group = TahfidzGroup::findOrFail($id);
        $this->authorizeSchedule($request, $group);
        TahfidzSchedule::where('tahfidz_group_id', $group->id)->findOrFail($scheduleId)->delete();

        return back()->with('success', 'Jadwal halaqah dihapus.');
    }

    private function validateSchedule(Request $request): array
    {
        $validated = $request->validate([
            'day_of_week' => 'required|integer|min:1|max:7',
            'time_start' => 'required|date_format:H:i',
            'time_end' => 'required|date_format:H:i',
            'room' => 'nullable|string|max:100',
            'schedule_type' => 'required|in:setoran,murajaah,tasmi,evaluasi,tikror',
            'notes' => 'nullable|string|max:1000',
        ]);
        abort_if($validated['time_end'] <= $validated['time_start'], 422, 'Jam selesai harus lebih besar dari jam mulai.');

        return $validated;
    }

    private function authorizeSchedule(Request $request, TahfidzGroup $group): void
    {
        abort_unless(
            $this->access->canManageGroup($request->user(), $group)
            || $this->access->canInputSetoran($request->user(), $group),
            403,
            'Anda tidak berwenang mengatur jadwal halaqah ini.'
        );
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

        $isMember = TahfidzGroupMember::query()
            ->where('tahfidz_group_id', $group->id)
            ->where('student_id', $validated['student_id'])
            ->where('status', 'aktif')
            ->exists();

        abort_unless($isMember, 422, 'Santri bukan anggota aktif halaqah ini.');

        return $validated;
    }

    private function authorizeTarget(Request $request, TahfidzGroup $group): void
    {
        abort_unless(
            $this->access->canInputSetoran($request->user(), $group)
            || $this->access->canManageGroup($request->user(), $group),
            403,
            'Anda tidak berwenang mengatur target halaqah ini.'
        );
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

        if ($this->access->isSchoolCoordinator($user, $schoolId, $academicYearId)
            || $this->access->isSchoolLeader($user)) {
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
            || $this->access->isSchoolLeader($request->user())
            || $this->access->isTahfidzTeacher($request->user())
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
