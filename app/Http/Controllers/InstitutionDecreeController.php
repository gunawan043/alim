<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\InstitutionDecree;
use App\Models\OtherTeacherTask;
use App\Models\School;
use App\Models\StudyGroup;
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
            $teacherIds = usersHavingPermission('general_teacher.readable');
        $teachers = User::whereIn('id', $teacherIds)
                ->whereHas('employments', fn ($q) => $q->where('school_id', $selectedSchoolId))
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

    public function show(Request $request, string $userId, string $id)
    {
        try {
            $decree = InstitutionDecree::with('academicYear', 'school', 'signer')->findOrFail($id);

            if ($request->wantsJson()) {
                return response()->json(['data' => $decree]);
            }

            return view('institution-decrees.show', compact('decree', 'userId'));
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
            $decree = InstitutionDecree::with('academicYear', 'school', 'signer')->findOrFail($id);

            return view('institution-decrees.print', compact('decree', 'userId'));
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
