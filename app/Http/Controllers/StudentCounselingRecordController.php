<?php

namespace App\Http\Controllers;

use App\Models\StudentCounselingRecord;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentCounselingRecordController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $activeAy = AcademicYear::where('is_active', true)->first();

        $query = StudentCounselingRecord::with(['student', 'academicYear', 'counselor'])
            ->orderByDesc('session_date');

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $schoolGender = $request->attributes->get('schoolGender');
        if ($schoolGender) {
            $query->whereHas('student', fn($s) => $s->where('gender', $schoolGender === 'putra' ? 'L' : 'P'));
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($sq) => $sq
                ->where('topic', 'like', "%{$q}%")
                ->orWhereHas('student', fn($st) => $st->where('name', 'like', "%{$q}%"))
            );
        }

        if ($request->filled('study_group_id')) {
            $query->whereHas('student', fn($st) => $st
                ->whereHas('studyGroups', fn($sc) => $sc
                    ->where('study_group_id', $request->study_group_id)
                    ->where('is_active', true)
                )
            );
        }

        if ($request->filled('counselor_id')) {
            $query->where('counselor_id', $request->counselor_id);
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        if ($request->filled('referral_needed')) {
            $query->where('referral_needed', true);
        }

        if ($request->filled('month') && $request->month !== '') {
            [$year, $month] = explode('-', $request->month);
            $query->whereRaw('YEAR(session_date) = ?', [$year])
                  ->whereRaw('MONTH(session_date) = ?', [$month]);
        } elseif ($activeAy) {
            $query->where('academic_year_id', $activeAy->id);
        }

        $records = $query->paginate(15)->withQueryString();

        $studyGroups = StudyGroup::with('gradeLevel')
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $counselors = User::orderBy('name')->get();

        return view('health.counseling-records.index', compact('records', 'studyGroups', 'activeAy', 'counselors', 'userId'));
    }

    public function create(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $schoolGender = $request->attributes->get('schoolGender');
        $activeAy = AcademicYear::where('is_active', true)->first();

        $studentQuery = Student::with('studyGroups.studyGroup.gradeLevel')
            ->where('status', 'active');

        if ($schoolId) $studentQuery->where('school_id', $schoolId);
        if ($schoolGender) $studentQuery->where('gender', $schoolGender === 'putra' ? 'L' : 'P');

        $students = $studentQuery->orderBy('name')->get();
        $groupedStudents = [];
        foreach ($students as $s) {
            $sg = $s->currentClassHistory?->studyGroup;
            $label = $sg ? $sg->full_name : 'Tanpa Kelas';
            if (!isset($groupedStudents[$label])) $groupedStudents[$label] = [];
            $groupedStudents[$label][] = $s;
        }

        return view('health.counseling-records.create', compact('groupedStudents', 'activeAy', 'userId'));
    }

    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'session_date' => 'required|date',
            'session_type' => 'required|string|max:50',
            'topic' => 'nullable|string|max:191',
            'description' => 'nullable|string',
            'analysis' => 'nullable|string',
            'follow_up_plan' => 'nullable|string',
            'referral_needed' => 'nullable|boolean',
            'referred_to' => 'nullable|string|max:191',
            'next_session_date' => 'nullable|date',
            'parent_informed' => 'nullable|boolean',
            'parent_informed_at' => 'nullable',
            'parent_informed_by' => 'nullable|exists:users,id',
            'is_confidential' => 'nullable|boolean',
        ]);

        $student = Student::find($validated['student_id']);
        $validated['school_id'] = $student->school_id;
        $validated['counselor_id'] = Auth::id();
        $validated['created_by'] = Auth::id();
        $validated['referral_needed'] = $request->boolean('referral_needed');
        $validated['parent_informed'] = $request->boolean('parent_informed');
        $validated['is_confidential'] = $request->boolean('is_confidential');

        StudentCounselingRecord::create($validated);

        return redirect()
            ->route('user.uks.counseling-records.index', ['userId' => $userId])
            ->with('success', 'Catatan konseling berhasil disimpan.');
    }

    public function show(Request $request, string $userId, string $uuid)
    {
        $record = StudentCounselingRecord::with([
            'student', 'academicYear', 'counselor', 'parentInformedBy', 'creator',
        ])->findOrFail($uuid);

        return view('health.counseling-records.show', compact('record', 'userId'));
    }

    public function edit(Request $request, string $userId, string $uuid)
    {
        $record = StudentCounselingRecord::findOrFail($uuid);
        $schoolId = $request->attributes->get('schoolContextId');
        $schoolGender = $request->attributes->get('schoolGender');

        $studentQuery = Student::with('studyGroups.studyGroup.gradeLevel')
            ->where('status', 'active');

        if ($schoolId) $studentQuery->where('school_id', $schoolId);
        if ($schoolGender) $studentQuery->where('gender', $schoolGender === 'putra' ? 'L' : 'P');

        $students = $studentQuery->orderBy('name')->get();
        $groupedStudents = [];
        foreach ($students as $s) {
            $sg = $s->currentClassHistory?->studyGroup;
            $label = $sg ? $sg->full_name : 'Tanpa Kelas';
            if (!isset($groupedStudents[$label])) $groupedStudents[$label] = [];
            $groupedStudents[$label][] = $s;
        }

        $academicYears = AcademicYear::orderByDesc('name')->get();

        return view('health.counseling-records.edit', compact('record', 'groupedStudents', 'academicYears', 'userId'));
    }

    public function update(Request $request, string $userId, string $uuid)
    {
        $record = StudentCounselingRecord::findOrFail($uuid);

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'session_date' => 'required|date',
            'session_type' => 'required|string|max:50',
            'topic' => 'nullable|string|max:191',
            'description' => 'nullable|string',
            'analysis' => 'nullable|string',
            'follow_up_plan' => 'nullable|string',
            'referral_needed' => 'nullable|boolean',
            'referred_to' => 'nullable|string|max:191',
            'next_session_date' => 'nullable|date',
            'parent_informed' => 'nullable|boolean',
            'parent_informed_at' => 'nullable',
            'parent_informed_by' => 'nullable|exists:users,id',
            'is_confidential' => 'nullable|boolean',
        ]);

        $validated['referral_needed'] = $request->boolean('referral_needed');
        $validated['parent_informed'] = $request->boolean('parent_informed');
        $validated['is_confidential'] = $request->boolean('is_confidential');

        $record->update($validated);

        return redirect()
            ->route('user.uks.counseling-records.show', ['userId' => $userId, 'uuid' => $uuid])
            ->with('success', 'Catatan konseling berhasil diperbarui.');
    }

    public function destroy(string $userId, string $uuid)
    {
        $record = StudentCounselingRecord::findOrFail($uuid);
        $record->delete();

        return redirect()
            ->route('user.uks.counseling-records.index', ['userId' => $userId])
            ->with('success', 'Catatan konseling berhasil dihapus.');
    }
}