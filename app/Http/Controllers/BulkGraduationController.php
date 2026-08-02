<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Alumni;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudentMutationOut;
use App\Models\StudyGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BulkGraduationController extends Controller
{
    public function index(Request $request)
    {
        $userId = request()->route('userId');
        $schoolContextId = $request->attributes->get('schoolContextId');
        $studyGroupId = $request->route('studyGroupId');

        $studyGroup = null;
        if ($studyGroupId) {
            $studyGroup = StudyGroup::with(['gradeLevel', 'homeroomTeacher', 'school'])->find($studyGroupId);
        }

        $schools = School::orderBy('name')->get();

        // Pre-fill filter dari request
        $selectedGrade = $request->get('entry_grade_level');
        $selectedYear = $request->get('graduation_year', date('Y'));
        $selectedSchoolId = $request->get('school_id');

        $students = collect([]);
        $totalMatch = 0;

        if ($studyGroup) {
            // Ambil dari rombel langsung
            $activeAcademicYear = AcademicYear::where('is_active', true)->first();
            $studentIds = StudentClassHistory::where('study_group_id', $studyGroup->id)
                ->where('is_active', true)
                ->when($activeAcademicYear, fn ($q) => $q->where('academic_year_id', $activeAcademicYear->id))
                ->pluck('student_id');

            $students = Student::whereIn('id', $studentIds)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'nisn', 'gender', 'birth_place', 'birth_date', 'school_id', 'entry_grade_level', 'entry_date']);
            $totalMatch = $students->count();
        } elseif ($selectedGrade && $selectedYear) {
            $query = Student::where('status', 'active')
                ->where('entry_grade_level', $selectedGrade)
                ->where('entry_date', 'like', $selectedYear.'%')
                ->orderBy('name');

            if ($schoolContextId) {
                $query->where('school_id', $schoolContextId);
            } elseif ($selectedSchoolId) {
                $query->where('school_id', $selectedSchoolId);
            }

            $students = $query->get(['id', 'name', 'nisn', 'gender', 'birth_place', 'birth_date', 'school_id', 'entry_grade_level', 'entry_date']);
            $totalMatch = $students->count();
        }

        return view('bulk-graduation.index', compact(
            'schools', 'userId', 'schoolContextId',
            'students', 'totalMatch',
            'selectedGrade', 'selectedYear', 'selectedSchoolId',
            'studyGroup',
        ));
    }

    public function store(Request $request)
    {
        $userId = request()->route('userId');
        $schoolContextId = $request->attributes->get('schoolContextId');

        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'graduation_year' => 'required|integer|min:1900|max:2100',
            'graduation_date' => 'nullable|date',
            'graduation_certificate_number' => 'nullable|string|max:50',
            'graduation_school_name' => 'nullable|string|max:255',
            'reason' => 'nullable|string',
        ]);

        $graduationDate = $validated['graduation_date'] ?? $validated['graduation_year'].'-06-01';

        $processed = 0;
        foreach ($validated['student_ids'] as $studentId) {
            $student = Student::find($studentId);

            // Skip if student is not active
            if (! $student || $student->status !== 'active') {
                continue;
            }

            $student->update([
                'status' => 'graduate',
                'graduation_year' => $validated['graduation_year'],
                'graduation_date' => $graduationDate,
            ]);

            // Create mutation-out record for audit trail
            StudentMutationOut::create([
                'school_id' => $student->school_id,
                'student_id' => $student->id,
                'out_type' => 'graduation',
                'student_name' => $student->name,
                'student_nisn' => $student->nisn,
                'student_nis' => $student->nis,
                'student_gender' => $student->gender,
                'student_birth_date' => $student->birth_date,
                'student_birth_place' => $student->birth_place,
                'student_address' => $student->address,
                'student_previous_school' => $student->previous_school,
                'graduation_year' => $validated['graduation_year'],
                'graduation_certificate_number' => $validated['graduation_certificate_number'] ?? null,
                'graduation_school_name' => $validated['graduation_school_name'] ?? null,
                'reason' => $validated['reason'] ?? 'Lulus sesuai periode angkatan',
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'requested_by' => Auth::id(),
            ]);

            // Auto-create alumni record
            Alumni::firstOrCreate(
                ['student_id' => $student->id],
                [
                    'school_id' => $student->school_id,
                    'graduation_year' => $validated['graduation_year'],
                    'graduation_certificate_number' => $validated['graduation_certificate_number'] ?? null,
                    'graduation_date' => $graduationDate,
                    'tracer_status' => 'pending',
                ]
            );

            $processed++;
        }

        return redirect()->route('user.bulk-graduation.index', ['userId' => $userId])
            ->with('success', "{$processed} santri berhasil ditandai lulus.");
    }
}
