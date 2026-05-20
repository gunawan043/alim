<?php

namespace App\Http\Controllers;

use App\Models\StudyGroup;
use App\Models\School;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\User;
use App\Models\StudentClassHistory;
use Illuminate\Http\Request;

class StudyGroupController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $activeSemester = AcademicYear::where('is_active', true)->value('semester');
        $schoolId = $request->attributes->get('schoolContextId');

        $query = StudyGroup::with(['school', 'academicYear', 'gradeLevel', 'homeroomTeacher'])
            ->where('is_active', true);

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        } else {
            $query->whereHas('academicYear', fn($q) => $q->where('semester', $activeSemester));
        }

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        } elseif ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $studyGroups = $query->orderBy('name')->paginate(15)->withQueryString();

        // Load student counts per study group
        $activeYearId = AcademicYear::where('is_active', true)->value('id');
        $sgIds = $studyGroups->pluck('id');
        $counts = StudentClassHistory::whereIn('study_group_id', $sgIds)
            ->where('is_active', true)
            ->when($activeYearId, fn($q) => $q->where('academic_year_id', $activeYearId))
            ->groupBy('study_group_id')
            ->selectRaw('study_group_id, COUNT(*) as total')
            ->pluck('total', 'study_group_id');

        $studyGroups->getCollection()->transform(function ($sg) use ($counts) {
            $sg->studentCount = $counts[$sg->id] ?? 0;
            return $sg;
        });

        $schools = School::orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $isGlobalView = $request->attributes->get('isGlobalView') === true;

        return view('study-groups.index', compact('studyGroups', 'schools', 'academicYears', 'userId', 'isGlobalView'));
    }

    public function create(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId) {
            $schools = School::where('id', $schoolId)->get();
            $gradeLevels = GradeLevel::where('school_id', $schoolId)->orderBy('level')->get();
            $teachers = User::whereHas('employment')
                ->whereHas('employment', fn($q) => $q->where('school_id', $schoolId))
                ->orderBy('name')->get();
        } else {
            $schools = School::orderBy('name')->get();
            $gradeLevels = collect();
            $teachers = collect();
        }
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();

        $schoolContext = $schoolId ? School::find($schoolId) : null;

        return view('study-groups.create', compact(
            'schools', 'academicYears', 'gradeLevels', 'teachers', 'userId', 'schoolContext'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $rules = [
            'academic_year_id'     => 'required|exists:academic_years,id',
            'grade_level_id'       => 'required|exists:grade_levels,id',
            'homeroom_teacher_id'  => 'nullable|exists:users,id',
            'name'                 => 'required|string|max:50',
            'code'                 => 'nullable|string|max:20',
            'capacity'             => 'nullable|integer|min:1|max:200',
            'room'                 => 'nullable|string|max:50',
            'curriculum_type'      => 'nullable|in:merdeka,2013,ktsp',
            'shift'                => 'nullable|in:pagi,siang',
            'is_active'            => 'boolean',
            'notes'                => 'nullable|string',
        ];
        if (!$schoolId) {
            $rules['school_id'] = 'required|exists:schools,id';
        }

        $data = $request->validate($rules);

        if ($schoolId) {
            $data['school_id'] = $schoolId;
        }

        $exists = StudyGroup::where('school_id', $data['school_id'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('name', $data['name'])
            ->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'Rombel dengan nama ini sudah ada pada sekolah dan tahun ajaran tersebut.');
        }

        $studyGroup = StudyGroup::create($data);
        return redirect()->route('user.study-groups.show', ['userId' => $userId, 'id' => $studyGroup->id])
            ->with('success', 'Rombel berhasil disimpan.');
    }

    public function show(string $userId, string $id)
    {
        $activeAcademicYear = \App\Models\AcademicYear::where('is_active', true)->first();

        $studyGroup = StudyGroup::with([
            'school', 'academicYear', 'gradeLevel', 'homeroomTeacher',
        ])->findOrFail($id);

        $schoolId = request()->attributes->get('schoolContextId');
        if ($schoolId && $studyGroup->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $activeHistories = $studyGroup->studentClassHistories()
            ->with('student:id,name,nisn,nis,gender')
            ->where('is_active', true)
            ->orderBy('attendance_number')
            ->get();

        return view('study-groups.show', compact('studyGroup', 'userId', 'activeHistories', 'activeAcademicYear'));
    }

    public function edit(string $userId, string $id)
    {
        $studyGroup = StudyGroup::findOrFail($id);
        $schoolId = request()->attributes->get('schoolContextId');

        if ($schoolId && $studyGroup->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $schools = School::orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $gradeLevels = GradeLevel::where('school_id', $studyGroup->school_id)->orderBy('level')->get();
        $teachers = User::whereHas('employment')
            ->whereHas('employment', fn($q) => $q->where('school_id', $studyGroup->school_id))
            ->orderBy('name')->get();
        $schoolContext = $schoolId ? School::find($schoolId) : null;

        return view('study-groups.edit', compact(
            'studyGroup', 'schools', 'academicYears', 'gradeLevels', 'teachers', 'userId', 'schoolContext'
        ));
    }

    public function update(Request $request, string $userId, string $id)
    {
        $studyGroup = StudyGroup::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $studyGroup->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $rules = [
            'academic_year_id'     => 'required|exists:academic_years,id',
            'grade_level_id'      => 'required|exists:grade_levels,id',
            'homeroom_teacher_id' => 'nullable|exists:users,id',
            'name'                 => 'required|string|max:50',
            'code'                 => 'nullable|string|max:20',
            'capacity'             => 'nullable|integer|min:1|max:200',
            'room'                 => 'nullable|string|max:50',
            'curriculum_type'     => 'nullable|in:merdeka,2013,ktsp',
            'shift'               => 'nullable|in:pagi,siang',
            'is_active'           => 'boolean',
            'notes'               => 'nullable|string',
        ];
        if (!$schoolId) {
            $rules['school_id'] = 'required|exists:schools,id';
        }

        $data = $request->validate($rules);

        if ($schoolId) {
            $data['school_id'] = $schoolId;
        }

        $exists = StudyGroup::where('school_id', $data['school_id'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('name', $data['name'])
            ->where('id', '!=', $id)
            ->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'Rombel dengan nama ini sudah ada pada sekolah dan tahun ajaran tersebut.');
        }

        $studyGroup->update($data);
        return redirect()->route('user.study-groups.show', ['userId' => $userId, 'id' => $studyGroup->id])
            ->with('success', 'Rombel berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $studyGroup = StudyGroup::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $studyGroup->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $studyGroup->delete();
        return redirect()->route('user.study-groups.index', ['userId' => $userId])
            ->with('success', 'Rombel berhasil dihapus.');
    }
}