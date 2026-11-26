<?php

namespace App\Http\Controllers\Tahfidz;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\GtkEmployment;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
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
use App\Services\TahfidzTargetService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class HalaqahController extends Controller
{
    public function __construct(
        private readonly TahfidzAccess $access,
        private readonly TahfidzProgressService $progress,
        private readonly TahfidzTargetService $targets,
    ) {}

    public function index(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $schoolId = $this->schoolId($request);
        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $activeYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $academicYearId = $request->input('academic_year_id', $activeYear?->id);

        $query = TahfidzGroup::with(['school:id,name', 'studyGroup:id,name', 'gradeLevel:id,name', 'package:id,name', 'teacher:id,name', 'coordinator:id,name'])
            ->withCount('members')
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->when($request->filled('school_id'), fn ($q) => $q->where('school_id', $request->school_id))
            ->when($request->filled('grade_level_id'), fn ($q) => $q->where('grade_level_id', $request->grade_level_id));

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

        // Sekolah di luar kewenangan tidak boleh dipakai untuk membuat halaqah.
        $this->assertSchoolAllowed($request, $school->id);

        // Gender mengikuti gender satuan pendidikan, bukan pilihan pengguna.
        $gender = $school->school_gender ?: ($validated['gender'] ?? 'putra');
        $coordinatorId = $this->access->coordinatorForSchool($school->id, $validated['academic_year_id'])?->id
            ?? ($validated['coordinator_id'] ?? null);

        $group = TahfidzGroup::create([
            'work_unit_id' => $school->work_unit_id,
            'school_id' => $school->id,
            'study_group_id' => $validated['study_group_id'] ?? null,
            'grade_level_id' => $this->resolveGradeLevelId($validated),
            'academic_year_id' => $validated['academic_year_id'],
            'tahfidz_package_id' => $validated['tahfidz_package_id'] ?? null,
            'curriculum_id' => $validated['curriculum_id'] ?? null,
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'gender' => $gender,
            'teacher_id' => $validated['teacher_id'],
            'coordinator_id' => $coordinatorId,
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

        $members = $this->attachMembers($group, $validated['member_ids'] ?? []);

        return redirect()->route('user.tahfidz.halaqah.show', ['userId' => $userId, 'id' => $group->id])
            ->with('success', 'Halaqah berhasil dibuat.'.$this->memberSummary($members));
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
            ->where('tahfidz_group_id', $group->id)
            ->where('academic_year_id', $group->academic_year_id)
            ->where('status', 'aktif')
            ->pluck('student_id');

        // Santri tinggal ditarik dari daftar santri sekolah. Santri yang sudah
        // punya halaqah lain tetap ditampilkan; saat ditambahkan ia dipindahkan.
        $availableStudents = Student::query()
            ->where('school_id', $group->school_id)
            ->where('status', 'active')
            ->whereNotIn('id', $activeStudentIds)
            ->orderBy('name')
            ->limit(500)
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
        $validated = $this->validateGroup($request, $group);
        $school = School::findOrFail($validated['school_id']);
        $this->assertSchoolAllowed($request, $school->id);

        $gender = $school->school_gender ?: ($validated['gender'] ?? $group->gender);
        $coordinatorId = $this->access->coordinatorForSchool($school->id, $validated['academic_year_id'])?->id
            ?? $group->coordinator_id;

        $group->update([
            'work_unit_id' => $school->work_unit_id,
            'school_id' => $school->id,
            'study_group_id' => $validated['study_group_id'] ?? null,
            'grade_level_id' => $this->resolveGradeLevelId($validated),
            'academic_year_id' => $validated['academic_year_id'],
            'tahfidz_package_id' => $validated['tahfidz_package_id'] ?? null,
            'curriculum_id' => $validated['curriculum_id'] ?? null,
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'gender' => $gender,
            'teacher_id' => $validated['teacher_id'],
            'coordinator_id' => $coordinatorId,
            'max_members' => $validated['max_members'] ?? null,
            'room' => $validated['room'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'notes' => $validated['notes'] ?? null,
        ]);

        $members = $this->attachMembers($group, $validated['member_ids'] ?? []);

        return redirect()->route('user.tahfidz.halaqah.show', ['userId' => $userId, 'id' => $group->id])
            ->with('success', 'Halaqah diperbarui.'.$this->memberSummary($members));
    }

    public function addMembers(Request $request, string $userId, string $id)
    {
        $group = TahfidzGroup::findOrFail($id);
        $this->authorizeGroupManage($request, $group);
        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
        ]);

        $result = $this->attachMembers($group, $request->input('student_ids', []));

        if ($result['added'] === 0 && $result['moved'] === 0) {
            $reason = ($result['grade_mismatch'] ?? 0) > 0
                ? 'Semua santri yang dipilih berasal dari tingkat berbeda. Halaqah hanya boleh berisi satu tingkat; ubah Tingkat halaqah atau pilih santri lain.'
                : 'Tidak ada santri baru yang ditambahkan.'.$this->memberSummary($result);

            return back()->with('error', $reason);
        }

        return back()->with('success', 'Santri ditambahkan ke halaqah.'.$this->memberSummary($result));
    }

    /**
     * Tarik santri ke halaqah. Santri yang sudah aktif di halaqah lain pada
     * tahun ajaran yang sama dipindahkan (bukan ditolak), sesuai alur
     * "santri halaqah tinggal ditarik".
     *
     * @param  array<int, string>  $studentIds
     * @return array{added: int, moved: int, skipped: int}
     */
    private function attachMembers(TahfidzGroup $group, array $studentIds): array
    {
        $added = 0;
        $moved = 0;
        $skipped = 0;
        $gradeMismatch = 0;

        $studentIds = array_values(array_unique($studentIds));

        // Satu halaqah hanya boleh berisi santri dari satu tingkat. Rombel boleh
        // berbeda (halaqah lintas rombel), tetapi tingkatnya harus sama.
        $gradeByStudent = collect();

        if ($group->grade_level_id && $studentIds !== []) {
            $gradeByStudent = StudentClassHistory::query()
                ->whereIn('student_id', $studentIds)
                ->where('is_active', true)
                ->with('studyGroup:id,grade_level_id')
                ->get()
                ->groupBy('student_id')
                ->map(fn ($rows) => $rows->firstWhere('academic_year_id', $group->academic_year_id)?->studyGroup?->grade_level_id
                    ?? $rows->sortByDesc('created_at')->first()?->studyGroup?->grade_level_id);
        }

        foreach ($studentIds as $studentId) {
            $student = Student::where('school_id', $group->school_id)->find($studentId);

            if (! $student) {
                $skipped++;

                continue;
            }

            if ($group->grade_level_id) {
                $studentGrade = $gradeByStudent[$studentId] ?? null;

                if ($studentGrade !== null && (string) $studentGrade !== (string) $group->grade_level_id) {
                    $gradeMismatch++;

                    continue;
                }
            }

            $alreadyHere = TahfidzGroupMember::query()
                ->where('tahfidz_group_id', $group->id)
                ->where('student_id', $studentId)
                ->where('academic_year_id', $group->academic_year_id)
                ->where('status', 'aktif')
                ->exists();

            if ($alreadyHere) {
                $skipped++;

                continue;
            }

            $otherMemberships = TahfidzGroupMember::query()
                ->where('student_id', $studentId)
                ->where('academic_year_id', $group->academic_year_id)
                ->where('status', 'aktif')
                ->where('tahfidz_group_id', '!=', $group->id)
                ->get();

            foreach ($otherMemberships as $membership) {
                $membership->update([
                    'status' => 'pindah_halaqah',
                    'leave_date' => now()->toDateString(),
                ]);
                $moved++;
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

        return [
            'added' => $added,
            'moved' => $moved,
            'skipped' => $skipped,
            'grade_mismatch' => $gradeMismatch,
        ];
    }

    /**
     * Tingkat halaqah dipilih langsung sebagai kelas (1, 2, 3 …), bukan
     * diturunkan dari rombel: satu halaqah boleh lintas rombel.
     *
     * @param  array<string, mixed>  $validated
     */
    private function resolveGradeLevelId(array $validated): ?string
    {
        if (! empty($validated['grade_level_id'])) {
            return $validated['grade_level_id'];
        }

        if (empty($validated['study_group_id'])) {
            return null;
        }

        return StudyGroup::whereKey($validated['study_group_id'])->value('grade_level_id');
    }

    /**
     * @param  array{added: int, moved: int, skipped: int}  $result
     */
    private function memberSummary(array $result): string
    {
        if ($result['added'] === 0 && $result['moved'] === 0 && $result['skipped'] === 0 && ($result['grade_mismatch'] ?? 0) === 0) {
            return '';
        }

        $parts = [];
        if ($result['added'] > 0) {
            $parts[] = $result['added'].' santri ditambahkan';
        }
        if ($result['moved'] > 0) {
            $parts[] = $result['moved'].' dipindahkan dari halaqah lain';
        }
        if ($result['skipped'] > 0) {
            $parts[] = $result['skipped'].' dilewati (sudah di halaqah ini / bukan santri sekolah ini)';
        }
        if (($result['grade_mismatch'] ?? 0) > 0) {
            $parts[] = $result['grade_mismatch'].' ditolak karena beda tingkat dengan halaqah ini';
        }

        return ' '.implode(', ', $parts).'.';
    }

    public function removeMember(Request $request, string $userId, string $id, string $memberId)
    {
        $group = TahfidzGroup::findOrFail($id);
        $this->authorizeGroupManage($request, $group);
        $member = TahfidzGroupMember::where('tahfidz_group_id', $group->id)->findOrFail($memberId);
        $member->update(['status' => 'pindah_halaqah', 'leave_date' => now()->toDateString()]);

        return back()->with('success', 'Santri dikeluarkan dari halaqah.');
    }

    public function applyTargets(Request $request, string $userId, string $id)
    {
        $group = TahfidzGroup::findOrFail($id);
        $this->authorizeTarget($request, $group);

        $count = $this->targets->applyToGroup($group, $request->user()?->id);

        if ($count === 0) {
            return back()->with('error', 'Target muqorror belum tersedia untuk jenjang/halaqah ini.');
        }

        return back()->with('success', $count.' target santri berhasil diterapkan dari muqorror.');
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
        $this->assertScheduleHasNoConflict($group, $validated);

        TahfidzSchedule::create([
            'tahfidz_group_id' => $group->id,
            'academic_year_id' => $group->academic_year_id,
            'day_of_week' => $validated['day_of_week'],
            'time_start' => $validated['time_start'],
            'time_end' => $validated['time_end'],
            'room' => $validated['room'] ?? null,
            'schedule_type' => $validated['schedule_type'],
            'is_active' => $request->boolean('is_active', true),
            'effective_from' => $validated['effective_from'] ?? null,
            'effective_until' => $validated['effective_until'] ?? null,
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
        $this->assertScheduleHasNoConflict($group, $validated, $schedule->id);

        $schedule->update([
            'day_of_week' => $validated['day_of_week'],
            'time_start' => $validated['time_start'],
            'time_end' => $validated['time_end'],
            'room' => $validated['room'] ?? null,
            'schedule_type' => $validated['schedule_type'],
            'is_active' => $request->boolean('is_active', true),
            'effective_from' => $validated['effective_from'] ?? null,
            'effective_until' => $validated['effective_until'] ?? null,
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
            'effective_from' => 'nullable|date',
            'effective_until' => 'nullable|date|after_or_equal:effective_from',
            'notes' => 'nullable|string|max:1000',
        ]);
        abort_if($validated['time_end'] <= $validated['time_start'], 422, 'Jam selesai harus lebih besar dari jam mulai.');

        return $validated;
    }

    private function assertScheduleHasNoConflict(TahfidzGroup $group, array $data, ?string $ignoreId = null): void
    {
        $conflicts = app(\App\Services\TahfidzScheduleConflictService::class)
            ->conflicts($group->id, $data, $ignoreId, $group->academic_year_id);

        if (! empty($conflicts)) {
            throw ValidationException::withMessages(['schedule' => $conflicts]);
        }
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
        $canChooseSchool = $this->canChooseSchoolOnForm($request, $group);
        $schools = $canChooseSchool
            ? School::orderBy('name')->get(['id', 'name', 'school_gender'])
            : School::whereKey($schoolId)->get(['id', 'name', 'school_gender']);
        $gradeLevels = GradeLevel::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderBy('level')
            ->get(['id', 'name', 'level']);

        // Guru halaqah hanya Guru Tahfidz pada satuan pendidikan tersebut.
        $teachers = $this->tahfidzTeachers($schoolId, $group);

        // Koordinator diisi otomatis dari tugas tambahan, tidak dipilih manual.
        $autoCoordinator = $group?->coordinator
            ?? $this->access->coordinatorForSchool($schoolId, $group?->academic_year_id);

        // Gender halaqah mengikuti gender satuan pendidikan (tidak dipilih).
        $schoolGender = $schools->firstWhere('id', $schoolId)?->school_gender
            ?? $group?->gender;

        // Santri tinggal ditarik dari daftar santri sekolah. Kelasnya ikut dimuat
        // agar form dapat menandai tingkat/rombel setiap santri.
        $students = Student::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'active')
            ->with(['currentClassHistory.studyGroup.gradeLevel'])
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name', 'nis', 'school_id']);

        return [
            'userId' => $request->route('userId'),
            'group' => $group,
            'academicYears' => $academicYears,
            'schools' => $schools,
            'canChooseSchool' => $canChooseSchool,
            'gradeLevels' => $gradeLevels,
            'teachers' => $teachers,
            'autoCoordinator' => $autoCoordinator,
            'schoolGender' => $schoolGender,
            'students' => $students,
            'schoolId' => $schoolId,
        ];
    }

    /**
     * Sekolah hanya dapat dipilih oleh Departemen Tahfidz (dan super admin).
     * Selain itu sekolah dikunci pada konteks satuan pendidikan pengguna.
     */
    private function canChooseSchoolOnForm(Request $request, ?TahfidzGroup $group = null): bool
    {
        if ($group) {
            return false;
        }

        return $this->access->canViewAllSchools($request->user());
    }

    /**
     * Guru yang boleh mengampu halaqah: Guru Tahfidz di satuan pendidikan
     * tersebut (dari jabatan, role, tugas tambahan, atau penugasan halaqah
     * yang sudah ada). Daftar TIDAK diperlebar ke seluruh GTK: guru non-tahfidz
     * tidak boleh tampil di pilihan pengampu.
     *
     * @return Collection<int, User>
     */
    private function tahfidzTeachers(?string $schoolId, ?TahfidzGroup $group = null): Collection
    {
        if (! $schoolId) {
            return collect();
        }

        $ids = collect();

        // 1) Jabatan kepegawaian di sekolah ini bernuansa tahfidz.
        $ids = $ids->merge(
            GtkEmployment::query()
                ->where('school_id', $schoolId)
                ->where(function ($q) {
                    $q->where('jabatan', 'like', '%tahfidz%')
                        ->orWhere('jabatan', 'like', '%tahfiz%')
                        ->orWhere('jabatan', 'like', '%musyrif%');
                })
                ->pluck('user_id')
        );

        // 2) Role / tugas tambahan tahfidz, tetap dibatasi pada GTK sekolah ini.
        $ids = $ids->merge(
            User::query()
                ->whereHas('employment', fn ($q) => $q->where('school_id', $schoolId))
                ->where(function ($q) {
                    $q->whereHas('roles', fn ($r) => $r->where('name', 'like', '%Tahfidz%')->orWhere('name', 'like', '%Tahfiz%')->orWhere('name', 'like', '%Musyrif%'))
                        ->orWhereHas('tugasTambahan', fn ($t) => $t->where('nama_tugas', 'like', '%Tahfidz%')->orWhere('nama_tugas', 'like', '%Tahfiz%')->orWhere('nama_tugas', 'like', '%Musyrif%'));
                })
                ->pluck('id')
        );

        // 3) Sudah mengampu halaqah di sekolah ini.
        $ids = $ids->merge(
            TahfidzGroupTeacher::query()
                ->where('is_active', true)
                ->whereHas('group', fn ($q) => $q->where('school_id', $schoolId))
                ->pluck('user_id')
        );

        // 4) Pengampu halaqah saat ini tetap muncul ketika mengedit.
        if ($group) {
            $ids->push($group->teacher_id, $group->coordinator_id);
        }

        $teachers = User::query()
            ->whereIn('id', $ids->filter()->unique()->values())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return $teachers;
    }

    private function validateGroup(Request $request, ?TahfidzGroup $group = null): array
    {
        $schoolId = $group?->school_id ?? $this->schoolId($request);

        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'study_group_id' => 'nullable|exists:study_groups,id',
            'grade_level_id' => 'nullable|exists:grade_levels,id',
            'tahfidz_package_id' => 'nullable|exists:tahfidz_packages,id',
            'curriculum_id' => 'nullable|exists:tahfidz_curriculums,id',
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:20',
            'gender' => 'nullable|in:putra,putri',
            'teacher_id' => 'required|exists:users,id',
            'coordinator_id' => 'nullable|exists:users,id',
            'max_members' => 'nullable|integer|min:1|max:100',
            'room' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:students,id',
        ]);

        // Form mengunci sekolah pada satuan pendidikan pengguna; hanya
        // Departemen Tahfidz yang boleh memilih sekolah lain.
        if (! $this->access->canViewAllSchools($request->user())) {
            $validated['school_id'] = $schoolId ?? $validated['school_id'];
        }

        $this->assertTeacherIsTahfidz($validated['teacher_id'], $validated['school_id'], $group);

        return $validated;
    }

    private function assertSchoolAllowed(Request $request, string $schoolId): void
    {
        if ($this->access->canViewAllSchools($request->user())) {
            return;
        }

        abort_unless(
            (string) $schoolId === (string) $this->schoolId($request),
            403,
            'Anda hanya dapat mengelola halaqah pada satuan pendidikan Anda.'
        );
    }

    private function assertTeacherIsTahfidz(string $teacherId, string $schoolId, ?TahfidzGroup $group = null): void
    {
        $allowed = $this->tahfidzTeachers($schoolId, $group)->pluck('id')->all();

        if ($allowed !== [] && ! in_array($teacherId, $allowed, true)) {
            throw ValidationException::withMessages([
                'teacher_id' => 'Guru halaqah harus Guru Tahfidz pada satuan pendidikan ini.',
            ]);
        }
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
