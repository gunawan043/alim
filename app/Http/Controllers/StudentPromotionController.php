<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudentPromotion;
use App\Models\StudentPromotionDetail;
use App\Models\StudyGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentPromotionController extends Controller
{
    /**
     * ── HALAMAN LIST PROMOSI ────────────────────────────────
     */
    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $query = StudentPromotion::with([
            'fromAcademicYear',
            'toAcademicYear',
            'fromStudyGroup',
            'toStudyGroup',
            'executedBy',
        ])
            ->withCount(['details as total_students'])
            ->withCount(['details as success_count' => fn($q) => $q->where('status', 'success')])
            ->withCount(['details as failed_count'  => fn($q) => $q->where('status', 'failed')]);

        if ($schoolId) {
            $query->whereHas('fromStudyGroup', fn($q) => $q->where('school_id', $schoolId));
        }

        if ($request->filled('academic_year')) {
            $query->where('from_academic_year_id', $request->academic_year);
        }

        $promotions = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $academicYears = AcademicYear::orderBy('name', 'desc')->get();

        return view('student-promotions.index', compact('promotions', 'academicYears', 'userId'));
    }

    /**
     * ── HALAMAN BUAT PROMOSI BARU ──────────────────────────
     */
    public function create(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $academicYears = AcademicYear::orderBy('name', 'desc')->get();

        $schools = $schoolId
            ? School::where('id', $schoolId)->get()
            : School::orderBy('name')->get();

        return view('student-promotions.create', compact(
            'userId', 'academicYears', 'schools', 'schoolId'
        ));
    }

    /**
     * ── GET DATA SISWA PER ROMBEL (AJAX) ───────────────────
     */
    public function getStudentsByStudyGroup(Request $request, string $userId, string $studyGroupId)
    {
        $studyGroup = StudyGroup::with(['gradeLevel', 'school'])->find($studyGroupId);
        if (!$studyGroup) {
            return response()->json(['error' => 'Rombel tidak ditemukan'], 404);
        }

        $academicYearId = $request->get('academic_year_id');
        if (!$academicYearId) {
            return response()->json(['error' => 'academic_year_id diperlukan'], 400);
        }

        $histories = StudentClassHistory::with('student:id,name,nisn,gender')
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->orderBy('attendance_number')
            ->get();

        return response()->json([
            'study_group' => $studyGroup,
            'students'    => $histories,
        ]);
    }

    /**
     * ── STORE PROMOSI (DRAFT) ───────────────────────────────
     */
    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $validated = $request->validate([
            'from_academic_year_id'  => 'required|exists:academic_years,id',
            'to_academic_year_id'   => 'required|exists:academic_years,id|different:from_academic_year_id',
            'from_study_group_id'   => 'nullable|exists:study_groups,id',
            'to_study_group_id'     => 'nullable|exists:study_groups,id',
            'promotion_date'        => 'required|date',
            'auto_enroll'           => 'boolean',
            'include_inactive'      => 'boolean',
            'skip_graduate'         => 'boolean',
            'grade_shift'           => 'integer|min:-2|max:2',
            'notes'                  => 'nullable|string',
            'student_ids'           => 'required|array|min:1',
            'student_ids.*'         => 'exists:students,id',
            'student_actions'       => 'nullable|array',
            'student_actions.*'     => 'in:promote,retain,graduate,mutate_out,skip',
        ]);

        // Cek rombel asal
        $fromAy = AcademicYear::find($validated['from_academic_year_id']);
        $toAy   = AcademicYear::find($validated['to_academic_year_id']);

        $fromStudyGroup = isset($validated['from_study_group_id'])
            ? StudyGroup::with('gradeLevel')->find($validated['from_study_group_id'])
            : null;
        $toStudyGroup = isset($validated['to_study_group_id'])
            ? StudyGroup::with('gradeLevel')->find($validated['to_study_group_id'])
            : null;

        // Validasi: dari rombel mana
        if (!$fromStudyGroup) {
            return back()->withInput()->with('error', 'Pilih rombel asal terlebih dahulu.');
        }

        // Cek siswa di rombel asal
        $studentIds = $validated['student_ids'];
        $existingHistories = StudentClassHistory::where('study_group_id', $fromStudyGroup->id)
            ->where('academic_year_id', $fromAy->id)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        $validStudentIds = $existingHistories->keys()->toArray();
        if (empty($validStudentIds)) {
            return back()->withInput()->with('error', 'Tidak ada siswa yang cocok di rombel ini pada tahun ajaran tersebut.');
        }

        // Buat record promosi
        $promotion = StudentPromotion::create([
            'from_academic_year_id' => $validated['from_academic_year_id'],
            'to_academic_year_id'   => $validated['to_academic_year_id'],
            'from_study_group_id'  => $validated['from_study_group_id'],
            'to_study_group_id'    => $validated['to_study_group_id'] ?? null,
            'promotion_date'       => $validated['promotion_date'],
            'status'               => 'draft',
            'auto_enroll'          => $validated['auto_enroll'] ?? true,
            'include_inactive'     => $validated['include_inactive'] ?? false,
            'skip_graduate'        => $validated['skip_graduate'] ?? true,
            'grade_shift'          => $validated['grade_shift'] ?? 1,
            'notes'                => $validated['notes'] ?? null,
        ]);

        // Buat detail per siswa
        $studentActions = $validated['student_actions'] ?? [];
        foreach ($validStudentIds as $studentId) {
            StudentPromotionDetail::create([
                'promotion_id'             => $promotion->id,
                'student_id'              => $studentId,
                'action'                  => $studentActions[$studentId] ?? 'promote',
                'status'                  => 'pending',
            ]);
        }

        return redirect()
            ->route('user.student-promotions.show', ['userId' => $userId, 'id' => $promotion->id])
            ->with('success', "Promosi disimpan sebagai draft. {$promotion->details()->count()} siswa siap diproses.");
    }

    /**
     * ── DETAIL PROMOSI ──────────────────────────────────────
     */
    public function show(Request $request, string $userId, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $promotion = StudentPromotion::with([
            'fromAcademicYear',
            'toAcademicYear',
            'fromStudyGroup.gradeLevel',
            'toStudyGroup.gradeLevel',
            'details.student',
            'executedBy',
        ])->findOrFail($id);

        if ($schoolId && $promotion->fromStudyGroup?->school_id !== $schoolId) {
            abort(403);
        }

        return view('student-promotions.show', compact('promotion', 'userId'));
    }

    /**
     * ── UPDATE DETAIL SISWA (EDIT ACTION) ───────────────────
     */
    public function updateDetail(Request $request, string $userId, string $id, string $detailId)
    {
        $detail = StudentPromotionDetail::where('promotion_id', $id)
            ->where('id', $detailId)
            ->firstOrFail();

        $validated = $request->validate([
            'action'   => 'required|in:promote,retain,graduate,mutate_out,skip',
            'notes'    => 'nullable|string',
            'override_grade_shift' => 'nullable|integer|min:-2|max:2',
        ]);

        $detail->update($validated);

        return back()->with('success', 'Aksi siswa berhasil diperbarui.');
    }

    /**
     * ── EKSEKUSI PROMOSI ─────────────────────────────────────
     */
    public function execute(Request $request, string $userId, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $promotion = StudentPromotion::with([
            'fromAcademicYear',
            'toAcademicYear',
            'fromStudyGroup.gradeLevel',
            'toStudyGroup',
            'details.student',
        ])->findOrFail($id);

        if ($schoolId && $promotion->fromStudyGroup?->school_id !== $schoolId) {
            abort(403);
        }

        if ($promotion->status === 'completed') {
            return back()->with('error', 'Promosi ini sudah pernah dieksekusi.');
        }

        $request->validate([
            'confirmed' => 'required|accepted',
        ]);

        DB::transaction(function () use ($promotion, $userId) {
            $now = now();
            $promotionDate = $promotion->promotion_date;

            foreach ($promotion->details as $detail) {
                $student = $detail->student;

                // Cek apakah harus di-skip
                if ($detail->action === 'skip') {
                    $detail->update(['status' => 'success', 'notes' => 'Dilompati (skip)']);
                    continue;
                }

                // Cek graduate — skip_graduate option
                if ($detail->action === 'graduate' || ($detail->action === 'promote' && $promotion->skip_graduate)) {
                    // Cek apakah siswa di tingkat akhir
                    $fromLevel = $promotion->fromStudyGroup?->gradeLevel?->level ?? 0;
                    $schoolType = $promotion->fromStudyGroup?->school?->school_type ?? 'smp';
                    $finalLevels = match($schoolType) {
                        'smp' => [9],
                        'sd'  => [6],
                        default => [6, 9, 12],
                    };

                    if (in_array($fromLevel, $finalLevels)) {
                        $student->update([
                            'status'          => 'graduate',
                            'graduation_year' => $promotionDate->format('Y'),
                            'graduation_date' => $promotionDate,
                        ]);
                        // Tutup histori lama
                        StudentClassHistory::where('student_id', $student->id)
                            ->where('academic_year_id', $promotion->from_academic_year_id)
                            ->where('is_active', true)
                            ->update([
                                'is_active'  => false,
                                'leave_date' => $promotionDate,
                            ]);
                        $detail->update(['status' => 'success', 'notes' => 'Diluluskan (tingkat akhir)']);
                        continue;
                    }
                }

                // Mutasi keluar
                if ($detail->action === 'mutate_out') {
                    $student->update(['status' => 'transfer']);
                    StudentClassHistory::where('student_id', $student->id)
                        ->where('academic_year_id', $promotion->from_academic_year_id)
                        ->where('is_active', true)
                        ->update([
                            'is_active'  => false,
                            'leave_date' => $promotionDate,
                        ]);
                    $detail->update(['status' => 'success', 'notes' => 'Mutasi keluar']);
                    continue;
                }

                // Tinggal kelas (retain)
                if ($detail->action === 'retain') {
                    // Tutup histori lama
                    StudentClassHistory::where('student_id', $student->id)
                        ->where('academic_year_id', $promotion->from_academic_year_id)
                        ->where('is_active', true)
                        ->update([
                            'is_active'  => false,
                            'leave_date' => $promotionDate,
                        ]);

                    // Buat record baru di rombel yang SAMA di tahun ajaran baru
                    if ($promotion->auto_enroll && $promotion->to_academic_year_id) {
                        $nextHistory = StudentClassHistory::create([
                            'student_id'       => $student->id,
                            'study_group_id'   => $promotion->from_study_group_id,
                            'academic_year_id' => $promotion->to_academic_year_id,
                            'is_active'        => true,
                            'join_date'        => $promotionDate,
                            'attendance_number' => StudentClassHistory::where('student_id', $student->id)
                                ->where('study_group_id', $promotion->from_study_group_id)
                                ->where('academic_year_id', $promotion->from_academic_year_id)
                                ->value('attendance_number'),
                        ]);
                    }

                    $detail->update(['status' => 'success', 'notes' => 'Tinggal kelas']);
                    continue;
                }

                // Naik kelas (promote) — logic utama
                if ($detail->action === 'promote') {
                    // Tutup histori lama
                    StudentClassHistory::where('student_id', $student->id)
                        ->where('academic_year_id', $promotion->from_academic_year_id)
                        ->where('is_active', true)
                        ->update([
                            'is_active'  => false,
                            'leave_date' => $promotionDate,
                        ]);

                    // Auto-enroll ke rombel baru
                    if ($promotion->auto_enroll && $promotion->to_academic_year_id) {
                        // Target rombel ditentukan dari:
                        // 1. Jika to_study_group_id diset langsung → pakai itu
                        // 2. Jika tidak → cari rombel dengan level +grade_shift di tahun ajaran baru
                        $targetStudyGroupId = $promotion->to_study_group_id;

                        if (!$targetStudyGroupId) {
                            $fromLevel = $promotion->fromStudyGroup?->gradeLevel?->level ?? 0;
                            $shift = $detail->override_grade_shift ?? $promotion->grade_shift;
                            $targetLevel = $fromLevel + $shift;

                            // Cari rombel dengan level yang dimaksud di tahun ajaran baru
                            $targetGradeLevel = GradeLevel::where('school_id', $promotion->fromStudyGroup->school_id)
                                ->where('level', $targetLevel)
                                ->first();

                            if ($targetGradeLevel) {
                                $targetStudyGroupId = StudyGroup::where('school_id', $promotion->fromStudyGroup->school_id)
                                    ->where('academic_year_id', $promotion->to_academic_year_id)
                                    ->where('grade_level_id', $targetGradeLevel->id)
                                    ->first()?->id;
                            }
                        }

                        if ($targetStudyGroupId) {
                            // Cek apakah sudah ada di rombel tujuan (unique constraint)
                            $alreadyEnrolled = StudentClassHistory::where('student_id', $student->id)
                                ->where('academic_year_id', $promotion->to_academic_year_id)
                                ->exists();

                            if (!$alreadyEnrolled) {
                                StudentClassHistory::create([
                                    'student_id'       => $student->id,
                                    'study_group_id'   => $targetStudyGroupId,
                                    'academic_year_id' => $promotion->to_academic_year_id,
                                    'is_active'        => true,
                                    'join_date'        => $promotionDate,
                                    'attendance_number' => StudentClassHistory::where('student_id', $student->id)
                                        ->where('study_group_id', $promotion->from_study_group_id)
                                        ->where('academic_year_id', $promotion->from_academic_year_id)
                                        ->value('attendance_number'),
                                ]);
                            } else {
                                $detail->update(['status' => 'failed', 'error_message' => 'Siswa sudah terdaftar di rombel tujuan tahun ajaran baru.']);
                                continue;
                            }
                        } else {
                            $detail->update(['status' => 'failed', 'error_message' => 'Rombel tujuan tidak ditemukan di tahun ajaran baru.']);
                            continue;
                        }
                    }

                    $detail->update(['status' => 'success', 'notes' => 'Berhasil dipromosikan']);
                    continue;
                }
            }

            // Update status promosi
            $promotion->update([
                'status'       => 'completed',
                'executed_by'  => auth()->id(),
                'executed_at'  => $now,
            ]);
        });

        $promotion->refresh();
        $success = $promotion->details()->where('status', 'success')->count();
        $failed  = $promotion->details()->where('status', 'failed')->count();

        return redirect()
            ->route('user.student-promotions.show', ['userId' => $userId, 'id' => $promotion->id])
            ->with('success', "Promosi selesai. {$success} berhasil, {$failed} gagal.");
    }

    /**
     * ── CANCEL PROMOSI ──────────────────────────────────────
     */
    public function cancel(Request $request, string $userId, string $id)
    {
        $promotion = StudentPromotion::findOrFail($id);

        if ($promotion->status === 'completed') {
            return back()->with('error', 'Promosi yang sudah selesai tidak dapat dibatalkan.');
        }

        $promotion->update(['status' => 'cancelled']);

        return back()->with('success', 'Promosi berhasil dibatalkan.');
    }

    /**
     * ── HAPUS PROMOSI DRAFT ─────────────────────────────────
     */
    public function destroy(Request $request, string $userId, string $id)
    {
        $promotion = StudentPromotion::findOrFail($id);

        if ($promotion->status === 'completed') {
            return back()->with('error', 'Promosi yang sudah selesai tidak dapat dihapus.');
        }

        $promotion->details()->delete();
        $promotion->delete();

        return redirect()
            ->route('user.student-promotions.index', ['userId' => $userId])
            ->with('success', 'Promosi berhasil dihapus.');
    }
}
