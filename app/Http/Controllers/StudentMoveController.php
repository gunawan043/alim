<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentMoveController extends Controller
{
    /**
     * Display the student move page.
     *
     * GET /{userId}/student-move?study_group_id=xxx
     */
    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();

        // Source study group (where students are currently)
        $sourceStudyGroup = null;
        if ($request->filled('study_group_id')) {
            $sourceStudyGroup = StudyGroup::with(['gradeLevel', 'school', 'homeroomTeacher'])
                ->find($request->study_group_id);
        }

        // Get available destination rombel options
        // Must be: same grade level, same academic year, different rombel, same school
        $availableDestinations = collect();
        if ($sourceStudyGroup) {
            $availableDestinations = StudyGroup::with(['gradeLevel', 'school'])
                ->where('id', '!=', $sourceStudyGroup->id)
                ->where('school_id', $sourceStudyGroup->school_id)
                ->where('academic_year_id', $sourceStudyGroup->academic_year_id)
                ->where('grade_level_id', $sourceStudyGroup->grade_level_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(function ($sg) use ($activeAcademicYear) {
                    $sg->studentCount = StudentClassHistory::where('study_group_id', $sg->id)
                        ->where('is_active', true)
                        ->when($activeAcademicYear, fn ($q) => $q->where('academic_year_id', $activeAcademicYear->id))
                        ->count();

                    return $sg;
                });
        }

        // Students currently in the source rombel
        $students = collect([]);
        if ($sourceStudyGroup) {
            $studentIds = StudentClassHistory::where('study_group_id', $sourceStudyGroup->id)
                ->where('is_active', true)
                ->when($activeAcademicYear, fn ($q) => $q->where('academic_year_id', $activeAcademicYear->id))
                ->pluck('student_id');

            $students = Student::whereIn('id', $studentIds)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'nisn', 'gender', 'birth_place', 'birth_date']);
        }

        return view('student-move.index', compact(
            'userId', 'sourceStudyGroup', 'students',
            'availableDestinations',
        ));
    }

    /**
     * Process the student move (pindahkan Santri ke rombel lain, tingkat sama).
     *
     * POST /{userId}/student-move
     */
    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $validated = $request->validate([
            'source_study_group_id' => 'required|exists:study_groups,id',
            'destination_study_group_id' => 'required|exists:study_groups,id|different:source_study_group_id',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'move_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $sourceSg = StudyGroup::with(['gradeLevel', 'school'])->findOrFail($validated['source_study_group_id']);
        $destSg = StudyGroup::with(['gradeLevel', 'school'])->findOrFail($validated['destination_study_group_id']);

        // Security: school scoping
        if ($schoolId && $sourceSg->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        // Validation: must be same academic year
        if ($sourceSg->academic_year_id !== $destSg->academic_year_id) {
            return back()->withInput()->with('error', 'Rombel asal dan tujuan harus berada pada tahun ajaran yang sama.');
        }

        // Validation: must be same grade level
        if ($sourceSg->grade_level_id !== $destSg->grade_level_id) {
            return back()->withInput()->with('error', 'Pindahan hanya dapat dilakukan antar rombel dengan tingkat yang SAMA. Gunakan menu Kenaikan Kelas untuk memindahkan ke tingkat berbeda.');
        }

        // Validation: cannot move to same rombel
        if ($sourceSg->id === $destSg->id) {
            return back()->withInput()->with('error', 'Rombel asal dan tujuan tidak boleh sama.');
        }

        // Capacity check: destination rombel capacity
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();
        $currentDestCount = StudentClassHistory::where('study_group_id', $destSg->id)
            ->where('is_active', true)
            ->when($activeAcademicYear, fn ($q) => $q->where('academic_year_id', $activeAcademicYear->id))
            ->count();

        $movingCount = count($validated['student_ids']);
        $availableSlots = max(0, $destSg->capacity - $currentDestCount);

        if ($movingCount > $availableSlots) {
            $sisa = $availableSlots;

            return back()->withInput()->with('error', "Rombel tujuan hanya memiliki {$sisa} slot tersisa.Anda mencoba memindahkan {$movingCount} santri. Kurangi jumlah yang dipilih atau pilih rombel lain.");
        }

        $moveDate = $validated['move_date'];
        $notes = $validated['notes'] ?? 'Pindahan rombel tingkat sama';
        $results = ['success' => 0, 'skipped' => 0];

        DB::transaction(function () use (
            $sourceSg, $destSg, $activeAcademicYear, $moveDate, $notes,
            $validated, &$results
        ) {
            foreach ($validated['student_ids'] as $studentId) {
                // Check if student is currently in source rombel
                $currentHistory = StudentClassHistory::where('student_id', $studentId)
                    ->where('study_group_id', $sourceSg->id)
                    ->where('is_active', true)
                    ->when($activeAcademicYear, fn ($q) => $q->where('academic_year_id', $activeAcademicYear->id))
                    ->first();

                if (! $currentHistory) {
                    $results['skipped']++;

                    continue;
                }

                // Deactivate old history
                $currentHistory->update([
                    'is_active' => false,
                    'leave_date' => $moveDate,
                ]);

                // Create new history in destination rombel
                $newCount = StudentClassHistory::where('study_group_id', $destSg->id)
                    ->where('academic_year_id', $activeAcademicYear->id)
                    ->count();

                StudentClassHistory::create([
                    'student_id' => $studentId,
                    'study_group_id' => $destSg->id,
                    'academic_year_id' => $activeAcademicYear->id,
                    'is_active' => true,
                    'join_date' => $moveDate,
                    'attendance_number' => $newCount + 1,
                    'notes' => $notes,
                ]);

                $results['success']++;
            }
        });

        $msg = "Pindahkan Santri selesai. {$results['success']} santri berhasil dipindahkan ke {$destSg->full_name}.";
        if ($results['skipped'] > 0) {
            $msg .= " {$results['skipped']} dilewati (tidak ditemukan di rombel asal).";
        }

        return redirect()
            ->route('user.students.index', [
                'userId' => $userId,
                'study_group_id' => $sourceSg->id,
            ])
            ->with('success', $msg);
    }
}
