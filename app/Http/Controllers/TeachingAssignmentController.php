<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\InstitutionDecree;
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

        $assignments = $query->orderBy('teacher_id')->orderBy('subject_id')->paginate(20)->withQueryString();
        $academicYears = AcademicYear::orderByDesc('name')->get();
        $schools = School::orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();
        $studyGroups = StudyGroup::orderBy('name')->get();
        $teacherIds = usersHavingPermission('general_teacher.readable');
        $teachers = User::whereIn('id', $teacherIds)->orderBy('name')->get();

        return view('teaching-assignments.index', compact(
            'assignments', 'academicYears', 'schools', 'subjects', 'studyGroups', 'teachers', 'userId'
        ));
    }

    public function create(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $schools = $schoolId ? School::where('id', $schoolId)->get() : School::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('name')->get();
        $subjects = Subject::orderBy('name')->get();
        $studyGroups = StudyGroup::orderBy('name')->get();
        $decrees = InstitutionDecree::where('status', 'active')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderByDesc('issued_date')->get();

        $nonTeachingIds = usersHavingPermission('general_staff.ineligible');
        $teachers = User::query()
            ->when(!empty($nonTeachingIds), fn ($q) => $q->whereNotIn('users.id', $nonTeachingIds))
            ->orderBy('name')
            ->get();

        $schoolContext = $schoolId ? School::find($schoolId) : null;

        return view('teaching-assignments.create', compact(
            'schools', 'academicYears', 'subjects', 'studyGroups', 'decrees', 'teachers', 'userId', 'schoolId', 'schoolContext'
        ));
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

        $schools = School::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('name')->get();
        $subjects = Subject::orderBy('name')->get();
        $studyGroups = StudyGroup::orderBy('name')->get();
        $decrees = InstitutionDecree::orderByDesc('issued_date')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->get();
        $nonTeachingIds = usersHavingPermission('general_staff.ineligible');
        $teachers = User::query()
            ->when(!empty($nonTeachingIds), fn ($q) => $q->whereNotIn('users.id', $nonTeachingIds))
            ->orderBy('name')
            ->get();

        return view('teaching-assignments.edit', compact('assignment', 'schools', 'academicYears', 'subjects', 'studyGroups', 'decrees', 'teachers', 'userId', 'schoolId'));
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
        $teacherIds = usersHavingPermission('general_teacher.readable');
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
        foreach ($assignments as $teacherId => $subjects) {
            foreach ($subjects as $subjectId => $groups) {
                foreach ($groups as $studyGroupId => $hours) {
                    $key = "{$teacherId}|{$subjectId}|{$studyGroupId}";
                    $h = is_numeric($hours) ? (int) $hours : null;

                    if ($existing = $decree->teachingAssignments->keyBy(fn ($a) => "{$a->teacher_id}|{$a->subject_id}|{$a->study_group_id}")->get($key)) {
                        // Update or delete
                        if ($h && $h > 0) {
                            $existing->update(['weekly_hours' => $h, 'status' => 'active']);

                            // Sync TeacherAdminBook (jika teacher berubah)
                            $sg = \App\Models\StudyGroup::with('gradeLevel')->find($studyGroupId);
                            $gradeLevel = $sg?->gradeLevel;
                            $activeAy = \App\Models\AcademicYear::find($decree->academic_year_id);
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
                                    'teaching_id' => $existing->id,
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
                        $sg = \App\Models\StudyGroup::with('gradeLevel')->find($studyGroupId);
                        $gradeLevel = $sg?->gradeLevel;
                        $activeAy = \App\Models\AcademicYear::find($decree->academic_year_id);
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
                                'teaching_id' => $ta->id,
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
