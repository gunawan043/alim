<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\GradeLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BulkPromotionController extends Controller
{
    public function index(Request $request, string $userId, ?string $studyGroupId = null)
    {
        $schoolContextId = $request->attributes->get('schoolContextId');

        $studyGroup = null;
        if ($studyGroupId) {
            $studyGroup = StudyGroup::with(['gradeLevel', 'homeroomTeacher', 'school'])->find($studyGroupId);
        }

        $schools = $schoolContextId
            ? School::where('id', $schoolContextId)->get()
            : School::orderBy('name')->get();

        $academicYears = AcademicYear::orderBy('name', 'desc')->get();

        $activeSemester = AcademicYear::where('is_active', true)->value('semester');
        $query = StudyGroup::with(['gradeLevel', 'academicYear'])
            ->where('is_active', true)
            ->whereHas('academicYear', fn($q) => $q->where('semester', $activeSemester));
        if ($schoolContextId) {
            $query->where('school_id', $schoolContextId);
        }
        $allStudyGroups = $query->orderBy('name')->get();

        $students = collect([]);
        $isFinalGrade = false;

        if ($studyGroup) {
            $activeAcademicYear = AcademicYear::where('is_active', true)->first();

            $studentIds = StudentClassHistory::where('study_group_id', $studyGroup->id)
                ->where('is_active', true)
                ->when($activeAcademicYear, fn($q) => $q->where('academic_year_id', $activeAcademicYear->id))
                ->pluck('student_id');

            $students = Student::whereIn('id', $studentIds)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'nisn', 'gender', 'birth_place', 'birth_date', 'school_id']);

            $level = $studyGroup->gradeLevel?->level ?? 0;
            $schoolType = $studyGroup->school?->school_type ?? null;
            $finalLevels = match($schoolType) {
                'smp' => [9],
                'sd'  => [6],
                default => [6, 9, 12],
            };
            $isFinalGrade = in_array($level, $finalLevels);
        }

        return view('bulk-promotion.index', compact(
            'userId', 'studyGroup', 'students', 'isFinalGrade',
            'schools', 'academicYears', 'schoolContextId', 'allStudyGroups',
        ));
    }

    public function store(Request $request, string $userId, string $studyGroupId)
    {
        $studyGroup = StudyGroup::with(['gradeLevel', 'school'])->findOrFail($studyGroupId);
        $schoolContextId = $request->attributes->get('schoolContextId');

        if ($schoolContextId && $studyGroup->school_id !== $schoolContextId) {
            abort(403);
        }

        $validated = $request->validate([
            'to_academic_year_id'   => 'required|exists:academic_years,id',
            'to_study_group_id'     => 'nullable|exists:study_groups,id',
            'promotion_date'        => 'required|date',
            'student_ids'          => 'required|array|min:1',
            'student_ids.*'         => 'exists:students,id',
            'notes'                 => 'nullable|string',
        ]);

        $fromAyId = AcademicYear::where('is_active', true)->value('id');

        $toAyId = $validated['to_academic_year_id'];
        $toStudyGroupId = $validated['to_study_group_id'] ?? null;
        $promotionDate = $validated['promotion_date'];
        $results = ['success' => 0, 'failed' => 0];

        // Hitung target rombel dulu (di luar transaction supaya bisa dibaca untuk redirect)
        $targetSgId = $toStudyGroupId;
        if (!$targetSgId) {
            $fromLevel = $studyGroup->gradeLevel?->level ?? 0;
            $targetLevel = $fromLevel + 1;
            $targetGradeLevel = GradeLevel::where('school_id', $studyGroup->school_id)
                ->where('level', $targetLevel)->first();
            if ($targetGradeLevel) {
                $targetSgId = StudyGroup::where('school_id', $studyGroup->school_id)
                    ->where('academic_year_id', $toAyId)
                    ->where('grade_level_id', $targetGradeLevel->id)
                    ->first()?->id;
            }
        }

        DB::transaction(function () use ($studyGroup, $fromAyId, $toAyId, $targetSgId, $promotionDate, &$results, $validated) {
            foreach ($validated['student_ids'] as $studentId) {
                $student = Student::find($studentId);
                if (!$student || $student->status !== 'active') { continue; }

                // Tutup histori lama
                StudentClassHistory::where('student_id', $studentId)
                    ->where('academic_year_id', $fromAyId)
                    ->where('is_active', true)
                    ->update(['is_active' => false, 'leave_date' => $promotionDate]);

                // Promote — global atau auto-detect
                if ($targetSgId) {
                    $this->enrollStudent($studentId, $targetSgId, $toAyId, $promotionDate);
                    $results['success']++;
                } else {
                    $results['failed']++;
                }
            }
        });

        $msg = "Kenaikan kelas selesai. {$results['success']} berhasil";
        if ($results['failed'] > 0) $msg .= ", {$results['failed']} gagal";

        return redirect()->route('user.students.index', [
            'userId' => $userId,
            'study_group_id' => $targetSgId,
        ])->with('success', $msg);
    }

    private function enrollStudent(string $studentId, string $studyGroupId, string $academicYearId, string $promotionDate): void
    {
        $alreadyEnrolled = StudentClassHistory::where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->exists();

        if ($alreadyEnrolled) return;

        $count = StudentClassHistory::where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $academicYearId)
            ->count();

        StudentClassHistory::create([
            'student_id' => $studentId,
            'study_group_id' => $studyGroupId,
            'academic_year_id' => $academicYearId,
            'is_active' => true,
            'join_date' => $promotionDate,
            'attendance_number' => $count + 1,
        ]);
    }

    /**
     * ── GET ROMBEL BY ACADEMIC YEAR (AJAX) ─────────────────────
     */
    public function getStudyGroups(Request $request, string $userId)
    {
        $schoolContextId = $request->attributes->get('schoolContextId');
        $ayId = $request->get('academic_year_id');
        $fromStudyGroupId = $request->get('from_study_group_id');

        if (!$ayId) {
            return response()->json(['study_groups' => []]);
        }

        $query = StudyGroup::with(['gradeLevel'])
            ->where('academic_year_id', $ayId)
            ->where('is_active', true)
            ->orderBy('name');

        if ($schoolContextId) {
            $query->where('school_id', $schoolContextId);
        } elseif ($fromStudyGroupId) {
            $fromSg = StudyGroup::find($fromStudyGroupId);
            if ($fromSg) {
                $query->where('school_id', $fromSg->school_id);
            }
        }

        $studyGroups = $query->get(['id', 'name', 'grade_level_id'])->map(fn($sg) => [
            'id' => $sg->id,
            'name' => $sg->name,
            'grade_level_name' => $sg->gradeLevel?->name ?? '',
        ]);

        return response()->json(['study_groups' => $studyGroups]);
    }

    /**
     * ── PROMOSI/KENAIKAN KELAS ────────────────────────────────
     */
    public function promote(Request $request, string $userId)
    {
        $schoolContextId = $request->attributes->get('schoolContextId');

        $validated = $request->validate([
            'from_study_group_id'   => 'required|exists:study_groups,id',
            'to_academic_year_id'   => 'required|exists:academic_years,id',
            'to_study_group_id'     => 'nullable|exists:study_groups,id',
            'promotion_date'        => 'required|date',
            'student_ids'           => 'required|array|min:1',
            'student_ids.*'         => 'exists:students,id',
            'student_actions'       => 'nullable|array',
            'student_actions.*'     => 'in:promote,retain,graduate,mutate_out,skip',
            'grade_shift'           => 'integer|min:-2|max:2',
        ]);

        $fromStudyGroup = StudyGroup::with(['gradeLevel', 'school'])->findOrFail($validated['from_study_group_id']);

        if ($schoolContextId && $fromStudyGroup->school_id !== $schoolContextId) {
            abort(403);
        }

        $fromAyId = AcademicYear::where('is_active', true)
            ->where('school_id', $fromStudyGroup->school_id)
            ->value('id');

        $toAyId = $validated['to_academic_year_id'];
        $promotionDate = $validated['promotion_date'];
        $studentActions = $validated['student_actions'] ?? [];
        $gradeShift = $validated['grade_shift'] ?? 1;

        $toStudyGroupId = $validated['to_study_group_id'] ?? null;

        $results = ['success' => 0, 'failed' => 0, 'skipped' => 0];

        DB::transaction(function () use ($validated, $fromStudyGroup, $fromAyId, $toAyId, $promotionDate, $studentActions, $gradeShift, $toStudyGroupId, &$results) {
            foreach ($validated['student_ids'] as $studentId) {
                $action = $studentActions[$studentId] ?? 'promote';

                // Skip
                if ($action === 'skip') {
                    $results['skipped']++;
                    continue;
                }

                // Tutup histori lama
                StudentClassHistory::where('student_id', $studentId)
                    ->where('academic_year_id', $fromAyId)
                    ->where('is_active', true)
                    ->update(['is_active' => false, 'leave_date' => $promotionDate]);

                $student = \App\Models\Student::find($studentId);

                // Mutasi keluar
                if ($action === 'mutate_out') {
                    $student->update(['status' => 'transfer']);
                    $results['success']++;
                    continue;
                }

                // Lulus
                if ($action === 'graduate') {
                    $student->update([
                        'status' => 'graduate',
                        'graduation_year' => date('Y'),
                        'graduation_date' => $promotionDate,
                    ]);
                    $results['success']++;
                    continue;
                }

                // Tinggal kelas — enroll di rombel yang sama di tahun ajaran baru
                if ($action === 'retain') {
                    $alreadyEnrolled = StudentClassHistory::where('student_id', $studentId)
                        ->where('academic_year_id', $toAyId)
                        ->exists();

                    if (!$alreadyEnrolled) {
                        $count = StudentClassHistory::where('study_group_id', $fromStudyGroup->id)
                            ->where('academic_year_id', $toAyId)
                            ->count();

                        StudentClassHistory::create([
                            'student_id' => $studentId,
                            'study_group_id' => $fromStudyGroup->id,
                            'academic_year_id' => $toAyId,
                            'is_active' => true,
                            'join_date' => $promotionDate,
                            'attendance_number' => $count + 1,
                        ]);
                    }
                    $results['success']++;
                    continue;
                }

                // Naik kelas (promote)
                if ($action === 'promote') {
                    $targetSgId = $toStudyGroupId;

                    if (!$targetSgId) {
                        $fromLevel = $fromStudyGroup->gradeLevel?->level ?? 0;
                        $targetLevel = $fromLevel + $gradeShift;

                        $targetGradeLevel = GradeLevel::where('school_id', $fromStudyGroup->school_id)
                            ->where('level', $targetLevel)
                            ->first();

                        if ($targetGradeLevel) {
                            $targetSgId = StudyGroup::where('school_id', $fromStudyGroup->school_id)
                                ->where('academic_year_id', $toAyId)
                                ->where('grade_level_id', $targetGradeLevel->id)
                                ->value('id');
                        }
                    }

                    if ($targetSgId) {
                        $alreadyEnrolled = StudentClassHistory::where('student_id', $studentId)
                            ->where('academic_year_id', $toAyId)
                            ->exists();

                        if (!$alreadyEnrolled) {
                            $count = StudentClassHistory::where('study_group_id', $targetSgId)
                                ->where('academic_year_id', $toAyId)
                                ->count();

                            StudentClassHistory::create([
                                'student_id' => $studentId,
                                'study_group_id' => $targetSgId,
                                'academic_year_id' => $toAyId,
                                'is_active' => true,
                                'join_date' => $promotionDate,
                                'attendance_number' => $count + 1,
                            ]);
                            $results['success']++;
                        } else {
                            $results['failed']++;
                        }
                    } else {
                        $results['failed']++;
                    }
                    continue;
                }
            }
        });

        $msg = "Promosi selesai. {$results['success']} berhasil";
        if ($results['failed'] > 0) $msg .= ", {$results['failed']} gagal";
        if ($results['skipped'] > 0) $msg .= ", {$results['skipped']} dilompati";

        return redirect()
            ->route('user.study-groups.show', ['userId' => $userId, 'id' => $fromStudyGroup->id])
            ->with('success', $msg);
    }
}
