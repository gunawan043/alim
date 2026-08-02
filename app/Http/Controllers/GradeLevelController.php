<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\School;
use App\Models\Subject;
use App\Models\SubjectKktp;
use Illuminate\Http\Request;

class GradeLevelController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $query = GradeLevel::with('school');

        $schoolId = $request->attributes->get('schoolContextId');
        $isGlobalView = $request->attributes->get('isGlobalView') === true;
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        } elseif ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $gradeLevels = $query->orderBy('school_id')->orderBy('level')->paginate(15)->withQueryString();
        $schools = School::orderBy('name')->get();

        return view('grade-levels.index', compact('gradeLevels', 'schools', 'userId', 'isGlobalView'));
    }

    public function create(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId) {
            $schools = School::where('id', $schoolId)->get();
        } else {
            $schools = School::orderBy('name')->get();
        }
        $schoolContext = $schoolId ? School::find($schoolId) : null;

        return view('grade-levels.create', compact('schools', 'userId', 'schoolContext'));
    }

    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $rules = [
            'level' => 'required|integer|min:1|max:15',
            'name' => 'required|string|max:50',
            'code' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ];
        if (! $schoolId) {
            $rules['school_id'] = 'required|exists:schools,id';
        }

        $data = $request->validate($rules);

        if ($schoolId) {
            $data['school_id'] = $schoolId;
        }

        $exists = GradeLevel::where('school_id', $data['school_id'])
            ->where('level', $data['level'])
            ->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'Tingkat kelas ini sudah ada untuk sekolah tersebut.');
        }

        $gradeLevel = GradeLevel::create($data);

        return redirect()->route('user.grade-levels.show', ['userId' => $userId, 'id' => $gradeLevel->id])
            ->with('success', 'Tingkat kelas berhasil disimpan.');
    }

    public function show(string $userId, string $id)
    {
        $gradeLevel = GradeLevel::with('school')->findOrFail($id);
        $schoolId = request()->attributes->get('schoolContextId');
        if ($schoolId && $gradeLevel->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $gradeLevelSubjects = GradeLevelSubject::with('subject')
            ->where('grade_level_id', $id)
            ->orderBy('subject_id')
            ->get();

        $availableSubjects = Subject::where('is_active', true)
            ->when($gradeLevel->school_id, fn ($q) => $q->where('school_id', $gradeLevel->school_id))
            ->orderBy('name')
            ->get();

        $activeAy = AcademicYear::where('is_active', true)->first();
        $semester = $activeAy?->semester ?? 'ganjil';

        // Ambil KKTP per mapel untuk semester aktif
        $kktpMap = SubjectKktp::where('grade_level_id', $id)
            ->when($activeAy, fn ($q) => $q->where('academic_year_id', $activeAy->id))
            ->where('semester', $semester)
            ->get()
            ->keyBy('subject_id');

        $academicYears = AcademicYear::orderBy('name', 'desc')->get();

        return view('grade-levels.show', compact(
            'gradeLevel', 'userId', 'gradeLevelSubjects', 'availableSubjects',
            'kktpMap', 'activeAy', 'semester', 'academicYears'
        ));
    }

    public function addSubject(Request $request, string $userId, string $id)
    {
        $gradeLevel = GradeLevel::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $gradeLevel->school_id !== $schoolId) {
            abort(403);
        }

        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'allocation_hours' => 'nullable|integer|min:0|max:40',
        ]);

        $exists = GradeLevelSubject::where('grade_level_id', $id)
            ->where('subject_id', $validated['subject_id'])
            ->exists();
        if ($exists) {
            return back()->with('error', 'Mapel ini sudah ditambahkan ke tingkat ini.');
        }

        GradeLevelSubject::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'grade_level_id' => $id,
            'subject_id' => $validated['subject_id'],
            'allocation_hours' => $validated['allocation_hours'] ?? 0,
        ]);

        return redirect()->route('user.grade-levels.show', ['userId' => $userId, 'id' => $id])
            ->with('success', 'Mapel berhasil ditambahkan.');
    }

    public function removeSubject(Request $request, string $userId, string $id)
    {
        $gradeLevel = GradeLevel::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $gradeLevel->school_id !== $schoolId) {
            abort(403);
        }

        $deleted = GradeLevelSubject::where('grade_level_id', $id)
            ->where('id', $request->input('subject_id'))
            ->delete();

        return redirect()->route('user.grade-levels.show', ['userId' => $userId, 'id' => $id])
            ->with('success', 'Mapel berhasil dihapus.');
    }

    public function saveKktp(Request $request, string $userId, string $id)
    {
        $gradeLevel = GradeLevel::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $gradeLevel->school_id !== $schoolId) {
            abort(403);
        }

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'kktp' => 'nullable|array',
            'kktp.*' => 'nullable|numeric|min:0|max:100',
        ]);

        $kktpData = $validated['kktp'] ?? [];

        foreach ($kktpData as $subjectId => $score) {
            if ($score === null || $score === '') {
                continue;
            }

            SubjectKktp::updateOrCreate(
                [
                    'subject_id' => $subjectId,
                    'grade_level_id' => $id,
                    'academic_year_id' => $validated['academic_year_id'],
                    'semester' => $validated['semester'],
                ],
                [
                    'kktp_score' => $score,
                    'school_id' => $gradeLevel->school_id,
                    'created_by' => $request->user()?->id,
                ]
            );
        }

        return back()->with('success', 'KKTP berhasil disimpan.');
    }

    public function edit(string $userId, string $id)
    {
        $gradeLevel = GradeLevel::findOrFail($id);
        $schoolId = request()->attributes->get('schoolContextId');
        if ($schoolId && $gradeLevel->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }
        $schools = School::orderBy('name')->get();
        $schoolContext = $schoolId ? School::find($schoolId) : null;

        return view('grade-levels.edit', compact('gradeLevel', 'schools', 'userId', 'schoolContext'));
    }

    public function update(Request $request, string $userId, string $id)
    {
        $gradeLevel = GradeLevel::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $gradeLevel->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $rules = [
            'level' => 'required|integer|min:1|max:15',
            'name' => 'required|string|max:50',
            'code' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ];
        if (! $schoolId) {
            $rules['school_id'] = 'required|exists:schools,id';
        }

        $data = $request->validate($rules);

        if ($schoolId) {
            $data['school_id'] = $schoolId;
        }

        $exists = GradeLevel::where('school_id', $data['school_id'])
            ->where('level', $data['level'])
            ->where('id', '!=', $id)
            ->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'Tingkat kelas ini sudah ada untuk sekolah tersebut.');
        }

        $gradeLevel->update($data);

        return redirect()->route('user.grade-levels.show', ['userId' => $userId, 'id' => $gradeLevel->id])
            ->with('success', 'Tingkat kelas berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $gradeLevel = GradeLevel::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $gradeLevel->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $gradeLevel->delete();

        return redirect()->route('user.grade-levels.index', ['userId' => $userId])
            ->with('success', 'Tingkat kelas berhasil dihapus.');
    }
}
