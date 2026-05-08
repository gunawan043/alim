<?php

namespace App\Http\Controllers;

use App\Models\StudentHealthPermit;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentHealthPermitController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $activeAy = AcademicYear::where('is_active', true)->first();

        $query = StudentHealthPermit::with(['student', 'academicYear', 'creator'])
            ->orderByDesc('created_at');

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
                ->where('description', 'like', "%{$q}%")
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

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('month') && $request->month !== '') {
            [$year, $month] = explode('-', $request->month);
            $query->whereRaw('YEAR(start_date) = ?', [$year])
                  ->whereRaw('MONTH(start_date) = ?', [$month]);
        } elseif ($activeAy) {
            $query->where('academic_year_id', $activeAy->id);
        }

        $permits = $query->paginate(15)->withQueryString();

        $studyGroups = StudyGroup::with('gradeLevel')
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('health.health-permits.index', compact('permits', 'studyGroups', 'activeAy', 'userId'));
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

        $dormitories = $schoolId
            ? Dormitory::where('school_id', $schoolId)->orderBy('name')->get()
            : Dormitory::orderBy('name')->get();

        return view('health.health-permits.create', compact('groupedStudents', 'activeAy', 'dormitories', 'userId'));
    }

    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'dormitory_id' => 'nullable|exists:dormitories,id',
            'permit_type' => 'required|string|max:50',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'rest_days' => 'nullable|integer|min:0',
            'attachment_path' => 'nullable|string|max:255',
        ]);

        $student = Student::find($validated['student_id']);
        $validated['school_id'] = $student->school_id;
        $validated['created_by'] = Auth::id();

        StudentHealthPermit::create($validated);

        return redirect()
            ->route('user.uks.health-permits.index', ['userId' => $userId])
            ->with('success', 'Izin sakit berhasil diajukan.');
    }

    public function show(Request $request, string $userId, string $uuid)
    {
        $permit = StudentHealthPermit::with([
            'student', 'academicYear', 'dormitory',
            'approvedBy', 'parentNotifiedBy', 'creator',
        ])->findOrFail($uuid);

        return view('health.health-permits.show', compact('permit', 'userId'));
    }

    public function edit(Request $request, string $userId, string $uuid)
    {
        $permit = StudentHealthPermit::findOrFail($uuid);
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
        $dormitories = $schoolId
            ? Dormitory::where('school_id', $schoolId)->orderBy('name')->get()
            : Dormitory::orderBy('name')->get();

        return view('health.health-permits.edit', compact('permit', 'groupedStudents', 'academicYears', 'dormitories', 'userId'));
    }

    public function update(Request $request, string $userId, string $uuid)
    {
        $permit = StudentHealthPermit::findOrFail($uuid);

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'dormitory_id' => 'nullable|exists:dormitories,id',
            'permit_type' => 'required|string|max:50',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'rest_days' => 'nullable|integer|min:0',
            'attachment_path' => 'nullable|string|max:255',
        ]);

        $permit->update($validated);

        return redirect()
            ->route('user.uks.health-permits.show', ['userId' => $userId, 'uuid' => $uuid])
            ->with('success', 'Izin sakit berhasil diperbarui.');
    }

    public function approve(Request $request, string $userId, string $uuid)
    {
        $permit = StudentHealthPermit::findOrFail($uuid);

        $validated = $request->validate([
            'approval_note' => 'nullable|string',
        ]);

        $permit->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'approval_note' => $validated['approval_note'] ?? null,
        ]);

        return redirect()
            ->route('user.uks.health-permits.show', ['userId' => $userId, 'uuid' => $uuid])
            ->with('success', 'Izin sakit disetujui.');
    }

    public function reject(Request $request, string $userId, string $uuid)
    {
        $permit = StudentHealthPermit::findOrFail($uuid);

        $validated = $request->validate([
            'approval_note' => 'nullable|string',
        ]);

        $permit->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'approval_note' => $validated['approval_note'] ?? null,
        ]);

        return redirect()
            ->route('user.uks.health-permits.show', ['userId' => $userId, 'uuid' => $uuid])
            ->with('success', 'Izin sakit ditolak.');
    }

    public function notifyParent(Request $request, string $userId, string $uuid)
    {
        $permit = StudentHealthPermit::findOrFail($uuid);

        $permit->update([
            'parent_notified' => true,
            'parent_notified_at' => now(),
            'parent_notified_by' => Auth::id(),
        ]);

        return redirect()
            ->route('user.uks.health-permits.show', ['userId' => $userId, 'uuid' => $uuid])
            ->with('success', 'Orang tua berhasil dinotifikasi.');
    }

    public function destroy(string $userId, string $uuid)
    {
        $permit = StudentHealthPermit::findOrFail($uuid);
        $permit->delete();

        return redirect()
            ->route('user.uks.health-permits.index', ['userId' => $userId])
            ->with('success', 'Izin sakit berhasil dihapus.');
    }
}