<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentHealthCheckup;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Http\Request;

class StudentHealthCheckupController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $activeAy = AcademicYear::where('is_active', true)->first();

        $query = StudentHealthCheckup::with(['student', 'academicYear', 'examBy'])
            ->orderByDesc('checkup_date');

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $schoolGender = $request->attributes->get('schoolGender');
        if ($schoolGender) {
            $query->whereHas('student', fn ($s) => $s->where('gender', $schoolGender === 'putra' ? 'L' : 'P'));
        }

        if ($activeAy) {
            $query->where('academic_year_id', $activeAy->id);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($sq) => $sq
                ->where('checkup_type', 'like', "%{$q}%")
                ->orWhereHas('student', fn ($st) => $st->where('name', 'like', "%{$q}%"))
            );
        }

        if ($request->filled('study_group_id')) {
            $query->whereHas('student', fn ($st) => $st
                ->whereHas('studyGroups', fn ($sc) => $sc
                    ->where('study_group_id', $request->study_group_id)
                    ->where('is_active', true)
                )
            );
        }

        if ($request->filled('checkup_type')) {
            $query->where('checkup_type', $request->checkup_type);
        }

        if ($request->filled('month') && $request->month !== '') {
            [$year, $month] = explode('-', $request->month);
            $query->whereRaw('YEAR(checkup_date) = ?', [$year])
                ->whereRaw('MONTH(checkup_date) = ?', [$month]);
        } elseif ($activeAy) {
            $query->where('academic_year_id', $activeAy->id);
        }

        $checkups = $query->paginate(15)->withQueryString();

        $studyGroups = StudyGroup::with('gradeLevel')
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('health.health-checkups.index', compact('checkups', 'studyGroups', 'activeAy', 'userId'));
    }

    public function create(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $schoolGender = $request->attributes->get('schoolGender');

        $studentQuery = Student::with('studyGroups.studyGroup.gradeLevel')
            ->where('status', 'active');

        if ($schoolId) {
            $studentQuery->where('school_id', $schoolId);
        }
        if ($schoolGender) {
            $studentQuery->where('gender', $schoolGender === 'putra' ? 'L' : 'P');
        }

        $students = $studentQuery->orderBy('name')->get();
        $groupedStudents = [];
        foreach ($students as $s) {
            $sg = $s->currentClassHistory?->studyGroup;
            $label = $sg ? $sg->full_name : 'Tanpa Kelas';
            if (! isset($groupedStudents[$label])) {
                $groupedStudents[$label] = [];
            }
            $groupedStudents[$label][] = $s;
        }

        $activeAy = AcademicYear::where('is_active', true)->first();
        $examStaff = User::orderBy('name')->get();

        return view('health.health-checkups.create', compact('groupedStudents', 'activeAy', 'examStaff', 'userId'));
    }

    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'checkup_date' => 'required|date',
            'checkup_type' => 'required|string|max:50',
            'height_cm' => 'nullable|integer|min:30|max:250',
            'weight_kg' => 'nullable|integer|min:5|max:200',
            'vision_left' => 'nullable|numeric|min:0|max:3',
            'vision_right' => 'nullable|numeric|min:0|max:3',
            'hearing_status' => 'nullable|string|max:50',
            'dental_status' => 'nullable|string|max:50',
            'tb_screening_result' => 'nullable|string|max:50',
            'tb_notes' => 'nullable|string',
            'is_school_entry' => 'nullable|boolean',
            'exam_by' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $student = Student::find($validated['student_id']);
        $validated['school_id'] = $student->school_id;
        $validated['is_school_entry'] = $request->boolean('is_school_entry');
        $validated['dental_status'] = $validated['dental_status'] ?: 'normal';
        $validated['hearing_status'] = $validated['hearing_status'] ?: 'normal';

        $checkup = StudentHealthCheckup::create($validated);
        $checkup->syncHeightWeightToStudent();

        return redirect()
            ->route('user.uks.health-checkups.index', ['userId' => $userId])
            ->with('success', 'Data medical check-up berhasil disimpan.');
    }

    public function show(Request $request, string $userId, string $uuid)
    {
        $checkup = StudentHealthCheckup::with(['student', 'academicYear', 'examBy'])->findOrFail($uuid);

        return view('health.health-checkups.show', compact('checkup', 'userId'));
    }

    public function edit(Request $request, string $userId, string $uuid)
    {
        $checkup = StudentHealthCheckup::findOrFail($uuid);
        $schoolId = $request->attributes->get('schoolContextId');
        $schoolGender = $request->attributes->get('schoolGender');

        $studentQuery = Student::with('studyGroups.studyGroup.gradeLevel')
            ->where('status', 'active');

        if ($schoolId) {
            $studentQuery->where('school_id', $schoolId);
        }
        if ($schoolGender) {
            $studentQuery->where('gender', $schoolGender === 'putra' ? 'L' : 'P');
        }

        $students = $studentQuery->orderBy('name')->get();
        $groupedStudents = [];
        foreach ($students as $s) {
            $sg = $s->currentClassHistory?->studyGroup;
            $label = $sg ? $sg->full_name : 'Tanpa Kelas';
            if (! isset($groupedStudents[$label])) {
                $groupedStudents[$label] = [];
            }
            $groupedStudents[$label][] = $s;
        }

        $academicYears = AcademicYear::orderByDesc('name')->get();
        $examStaff = User::orderBy('name')->get();

        return view('health.health-checkups.edit', compact('checkup', 'groupedStudents', 'academicYears', 'examStaff', 'userId'));
    }

    public function update(Request $request, string $userId, string $uuid)
    {
        $checkup = StudentHealthCheckup::findOrFail($uuid);

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'checkup_date' => 'required|date',
            'checkup_type' => 'required|string|max:50',
            'height_cm' => 'nullable|integer|min:30|max:250',
            'weight_kg' => 'nullable|integer|min:5|max:200',
            'vision_left' => 'nullable|numeric|min:0|max:3',
            'vision_right' => 'nullable|numeric|min:0|max:3',
            'hearing_status' => 'nullable|string|max:50',
            'dental_status' => 'nullable|string|max:50',
            'tb_screening_result' => 'nullable|string|max:50',
            'tb_notes' => 'nullable|string',
            'is_school_entry' => 'nullable|boolean',
            'exam_by' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $validated['is_school_entry'] = $request->boolean('is_school_entry');
        $validated['dental_status'] = $validated['dental_status'] ?: 'normal';
        $validated['hearing_status'] = $validated['hearing_status'] ?: 'normal';
        $checkup->update($validated);
        $checkup->syncHeightWeightToStudent();

        return redirect()
            ->route('user.uks.health-checkups.show', ['userId' => $userId, 'uuid' => $uuid])
            ->with('success', 'Data medical check-up berhasil diperbarui.');
    }

    public function destroy(string $userId, string $uuid)
    {
        $checkup = StudentHealthCheckup::findOrFail($uuid);
        $checkup->delete();

        return redirect()
            ->route('user.uks.health-checkups.index', ['userId' => $userId])
            ->with('success', 'Data medical check-up berhasil dihapus.');
    }
}
