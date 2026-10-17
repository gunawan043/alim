<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\InstitutionDecree;
use App\Models\OtherTeacherTask;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\SubjectKktp;
use App\Models\TeacherAdminBook;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Http\Request;

class TeachingAssignmentController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $query = TeachingAssignment::with(['teacher', 'subject', 'studyGroup', 'school', 'decree', 'academicYear']);

        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        } elseif ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }
        if ($request->filled('study_group_id')) {
            $query->where('study_group_id', $request->study_group_id);
        }
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->whereHas('teacher', fn ($q) => $q->where('name', 'like', "%{$request->search}%"));
        }

        $stats = [
            'total' => (clone $query)->count(),
            'guru' => (clone $query)->distinct()->count('teacher_id'),
            'mapel' => (clone $query)->distinct()->count('subject_id'),
            'jp' => (int) (clone $query)->sum('weekly_hours'),
        ];

        $assignments = $query->orderBy('teacher_id')->orderBy('subject_id')->paginate(20)->withQueryString();

        // Privasi antar sekolah: hanya Super Admin/Pimpinan (isGlobalView) yang melihat lintas satuan.
        $isGlobalView = $request->attributes->get('isGlobalView') === true;
        $scopeSchoolId = $isGlobalView ? null : $schoolId;

        $academicYears = AcademicYear::orderByDesc('name')->get();
        $schools = $scopeSchoolId ? School::where('id', $scopeSchoolId)->get() : School::orderBy('name')->get();
        $subjects = Subject::query()->when($scopeSchoolId, fn ($q) => $q->where('school_id', $scopeSchoolId))->orderBy('name')->get();
        $studyGroups = StudyGroup::query()->when($scopeSchoolId, fn ($q) => $q->where('school_id', $scopeSchoolId))->orderBy('name')->get();
        $teacherIds = usersHavingPermission('general_teacher.readable');
        $teachers = User::whereIn('id', $teacherIds)
            ->when($scopeSchoolId, fn ($q) => $q->whereHas('employments', fn ($e) => $e->where('school_id', $scopeSchoolId)))
            ->orderBy('name')
            ->get();

        return view('teaching-assignments.index', compact(
            'assignments', 'academicYears', 'schools', 'subjects', 'studyGroups', 'teachers', 'stats', 'userId'
        ));
    }

    public function create(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $schools = $schoolId ? School::where('id', $schoolId)->get() : School::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('name')->get();
        $subjects = Subject::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')->get();
        $studyGroups = StudyGroup::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')->get();
        $decrees = InstitutionDecree::where('status', 'active')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderByDesc('issued_date')->get();

        $nonTeachingIds = usersHavingPermission('general_staff.ineligible');
        $teachers = User::query()
            ->when(! empty($nonTeachingIds), fn ($q) => $q->whereNotIn('users.id', $nonTeachingIds))
            ->when($schoolId, fn ($q) => $q->whereHas('employments', fn ($e) => $e->where('school_id', $schoolId)))
            ->orderBy('name')
            ->get();

        $schoolContext = $schoolId ? School::find($schoolId) : null;

        // JP otomatis dari struktur kurikulum (mapel × tingkat → alokasi JP).
        $glsHours = \App\Models\GradeLevelSubject::query()
            ->when($schoolId, fn ($q) => $q->whereHas('gradeLevel', fn ($g) => $g->where('school_id', $schoolId)))
            ->get(['subject_id', 'grade_level_id', 'allocation_hours'])
            ->mapWithKeys(fn ($row) => [$row->subject_id.'|'.$row->grade_level_id => (int) $row->allocation_hours]);

        return view('teaching-assignments.create', compact(
            'schools', 'academicYears', 'subjects', 'studyGroups', 'decrees', 'teachers', 'userId', 'schoolId', 'schoolContext', 'glsHours'
        ));
    }

    /**
     * Form input massal penugasan (tabel baris guru × mapel × kelas).
     */
    public function bulkCreate(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $isGlobalView = $request->attributes->get('isGlobalView') === true;
        $scopeSchoolId = $isGlobalView ? null : $schoolId;

        $academicYears = AcademicYear::orderByDesc('name')->get();
        $activeAy = AcademicYear::active()->first() ?? $academicYears->first();

        // Semua rombel sekolah (lintas TA) — dipilih di form sesuai Tahun Ajaran.
        $studyGroups = StudyGroup::query()
            ->with('gradeLevel:id,name,level')
            ->when($scopeSchoolId, fn ($q) => $q->where('school_id', $scopeSchoolId))
            ->where('is_active', true)
            ->orderedByGrade()
            ->get();

        // Default TA: utamakan TA yang benar-benar punya rombel.
        $ayIdsWithGroups = $studyGroups->pluck('academic_year_id')->filter()->unique();
        if ($request->filled('academic_year_id')) {
            $activeAyId = $request->academic_year_id;
        } elseif (! $ayIdsWithGroups->contains($activeAy?->id) && $ayIdsWithGroups->isNotEmpty()) {
            $activeAyId = $ayIdsWithGroups->first();
        } else {
            $activeAyId = $activeAy?->id;
        }

        $schools = $scopeSchoolId ? School::where('id', $scopeSchoolId)->get() : School::orderBy('name')->get();

        $subjects = Subject::query()
            ->when($scopeSchoolId, fn ($q) => $q->where('school_id', $scopeSchoolId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'school_id']);

        // Guru: GTK yang tertaut ke sekolah ini via Work Unit (prioritas) atau kepegawaian;
        // bila data penautan tidak ada, tampilkan semua GTK pengajar (jangan kosong).
        $nonTeachingIds = usersHavingPermission('general_staff.ineligible');

        $scopedTeacherIds = collect();
        if ($scopeSchoolId) {
            $workUnitId = School::whereKey($scopeSchoolId)->value('work_unit_id');
            if ($workUnitId) {
                $scopedTeacherIds = $scopedTeacherIds->merge(
                    \App\Models\GtkWorkUnit::where('work_unit_id', $workUnitId)->pluck('user_id')
                );
            }
            $scopedTeacherIds = $scopedTeacherIds->merge(
                \Illuminate\Support\Facades\DB::table('gtk_employments')->where('school_id', $scopeSchoolId)->pluck('user_id')
            );
            $scopedTeacherIds = $scopedTeacherIds->unique()->values();
        }

        $teacherQuery = User::query()
            ->when(! empty($nonTeachingIds), fn ($q) => $q->whereNotIn('users.id', $nonTeachingIds));

        if ($scopeSchoolId && $scopedTeacherIds->isNotEmpty()) {
            $teacherQuery->whereIn('users.id', $scopedTeacherIds);
        }

        $teachers = $teacherQuery->orderBy('name')->get(['id', 'name']);

        // Klaim mapel per rombel (untuk cegah ganda di sisi UI).
        $existingAssignments = TeachingAssignment::query()
            ->with('teacher:id,name')
            ->where('status', 'active')
            ->when($scopeSchoolId, fn ($q) => $q->where('school_id', $scopeSchoolId))
            ->get(['id', 'school_id', 'teacher_id', 'subject_id', 'study_group_id', 'academic_year_id'])
            ->map(fn ($a) => [
                'school' => $a->school_id,
                'ay' => $a->academic_year_id,
                'group' => $a->study_group_id,
                'subject' => $a->subject_id,
                'teacher' => $a->teacher?->name,
            ])
            ->values();

        // Peta sekolah guru untuk filter lintas-sekolah (via Work Unit → Sekolah).
        $unitToSchool = School::whereNotNull('work_unit_id')->pluck('id', 'work_unit_id');
        $teacherSchools = \App\Models\GtkWorkUnit::whereIn('user_id', $teachers->pluck('id'))
            ->get(['user_id', 'work_unit_id'])
            ->mapWithKeys(fn ($g) => [$g->user_id => $unitToSchool[$g->work_unit_id] ?? null])
            ->filter();

        $decrees = InstitutionDecree::where('status', 'active')
            ->when($scopeSchoolId, fn ($q) => $q->where('school_id', $scopeSchoolId))
            ->orderByDesc('issued_date')
            ->get(['id', 'decree_number', 'title', 'school_id']);

        // JP otomatis dari struktur kurikulum (mapel × tingkat).
        $glsHours = \App\Models\GradeLevelSubject::query()
            ->when($scopeSchoolId, fn ($q) => $q->whereHas('gradeLevel', fn ($g) => $g->where('school_id', $scopeSchoolId)))
            ->get(['subject_id', 'grade_level_id', 'allocation_hours'])
            ->mapWithKeys(fn ($row) => [$row->subject_id.'|'.$row->grade_level_id => (int) $row->allocation_hours]);

        return view('teaching-assignments.bulk', compact(
            'userId', 'academicYears', 'activeAyId', 'schools', 'subjects', 'studyGroups',
            'teachers', 'decrees', 'glsHours', 'scopeSchoolId', 'isGlobalView', 'teacherSchools', 'existingAssignments'
        ));
    }

    /**
     * Simpan penugasan massal — SK opsional (dibuat otomatis bila tidak dipilih).
     */
    public function bulkStore(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $isGlobalView = $request->attributes->get('isGlobalView') === true;

        $rules = [
            'academic_year_id' => 'required|exists:academic_years,id',
            'decree_id' => 'nullable|exists:institution_decrees,id',
            'rows' => 'required|array|min:1',
            'rows.*.teacher_id' => 'required|exists:users,id',
            'rows.*.subject_id' => 'required|exists:subjects,id',
            'rows.*.study_group_id' => 'required|exists:study_groups,id',
            'rows.*.weekly_hours' => 'required|integer|min:0|max:40',
        ];
        if (! $schoolId && $isGlobalView) {
            $rules['school_id'] = 'required|exists:schools,id';
        }

        $data = $request->validate($rules);
        $school = $schoolId ?: ($data['school_id'] ?? null);

        if (! $school) {
            return back()->withInput()->with('error', 'Satuan pendidikan tidak terdeteksi.');
        }

        // Anti data lintas sekolah: mapel & rombel wajib milik satuan pendidikan ini.
        // Guru divalidasi lunak (tautan bisa via Work Unit atau kepegawaian).
        $workUnitId = School::whereKey($school)->value('work_unit_id');

        foreach ($data['rows'] as $row) {
            $subjectOk = Subject::where('id', $row['subject_id'])->where('school_id', $school)->exists();
            $groupOk = StudyGroup::where('id', $row['study_group_id'])->where('school_id', $school)->exists();

            $teacherLinks = $workUnitId
                ? \App\Models\GtkWorkUnit::where('user_id', $row['teacher_id'])->pluck('work_unit_id')
                : collect();

            $teacherOk = $teacherLinks->isEmpty()
                ? true // data penautan tidak tersedia → jangan blokir
                : $teacherLinks->contains($workUnitId)
                    || \Illuminate\Support\Facades\DB::table('gtk_employments')
                        ->where('user_id', $row['teacher_id'])->where('school_id', $school)->exists();

            if (! $subjectOk || ! $groupOk || ! $teacherOk) {
                abort(403, 'Data penugasan harus berada pada satuan pendidikan Anda.');
            }
        }

        // SK: gunakan yang dipilih, atau buat otomatis.
        if (! empty($data['decree_id'])) {
            $decree = InstitutionDecree::where('id', $data['decree_id'])->where('school_id', $school)->firstOrFail();
        } else {
            $decree = InstitutionDecree::firstOrCreate(
                [
                    'school_id' => $school,
                    'academic_year_id' => $data['academic_year_id'],
                    'decree_type' => 'SK Pembagian Tugas',
                ],
                [
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'decree_number' => 'PSB/'.now()->format('Ymd').'/'.strtoupper(\Illuminate\Support\Str::random(5)),
                    'title' => 'SK Pembagian Tugas (Input Massal)',
                    'issued_date' => now()->format('Y-m-d'),
                    'effective_date' => now()->format('Y-m-d'),
                    'status' => 'active',
                ]
            );
        }

        $created = 0;
        $skipped = 0; // duplikat persis / guru yang sama
        $blocked = 0; // mapel sudah diajar guru lain di rombel yang sama

        \Illuminate\Support\Facades\DB::transaction(function () use ($data, $school, $decree, &$created, &$skipped, &$blocked) {
            $subjectModels = Subject::whereIn('id', collect($data['rows'])->pluck('subject_id')->unique())
                ->get()
                ->keyBy('id');

            foreach ($data['rows'] as $row) {
                $subjectModel = $subjectModels->get($row['subject_id']);
                $allowsMultiple = $subjectModel?->allowsMultipleTeachers() ?? false;

                $existing = TeachingAssignment::query()
                    ->where('school_id', $school)
                    ->where('academic_year_id', $data['academic_year_id'])
                    ->where('study_group_id', $row['study_group_id'])
                    ->where('subject_id', $row['subject_id'])
                    ->where('status', 'active')
                    ->get(['id', 'teacher_id']);

                // Mapel yang sama di rombel yang sama sudah diajar guru lain → ditolak.
                if (! $allowsMultiple && $existing->contains(fn ($a) => (string) $a->teacher_id !== (string) $row['teacher_id'])) {
                    $blocked++;
                    continue;
                }

                // Guru yang sama sudah tercatat untuk mapel & rombel ini → dilewati.
                if ($existing->contains(fn ($a) => (string) $a->teacher_id === (string) $row['teacher_id'])) {
                    $skipped++;
                    continue;
                }

                TeachingAssignment::create([
                    'decree_id' => $decree->id,
                    'teacher_id' => $row['teacher_id'],
                    'school_id' => $school,
                    'academic_year_id' => $data['academic_year_id'],
                    'study_group_id' => $row['study_group_id'],
                    'subject_id' => $row['subject_id'],
                    'weekly_hours' => (int) $row['weekly_hours'],
                    'status' => 'active',
                ]);

                $created++;
            }
        });

        $message = "{$created} penugasan massal disimpan";
        if ($skipped) {
            $message .= ", {$skipped} duplikat dilewati";
        }
        if ($blocked) {
            $message .= ", {$blocked} ditolak karena mapel sudah diajar guru lain di rombel yang sama";
        }

        return redirect()
            ->route('user.teaching-assignments.index', ['userId' => $userId])
            ->with($created === 0 && ($skipped || $blocked) ? 'error' : 'success', $message.'.');
    }

    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $rules = [
            'decree_id' => 'nullable|exists:institution_decrees,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'study_group_id' => 'required|exists:study_groups,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'role' => 'required|in:guru_mapel,guru_pendamping,guru_praktik,ustadz_pengasuh',
            'is_coordinator' => 'boolean',
            'weekly_hours' => 'required|integer|min:0|max:40',
            'status' => 'required|in:active,inactive',
        ];
        if (! $schoolId) {
            $rules['school_id'] = 'required|exists:schools,id';
        }

        $data = $request->validate($rules);
        if ($schoolId) {
            $data['school_id'] = $schoolId;
        }

        // Cek duplikat
        $exists = TeachingAssignment::where('teacher_id', $data['teacher_id'])
            ->where('study_group_id', $data['study_group_id'])
            ->where('subject_id', $data['subject_id'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'Penugasan ini sudah ada (guru, mapel, kelas, tahun ajaran yang sama).');
        }

        // Aturan: satu mapel hanya satu guru per rombel (kecuali Tahfidz & Hadits).
        $subjectModel = Subject::find($data['subject_id']);
        if ($subjectModel && ! $subjectModel->allowsMultipleTeachers() && ($data['status'] ?? 'active') === 'active') {
            $conflict = TeachingAssignment::with('teacher:id,name')
                ->where('school_id', $data['school_id'])
                ->where('academic_year_id', $data['academic_year_id'])
                ->where('study_group_id', $data['study_group_id'])
                ->where('subject_id', $data['subject_id'])
                ->where('status', 'active')
                ->where('teacher_id', '!=', $data['teacher_id'])
                ->first();

            if ($conflict) {
                return back()->withInput()->with('error', 'Mapel ini sudah diajar oleh '.($conflict->teacher?->name ?? 'guru lain').' di rombel tersebut. Satu mapel hanya boleh satu guru per rombel (kecuali Tahfidz & Hadits, karena berkaitan dengan hafalan).');
            }
        }

        TeachingAssignment::create($data);

        return redirect()->route('user.teaching-assignments.show', ['userId' => $userId, 'id' => TeachingAssignment::latest()->first()->id])
            ->with('success', 'Penugasan mengajar berhasil disimpan.');
    }

    public function show(string $userId, string $id)
    {
        $assignment = TeachingAssignment::with([
            'teacher', 'subject', 'studyGroup', 'school',
            'decree', 'academicYear',
        ])->findOrFail($id);

        $schoolId = request()->attributes->get('schoolContextId');
        if ($schoolId && $assignment->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        return view('teaching-assignments.show', compact('assignment', 'userId'));
    }

    public function edit(string $userId, string $id)
    {
        $assignment = TeachingAssignment::findOrFail($id);

        $schoolId = request()->attributes->get('schoolContextId');
        if ($schoolId && $assignment->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $schools = $schoolId ? School::where('id', $schoolId)->get() : School::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('name')->get();
        $subjects = Subject::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')->get();
        $studyGroups = StudyGroup::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')->get();
        $decrees = InstitutionDecree::orderByDesc('issued_date')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->get();
        $nonTeachingIds = usersHavingPermission('general_staff.ineligible');
        $teachers = User::query()
            ->when(! empty($nonTeachingIds), fn ($q) => $q->whereNotIn('users.id', $nonTeachingIds))
            ->when($schoolId, fn ($q) => $q->whereHas('employments', fn ($e) => $e->where('school_id', $schoolId)))
            ->orderBy('name')
            ->get();

        // JP otomatis dari struktur kurikulum (mapel × tingkat → alokasi JP).
        $glsHours = \App\Models\GradeLevelSubject::query()
            ->when($schoolId, fn ($q) => $q->whereHas('gradeLevel', fn ($g) => $g->where('school_id', $schoolId)))
            ->get(['subject_id', 'grade_level_id', 'allocation_hours'])
            ->mapWithKeys(fn ($row) => [$row->subject_id.'|'.$row->grade_level_id => (int) $row->allocation_hours]);

        return view('teaching-assignments.edit', compact('assignment', 'schools', 'academicYears', 'subjects', 'studyGroups', 'decrees', 'teachers', 'userId', 'schoolId', 'glsHours'));
    }

    public function update(Request $request, string $userId, string $id)
    {
        $assignment = TeachingAssignment::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $assignment->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $rules = [
            'decree_id' => 'nullable|exists:institution_decrees,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'study_group_id' => 'required|exists:study_groups,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'role' => 'required|in:guru_mapel,guru_pendamping,guru_praktik,ustadz_pengasuh',
            'is_coordinator' => 'boolean',
            'weekly_hours' => 'required|integer|min:0|max:40',
            'status' => 'required|in:active,inactive',
        ];
        if (! $schoolId) {
            $rules['school_id'] = 'required|exists:schools,id';
        }

        $data = $request->validate($rules);
        if ($schoolId) {
            $data['school_id'] = $schoolId;
        }

        $exists = TeachingAssignment::where('teacher_id', $data['teacher_id'])
            ->where('study_group_id', $data['study_group_id'])
            ->where('subject_id', $data['subject_id'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('id', '!=', $id)
            ->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'Penugasan ini sudah ada (guru, mapel, kelas, tahun ajaran yang sama).');
        }

        // Aturan: satu mapel hanya satu guru per rombel (kecuali Tahfidz & Hadits).
        $subjectModel = Subject::find($data['subject_id']);
        if ($subjectModel && ! $subjectModel->allowsMultipleTeachers() && ($data['status'] ?? 'active') === 'active') {
            $conflict = TeachingAssignment::with('teacher:id,name')
                ->where('school_id', $data['school_id'])
                ->where('academic_year_id', $data['academic_year_id'])
                ->where('study_group_id', $data['study_group_id'])
                ->where('subject_id', $data['subject_id'])
                ->where('status', 'active')
                ->where('teacher_id', '!=', $data['teacher_id'])
                ->where('id', '!=', $id)
                ->first();

            if ($conflict) {
                return back()->withInput()->with('error', 'Mapel ini sudah diajar oleh '.($conflict->teacher?->name ?? 'guru lain').' di rombel tersebut. Satu mapel hanya boleh satu guru per rombel (kecuali Tahfidz & Hadits).');
            }
        }

        $assignment->update($data);

        return redirect()->route('user.teaching-assignments.show', ['userId' => $userId, 'id' => $assignment->id])
            ->with('success', 'Penugasan mengajar berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $assignment = TeachingAssignment::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $assignment->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $assignment->delete();

        return redirect()->route('user.teaching-assignments.index', ['userId' => $userId])
            ->with('success', 'Penugasan mengajar berhasil dihapus.');
    }

    /**
     * Matriks inline editing — prepare data
     */
    public function editMatrix(Request $request, string $userId, string $decreeId)
    {
        $decree = InstitutionDecree::with(['academicYear', 'school'])->findOrFail($decreeId);

        $schoolId = $request->attributes->get('schoolContextId');
        $currentUser = auth()->user();
        $isGlobal = canPermission('teaching-assignment-all-access');

        if (! $isGlobal && $schoolId && $decree->school_id && $decree->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        // Teachers — hanya guru yang terassign di sekolah decree ini
        // Roster: permission snapshot → data kepegawaian (jenis/jabatan guru) sebagai fallback.
        $teacherIds = app(\App\Services\TeacherRosterService::class)->idsForSchool($decree->school_id);
        $teacherQuery = User::whereIn('id', $teacherIds)
            ->whereHas('employments', fn ($q) => $q->where('school_id', $decree->school_id));

        // Global user bisa lihat semua guru di sekolah decree
        $teachers = $teacherQuery->orderBy('name')->get();

        // Subjects
        $subjects = Subject::orderBy('name')->get();

        // Study groups — berdasarkan school & academic year SK
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

        // Existing assignments keyed by teacher_subject_group
        $existing = $decree->teachingAssignments
            ->keyBy(fn ($a) => "{$a->teacher_id}|{$a->subject_id}|{$a->study_group_id}");

        // Other teacher tasks (tugas tambahan)
        $otherTasks = OtherTeacherTask::where('academic_year_id', $decree->academic_year_id)
            ->when($decree->school_id, fn ($q) => $q->where('school_id', $decree->school_id))
            ->get()
            ->toArray();

        return view('teaching-assignments.edit-matrix', compact(
            'decree', 'userId', 'teachers', 'subjects', 'studyGroups', 'existing', 'otherTasks'
        ));
    }

    /**
     * Matriks inline editing — save all
     */
    public function updateMatrix(Request $request, string $userId, string $decreeId)
    {
        $decree = InstitutionDecree::with('school')->findOrFail($decreeId);

        $schoolId = $request->attributes->get('schoolContextId');
        $currentUser = auth()->user();
        $isGlobal = canPermission('teaching-assignment-all-access');

        if (! $isGlobal && $schoolId && $decree->school_id && $decree->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        // Parse JSON assignments (new format) or fallback to old format
        $jsonInput = $request->input('assignments_json');
        if ($jsonInput) {
            $assignments = json_decode($jsonInput, true) ?? [];
        } else {
            $assignments = $request->input('assignments', []);
        }

        $totalBookCreated = 0;
        $claimedPairs = []; // mapel|rombel → guru pertama (aturan satu mapel satu guru)
        $subjectModelCache = [];

        foreach ($assignments as $teacherId => $subjects) {
            foreach ($subjects as $subjectId => $groups) {
                foreach ($groups as $studyGroupId => $hours) {
                    // Aturan: satu mapel hanya satu guru per rombel (kecuali Tahfidz & Hadits).
                    $subjectModel = $subjectModelCache[$subjectId] ??= Subject::find($subjectId);
                    if ($subjectModel && ! $subjectModel->allowsMultipleTeachers()) {
                        $pairKey = $subjectId.'|'.$studyGroupId;
                        if (isset($claimedPairs[$pairKey]) && (string) $claimedPairs[$pairKey] !== (string) $teacherId) {
                            continue;
                        }
                        $claimedPairs[$pairKey] = $teacherId;
                    }

                    $key = "{$teacherId}|{$subjectId}|{$studyGroupId}";
                    $h = is_numeric($hours) ? (int) $hours : null;

                    if ($existing = $decree->teachingAssignments->keyBy(fn ($a) => "{$a->teacher_id}|{$a->subject_id}|{$a->study_group_id}")->get($key)) {
                        // Update or delete
                        if ($h && $h > 0) {
                            $existing->update(['weekly_hours' => $h, 'status' => 'active']);

                            // Sync TeacherAdminBook (jika teacher berubah)
                            $sg = StudyGroup::with('gradeLevel')->find($studyGroupId);
                            $gradeLevel = $sg?->gradeLevel;
                            $activeAy = AcademicYear::find($decree->academic_year_id);
                            $semester = $activeAy?->semester ?? 'ganjil';

                            $kktp = null;
                            if ($gradeLevel) {
                                $kktp = SubjectKktp::where('subject_id', $subjectId)
                                    ->where('grade_level_id', $gradeLevel->id)
                                    ->where('academic_year_id', $decree->academic_year_id)
                                    ->where('semester', $semester)
                                    ->first();
                            }

                            $created = TeacherAdminBook::updateOrCreate(
                                [
                                    'teacher_id' => $teacherId,
                                    'subject_id' => $subjectId,
                                    'study_group_id' => $studyGroupId,
                                    'academic_year_id' => $decree->academic_year_id,
                                    'semester' => $semester,
                                ],
                                [
                                    'school_id' => $decree->school_id ?? $schoolId,
                                    'kktp_id' => $kktp?->id,
                                    'is_active' => true,
                                ]
                            );
                        } else {
                            $existing->delete();
                        }
                    } elseif ($h && $h > 0) {
                        // Create new teaching assignment
                        $ta = TeachingAssignment::create([
                            'decree_id' => $decreeId,
                            'teacher_id' => $teacherId,
                            'subject_id' => $subjectId,
                            'study_group_id' => $studyGroupId,
                            'school_id' => $decree->school_id ?? $schoolId,
                            'academic_year_id' => $decree->academic_year_id,
                            'weekly_hours' => $h,
                            'role' => 'guru_mapel',
                            'status' => 'active',
                        ]);

                        // Auto-create TeacherAdminBook untuk setiap rombel
                        $sg = StudyGroup::with('gradeLevel')->find($studyGroupId);
                        $gradeLevel = $sg?->gradeLevel;
                        $activeAy = AcademicYear::find($decree->academic_year_id);
                        $semester = $activeAy?->semester ?? 'ganjil';

                        $kktp = null;
                        if ($gradeLevel) {
                            $kktp = SubjectKktp::where('subject_id', $subjectId)
                                ->where('grade_level_id', $gradeLevel->id)
                                ->where('academic_year_id', $decree->academic_year_id)
                                ->where('semester', $semester)
                                ->first();
                        }

                        $alreadyHasBook = TeacherAdminBook::where('teacher_id', $teacherId)
                            ->where('subject_id', $subjectId)
                            ->where('study_group_id', $studyGroupId)
                            ->where('academic_year_id', $decree->academic_year_id)
                            ->where('semester', $semester)
                            ->exists();

                        if (! $alreadyHasBook) {
                            TeacherAdminBook::create([
                                'teacher_id' => $teacherId,
                                'subject_id' => $subjectId,
                                'study_group_id' => $studyGroupId,
                                'school_id' => $decree->school_id ?? $schoolId,
                                'academic_year_id' => $decree->academic_year_id,
                                'semester' => $semester,
                                'kktp_id' => $kktp?->id,
                                'is_active' => true,
                            ]);
                            $totalBookCreated++;
                        }
                    }
                }
            }
        }

        $msg = 'Pembagian tugas mengajar berhasil disimpan.';
        if ($totalBookCreated > 0) {
            $msg .= " {$totalBookCreated} Buku Admin otomatis terbuat.";
        }

        return redirect()->route('user.institution-decrees.show', ['userId' => $userId, 'id' => $decreeId])
            ->with('success', $msg);
    }
}
