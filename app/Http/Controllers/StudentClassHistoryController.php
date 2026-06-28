<?php

namespace App\Http\Controllers;

use App\Events\StudentAssignedToRombel;
use App\Events\StudentExitedFromRombel;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class StudentClassHistoryController extends Controller
{
    /**
     * Show form to assign a student to a rombel (study group).
     */
    public function create(Request $request, string $userId, string $studentId)
    {
        $student = Student::findOrFail($studentId);

        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $student->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $activeAcademicYear = AcademicYear::where('is_active', true)->first();

        // Rombel yang eligible: milik sekolah siswa, tahun ajaran aktif, dan belum penuh
        $studyGroups = StudyGroup::with(['gradeLevel'])
            ->where('school_id', $student->school_id)
            ->when(
                $activeAcademicYear,
                fn ($q) => $q->where('academic_year_id', $activeAcademicYear->id)
            )
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $existingHistory = $student->classHistories()
            ->where('is_active', true)
            ->with('studyGroup.gradeLevel')
            ->first();

        return view('student-class-histories.create', compact(
            'student',
            'studyGroups',
            'activeAcademicYear',
            'existingHistory',
            'userId'
        ));
    }

    /**
     * Store a new class history (assign student to a rombel).
     */
    public function store(Request $request, string $userId, string $studentId)
    {
        $validated = $request->validate([
            'study_group_id' => 'required|exists:study_groups,id',
            'attendance_number' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:255',
            'join_date' => 'nullable|date',
        ]);

        $student = Student::findOrFail($studentId);

        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $student->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $studyGroup = StudyGroup::findOrFail($validated['study_group_id']);
        if ($studyGroup->school_id !== $student->school_id) {
            return Redirect::back()
                ->withErrors(['study_group_id' => 'Rombel tidak sesuai dengan sekolah siswa.'])
                ->withInput();
        }

        $activeAcademicYear = AcademicYear::where('is_active', true)->first();

        // Deaktifkan history aktif sebelumnya (jika ada)
        StudentClassHistory::where('student_id', $student->id)
            ->where('is_active', true)
            ->each(function ($h) use ($activeAcademicYear) {
                $h->update(['is_active' => false]);
                if ($activeAcademicYear && $h->academic_year_id === $activeAcademicYear->id) {
                    StudentExitedFromRombel::dispatch($h->student_id, $h->study_group_id, $activeAcademicYear->id);
                }
            });

        // Jika sudah ada record untuk TA yang sama, reuse; jika belum, buat baru
        $history = StudentClassHistory::where('student_id', $student->id)
            ->where('academic_year_id', $activeAcademicYear?->id)
            ->first();

        if ($history) {
            $oldGroupId = $history->study_group_id;
            $history->update([
                'study_group_id' => $studyGroup->id,
                'is_active' => true,
                'attendance_number' => $validated['attendance_number'] ?? $history->attendance_number,
                'notes' => $validated['notes'] ?? $history->notes,
                'join_date' => $validated['join_date'] ?? $history->join_date ?? now()->toDateString(),
            ]);

            // Jika rombel berubah, dispatch event agar provisioning berjalan
            if ($oldGroupId !== $studyGroup->id) {
                event(new StudentAssignedToRombel($history));
            }
        } else {
            // Auto-number kalau user tidak isi no absen
            if (empty($validated['attendance_number'])) {
                $count = StudentClassHistory::where('study_group_id', $studyGroup->id)
                    ->where('is_active', true)
                    ->count();
                $validated['attendance_number'] = $count + 1;
            }

            $history = StudentClassHistory::create([
                'id' => (string) Str::uuid(),
                'student_id' => $student->id,
                'study_group_id' => $studyGroup->id,
                'academic_year_id' => $activeAcademicYear?->id,
                'attendance_number' => $validated['attendance_number'],
                'is_active' => true,
                'notes' => $validated['notes'] ?? null,
                'join_date' => $validated['join_date'] ?? now()->toDateString(),
            ]);

            event(new StudentAssignedToRombel($history));
        }

        return redirect()
            ->route('user.students.show', ['userId' => $userId, 'santriUuid' => $student->id])
            ->with('success', 'Santri berhasil ditambahkan ke rombel.');
    }

    /**
     * Show form to edit a class history (change rombel, attendance number, etc).
     */
    public function edit(Request $request, string $userId, string $historyUuid)
    {
        $history = StudentClassHistory::with(['studyGroup.gradeLevel', 'student', 'academicYear'])
            ->findOrFail($historyUuid);

        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $history->student?->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $student = $history->student;

        $studyGroups = StudyGroup::with(['gradeLevel'])
            ->where('school_id', $student->school_id)
            ->where('academic_year_id', $history->academic_year_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('student-class-histories.edit', compact(
            'student',
            'history',
            'studyGroups',
            'userId'
        ));
    }

    /**
     * Update a class history (change rombel, attendance number, notes, etc).
     */
    public function update(Request $request, string $userId, string $historyUuid)
    {
        $history = StudentClassHistory::findOrFail($historyUuid);

        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $history->student?->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'study_group_id' => 'required|exists:study_groups,id',
            'attendance_number' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:255',
            'join_date' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        $studyGroup = StudyGroup::findOrFail($validated['study_group_id']);
        if ($studyGroup->school_id !== $history->student->school_id) {
            return Redirect::back()
                ->withErrors(['study_group_id' => 'Rombel tidak sesuai dengan sekolah siswa.'])
                ->withInput();
        }

        $isActive = $request->boolean('is_active');

        // Jika akan diaktifkan, nonaktifkan history aktif lain milik siswa yang sama
        if ($isActive && ! $history->is_active) {
            StudentClassHistory::where('student_id', $history->student_id)
                ->where('id', '!=', $history->id)
                ->where('is_active', true)
                ->get()
                ->each(function ($old) {
                    $old->update(['is_active' => false]);
                    StudentExitedFromRombel::dispatch($old->student_id, $old->study_group_id, $old->academic_year_id);
                });
        }

        // Jika transisi dari aktif → nonaktif tanpa diganti, dispatch event deactivation
        if (! $isActive && $history->getOriginal('is_active') && $history->is_active === false) {
            StudentExitedFromRombel::dispatch($history->student_id, $history->study_group_id, $history->academic_year_id);
        }

        $history->update([
            'study_group_id' => $studyGroup->id,
            'attendance_number' => $validated['attendance_number'] ?? $history->attendance_number,
            'notes' => $validated['notes'] ?? $history->notes,
            'join_date' => $validated['join_date'] ?? $history->join_date,
            'is_active' => $isActive,
        ]);

        // Jika rombel berubah saat update, dispatch event agar provisioning berjalan
        if ($studyGroup->id !== $history->getOriginal('study_group_id')) {
            event(new StudentAssignedToRombel($history));
        }

        return redirect()
            ->route('user.students.show', ['userId' => $userId, 'santriUuid' => $history->student_id])
            ->with('success', 'Riwayat rombel berhasil diperbarui.');
    }

    /**
     * Delete a class history record.
     */
    public function destroy(Request $request, string $userId, string $historyUuid)
    {
        $history = StudentClassHistory::findOrFail($historyUuid);

        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $history->student?->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $studentId = $history->student_id;
        $studyGroupId = $history->study_group_id;
        $academicYearId = $history->academic_year_id;

        // Dispatch deactivation event BEFORE delete so downstream records can be cleaned up
        if ($history->is_active) {
            StudentExitedFromRombel::dispatch($studentId, $studyGroupId, $academicYearId);
        }

        $history->delete();

        return redirect()
            ->route('user.students.show', ['userId' => $userId, 'santriUuid' => $studentId])
            ->with('success', 'Riwayat rombel berhasil dihapus.');
    }
}
