<?php

namespace App\Http\Controllers;

use App\Events\StudentGraduated;
use App\Events\StudentMutatedOut;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Services\StudentExitLetterService;
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

        // Tahun ajaran asal & tingkat asal rombel.
        $fromAyId = $studyGroup?->academic_year_id;
        $fromAy = $fromAyId ? AcademicYear::find($fromAyId) : null;
        $fromLevel = $studyGroup?->gradeLevel?->level ?? 0;

        // KEBIJAKAN: kenaikan kelas HANYA ke TAHUN AJARAN BARU (bukan TA yang sama —
        // pindah kelas pada TA yang sama adalah fitur Mutasi Kelas).
        $targetAcademicYears = $academicYears->filter(function ($ay) use ($fromAy) {
            if (! $fromAy) {
                return true;
            }

            return ($ay->year_start ?? (int) substr($ay->name, 0, 4)) > ($fromAy->year_start ?? (int) substr($fromAy->name, 0, 4));
        })->values();

        // Rombel tujuan: hanya rombel aktif pada TA yang LEBIH BARU dan TINGKAT DI ATAS saat ini.
        $allStudyGroups = StudyGroup::with(['gradeLevel', 'academicYear'])
            ->where('is_active', true)
            ->when($schoolContextId, fn ($q) => $q->where('school_id', $schoolContextId))
            ->whereIn('academic_year_id', $targetAcademicYears->pluck('id'))
            ->when($fromLevel > 0, fn ($q) => $q->whereHas('gradeLevel', fn ($g) => $g->where('level', '>', $fromLevel)))
            ->orderBy('name')
            ->get()
            ->map(fn ($sg) => [
                'id' => $sg->id,
                'name' => $sg->name,
                'academic_year_id' => $sg->academic_year_id,
                'academic_year' => $sg->academicYear?->name,
                'grade_level_id' => $sg->grade_level_id,
                'grade_level' => $sg->gradeLevel?->level,
            ]);

        $students = collect([]);
        $isFinalGrade = false;

        if ($studyGroup) {
            // Gunakan tahun ajaran ROMBEL terpilih — bukan TA aktif global —
            // agar kelas tujuan (mis. 2A di TA baru) tetap menampilkan datanya.
            $historyAyId = $studyGroup->academic_year_id
                ?: AcademicYear::where('is_active', true)->value('id');

            $studentIds = StudentClassHistory::where('study_group_id', $studyGroup->id)
                ->where('is_active', true)
                ->when($historyAyId, fn ($q) => $q->where('academic_year_id', $historyAyId))
                ->pluck('student_id');

            $students = Student::whereIn('id', $studentIds)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'nisn', 'gender', 'birth_place', 'birth_date', 'school_id']);

            $level = $studyGroup->gradeLevel?->level ?? 0;
            $schoolType = $studyGroup->school?->school_type ?? null;
            $finalLevels = match ($schoolType) {
                'smp' => [9],
                'sd' => [6],
                default => [6, 9, 12],
            };
            $isFinalGrade = in_array($level, $finalLevels);
        }

        return view('bulk-promotion.index', compact(
            'userId', 'studyGroup', 'students', 'isFinalGrade',
            'schools', 'academicYears', 'schoolContextId', 'allStudyGroups', 'targetAcademicYears',
        ) + [
            'fromAyId' => $studyGroup?->academic_year_id,
            'fromLevel' => $studyGroup?->gradeLevel?->level ?? 0,
            'fromAyName' => $fromAy?->name,
            'fromRombelName' => $studyGroup?->name,
        ]);
    }

    public function store(Request $request, string $userId, string $studyGroupId)
    {
        $studyGroup = StudyGroup::with(['gradeLevel', 'school'])->findOrFail($studyGroupId);
        $schoolContextId = $request->attributes->get('schoolContextId');

        if ($schoolContextId && $studyGroup->school_id !== $schoolContextId) {
            abort(403);
        }

        $validated = $request->validate([
            'to_academic_year_id' => 'required|exists:academic_years,id',
            'to_study_group_id' => 'nullable|exists:study_groups,id',
            'promotion_date' => 'required|date',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'notes' => 'nullable|string',
        ]);

        // TA asal = TA rombel sumber (bukan TA aktif global — itu penyebab
        // histori lama tidak tertutup dan kelas tujuan tampak kosong).
        $fromAyId = $studyGroup->academic_year_id
            ?: AcademicYear::where('is_active', true)->value('id');

        $toAyId = $validated['to_academic_year_id'];
        $toStudyGroupId = $validated['to_study_group_id'] ?? null;
        $promotionDate = $validated['promotion_date'];
        $results = ['success' => 0, 'failed' => 0];

        // KEBIJAKAN: kenaikan kelas hanya ke TAHUN AJARAN BARU. Pindah kelas pada TA
        // yang sama bukan kenaikan kelas → gunakan fitur Mutasi Kelas.
        $fromAy = AcademicYear::find($fromAyId);
        $toAy = AcademicYear::find($toAyId);

        $fromStart = $fromAy?->year_start ?? ($fromAy ? (int) substr((string) $fromAy->name, 0, 4) : 0);
        $toStart = $toAy?->year_start ?? ($toAy ? (int) substr((string) $toAy->name, 0, 4) : 0);

        if ($toStart <= $fromStart) {
            return back()->withInput()->withErrors([
                'to_academic_year_id' => 'Kenaikan kelas hanya ke TAHUN AJARAN BARU. Untuk pindah kelas pada tahun ajaran yang sama gunakan fitur Mutasi Kelas.',
            ]);
        }

        if ($toStudyGroupId && (string) $toStudyGroupId === (string) $studyGroup->id) {
            return back()->withInput()->withErrors([
                'to_study_group_id' => 'Rombel tujuan sama dengan rombel saat ini.',
            ]);
        }

        // Rombel tujuan: pilihan manual, atau paralel otomatis pada TA baru (1A → 2A).
        $targetSgId = $toStudyGroupId ?: $this->targetStudyGroupId($studyGroup, $toAyId);

        // WAJIB ada target SEBELUM mengubah data apa pun — mencegah santri
        // kehilangan kelas (histori lama ditutup tanpa baris baru).
        if (! $targetSgId) {
            return back()->withInput()->withErrors([
                'to_study_group_id' => 'Rombel tujuan tidak ditemukan pada tahun ajaran baru. Buat rombel tingkat di atasnya terlebih dahulu — tidak ada data yang diubah.',
            ]);
        }

        DB::transaction(function () use ($fromAyId, $toAyId, $targetSgId, $promotionDate, &$results, $validated) {
            foreach ($validated['student_ids'] as $studentId) {
                $student = Student::find($studentId);
                if (! $student || $student->status !== 'active') {
                    continue;
                }

                // 1) Buat/pastikan enrollment BARU pada TA baru terlebih dahulu.
                $this->enrollStudent($studentId, $targetSgId, $toAyId, $promotionDate);

                // 2) Baru tutup histori lama (histori lama TETAP tersimpan dengan leave_date).
                StudentClassHistory::where('student_id', $studentId)
                    ->where('academic_year_id', $fromAyId)
                    ->where('is_active', true)
                    ->update(['is_active' => false, 'leave_date' => $promotionDate]);

                $results['success']++;
            }
        });

        $msg = "Kenaikan kelas selesai. {$results['success']} berhasil";
        if ($results['failed'] > 0) {
            $msg .= ", {$results['failed']} gagal";
        }

        return redirect()->route('user.students.index', [
            'userId' => $userId,
            'study_group_id' => $targetSgId,
        ])->with('success', $msg);
    }

    /**
     * Rombel tujuan otomatis: utamakan KELAS PARALEL yang sama (1A → 2A),
     * fallback ke rombel pertama pada tingkat tujuan. Sebelumnya memakai
     * rombel pertama tanpa memperhatikan nama paralel sehingga 2A kosong.
     */
    private function targetStudyGroupId(StudyGroup $from, string $toAyId, int $gradeShift = 1): ?string
    {
        $fromLevel = $from->gradeLevel?->level ?? 0;
        $targetLevel = $fromLevel + $gradeShift;

        $targetGradeLevel = GradeLevel::where('school_id', $from->school_id)
            ->where('level', $targetLevel)
            ->first();

        if (! $targetGradeLevel) {
            return null;
        }

        $base = StudyGroup::where('school_id', $from->school_id)
            ->where('academic_year_id', $toAyId)
            ->where('grade_level_id', $targetGradeLevel->id)
            ->where('is_active', true);

        // Suffix paralel dari nama sumber: '1A' → 'A', '7-B' → 'B'.
        $suffix = preg_replace('/^\d+[\s\-_.]*/u', '', (string) $from->name);

        if ($suffix !== '') {
            $parallel = (clone $base)->where('name', $targetLevel.$suffix)->value('id');
            if ($parallel) {
                return $parallel;
            }

            $suffixMatch = (clone $base)->where('name', 'like', '%'.$suffix)->value('id');
            if ($suffixMatch) {
                return $suffixMatch;
            }
        }

        return $base->orderBy('name')->value('id');
    }

    /**
     * Enroll student ke rombel tujuan secara IDEMPOTEN:
     * bila sudah ada baris untuk TA tujuan, baris itu dipindahkan/diaktifkan
     * (sebelumnya hanya `exists()` lalu return diam → kelas tujuan kosong).
     */
    private function enrollStudent(string $studentId, string $studyGroupId, string $academicYearId, string $promotionDate): bool
    {
        $existing = StudentClassHistory::where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->first();

        $count = StudentClassHistory::where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $academicYearId)
            ->count();

        if ($existing) {
            // Sudah ada baris pada TA tujuan → pindahkan/aktifkan (idempoten).
            $existing->forceFill([
                'study_group_id' => $studyGroupId,
                'is_active' => true,
                'leave_date' => null,
                'join_date' => $existing->join_date ?: $promotionDate,
                'attendance_number' => $existing->attendance_number ?: ($count + 1),
            ])->save();

            return true;
        }

        StudentClassHistory::create([
            'student_id' => $studentId,
            'study_group_id' => $studyGroupId,
            'academic_year_id' => $academicYearId,
            'is_active' => true,
            'join_date' => $promotionDate,
            'attendance_number' => $count + 1,
        ]);

        return true;
    }

    /**
     * ── GET ROMBEL BY ACADEMIC YEAR (AJAX) ─────────────────────
     */
    public function getStudyGroups(Request $request, string $userId)
    {
        $schoolContextId = $request->attributes->get('schoolContextId');
        $ayId = $request->get('academic_year_id');
        $fromStudyGroupId = $request->get('from_study_group_id');

        if (! $ayId) {
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

        $studyGroups = $query->get(['id', 'name', 'grade_level_id'])->map(fn ($sg) => [
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
            'from_study_group_id' => 'required|exists:study_groups,id',
            'to_academic_year_id' => 'required|exists:academic_years,id',
            'to_study_group_id' => 'nullable|exists:study_groups,id',
            'promotion_date' => 'required|date',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'student_actions' => 'nullable|array',
            'student_actions.*' => 'in:promote,retain,graduate,mutate_out,skip',
            'grade_shift' => 'integer|min:-2|max:2',
        ]);

        $fromStudyGroup = StudyGroup::with(['gradeLevel', 'school'])->findOrFail($validated['from_study_group_id']);

        if ($schoolContextId && $fromStudyGroup->school_id !== $schoolContextId) {
            abort(403);
        }

        // TA asal = TA rombel sumber (bukan TA aktif global).
        $fromAyId = $fromStudyGroup->academic_year_id
            ?: AcademicYear::where('is_active', true)->value('id');

        $toAyId = $validated['to_academic_year_id'];
        $promotionDate = $validated['promotion_date'];
        $studentActions = $validated['student_actions'] ?? [];
        $gradeShift = $validated['grade_shift'] ?? 1;

        $toStudyGroupId = $validated['to_study_group_id'] ?? null;

        $isSameYear = (string) $toAyId === (string) $fromAyId;

        // Aksi promote pada TA yang sama = mutasi kelas → bukan ranah kenaikan kelas.
        // (Aksi graduate/mutate_out/retain/skip tetap boleh.)
        $hasPromoteAction = empty($studentActions) || in_array('promote', array_values($studentActions), true);

        if ($isSameYear && $hasPromoteAction) {
            return back()->withInput()->withErrors([
                'to_academic_year_id' => 'Kenaikan kelas hanya ke TAHUN AJARAN BARU. Untuk pindah kelas pada tahun ajaran yang sama gunakan fitur Mutasi Kelas.',
            ]);
        }

        $results = ['success' => 0, 'failed' => 0, 'skipped' => 0];

        DB::transaction(function () use ($validated, $fromStudyGroup, $fromAyId, $toAyId, $promotionDate, $studentActions, $gradeShift, $toStudyGroupId, $isSameYear, &$results) {
            foreach ($validated['student_ids'] as $studentId) {
                $action = $studentActions[$studentId] ?? 'promote';

                // Skip
                if ($action === 'skip') {
                    $results['skipped']++;

                    continue;
                }

                $student = Student::find($studentId);

                // Mutasi keluar — keluar karena pindah WAJIB punya surat mutasi,
                // jadi suratnya dibuat otomatis (status transfer_out + audit + notifikasi).
                if ($action === 'mutate_out') {
                    $letter = app(StudentExitLetterService::class)
                        ->createForPromotion($student, $promotionDate, auth()->id(), 'Promosi Massal');

                    StudentMutatedOut::dispatch(
                        $student,
                        $letter,
                        StudentMutatedOut::TYPE_MUTATION,
                        $promotionDate,
                        auth()->id(),
                    );
                    $results['success']++;

                    continue;
                }

                // Lulus — jalur lifecycle resmi (status graduate + alumni + audit)
                if ($action === 'graduate') {
                    $fromAy = AcademicYear::find($fromAyId);
                    if ($fromAy) {
                        StudentGraduated::dispatch(
                            $student,
                            $fromStudyGroup,
                            $fromAy,
                            $promotionDate,
                            substr($promotionDate, 0, 4),
                            auth()->id(),
                            'bulk-promotion',
                        );
                    }
                    $results['success']++;

                    continue;
                }

                // Tinggal kelas — enroll di rombel yang sama di tahun ajaran baru
                if ($action === 'retain') {
                    $alreadyEnrolled = StudentClassHistory::where('student_id', $studentId)
                        ->where('academic_year_id', $toAyId)
                        ->exists();

                    if (! $alreadyEnrolled) {
                        $retainCount = StudentClassHistory::where('study_group_id', $fromStudyGroup->id)
                            ->where('academic_year_id', $toAyId)
                            ->where('is_active', true)
                            ->count();

                        if ($fromStudyGroup->capacity > 0 && $retainCount >= $fromStudyGroup->capacity) {
                            $results['failed']++;

                            continue;
                        }

                        StudentClassHistory::where('student_id', $studentId)
                            ->where('academic_year_id', $fromAyId)
                            ->where('is_active', true)
                            ->update(['is_active' => false, 'leave_date' => $promotionDate]);

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

                    // Rombel tujuan: pilihan manual, atau paralel otomatis (1A → 2A).
                    $targetSgId = $toStudyGroupId ?: $this->targetStudyGroupId($fromStudyGroup, $toAyId, $gradeShift);

                    if ($targetSgId) {
                        $targetGroup = StudyGroup::find($targetSgId);

                        // Baris santri pada TA tujuan (pada TA yang sama, ini bisa
                        // merupakan baris SUMBER itu sendiri — jangan dianggap "sudah enroll").
                        $existing = StudentClassHistory::where('student_id', $studentId)
                            ->where('academic_year_id', $toAyId)
                            ->first();

                        $alreadyAtTarget = $existing
                            && (string) $existing->study_group_id === (string) $targetSgId
                            && (bool) $existing->is_active;

                        if ($alreadyAtTarget) {
                            // Sudah benar-benar di rombel tujuan → idempoten.
                            $results['success']++;

                            continue;
                        }

                        // Kapasitas target dihitung tanpa baris santri ini.
                        $targetCount = StudentClassHistory::where('study_group_id', $targetSgId)
                            ->where('academic_year_id', $toAyId)
                            ->where('is_active', true)
                            ->when($existing && (string) $existing->study_group_id === (string) $targetSgId,
                                fn ($q) => $q->where('student_id', '!=', $studentId))
                            ->count();

                        if ($targetGroup && $targetGroup->capacity > 0 && $targetCount >= $targetGroup->capacity) {
                            $results['failed']++;

                            continue;
                        }

                        // Tutup histori lama pada TA ASAL (bila TA sama, jangan sentuh baris target).
                        StudentClassHistory::where('student_id', $studentId)
                            ->where('academic_year_id', $fromAyId)
                            ->where('is_active', true)
                            ->when($toAyId === $fromAyId, fn ($q) => $q->where('study_group_id', '!=', $targetSgId))
                            ->update(['is_active' => false, 'leave_date' => $promotionDate]);

                        // Idempoten: buat atau pindahkan baris TA tujuan.
                        $this->enrollStudent($studentId, $targetSgId, $toAyId, $promotionDate);
                        $results['success']++;
                    } else {
                        $results['failed']++;
                    }

                    continue;
                }
            }
        });

        $msg = "Promosi selesai. {$results['success']} berhasil";
        if ($results['failed'] > 0) {
            $msg .= ", {$results['failed']} gagal";
        }
        if ($results['skipped'] > 0) {
            $msg .= ", {$results['skipped']} dilompati";
        }

        return redirect()
            ->route('user.study-groups.show', ['userId' => $userId, 'id' => $fromStudyGroup->id])
            ->with('success', $msg);
    }
}
