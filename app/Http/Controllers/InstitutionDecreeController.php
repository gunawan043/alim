<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\InstitutionDecree;
use App\Models\OtherTeacherTask;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\GtkAdditionalTask;
use App\Models\GtkEmployment;
use App\Models\GtkWorkUnit;
use App\Models\StructuralAssignment;
use App\Models\StructuralPosition;
use App\Models\StructuralPositionWorkUnitHour;
use App\Models\WorkUnit;
use App\Services\NotificationUniversalService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InstitutionDecreeController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $query = InstitutionDecree::with(['academicYear', 'signer', 'school']);

        $schoolId = $request->attributes->get('schoolContextId');
        $currentUser = auth()->user();
        $isGlobal = canPermission('institution-decree-all-access');

        if ($isGlobal) {
            if ($request->filled('school_id')) {
                $query->where('school_id', $request->school_id);
            }
        } elseif ($schoolId) {
            $query->where('school_id', $schoolId);
        } else {
            $query->whereNull('school_id');
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('decree_type')) {
            $query->where('decree_type', 'like', "%{$request->decree_type}%");
        }
        if ($request->filled('search')) {
            $query->where(fn ($q) => $q->where('decree_number', 'like', "%{$request->search}%")
                ->orWhere('title', 'like', "%{$request->search}%"));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $decrees = $query->orderByDesc('issued_date')->paginate(15)->withQueryString();
        $academicYears = AcademicYear::orderByDesc('name')->get();
        $schools = School::orderBy('name')->get();

        return view('institution-decrees.index', compact('decrees', 'academicYears', 'schools', 'userId'));
    }

    public function create(Request $request, string $userId)
    {
        $schoolContextId = $request->attributes->get('schoolContextId');
        $currentUser = auth()->user();
        $isGlobal = canPermission('institution-decree-all-access');

        // Super Admin: school from query param, others from session
        $selectedSchoolId = $isGlobal
            ? ($request->filled('school_id') ? $request->school_id : null)
            : $schoolContextId;

        $selectedAyId = $request->filled('academic_year_id')
            ? $request->academic_year_id
            : (AcademicYear::active()->first()?->id);

        $academicYears = AcademicYear::orderByDesc('name')->get();
        $activeYear = AcademicYear::active()->first();
        $signers = User::whereIn('id', usersHavingPermission('decree.signer.certifiable'))->orderBy('name')->get();
        $schools = School::orderBy('name')->get();

        // Matrix data — only load if school is selected
        $teachers = collect();
        $subjects = collect();
        $studyGroups = collect();

        if ($selectedSchoolId) {
            // Guru: GTK yang tertaut ke sekolah (work unit/kepegawaian); jangan bergantung izin khusus.
            $nonTeachingIds = usersHavingPermission('general_staff.ineligible');
            $teachers = User::query()
                ->when(! empty($nonTeachingIds), fn ($q) => $q->whereNotIn('users.id', $nonTeachingIds))
                ->whereIn('users.id', \App\Services\SchoolGroupService::userIdsForSchool($selectedSchoolId))
                ->orderBy('name')->get();

            $subjects = Subject::where('is_active', true)
                ->when($selectedSchoolId, fn ($q) => $q->where('school_id', $selectedSchoolId))
                ->orderBy('name')->get();

            if ($selectedAyId) {
                $studyGroups = StudyGroup::with('gradeLevel')
                    ->where('school_id', $selectedSchoolId)
                    ->where('academic_year_id', $selectedAyId)
                    ->where('is_active', true)
                    ->get()
                    ->sortBy(fn ($sg) => [$sg->gradeLevel->level ?? 0, $sg->name])
                    ->values();
            }
        }

        $selectedSchool = $selectedSchoolId ? School::find($selectedSchoolId) : null;

        // For non-global users, auto-select school from session and lock decree_type
        $canSelectSchool = $isGlobal;

        return view('institution-decrees.create', compact(
            'userId', 'schools', 'academicYears', 'activeYear', 'signers',
            'selectedSchoolId', 'selectedAyId', 'selectedSchool',
            'teachers', 'subjects', 'studyGroups', 'canSelectSchool'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $schoolContextId = $request->attributes->get('schoolContextId');
        $currentUser = auth()->user();
        $isGlobal = canPermission('institution-decree-all-access');

        $selectedSchoolId = $isGlobal
            ? ($request->filled('school_id') ? $request->school_id : $schoolContextId)
            : $schoolContextId;

        $data = $request->validate([
            'decree_number' => 'required|string|max:100',
            'decree_type' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'academic_year_id' => 'required|exists:academic_years,id',
            'issued_date' => 'required|date',
            'effective_date' => 'required|date|after_or_equal:issued_date',
            'end_date' => 'nullable|date|after_or_equal:effective_date',
            'signed_by' => 'nullable|exists:users,id',
            'signed_position' => 'nullable|string|max:100',
            'status' => 'required|in:draft,active,archived',
        ]);

        $exists = InstitutionDecree::where('decree_number', $data['decree_number'])->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'Nomor SK sudah terdaftar.');
        }

        $data['school_id'] = $selectedSchoolId;

        $decree = InstitutionDecree::create($data);

        if ($data['decree_type'] === 'SK Pembagian Tugas') {
            $this->_saveMatrixFromJson($decree, $request, $selectedSchoolId);
        }

        return redirect()->route('user.institution-decrees.show', [
            'userId' => $userId, 'id' => $decree->id,
        ])->with('success', 'Surat Keputusan berhasil disimpan.');
    }

    private function _saveMatrixFromJson($decree, $request, $schoolId)
    {
        $jsonInput = $request->input('assignments_json');
        $assignments = $jsonInput ? json_decode($jsonInput, true) : [];
        if (empty($assignments)) {
            return;
        }

        foreach ($assignments as $teacherId => $subjects) {
            foreach ($subjects as $subjectId => $groups) {
                foreach ($groups as $studyGroupId => $hours) {
                    $h = is_numeric($hours) ? (int) $hours : null;
                    if (! $h || $h <= 0) {
                        continue;
                    }

                    TeachingAssignment::create([
                        'decree_id' => $decree->id,
                        'teacher_id' => $teacherId,
                        'subject_id' => $subjectId,
                        'study_group_id' => $studyGroupId,
                        'school_id' => $decree->school_id ?? $schoolId,
                        'academic_year_id' => $decree->academic_year_id,
                        'weekly_hours' => $h,
                        'role' => 'guru_mapel',
                        'status' => 'active',
                    ]);
                }
            }
        }

        // Save other teacher tasks
        $tasksJson = $request->input('other_tasks_json');
        $tasks = $tasksJson ? json_decode($tasksJson, true) : [];
        foreach ($tasks as $t) {
            OtherTeacherTask::create([
                'teacher_id' => $t['teacher_id'],
                'school_id' => $decree->school_id ?? $schoolId,
                'academic_year_id' => $decree->academic_year_id,
                'study_group_id' => $t['study_group_id'] ?? null,
                'task_name' => $t['task_name'],
                'task_code' => $t['task_code'] ?? null,
                'weekly_hours' => $t['weekly_hours'] ?? 0,
                'is_active' => true,
            ]);
        }
    }

    public function unified(Request $request, string $userId)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId') ?: $user->employment?->school_id;
        $workUnitId = $request->input('work_unit_id');
        if (! $workUnitId && $schoolId) {
            $workUnitId = School::whereKey($schoolId)->value('work_unit_id');
        }
        if (! $workUnitId) {
            $workUnitId = GtkWorkUnit::where('user_id', $user->id)->where('is_primary', true)->value('work_unit_id');
        }
        $workUnit = $workUnitId ? WorkUnit::find($workUnitId) : null;
        $schools = $workUnit ? School::where('work_unit_id', $workUnit->id)->get() : collect();
        $isSchoolUnit = $schools->isNotEmpty();
        $userIds = collect();
        if ($workUnit) {
            $userIds = GtkWorkUnit::where('work_unit_id', $workUnit->id)->pluck('user_id');
            $userIds = $userIds->merge(GtkEmployment::whereIn('school_id', $schools->pluck('id'))->pluck('user_id'))->unique();
        }
        $employees = User::whereIn('id', $userIds)->where('is_active', true)->orderBy('name')->get();
        $assignments = TeachingAssignment::with(['subject:id,name', 'studyGroup:id,name'])->whereIn('teacher_id', $userIds)->where('status', 'active')->get()->groupBy('teacher_id');
        $additional = collect();
        $additional = $additional->merge(GtkAdditionalTask::whereIn('user_id', $userIds)->where('is_active', true)->get()->groupBy('user_id'));
        $otherTasks = OtherTeacherTask::whereIn('teacher_id', $userIds)->where('is_active', true)->get()->groupBy('teacher_id');
        $positions = StructuralAssignment::with('position')->whereIn('user_id', $userIds)->where('status', 'active')->get()->keyBy('user_id');
        $positionHours = StructuralPositionWorkUnitHour::where('work_unit_id', $workUnit?->id)->pluck('weekly_hours', 'structural_position_id');
        $rows = $employees->map(function (User $employee) use ($assignments, $additional, $otherTasks, $positions, $positionHours) {
            $position = $positions->get($employee->id)?->position;
            $ta = $assignments->get($employee->id, collect());
            $teaching = $ta->map(fn ($a) => $a->subject?->name.' '.$a->studyGroup?->name)->filter()->implode(', ');
            $rombel = $ta->pluck('studyGroup.name')->filter()->unique()->implode(', ');
            $tasks = $additional->get($employee->id, collect())->pluck('nama_tugas')->filter()
                ->merge($otherTasks->get($employee->id, collect())->pluck('task_name'))->filter()->implode(', ');
            $jp = (float) $ta->sum('weekly_hours')
                + (float) ($additional->get($employee->id, collect())->sum('hours_per_week'))
                + (float) ($otherTasks->get($employee->id, collect())->sum('weekly_hours'));
            $posHours = (float) ($position ? ($positionHours[$position->id] ?? 0) : 0);
            return ['name'=>$employee->name,'position'=>$position?->name ?? 'GTK','teaching'=>$teaching,'additional'=>$tasks,'rombel'=>$rombel,'jp'=>$jp,'total_hours'=>$jp+$posHours,'sort'=>$position?->hierarchy_level ?? 99];
        })->sortBy(['sort','name'])->values();
        $kspId = $workUnit?->metadata['head_user_id'] ?? $schools->first()?->principal_user_id;
        $ksp = $kspId ? User::find($kspId) : null;
        $positionsForModal = StructuralPosition::where('is_active', true)->orderBy('name')->get();

        return view('institution-decrees.unified', ['userId'=>$userId, 'workUnit'=>$workUnit, 'rows'=>$rows, 'ksp'=>$ksp, 'positions'=>$positionsForModal, 'positionHours'=>$positionHours, 'isSchoolUnit'=>$isSchoolUnit]);
    }

    public function updatePositionHours(Request $request, string $userId, string $workUnitId)
    {
        $user = $request->user();
        abort_unless($user->hasRole('Humas Personalia') || $user->isSystemAdmin() || $user->isSuperAdmin() || str_contains(strtolower((string) ($user->employment?->jabatan ?? '')), 'kepala') || str_contains(strtolower((string) ($user->employment?->jabatan ?? '')), 'tata usaha'), 403);
        $validated = $request->validate(['hours' => 'required|array', 'hours.*' => 'nullable|numeric|min:0']);
        foreach ($validated['hours'] as $positionId => $hours) {
            StructuralPositionWorkUnitHour::updateOrCreate(
                ['structural_position_id'=>$positionId, 'work_unit_id'=>$workUnitId],
                ['weekly_hours'=>(float) ($hours ?? 0)]
            );
        }

        return back()->with('success', 'JP jabatan untuk satuan kerja disimpan.');
    }

    public function submit(Request $request, string $userId, string $id)
    {
        $decree = InstitutionDecree::findOrFail($id);
        abort_unless($this->canEditDecree($request->user(), $decree), 403);
        abort_unless(in_array($decree->status, ['draft', 'rejected'], true), 422, 'SK tidak dapat diajukan pada status ini.');
        if ($decree->decree_type === 'SK Pembagian Tugas' && $decree->work_unit_id) {
            $missing = $this->positionsWithoutHours($decree->work_unit_id);
            abort_if($missing->isNotEmpty(), 422, 'Lengkapi JP jabatan terlebih dahulu: '.$missing->implode(', '));
        }
        $decree->update(['status' => 'submitted', 'submitted_by' => $request->user()->id, 'submitted_at' => now()]);

        return back()->with('success', 'SK berhasil diajukan.');
    }

    public function review(Request $request, string $userId, string $id)
    {
        abort_unless($this->isPersonalia($request->user()), 403, 'Review hanya dapat dilakukan Personalia.');
        $validated = $request->validate(['review_notes' => 'nullable|string|max:2000']);
        $decree = InstitutionDecree::findOrFail($id);
        $decree->update(['reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_notes' => $validated['review_notes'] ?? null]);

        return back()->with('success', 'Review Personalia disimpan tanpa mengubah isi SK.');
    }

    public function approve(Request $request, string $userId, string $id)
    {
        $decree = InstitutionDecree::findOrFail($id);
        abort_unless($this->isKsp($request->user(), $decree), 403, 'Hanya Kepala Satuan Kerja yang dapat menyetujui SK.');
        abort_unless($decree->status === 'submitted', 422, 'SK belum diajukan.');
        $decree->update(['status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now()]);

        return back()->with('success', 'SK disetujui.');
    }

    public function publish(Request $request, string $userId, string $id)
    {
        $decree = InstitutionDecree::findOrFail($id);
        abort_unless($this->isKsp($request->user(), $decree), 403, 'Hanya Kepala Satuan Kerja yang dapat mengaktifkan SK.');
        abort_unless($decree->status === 'approved', 422, 'SK harus disetujui sebelum diaktifkan.');
        $workUnitId = $decree->work_unit_id ?: $decree->school?->work_unit_id;

        DB::transaction(function () use ($decree, $request, $workUnitId) {
            if ($decree->decree_type === 'SK Pembagian Tugas' && $workUnitId) {
                InstitutionDecree::where('id', '!=', $decree->id)
                    ->where('decree_type', 'SK Pembagian Tugas')
                    ->where('work_unit_id', $workUnitId)
                    ->where('academic_year_id', $decree->academic_year_id)
                    ->where('semester', $decree->semester)
                    ->where('status', 'active')
                    ->update([
                        'status' => 'inactive',
                        'deactivated_by' => $request->user()->id,
                        'deactivated_at' => now(),
                        'deactivation_reason' => 'Digantikan SK aktif baru.',
                    ]);
            }
            $decree->update(['status' => 'active', 'activated_at' => now()]);
        });

        $this->notifyWorkUnit($decree, $request->user());

        return back()->with('success', 'SK dipublikasikan dan aktif. Notifikasi dikirim ke GTK satuan kerja.');
    }

    public function deactivate(Request $request, string $userId, string $id)
    {
        $validated = $request->validate(['deactivation_reason' => 'required|string|max:1000']);
        $decree = InstitutionDecree::findOrFail($id);
        abort_unless($this->isKsp($request->user(), $decree), 403);
        abort_unless($decree->status === 'active', 422, 'SK tidak sedang aktif.');
        $decree->update([
            'status' => 'inactive',
            'deactivated_by' => $request->user()->id,
            'deactivated_at' => now(),
            'deactivation_reason' => $validated['deactivation_reason'],
        ]);

        return back()->with('success', 'SK dinonaktifkan.');
    }

    private function canEditDecree($user, InstitutionDecree $decree): bool
    {
        if ($this->isPersonalia($user) || $user->isSystemAdmin() || $user->isSuperAdmin()) {
            return true;
        }
        if ($user->hasRole('Pimpinan')) {
            return true;
        }
        $jabatan = strtolower((string) ($user->employment?->jabatan ?? ''));
        return str_contains($jabatan, 'kepala satuan pendidikan')
            || str_contains($jabatan, 'wakil kepala')
            || str_contains($jabatan, 'tata usaha')
            || $this->isKsp($user, $decree);
    }

    private function isPersonalia($user): bool
    {
        return $user->hasRole('Humas Personalia') || $user->isSystemAdmin() || $user->isSuperAdmin();
    }

    private function isKsp($user, InstitutionDecree $decree): bool
    {
        if ($user->isSystemAdmin() || $user->isSuperAdmin()) {
            return true;
        }
        if ($decree->school?->principal_user_id && (string) $decree->school->principal_user_id === (string) $user->id) {
            return true;
        }
        $workUnit = $decree->workUnit ?: ($decree->school?->work_unit_id ? WorkUnit::find($decree->school->work_unit_id) : null);
        if ($workUnit && (string) ($workUnit->metadata['head_user_id'] ?? '') === (string) $user->id) {
            return true;
        }
        $jabatan = strtolower((string) ($user->employment?->jabatan ?? ''));
        return str_contains($jabatan, 'kepala satuan pendidikan') && (string) ($user->employment?->school_id ?? '') === (string) $decree->school_id;
    }

    private function positionsWithoutHours(string $workUnitId)
    {
        return \App\Models\StructuralPositionWorkUnitHour::query()
            ->where('work_unit_id', $workUnitId)
            ->where('weekly_hours', '<=', 0)
            ->with('position:id,name')
            ->get()->pluck('position.name')->filter()->values();
    }

    private function notifyWorkUnit(InstitutionDecree $decree, $publisher): void
    {
        $workUnitId = $decree->work_unit_id ?: $decree->school?->work_unit_id;
        if (! $workUnitId) {
            return;
        }
        $service = app(NotificationUniversalService::class);
        $ids = $service->userIdsForWorkUnit($workUnitId);
        foreach ($ids as $recipientId) {
            if ((string) $recipientId === (string) $publisher->id) {
                continue;
            }
            $service->send((string) $recipientId, [
                'module' => 'system', 'type' => 'info', 'priority' => 'high',
                'title' => 'SK Pembagian Tugas aktif',
                'message' => $decree->decree_number.' — '.$decree->title.' telah aktif.',
                'action_url' => route('user.institution-decrees.show', ['userId' => $recipientId, 'id' => $decree->id]),
                'reference_type' => InstitutionDecree::class,
                'reference_id' => $decree->id,
                'action_text' => 'Lihat SK',
            ]);
        }
    }

    /**
     * Data matriks pembagian tugas (dipakai halaman detail & cetak SK).
     */
    private function buildDecreeMatrixData(InstitutionDecree $decree): array
    {
        $teacherIds = app(\App\Services\TeacherRosterService::class)->idsForSchool($decree->school_id);

        $teachers = User::whereIn('id', $teacherIds)
            ->whereHas('employments', fn ($q) => $q->where('school_id', $decree->school_id))
            ->orderBy('name')
            ->get();

        $subjects = Subject::query()
            ->when($decree->school_id, fn ($q) => $q->where('school_id', $decree->school_id))
            ->orderBy('name')
            ->get();

        $studyGroups = collect();
        if ($decree->school_id && $decree->academic_year_id) {
            $studyGroups = StudyGroup::with('gradeLevel')
                ->where('school_id', $decree->school_id)
                ->where('academic_year_id', $decree->academic_year_id)
                ->where('is_active', true)
                ->get()
                ->sortBy(fn ($sg) => [$sg->gradeLevel->level ?? 0, $sg->name])
                ->values();
        }

        $activeAssignments = $decree->teachingAssignments->where('status', 'active');

        $matrix = [];
        foreach ($activeAssignments as $assignment) {
            $matrix[$assignment->teacher_id][$assignment->subject_id][$assignment->study_group_id] = $assignment->weekly_hours;
        }

        $sortedGrades = $studyGroups->groupBy(fn ($sg) => $sg->gradeLevel->level ?? 0)->sortKeys();

        $otherTeacherTasks = OtherTeacherTask::query()
            ->where('academic_year_id', $decree->academic_year_id)
            ->when($decree->school_id, fn ($q) => $q->where('school_id', $decree->school_id))
            ->get()
            ->groupBy('teacher_id');

        $teacherRows = [];
        foreach ($teachers as $teacher) {
            $tAssignments = $activeAssignments->where('teacher_id', $teacher->id);
            if ($tAssignments->isEmpty()) {
                continue;
            }

            $taskHours = (float) $otherTeacherTasks->get($teacher->id, collect())->sum('weekly_hours');

            $bySubject = $tAssignments->groupBy('subject_id');
            $firstSubjectId = $bySubject->keys()->first();

            foreach ($bySubject as $subjectId => $subjectAssignments) {
                $rowHours = [];
                $totalHours = 0;
                foreach ($studyGroups as $sg) {
                    $hours = $matrix[$teacher->id][$subjectId][$sg->id] ?? null;
                    $rowHours[$sg->id] = $hours;
                    if ($hours) {
                        $totalHours += $hours;
                    }
                }

                $teacherRows[] = [
                    'teacher' => $teacher,
                    'subject' => $subjects->get($subjectId),
                    'hours' => $rowHours,
                    'total' => $totalHours,
                    'teachingHours' => $totalHours,
                    'totalHours' => $totalHours + (int) $taskHours,
                    'is_first_subject' => $subjectId === $firstSubjectId,
                ];
            }
        }

        return compact('teachers', 'subjects', 'studyGroups', 'sortedGrades', 'teacherRows', 'matrix', 'otherTeacherTasks');
    }

    public function show(Request $request, string $userId, string $id)
    {
        try {
            $decree = InstitutionDecree::with(['academicYear', 'school', 'signer', 'teachingAssignments'])->findOrFail($id);

            if ($request->wantsJson()) {
                return response()->json(['data' => $decree]);
            }

            return view('institution-decrees.show', array_merge(
                ['decree' => $decree, 'userId' => $userId],
                $this->buildDecreeMatrixData($decree)
            ));
        } catch (\Throwable $e) {
            Log::error('InstitutionDecree show failed', [
                'decree_id' => $id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            abort(500, 'Gagal memuat Surat Keputusan.');
        }
    }

    public function print(Request $request, string $userId, string $id)
    {
        try {
            $decree = InstitutionDecree::with(['academicYear', 'school', 'signer', 'teachingAssignments'])->findOrFail($id);

            return app(\App\Services\Pdf\DocumentPdfService::class)->fromView(
                'institution-decrees.print',
                array_merge(
                    ['decree' => $decree, 'userId' => $userId],
                    $this->buildDecreeMatrixData($decree)
                ),
                'SK-'.$decree->decree_number,
                'landscape',
            );
        } catch (\Throwable $e) {
            Log::error('InstitutionDecree print failed', [
                'decree_id' => $id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            abort(500, 'Gagal mencetak Surat Keputusan.');
        }
    }

    public function edit(Request $request, string $userId, string $id)
    {
        try {
            $decree = InstitutionDecree::with('academicYear', 'school', 'signer')->findOrFail($id);

            $academicYears = AcademicYear::orderByDesc('name')->get();
            $activeYear = AcademicYear::active()->first();
            $signers = User::whereIn('id', usersHavingPermission('decree.signer.certifiable'))->orderBy('name')->get();
            $schools = School::orderBy('name')->get();

            $currentUser = auth()->user();
            $isGlobal = canPermission('institution-decree-all-access');
            $canSelectSchool = $isGlobal;

            return view('institution-decrees.edit', compact(
                'decree', 'userId', 'academicYears', 'activeYear',
                'signers', 'schools', 'canSelectSchool'
            ));
        } catch (\Throwable $e) {
            Log::error('InstitutionDecree edit failed', [
                'decree_id' => $id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            abort(500, 'Gagal memuat form edit Surat Keputusan.');
        }
    }

    public function update(Request $request, string $userId, string $id)
    {
        $decree = InstitutionDecree::findOrFail($id);

        $data = $request->validate([
            'decree_number' => 'required|string|max:100|unique:institution_decrees,decree_number,'.$decree->id,
            'decree_type' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'academic_year_id' => 'required|exists:academic_years,id',
            'issued_date' => 'required|date',
            'effective_date' => 'required|date|after_or_equal:issued_date',
            'end_date' => 'nullable|date|after_or_equal:effective_date',
            'signed_by' => 'nullable|exists:users,id',
            'signed_position' => 'nullable|string|max:100',
            'status' => 'required|in:draft,active,archived',
        ]);

        try {
            DB::transaction(function () use ($decree, $data) {
                $decree->update($data);
            });

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Surat Keputusan berhasil diperbarui.',
                    'data' => $decree->fresh(),
                ]);
            }

            return redirect()->route('user.institution-decrees.show', [
                'userId' => $userId, 'id' => $decree->id,
            ])->with('success', 'Surat Keputusan berhasil diperbarui.');
        } catch (\Throwable $e) {
            Log::error('InstitutionDecree update failed', [
                'decree_id' => $id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Gagal memperbarui Surat Keputusan.'], 500);
            }

            return back()->withInput()->with('error', 'Gagal memperbarui Surat Keputusan.');
        }
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $decree = InstitutionDecree::findOrFail($id);

        try {
            DB::transaction(function () use ($decree) {
                TeachingAssignment::where('decree_id', $decree->id)->delete();
                $decree->delete();
            });

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Surat Keputusan berhasil dihapus.']);
            }

            return redirect()->route('user.institution-decrees.index', [
                'userId' => $userId,
            ])->with('success', 'Surat Keputusan berhasil dihapus.');
        } catch (\Throwable $e) {
            Log::error('InstitutionDecree destroy failed', [
                'decree_id' => $id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Gagal menghapus Surat Keputusan.'], 500);
            }

            return back()->with('error', 'Gagal menghapus Surat Keputusan.');
        }
    }
}
