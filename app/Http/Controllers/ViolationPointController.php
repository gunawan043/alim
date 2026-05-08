<?php

namespace App\Http\Controllers;

use App\Models\ViolationPoint;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\GradeLevel;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ViolationPointController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $query = ViolationPoint::with(['student', 'studyGroup', 'recordedBy'])
            ->orderByDesc('violation_date');

        // Filter by school context if available
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId) {
            $query->whereHas('student', fn($q) => $q->where('school_id', $schoolId));
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($sq) => $sq
                ->where('violation_type', 'like', "%{$q}%")
                ->orWhereHas('student', fn($st) => $st->where('name', 'like', "%{$q}%"))
            );
        }

        if ($request->filled('study_group_id')) {
            $query->where('study_group_id', $request->study_group_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('violation_date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        $violations = $query->paginate(15)->withQueryString();

        // For filter dropdowns
        $studyGroups = $schoolId
            ? StudyGroup::where('school_id', $schoolId)->orderBy('name')->get()
            : StudyGroup::orderBy('name')->get();

        return view('violation-points.index', compact('violations', 'studyGroups', 'userId'));
    }

    public function create(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $students = $schoolId
            ? Student::where('school_id', $schoolId)->where('status', 'active')->orderBy('name')->get()
            : Student::where('status', 'active')->orderBy('name')->get();

        $studyGroups = $schoolId
            ? StudyGroup::where('school_id', $schoolId)->orderBy('name')->get()
            : StudyGroup::orderBy('name')->get();

        return view('violation-points.create', compact('students', 'studyGroups', 'userId'));
    }

    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'study_group_id' => 'required|exists:study_groups,id',
            'violation_date' => 'required|date',
            'violation_type' => 'required|string|max:100',
            'points' => 'required|integer|min:0|max:100',
            'description' => 'nullable|string|max:1000',
            'action_taken' => 'nullable|string|max:1000',
        ]);

        ViolationPoint::create([
            ...$validated,
            'recorded_by' => Auth::id(),
        ]);

        return redirect()
            ->route('user.violation-points.index', ['userId' => $userId])
            ->with('success', 'Poin pelanggaran berhasil disimpan.');
    }

    public function show(Request $request, string $userId, string $violationUuid)
    {
        $violation = ViolationPoint::with(['student', 'studyGroup', 'recordedBy'])
            ->findOrFail($violationUuid);

        return view('violation-points.show', compact('violation', 'userId'));
    }

    public function edit(Request $request, string $userId, string $violationUuid)
    {
        $violation = ViolationPoint::findOrFail($violationUuid);

        $schoolId = $request->attributes->get('schoolContextId');

        $students = $schoolId
            ? Student::where('school_id', $schoolId)->where('status', 'active')->orderBy('name')->get()
            : Student::where('status', 'active')->orderBy('name')->get();

        $studyGroups = $schoolId
            ? StudyGroup::where('school_id', $schoolId)->orderBy('name')->get()
            : StudyGroup::orderBy('name')->get();

        return view('violation-points.edit', compact('violation', 'students', 'studyGroups', 'userId'));
    }

    public function update(Request $request, string $userId, string $violationUuid)
    {
        $violation = ViolationPoint::findOrFail($violationUuid);

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'study_group_id' => 'required|exists:study_groups,id',
            'violation_date' => 'required|date',
            'violation_type' => 'required|string|max:100',
            'points' => 'required|integer|min:0|max:100',
            'description' => 'nullable|string|max:1000',
            'action_taken' => 'nullable|string|max:1000',
        ]);

        $violation->update($validated);

        return redirect()
            ->route('user.violation-points.index', ['userId' => $userId])
            ->with('success', 'Poin pelanggaran berhasil diperbarui.');
    }

    public function destroy(string $userId, string $violationUuid)
    {
        $violation = ViolationPoint::findOrFail($violationUuid);
        $violation->delete();

        return redirect()
            ->route('user.violation-points.index', ['userId' => $userId])
            ->with('success', 'Poin pelanggaran berhasil dihapus.');
    }

    // ── DASHBOARD ─────────────────────────────────────────────────

    public function dashboard(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        // Overall stats
        $totalViolations = ViolationPoint::when($schoolId, fn($q) => $q->whereHas('student', fn($s) => $s->where('school_id', $schoolId)))->count();
        $totalPoints     = ViolationPoint::when($schoolId, fn($q) => $q->whereHas('student', fn($s) => $s->where('school_id', $schoolId)))->sum('points');
        $uniqueStudents  = ViolationPoint::when($schoolId, fn($q) => $q->whereHas('student', fn($s) => $s->where('school_id', $schoolId)))
            ->distinct('student_id')->count('student_id');

        // By study group
        $byStudyGroup = ViolationPoint::when($schoolId, fn($q) => $q->whereHas('student', fn($s) => $s->where('school_id', $schoolId)))
            ->select('study_group_id', DB::raw('COUNT(*) as total'), DB::raw('SUM(points) as total_points'))
            ->groupBy('study_group_id')
            ->orderByDesc('total')
            ->get();

        // Load studyGroup relationship manually
        $studyGroupIds = $byStudyGroup->pluck('study_group_id');
        $loadedStudyGroups = StudyGroup::whereIn('id', $studyGroupIds)
            ->with('gradeLevel:id,name,level')->get()->keyBy('id');
        $byStudyGroup->each(fn($row) => $row->setRelation('studyGroup', $loadedStudyGroups->get($row->study_group_id)));

        // By grade level — using join + raw select (MySQL compatible)
        $byGradeLevel = ViolationPoint::when($schoolId, fn($q) => $q->whereHas('student', fn($s) => $s->where('school_id', $schoolId)))
            ->join('study_groups', 'violation_points.study_group_id', '=', 'study_groups.id')
            ->join('grade_levels', 'study_groups.grade_level_id', '=', 'grade_levels.id')
            ->select(
                'study_groups.grade_level_id',
                'grade_levels.name as grade_level_name',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(violation_points.points) as total_points')
            )
            ->groupBy('study_groups.grade_level_id', 'grade_levels.name')
            ->orderByDesc('total')
            ->get();

        // Monthly trend (current year) — MySQL compatible
        $monthlyTrend = ViolationPoint::when($schoolId, fn($q) => $q->whereHas('student', fn($s) => $s->where('school_id', $schoolId)))
            ->selectRaw("DATE_FORMAT(violation_date, '%m') as month, COUNT(*) as total, SUM(points) as total_points")
            ->whereRaw("DATE_FORMAT(violation_date, '%Y') = ?", [now()->year])
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        // Fill missing months with 0
        $months = collect(range(1, 12))->map(fn($m) => [
            'month' => str_pad($m, 2, '0', STR_PAD_LEFT),
            'label' => now()->month($m)->translatedFormat('M'),
            'total' => $monthlyTrend->get(str_pad($m, 2, '0', STR_PAD_LEFT))?->total ?? 0,
            'total_points' => $monthlyTrend->get(str_pad($m, 2, '0', STR_PAD_LEFT))?->total_points ?? 0,
        ]);

        // Top pelanggaran jenis
        $topViolationTypes = ViolationPoint::when($schoolId, fn($q) => $q->whereHas('student', fn($s) => $s->where('school_id', $schoolId)))
            ->select('violation_type', DB::raw('COUNT(*) as total'))
            ->groupBy('violation_type')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $studyGroups = $schoolId
            ? StudyGroup::where('school_id', $schoolId)->orderBy('name')->get()
            : StudyGroup::orderBy('name')->get();

        return view('violation-points.dashboard', compact(
            'userId', 'totalViolations', 'totalPoints', 'uniqueStudents',
            'byStudyGroup', 'byGradeLevel', 'months', 'topViolationTypes', 'studyGroups'
        ));
    }

    // ── REKAP POIN PER SISWA ─────────────────────────────────────

    public function recap(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $query = Student::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->where('status', 'active')
            ->with(['studyGroups' => fn($q) => $q->limit(1)])
            ->withSum('violationPoints', 'points')
            ->orderByDesc('violation_points_sum_points');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($sq) => $sq
                ->where('name', 'like', "%{$q}%")
                ->orWhere('nisn', 'like', "%{$q}%")
            );
        }

        if ($request->filled('study_group_id')) {
            $sg = $request->study_group_id;
            $query->whereHas('studyGroups', fn($sq) => $sq->where('study_groups.id', $sg));
        }

        if ($request->filled('min_points')) {
            $query->havingRaw('COALESCE(violation_points_sum_points, 0) >= ?', [$request->min_points]);
        }

        $recaps = $query->paginate(20)->withQueryString();

        $studyGroups = $schoolId
            ? StudyGroup::where('school_id', $schoolId)->orderBy('name')->get()
            : StudyGroup::orderBy('name')->get();

        // Summary stats
        $summary = [
            'total_students' => Student::when($schoolId, fn($q) => $q->where('school_id', $schoolId))->where('status', 'active')->count(),
            'total_violations' => ViolationPoint::when($schoolId, fn($q) => $q->whereHas('student', fn($s) => $s->where('school_id', $schoolId)))->count(),
            'total_points' => ViolationPoint::when($schoolId, fn($q) => $q->whereHas('student', fn($s) => $s->where('school_id', $schoolId)))->sum('points'),
            'students_with_violations' => ViolationPoint::when($schoolId, fn($q) => $q->whereHas('student', fn($s) => $s->where('school_id', $schoolId)))->distinct('student_id')->count('student_id'),
        ];

        return view('violation-points.recap', compact('userId', 'recaps', 'studyGroups', 'summary'));
    }

    public function recapDetail(Request $request, string $userId, string $studentUuid)
    {
        $student = Student::with(['studyGroups' => fn($q) => $q->limit(1)])->findOrFail($studentUuid);

        $violations = ViolationPoint::with(['studyGroup', 'recordedBy'])
            ->where('student_id', $studentUuid)
            ->orderByDesc('violation_date')
            ->paginate(15);

        $totalPoints = ViolationPoint::where('student_id', $studentUuid)->sum('points');

        return view('violation-points.recap-detail', compact('userId', 'student', 'violations', 'totalPoints'));
    }

    // ── EXPORT PDF ───────────────────────────────────────────────

    public function exportPdf(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $query = ViolationPoint::with(['student', 'studyGroup', 'recordedBy'])
            ->orderByDesc('violation_date');

        if ($schoolId) {
            $query->whereHas('student', fn($q) => $q->where('school_id', $schoolId));
        }

        if ($request->filled('study_group_id')) {
            $query->where('study_group_id', $request->study_group_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('violation_date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($sq) => $sq
                ->where('violation_type', 'like', "%{$q}%")
                ->orWhereHas('student', fn($st) => $st->where('name', 'like', "%{$q}%"))
            );
        }

        $violations = $query->get();
        $totalPoints = $violations->sum('points');

        $pdf = Pdf::loadView('violation-points.pdf.export', compact('violations', 'totalPoints', 'userId'));
        $pdf->setPaper('A4', 'landscape');

        $filename = 'poin-pelanggaran-' . now()->format('Ymd-His') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * API: cari siswa berdasarkan nama/NISN
     */
    public function findStudent(Request $request)
    {
        $q = $request->get('q', '');
        if (strlen($q) < 2) return response()->json([]);

        $students = Student::where('status', 'active')
            ->where(fn($sq) => $sq
                ->where('name', 'like', "%{$q}%")
                ->orWhere('nisn', 'like', "%{$q}%")
            )
            ->limit(20)
            ->get(['id', 'name', 'nisn', 'gender', 'birth_place', 'birth_date', 'address']);

        return response()->json($students->map(fn($s) => [
            'id'          => $s->id,
            'name'        => $s->name,
            'nisn'        => $s->nisn,
            'gender'      => $s->gender,
            'gender_text' => $s->gender_text,
            'birth_place' => $s->birth_place,
            'birth_date'  => $s->birth_date?->format('d/m/Y'),
            'address'     => $s->address,
        ]));
    }
}
