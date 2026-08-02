<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentHealthMetric;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Http\Request;

class StudentHealthMetricController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $activeAy = AcademicYear::where('is_active', true)->first();

        $query = StudentHealthMetric::with(['student', 'academicYear', 'measuredBy'])
            ->orderByDesc('record_date');

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $schoolGender = $request->attributes->get('schoolGender');
        if ($schoolGender) {
            $query->whereHas('student', fn ($s) => $s->where('gender', $schoolGender === 'putra' ? 'L' : 'P'));
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($sq) => $sq
                ->orWhereHas('student', fn ($st) => $st->where('name', 'like', "%{$q}%"))
            );
        }

        if ($request->filled('study_group_id')) {
            $query->whereHas('student', fn ($st) => $st
                ->whereHas('studyGroups', fn ($sc) => $sc
                    ->where('study_group_id', $request->study_group_id)
                    ->where('is_active', true)
                )
            );
        }

        if ($request->filled('bmi_category')) {
            $query->where('bmi_category', $request->bmi_category);
        }

        if ($request->filled('month') && $request->month !== '') {
            [$year, $month] = explode('-', $request->month);
            $query->whereRaw('YEAR(record_date) = ?', [$year])
                ->whereRaw('MONTH(record_date) = ?', [$month]);
        } elseif ($activeAy) {
            $query->where('academic_year_id', $activeAy->id);
        }

        $metrics = $query->paginate(15)->withQueryString();

        $studyGroups = StudyGroup::with('gradeLevel')
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('health.health-metrics.index', compact('metrics', 'studyGroups', 'activeAy', 'userId'));
    }

    public function dashboard(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $academicYearId = $request->get('academic_year_id');

        $baseQuery = StudentHealthMetric::where('school_id', $schoolId);

        if ($academicYearId) {
            $baseQuery->where('academic_year_id', $academicYearId);
        }

        // Statistik BMI
        $bmiStats = (clone $baseQuery)
            ->whereNotNull('bmi_category')
            ->selectRaw('bmi_category, count(*) as total')
            ->groupBy('bmi_category')
            ->pluck('total', 'bmi_category');

        $total = $bmiStats->sum() ?: 1;

        $stats = [
            'sangat_kurang' => $bmiStats['sangat_kurang'] ?? 0,
            'kurang' => $bmiStats['kurang'] ?? 0,
            'normal' => $bmiStats['normal'] ?? 0,
            'lebih' => $bmiStats['lebih'] ?? 0,
            'gemuk' => $bmiStats['gemuk'] ?? 0,
        ];

        $academicYears = AcademicYear::orderByDesc('name')->get();

        return view('health.health-metrics.dashboard', compact('stats', 'total', 'academicYears', 'academicYearId', 'userId'));
    }

    public function studentChart(Request $request, string $userId, string $studentUuid)
    {
        $student = Student::findOrFail($studentUuid);
        $schoolId = $request->attributes->get('schoolContextId');

        $metrics = StudentHealthMetric::where('student_id', $studentUuid)
            ->where('school_id', $schoolId)
            ->orderBy('record_date')
            ->get(['id', 'record_date', 'height_cm', 'weight_kg', 'bmi', 'bmi_category']);

        return view('health.health-metrics.student-chart', compact('student', 'metrics', 'userId'));
    }

    public function create(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $schoolGender = $request->attributes->get('schoolGender');
        $activeAy = AcademicYear::where('is_active', true)->first();

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
            if (! isset($groupedStudents[$label])) {
                $groupedStudents[$label] = [];
            }
            $groupedStudents[$label][] = $s;
        }

        $staff = User::orderBy('name')->get();

        return view('health.health-metrics.create', compact('groupedStudents', 'activeAy', 'staff', 'userId'));
    }

    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'record_date' => 'required|date',
            'height_cm' => 'nullable|integer|min:30|max:250',
            'weight_kg' => 'nullable|integer|min:5|max:200',
            'measurement_session' => 'nullable|string|max:50',
            'measured_by' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $student = Student::find($validated['student_id']);
        $validated['school_id'] = $student->school_id;

        $metric = StudentHealthMetric::create($validated);
        $metric->syncHeightWeightToStudent();

        return redirect()
            ->route('user.uks.health-metrics.index', ['userId' => $userId])
            ->with('success', 'Data antropometri berhasil disimpan.');
    }

    public function show(Request $request, string $userId, string $uuid)
    {
        $metric = StudentHealthMetric::with(['student', 'academicYear', 'measuredBy'])->findOrFail($uuid);

        return view('health.health-metrics.show', compact('metric', 'userId'));
    }

    public function edit(Request $request, string $userId, string $uuid)
    {
        $metric = StudentHealthMetric::findOrFail($uuid);
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
            if (! isset($groupedStudents[$label])) {
                $groupedStudents[$label] = [];
            }
            $groupedStudents[$label][] = $s;
        }

        $academicYears = AcademicYear::orderByDesc('name')->get();
        $staff = User::orderBy('name')->get();

        return view('health.health-metrics.edit', compact('metric', 'groupedStudents', 'academicYears', 'staff', 'userId'));
    }

    public function update(Request $request, string $userId, string $uuid)
    {
        $metric = StudentHealthMetric::findOrFail($uuid);

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'record_date' => 'required|date',
            'height_cm' => 'nullable|integer|min:30|max:250',
            'weight_kg' => 'nullable|integer|min:5|max:200',
            'measurement_session' => 'nullable|string|max:50',
            'measured_by' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $metric->update($validated);
        $metric->syncHeightWeightToStudent();

        return redirect()
            ->route('user.uks.health-metrics.show', ['userId' => $userId, 'uuid' => $uuid])
            ->with('success', 'Data antropometri berhasil diperbarui.');
    }

    public function destroy(string $userId, string $uuid)
    {
        $metric = StudentHealthMetric::findOrFail($uuid);
        $metric->delete();

        return redirect()
            ->route('user.uks.health-metrics.index', ['userId' => $userId])
            ->with('success', 'Data antropometri berhasil dihapus.');
    }
}
