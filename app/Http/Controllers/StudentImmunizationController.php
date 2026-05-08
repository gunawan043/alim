<?php

namespace App\Http\Controllers;

use App\Models\StudentImmunization;
use App\Models\Student;
use App\Models\StudyGroup;
use Illuminate\Http\Request;

class StudentImmunizationController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $query = StudentImmunization::with(['student', 'school'])
            ->orderByDesc('date_given');

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        // Auto-filter by logged-in user's school gender (putra | putri)
        $schoolGender = $request->attributes->get('schoolGender');
        if ($schoolGender) {
            $query->whereHas('student', fn($s) => $s->where('gender', $schoolGender === 'putra' ? 'L' : 'P'));
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($sq) => $sq
                ->where('immunization_type', 'like', "%{$q}%")
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

        if ($request->filled('immunization_type')) {
            $query->where('immunization_type', $request->immunization_type);
        }

        $immunizations = $query->paginate(15)->withQueryString();

        $studyGroups = StudyGroup::with('gradeLevel')
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('health.immunizations.index', compact('immunizations', 'studyGroups', 'userId'));
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
            if (!isset($groupedStudents[$label])) $groupedStudents[$label] = [];
            $groupedStudents[$label][] = $s;
        }

        return view('health.immunizations.create', compact('groupedStudents', 'userId'));
    }

    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'immunization_type' => 'required|string|max:100',
            'vaccine_name' => 'nullable|string|max:191',
            'date_given' => 'required|date',
            'age_at_vaccination_days' => 'nullable|integer|min:0',
            'place' => 'nullable|string|max:191',
            'batch_number' => 'nullable|string|max:50',
            'side_effects' => 'nullable|string',
            'medical_staff' => 'nullable|string|max:191',
            'notes' => 'nullable|string',
        ]);

        $student = Student::find($validated['student_id']);
        $validated['school_id'] = $student->school_id;

        StudentImmunization::create($validated);

        return redirect()
            ->route('user.uks.immunizations.index', ['userId' => $userId])
            ->with('success', 'Data imunisasi berhasil disimpan.');
    }

    public function show(Request $request, string $userId, string $uuid)
    {
        $immunization = StudentImmunization::with(['student', 'school'])->findOrFail($uuid);

        return view('health.immunizations.show', compact('immunization', 'userId'));
    }

    public function edit(Request $request, string $userId, string $uuid)
    {
        $immunization = StudentImmunization::findOrFail($uuid);
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

        return view('health.immunizations.edit', compact('immunization', 'groupedStudents', 'userId'));
    }

    public function update(Request $request, string $userId, string $uuid)
    {
        $immunization = StudentImmunization::findOrFail($uuid);

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'immunization_type' => 'required|string|max:100',
            'vaccine_name' => 'nullable|string|max:191',
            'date_given' => 'required|date',
            'age_at_vaccination_days' => 'nullable|integer|min:0',
            'place' => 'nullable|string|max:191',
            'batch_number' => 'nullable|string|max:50',
            'side_effects' => 'nullable|string',
            'medical_staff' => 'nullable|string|max:191',
            'notes' => 'nullable|string',
        ]);

        $immunization->update($validated);

        return redirect()
            ->route('user.uks.immunizations.show', ['userId' => $userId, 'uuid' => $uuid])
            ->with('success', 'Data imunisasi berhasil diperbarui.');
    }

    public function destroy(string $userId, string $uuid)
    {
        $immunization = StudentImmunization::findOrFail($uuid);
        $immunization->delete();

        return redirect()
            ->route('user.uks.immunizations.index', ['userId' => $userId])
            ->with('success', 'Data imunisasi berhasil dihapus.');
    }

    public function byStudent(Request $request, string $userId, string $studentUuid)
    {
        $student = Student::findOrFail($studentUuid);

        $records = StudentImmunization::where('student_id', $studentUuid)
            ->orderByDesc('date_given')
            ->get();

        return view('health.immunizations.by-student', compact('student', 'records', 'userId'));
    }
}