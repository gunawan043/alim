<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StudyGroupApiController extends Controller
{
    /**
     * Get students not yet assigned to any active rombel in the current academic year.
     */
    public function unassignedStudents(Request $request, string $userId, string $studyGroupId)
    {
        // Ambil school_id langsung dari study group — tidak perlu middleware
        $studyGroup = StudyGroup::find($studyGroupId);
        if (! $studyGroup) {
            return response()->json(['success' => false, 'message' => 'Rombel tidak ditemukan.'], 404);
        }

        // Students who already have an active class history (is_active = true)
        $assignedStudentIds = StudentClassHistory::where('is_active', true)
            ->pluck('student_id');

        $query = Student::where('school_id', $studyGroup->school_id)
            ->whereNotIn('id', $assignedStudentIds)
            ->where('status', 'active')
            ->orderBy('name');

        if ($request->filled('q')) {
            $query->where(fn ($sq) => $sq
                ->where('name', 'like', '%'.$request->q.'%')
                ->orWhere('nisn', 'like', '%'.$request->q.'%')
            );
        }

        $students = $query->get(['id', 'name', 'nisn', 'gender', 'birth_place', 'birth_date']);

        return response()->json(['success' => true, 'data' => $students]);
    }

    /**
     * Add a student to a study group (rombel).
     */
    public function addStudent(Request $request, string $userId, string $studyGroupId)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $studyGroup = StudyGroup::findOrFail($studyGroupId);
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $studyGroup->school_id !== $schoolId) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $activeAcademicYear = AcademicYear::where('is_active', true)->first();

        // Deactivate any previous active history for this student
        StudentClassHistory::where('student_id', $request->student_id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Check if already has a record for this student + AY (may be inactive in a different SG)
        $exists = StudentClassHistory::where('student_id', $request->student_id)
            ->where('academic_year_id', $activeAcademicYear?->id)
            ->first();

        if ($exists) {
            // Reactivate the existing record (move to this study group)
            $exists->update([
                'study_group_id' => $studyGroupId,
                'is_active' => true,
            ]);
            $history = $exists;
        } else {
            // Count existing active members
            $count = StudentClassHistory::where('study_group_id', $studyGroupId)
                ->where('is_active', true)
                ->count();

            $history = StudentClassHistory::create([
                'id' => (string) Str::uuid(),
                'student_id' => $request->student_id,
                'study_group_id' => $studyGroupId,
                'academic_year_id' => $activeAcademicYear?->id,
                'is_active' => true,
                'join_date' => now()->toDateString(),
                'attendance_number' => $count + 1,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Santri berhasil ditambahkan ke rombel.',
            'data' => $history,
        ]);
    }

    /**
     * Add multiple students to a study group at once (bulk).
     */
    public function bulkAddStudents(Request $request, string $userId, string $studyGroupId)
    {
        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
        ], [
            'student_ids.required' => 'Pilih minimal satu Santri.',
            'student_ids.*.exists' => 'ID Santri tidak valid.',
        ]);

        $studyGroup = StudyGroup::findOrFail($studyGroupId);
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $studyGroup->school_id !== $schoolId) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $activeAcademicYear = AcademicYear::where('is_active', true)->first();

        // Count existing active members
        $currentCount = StudentClassHistory::where('study_group_id', $studyGroupId)
            ->where('is_active', true)
            ->count();

        $added = 0;
        $now = now()->toDateString();

        foreach ($request->student_ids as $studentId) {
            // Deactivate any previous active history for this student
            StudentClassHistory::where('student_id', $studentId)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // Check if already has an active record for this study group + AY
            $exists = StudentClassHistory::where('student_id', $studentId)
                ->where('study_group_id', $studyGroupId)
                ->where('academic_year_id', $activeAcademicYear?->id)
                ->where('is_active', true)
                ->first();

            if ($exists) {
                $exists->update(['is_active' => true]);
            } else {
                $currentCount++;
                StudentClassHistory::create([
                    'id' => (string) Str::uuid(),
                    'student_id' => $studentId,
                    'study_group_id' => $studyGroupId,
                    'academic_year_id' => $activeAcademicYear?->id,
                    'is_active' => true,
                    'join_date' => $now,
                    'attendance_number' => $currentCount,
                ]);
                $added++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => $added > 0
                ? "{$added} Santri berhasil ditambahkan ke {$studyGroup->full_name}."
                : 'Semua Santri yang dipilih sudah ada di rombel ini.',
        ]);
    }

    /**
     * Get students enrolled in a specific study group for a given academic year.
     * Used by the student promotion feature.
     */
    public function getAssignedStudents(Request $request, string $studyGroupId)
    {
        $studyGroup = StudyGroup::with(['gradeLevel', 'school'])->find($studyGroupId);
        if (! $studyGroup) {
            return response()->json(['success' => false, 'message' => 'Rombel tidak ditemukan.'], 404);
        }

        $academicYearId = $request->get('academic_year_id');

        $query = StudentClassHistory::with('student:id,name,nisn,gender,birth_date')
            ->where('study_group_id', $studyGroupId)
            ->where('is_active', true);

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        $histories = $query->orderBy('attendance_number')->get();

        return response()->json([
            'success' => true,
            'study_group' => $studyGroup,
            'students' => $histories,
        ]);
    }

    /**
     * Remove a student from a study group.
     */
    public function removeStudent(Request $request, string $userId, string $studyGroupId)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $studyGroup = StudyGroup::findOrFail($studyGroupId);
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $studyGroup->school_id !== $schoolId) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $activeAcademicYear = AcademicYear::where('is_active', true)->first();

        $deleted = StudentClassHistory::where('student_id', $request->student_id)
            ->where('study_group_id', $studyGroupId)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Santri berhasil dikeluarkan dari rombel.',
        ]);
    }

    /**
     * Get study groups by school + academic year (for matrix builder).
     */
    public function bySchool(Request $request)
    {
        $schoolId = $request->get('school_id');
        $academicYearId = $request->get('academic_year_id');

        if (! $schoolId) {
            return response()->json(['success' => false, 'message' => 'school_id diperlukan.'], 400);
        }

        $query = StudyGroup::with('gradeLevel')
            ->where('school_id', $schoolId)
            ->where('is_active', true);

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        } else {
            $activeAy = AcademicYear::where('is_active', true)->first();
            if ($activeAy) {
                $query->where('academic_year_id', $activeAy->id);
            }
        }

        $groups = $query->orderBy(fn ($q) => $q->orderByRaw('COALESCE((SELECT level FROM grade_levels gl WHERE gl.id = study_groups.grade_level_id), 0) ASC'))
            ->orderBy('name')
            ->get(['id', 'name', 'grade_level_id'])
            ->map(fn ($sg) => [
                'id' => $sg->id,
                'name' => $sg->name,
                'level' => $sg->gradeLevel?->level ?? 0,
                'grade_name' => $sg->gradeLevel?->name ?? '',
            ]);

        return response()->json(['success' => true, 'data' => $groups]);
    }
}
