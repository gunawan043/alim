<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\AcademicYear;
use App\Models\StudentClassHistory;
use App\Models\School;
use App\Models\User;
use App\Models\StudentMutationIn;
use App\Models\StudentMutationOut;
use App\Models\StudentAchievement;
use App\Models\ViolationPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WakaController extends Controller
{
    public function dashboard(Request $request)
    {
        $userId = auth()->id();
        $schoolId = $request->attributes->get('schoolContextId');
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();
        $activeSemester = $activeAcademicYear?->semester;

        // ── 1. Overview Stats ────────────────────────────────────
        $stats = $this->getOverviewStats($schoolId, $activeAcademicYear);

        // ── 2. Per-Level Distribution ────────────────────────────
        $levelDistribution = $this->getLevelDistribution($schoolId, $activeAcademicYear);

        // ── 3. Rombel Capacity Status ───────────────────────────
        $rombelCapacity = $this->getRombelCapacity($schoolId, $activeAcademicYear);

        // ── 4. Over-capacity Warnings ─────────────────────────
        $overCapacityRombels = $rombelCapacity->filter(fn($r) => $r['student_count'] > $r['capacity'])->values();

        // ── 5. GTK Stats ─────────────────────────────────────────
        $gtkStats = $this->getGtkStats($schoolId);

        // ── 6. Recent Mutations ──────────────────────────────────
        $recentMutations = $this->getRecentMutations($activeAcademicYear);

        // ── 7. Top Violations This Month ─────────────────────────
        $topViolations = $this->getTopViolations();

        // ── 8. Student Achievements ───────────────────────────────
        $recentAchievements = $this->getRecentAchievements();

        // ── 9. Quick Actions ─────────────────────────────────────
        $quickActions = $this->getQuickActions($userId, $schoolId);

        // ── 10. Rombel List (compact) ────────────────────────────
        $rombelList = $this->getRombelList($schoolId, $activeAcademicYear);

        return view('waka.dashboard', compact(
            'stats', 'levelDistribution', 'rombelCapacity', 'overCapacityRombels',
            'gtkStats', 'recentMutations', 'topViolations', 'recentAchievements',
            'quickActions', 'rombelList', 'activeAcademicYear',
        ));
    }

    private function getOverviewStats($schoolId, $activeAcademicYear)
    {
        // Total students
        $studentQuery = Student::query();
        $gtkQuery = User::query();

        if ($schoolId) {
            $studentQuery->where('school_id', $schoolId);
            $gtkQuery->whereHas('employment', fn($q) => $q->where('school_id', $schoolId));
        }

        $totalStudents = (clone $studentQuery)->count();
        $activeStudents = (clone $studentQuery)->where('status', 'active')->count();

        // In rombel
        $inRombelQuery = StudentClassHistory::where('is_active', true);
        if ($activeAcademicYear) {
            $inRombelQuery->where('academic_year_id', $activeAcademicYear->id);
        }
        if ($schoolId) {
            $inRombelQuery->whereHas('studyGroup', fn($q) => $q->where('school_id', $schoolId));
        }
        $inRombelCount = (clone $inRombelQuery)->count();

        // Unassigned
        $assignedStudentIds = StudentClassHistory::where('is_active', true)
            ->when($activeAcademicYear, fn($q) => $q->where('academic_year_id', $activeAcademicYear->id))
            ->pluck('student_id');
        $unassignedCount = (clone $studentQuery)
            ->where('status', 'active')
            ->whereNotIn('id', $assignedStudentIds)
            ->count();

        // GTK count
        $totalGtk = (clone $gtkQuery)
            ->whereHas('employment', fn($q) => $q->where('is_active', true))
            ->count();

        // Total rombel
        $rombelQuery = StudyGroup::where('is_active', true);
        if ($activeAcademicYear) {
            $rombelQuery->where('academic_year_id', $activeAcademicYear->id);
        }
        if ($schoolId) {
            $rombelQuery->where('school_id', $schoolId);
        }
        $totalRombel = (clone $rombelQuery)->count();

        // Active mutations this month
        $monthStart = now()->startOfMonth();
        $mutationIn = StudentMutationIn::whereIn('status', ['approved', 'submitted'])
            ->whereDate('created_at', '>=', $monthStart)
            ->count();
        $mutationOut = StudentMutationOut::whereIn('status', ['approved', 'submitted'])
            ->whereDate('created_at', '>=', $monthStart)
            ->count();

        return [
            'total_students'   => $totalStudents,
            'active_students'  => $activeStudents,
            'in_rombel'        => $inRombelCount,
            'unassigned'       => $unassignedCount,
            'total_gtk'         => $totalGtk,
            'total_rombel'      => $totalRombel,
            'mutation_in_month' => $mutationIn,
            'mutation_out_month'=> $mutationOut,
        ];
    }

    private function getLevelDistribution($schoolId, $activeAcademicYear)
    {
        return StudentClassHistory::selectRaw('grade_levels.name as level_name,
            grade_levels.level as level_number,
            COUNT(student_class_histories.id) as student_count,
            SUM(study_groups.capacity) as total_capacity')
            ->join('study_groups', 'study_groups.id', '=', 'student_class_histories.study_group_id')
            ->join('grade_levels', 'grade_levels.id', '=', 'study_groups.grade_level_id')
            ->where('student_class_histories.is_active', true)
            ->when($activeAcademicYear, fn($q) => $q->where('student_class_histories.academic_year_id', $activeAcademicYear->id))
            ->when($schoolId, fn($q) => $q->where('study_groups.school_id', $schoolId))
            ->groupBy('grade_levels.id', 'grade_levels.name', 'grade_levels.level')
            ->orderByRaw('CAST(grade_levels.name AS INTEGER) ASC')
            ->get()
            ->map(function ($row) {
                $row->filled_pct = $row->total_capacity > 0
                    ? min(100, round($row->student_count / $row->total_capacity * 100))
                    : 0;
                $row->over_capacity = $row->student_count > $row->total_capacity;
                return $row;
            });
    }

    private function getRombelCapacity($schoolId, $activeAcademicYear)
    {
        // Ambil dulu ID rombel yang aktif
        $sgIds = StudyGroup::where('is_active', true)
            ->when($activeAcademicYear, fn($q) => $q->where('academic_year_id', $activeAcademicYear->id))
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->pluck('id');

        // Hitung student count per rombel dalam 1 query
        $counts = StudentClassHistory::whereIn('study_group_id', $sgIds)
            ->where('is_active', true)
            ->when($activeAcademicYear, fn($q) => $q->where('academic_year_id', $activeAcademicYear->id))
            ->groupBy('study_group_id')
            ->selectRaw('study_group_id, COUNT(*) as student_count')
            ->pluck('student_count', 'study_group_id');

        // Ambil data rombel + relasi
        $studyGroups = StudyGroup::with(['gradeLevel', 'homeroomTeacher'])
            ->whereIn('id', $sgIds)
            ->orderBy('name')
            ->get();

        return $studyGroups->map(fn($sg) => [
            'id'            => $sg->id,
            'full_name'     => $sg->full_name,
            'capacity'      => $sg->capacity,
            'student_count' => (int) ($counts[$sg->id] ?? 0),
            'filled_pct'    => $sg->capacity > 0 ? min(100, round(($counts[$sg->id] ?? 0) / $sg->capacity * 100)) : 0,
            'sisa'          => max(0, $sg->capacity - ($counts[$sg->id] ?? 0)),
            'is_over'       => ($counts[$sg->id] ?? 0) > $sg->capacity,
            'over_by'       => max(0, ($counts[$sg->id] ?? 0) - $sg->capacity),
            'room'          => $sg->room,
            'teacher'       => $sg->homeroomTeacher?->name,
        ]);
    }

    private function getGtkStats($schoolId)
    {
        $query = User::query();
        if ($schoolId) {
            $query->whereHas('employment', fn($q) => $q->where('school_id', $schoolId));
        }

        $total = (clone $query)
            ->whereHas('employment', fn($q) => $q->where('is_active', true))
            ->count();

        $guru = (clone $query)
            ->whereHas('employment', fn($q) => $q->where('school_id', $schoolId)->where('jenis_gtk', 'guru'))
            ->count();

        $tendik = (clone $query)
            ->whereHas('employment', fn($q) => $q->where('school_id', $schoolId)->where('jenis_gtk', 'tendik'))
            ->count();

        return ['total' => $total, 'guru' => $guru, 'tendik' => $tendik];
    }

    private function getRecentMutations($activeAcademicYear)
    {
        $mutationsIn = StudentMutationIn::with('student:id,name,nisn')
            ->whereIn('status', ['approved', 'submitted'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $mutationsOut = StudentMutationOut::with('student:id,name,nisn')
            ->whereIn('status', ['approved', 'submitted'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return ['in' => $mutationsIn, 'out' => $mutationsOut];
    }

    private function getTopViolations()
    {
        // Top pelanggaran berdasarkan jumlah insiden bulan ini
        return ViolationPoint::selectRaw('violation_type, COUNT(*) as total')
            ->whereMonth('violation_date', now()->month)
            ->whereYear('violation_date', now()->year)
            ->groupBy('violation_type')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(5)
            ->get();
    }

    private function getRecentAchievements()
    {
        return StudentAchievement::with('student:id,name')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    private function getQuickActions($userId, $schoolId)
    {
        return [
            ['label' => 'Data Santri',    'icon' => 'ri-user-heart-line',     'route' => 'user.students.index',        'color' => 'primary'],
            ['label' => 'Rombel',         'icon' => 'ri-group-line',           'route' => 'user.study-groups.index',   'color' => 'info'],
            ['label' => 'Mutasi Masuk',   'icon' => 'ri-login-box-line',      'route' => 'user.mutations-in.index',   'color' => 'success'],
            ['label' => 'Mutasi Keluar',  'icon' => 'ri-logout-box-line',     'route' => 'user.mutations-out.index',  'color' => 'warning'],
            ['label' => 'GTK',            'icon' => 'ri-contacts-book-2-line', 'route' => 'user.gtk.index',            'color' => 'secondary'],
            ['label' => 'Import Excel',   'icon' => 'ri-file-upload-line',    'route' => 'user.students.import-form',  'color' => 'danger'],
        ];
    }

    private function getRombelList($schoolId, $activeAcademicYear)
    {
        return StudyGroup::with(['gradeLevel', 'homeroomTeacher'])
            ->where('is_active', true)
            ->when($activeAcademicYear, fn($q) => $q->where('academic_year_id', $activeAcademicYear->id))
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn($sg) => [
                'id'        => $sg->id,
                'full_name' => $sg->full_name,
                'room'      => $sg->room,
                'teacher'   => $sg->homeroomTeacher?->name,
            ]);
    }
}