<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AdditionalTaskType;
use App\Models\AdditionalTaskTypeWorkUnitHour;
use App\Models\GtkEmployment;
use App\Models\GtkWorkUnit;
use App\Models\InstitutionDecree;
use App\Models\OtherTeacherTask;
use App\Models\School;
use App\Models\StructuralPosition;
use App\Models\StructuralAssignment;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Models\Subject;
use App\Models\TeachingAssignment;
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

        $currentAssignments = [];
        $currentTasks = [];
        $currentPositions = [];
        $currentPositionIds = [];
        $positionOptions = collect();
        $jpDefaults = [];
        $taskTypeOptions = collect();
        if ($teachers->isNotEmpty()) {
            $teacherIds = $teachers->pluck('id');
            $workUnitId = $selectedSchool?->work_unit_id;

            $jpDefaults = StudyGroupSubject::query()
                ->whereIn('study_group_id', $studyGroups->pluck('id'))
                ->whereIn('subject_id', $subjects->pluck('id'))
                ->where('is_active', true)
                ->get()
                ->mapWithKeys(fn ($row) => [$row->subject_id.'|'.$row->study_group_id => (float) $row->weekly_hours])
                ->all();

            $teachingRows = TeachingAssignment::with(['subject:id,name', 'studyGroup:id,name'])
                ->whereIn('teacher_id', $teacherIds)
                ->where('status', 'active')
                ->when($selectedSchoolId, fn ($q) => $q->where('school_id', $selectedSchoolId))
                ->when($selectedAyId, fn ($q) => $q->where('academic_year_id', $selectedAyId))
                ->get();

            $structuralRows = StructuralAssignment::with('position:id,name')
                ->whereIn('user_id', $teacherIds)
                ->where('status', 'active')
                ->when($selectedSchoolId, fn ($q) => $q->where('school_id', $selectedSchoolId))
                ->when($selectedAyId, fn ($q) => $q->where('academic_year_id', $selectedAyId))
                ->get()
                ->keyBy('user_id');

            $employmentRows = GtkEmployment::with('jabatanRel:id,name')
                ->whereIn('user_id', $teacherIds)
                ->when($selectedSchoolId, fn ($q) => $q->where('school_id', $selectedSchoolId))
                ->orderByDesc('academic_year_id')
                ->get()
                ->groupBy('user_id');

            $workUnitRows = GtkWorkUnit::query()
                ->whereIn('user_id', $teacherIds)
                ->when($workUnitId, fn ($q) => $q->where('work_unit_id', $workUnitId))
                ->get()
                ->keyBy('user_id');

            $workUnitPositionNames = GtkWorkUnit::query()
                ->when($workUnitId, fn ($q) => $q->where('work_unit_id', $workUnitId))
                ->pluck('jabatan')
                ->filter()
                ->map(fn ($name) => mb_strtolower(trim($name)))
                ->unique();

            $positionOptions = StructuralPosition::active()
                ->orderBy('urutan')
                ->orderBy('name')
                ->get(['id', 'name', 'urutan'])
                ->filter(fn ($position) => $workUnitPositionNames->isEmpty()
                    || $workUnitPositionNames->contains(mb_strtolower($position->name)))
                ->values();

            if ($positionOptions->isEmpty()) {
                $positionOptions = StructuralPosition::active()
                    ->orderBy('urutan')
                    ->orderBy('name')
                    ->get(['id', 'name', 'urutan']);
            }

            $positionOptionsByName = $positionOptions->keyBy(fn ($position) => mb_strtolower($position->name));

            $currentPositions = [];
            $currentPositionIds = [];
            foreach ($teachers as $teacher) {
                $structural = $structuralRows->get($teacher->id);
                $employment = $employmentRows->get($teacher->id)?->first();
                $workUnitPosition = $workUnitRows->get($teacher->id)?->jabatan;
                $positionName = $workUnitPosition
                    ?? $structural?->position?->name
                    ?? $employment?->jabatanRel?->name
                    ?? $employment?->jabatan
                    ?? 'GTK';
                $currentPositions[$teacher->id] = $positionName;
                $currentPositionIds[$teacher->id] = $structural?->position_id
                    ?? $employment?->jabatan_id
                    ?? $positionOptionsByName->get(mb_strtolower($positionName))?->id;
            }

            $additionalRows = \App\Models\GtkAdditionalTask::whereIn('user_id', $teacherIds)
                ->when($workUnitId, fn ($q) => $q->where('work_unit_id', $workUnitId))
                ->get();

            $otherRows = OtherTeacherTask::whereIn('teacher_id', $teacherIds)
                ->where('is_active', true)
                ->when($selectedSchoolId, fn ($q) => $q->where('school_id', $selectedSchoolId))
                ->when($workUnitId, fn ($q) => $q->where(fn ($unit) => $unit
                    ->where('work_unit_id', $workUnitId)
                    ->orWhereNull('work_unit_id')
                    ->orWhere('work_unit_id', '')))
                ->get();

            foreach ($teachers as $teacher) {
                $currentAssignments[$teacher->id] = $teachingRows
                    ->where('teacher_id', $teacher->id)
                    ->map(fn ($row) => [
                        'subject_id' => $row->subject_id,
                        'subject' => $row->subject?->name ?? '-',
                        'study_group_id' => $row->study_group_id,
                        'rombel' => $row->studyGroup?->name ?? '-',
                        'hours' => (int) $row->weekly_hours,
                    ])->values()->all();

                $currentTasks[$teacher->id] = collect($additionalRows->where('user_id', $teacher->id)
                    ->map(fn ($row) => [
                        'name' => $row->nama_tugas,
                        'hours' => (int) $row->hours_per_week,
                        'code' => 'additional',
                        'study_group_id' => null,
                    ])->values()->all())
                    ->merge($otherRows->where('teacher_id', $teacher->id)->map(fn ($row) => [
                        'name' => $row->task_name,
                        'hours' => (int) $row->weekly_hours,
                        'code' => $row->task_code,
                        'study_group_id' => $row->study_group_id,
                    ])->values()->all())
                    ->values()->all();
            }

            $taskHourMap = AdditionalTaskTypeWorkUnitHour::where('work_unit_id', $workUnitId)
                ->pluck('weekly_hours', 'additional_task_type_id');

            $jenisGtkIds = $employmentRows->flatten()->pluck('jenis_gtk_id')->filter()->unique();
            $taskTypeOptions = AdditionalTaskType::active()
                ->when($jenisGtkIds->isNotEmpty(), fn ($q) => $q->whereIn('jenis_gtk_id', $jenisGtkIds))
                ->orderBy('urutan')
                ->orderBy('nama')
                ->get(['id', 'nama', 'kode', 'jenis_gtk_id']);

            if ($taskTypeOptions->isEmpty()) {
                $taskTypeOptions = AdditionalTaskType::active()
                    ->orderBy('urutan')
                    ->orderBy('nama')
                    ->get(['id', 'nama', 'kode', 'jenis_gtk_id']);
            }

            $taskTypeOptions = $taskTypeOptions->map(fn ($type) => [
                'id' => $type->id,
                'nama' => $type->nama,
                'kode' => $type->kode,
                'hours' => (float) ($taskHourMap[$type->id] ?? ($type->kode === 'wali-kelas' ? 12 : 3)),
            ])->values();

            $positionOrder = $positionOptions->mapWithKeys(
                fn ($position) => [mb_strtolower($position->name) => (int) $position->urutan]
            );
            $teachers = $teachers->sortBy(function ($teacher) use ($currentPositions, $positionOrder) {
                $position = mb_strtolower($currentPositions[$teacher->id] ?? '');
                return sprintf('%03d|%s', $positionOrder->get($position, 999), mb_strtolower($teacher->name));
            })->values();
        }

        // For non-global users, auto-select school from session and lock decree_type
        $canSelectSchool = $isGlobal;

        return view('institution-decrees.create', compact(
            'userId', 'schools', 'academicYears', 'activeYear', 'signers',
            'selectedSchoolId', 'selectedAyId', 'selectedSchool',
            'teachers', 'subjects', 'studyGroups', 'canSelectSchool', 'currentAssignments', 'currentTasks', 'currentPositions', 'currentPositionIds', 'positionOptions', 'jpDefaults', 'taskTypeOptions'
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
            $this->_savePositionsFromJson($request, $selectedSchoolId);
        }

        return redirect()->route('user.institution-decrees.show', [
            'userId' => $userId, 'id' => $decree->id,
        ])->with('success', 'Surat Keputusan berhasil disimpan.');
    }

    private function _savePositionsFromJson(Request $request, ?string $schoolId): void
    {
        $raw = $request->input('positions_json');
        $positions = $raw ? json_decode($raw, true) : [];
        if (! is_array($positions) || empty($positions) || ! $schoolId) {
            return;
        }

        $teacherIds = array_keys($positions);
        $workUnitId = School::find($schoolId)?->work_unit_id;
        $workUnitRows = GtkWorkUnit::whereIn('user_id', $teacherIds)
            ->when($workUnitId, fn ($q) => $q->where('work_unit_id', $workUnitId))
            ->get()
            ->keyBy('user_id');
        $employments = GtkEmployment::whereIn('user_id', $teacherIds)
            ->where('school_id', $schoolId)
            ->get()
            ->keyBy('user_id');

        foreach ($positions as $teacherId => $positionId) {
            $position = $positionId ? StructuralPosition::find($positionId) : null;
            $positionName = $position?->name;

            $workUnitRow = $workUnitRows->get($teacherId);
            if ($workUnitRow) {
                $workUnitRow->update(['jabatan' => $positionName]);
                continue;
            }

            $employment = $employments->get($teacherId);
            if (! $employment) {
                continue;
            }

            $employment->update([
                'jabatan_id' => $position?->id,
                'jabatan' => $positionName,
            ]);
        }
    }

    private function _saveMatrixFromJson($decree, $request, $schoolId)
    {
        $jsonInput = $request->input('assignments_json');
        $assignments = $jsonInput ? json_decode($jsonInput, true) : [];

        $subjectNames = Subject::whereIn('id', collect($assignments)->flatMap(fn ($subjects) => array_keys($subjects))->unique())
            ->pluck('name', 'id');
        $usedSubjectGroups = [];

        foreach ($assignments as $teacherId => $subjects) {
            foreach ($subjects as $subjectId => $groups) {
                foreach ($groups as $studyGroupId => $hours) {
                    $h = is_numeric($hours) ? (int) $hours : null;
                    if (! $h || $h <= 0) {
                        continue;
                    }

                    $subjectName = mb_strtolower((string) ($subjectNames[$subjectId] ?? ''));
                    $subjectGroupKey = $subjectId.'|'.$studyGroupId;
                    if (isset($usedSubjectGroups[$subjectGroupKey]) && ! str_contains($subjectName, 'tahfidz')) {
                        continue;
                    }
                    $usedSubjectGroups[$subjectGroupKey] = true;

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
        $workUnitId = School::find($decree->school_id ?? $schoolId)?->work_unit_id;
        foreach ($tasks as $t) {
            OtherTeacherTask::create([
                'teacher_id' => $t['teacher_id'],
                'school_id' => $decree->school_id ?? $schoolId,
                'work_unit_id' => $workUnitId,
                'academic_year_id' => $decree->academic_year_id,
                'study_group_id' => $t['study_group_id'] ?? null,
                'task_name' => $t['task_name'],
                'task_code' => $t['task_code'] ?? null,
                'weekly_hours' => $t['weekly_hours'] ?? 0,
                'is_active' => true,
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
