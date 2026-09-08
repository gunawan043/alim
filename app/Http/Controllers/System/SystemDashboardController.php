<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Dormitory;
use App\Models\DormitoryPermit;
use App\Models\DormitoryRoom;
use App\Models\DormitoryViolation;
use App\Models\DormitoryVisitLog;
use App\Models\GtkEmployment;
use App\Models\Student;
use App\Models\StudentMutationIn;
use App\Models\StudyGroup;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Http\Request;

class SystemDashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $stats = [
            'users_total' => User::count(),
            'users_active' => User::where('is_active', true)->count(),
            'users_inactive' => User::where('is_active', false)->count(),
            'system_admins' => User::where('is_system_admin', true)->count(),
            'roles_total' => \DB::table('roles')->count(),
            'permissions_total' => \DB::table('permissions')->count(),
            'schools_total' => \DB::table('schools')->count(),
            'schools_active' => \DB::table('schools')->where('is_active', true)->count(),
            'dormitories_total' => Dormitory::where('is_active', true)->count(),
            'students_total' => Student::count(),
            'students_active' => Student::where('status', 'active')->count(),
            'students_graduate' => Student::where('status', 'graduate')->count(),
            'students_inactive' => Student::where('status', 'inactive')->count(),
            'students_dropped' => Student::where('status', 'dropped')->count(),
            'study_groups_total' => StudyGroup::count(),
            'teaching_assignments' => TeachingAssignment::where('status', 'active')->count(),
            'permits_pending' => DormitoryPermit::where('status', 'pending')->count(),
            'permits_approved' => DormitoryPermit::where('status', 'approved')->count(),
            'permits_rejected' => DormitoryPermit::where('status', 'rejected')->count(),
            'violations_heavy' => DormitoryViolation::where('violation_category', 'berat')->count(),
            'violations_light' => DormitoryViolation::where('violation_category', 'ringan')->count(),
            'visits_pending' => DormitoryVisitLog::where('status', 'pending')->count(),
            'visits_today' => DormitoryVisitLog::whereDate('expected_arrival_datetime', now()->toDateString())->count(),
            'teachers_total' => GtkEmployment::count(),
            'gtk_active' => GtkEmployment::where('status_kepegawaian', '!=', 'Magang')->count(),
        ];

        $recentVisits = DormitoryVisitLog::with(['student', 'dormitory'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $recentPermits = DormitoryPermit::with(['student', 'dormitory'])
            ->whereIn('status', ['pending', 'approved', 'rejected'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // ── Chart Data ────────────────────────────────────────────────

        // Monthly student arrivals (Jan–Dec current year)
        $studentArrivalLabels = [];
        $studentArrivalData = [];
        $year = now()->year;
        for ($m = 1; $m <= 12; $m++) {
            $start = $year.'-'.sprintf('%02d', $m).'-01';
            $end = $m < 12
                ? $year.'-'.sprintf('%02d', $m + 1).'-01'
                : ($year + 1).'-01-01';
            $studentArrivalLabels[] = date('M', strtotime($start));
            $studentArrivalData[] = StudentMutationIn::whereBetween('created_at', [$start, $end])->count();
        }

        // Monthly violations by category (last 6 months)
        $incidentLabels = [];
        $incidentHeavy = [];
        $incidentLight = [];
        for ($i = 5; $i >= 0; $i--) {
            $ts = now()->subMonths($i);
            $start = $ts->copy()->startOfMonth()->format('Y-m-d');
            $end = $ts->copy()->endOfMonth()->format('Y-m-d');
            $incidentLabels[] = $ts->format('M');
            $incidentHeavy[] = DormitoryViolation::whereBetween('created_at', [$start, $end])
                ->where('violation_category', 'berat')->count();
            $incidentLight[] = DormitoryViolation::whereBetween('created_at', [$start, $end])
                ->where('violation_category', 'ringan')->count();
        }

        // Room occupancy per dormitory
        $dormitoryIds = Dormitory::where('is_active', true)->pluck('id');
        $occupancyLabels = [];
        $occupancyCurrent = [];
        $occupancyCap = [];
        foreach ($dormitoryIds as $dormId) {
            $dorm = Dormitory::find($dormId);
            $occupancyLabels[] = $dorm ? $dorm->name : "Asrama {$dormId}";
            $cap = DormitoryRoom::where('dormitory_id', $dormId)
                ->where('is_active', true)
                ->sum('capacity');
            $occ = DormitoryRoom::where('dormitory_id', $dormId)
                ->where('is_active', true)
                ->get()
                ->sum(function ($room) {
                    return $room->residents()->count();
                });
            $occupancyCurrent[] = $occ;
            $occupancyCap[] = $cap ?: 1;
        }

        // Weekly permits per day of week (Mon–Sun)
        $permitDayLabels = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
        $permitDayData = [0, 0, 0, 0, 0, 0, 0];
        for ($d = 0; $d < 7; $d++) {
            $dayTs = now()->subDays($d)->copy()->startOfDay();
            $next = $dayTs->copy()->addDay();
            $slot = (7 - (int) $dayTs->dayOfWeek) % 7; // Sun→0, Mon→1 … Sat→6
            $permitDayData[$slot] += DormitoryPermit::whereBetween('created_at', [$dayTs, $next])->count();
        }

        // Recent violations table (last 5)
        $recentIncidents = DormitoryViolation::with(['student', 'room'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Monthly teacher additions (Jan–Dec current year)
        $teacherLabels = [];
        $teacherData = [];
        for ($m = 1; $m <= 12; $m++) {
            $start = $year.'-'.sprintf('%02d', $m).'-01';
            $end = $m < 12
                ? $year.'-'.sprintf('%02d', $m + 1).'-01'
                : ($year + 1).'-01-01';
            $teacherLabels[] = date('M', strtotime($start));
            $teacherData[] = GtkEmployment::whereBetween('created_at', [$start, $end])->count();
        }

        // Student status distribution (for donut chart)
        $studentStatusLabels = ['Aktif', 'Lulus', 'Nonaktif', 'Dropout'];
        $studentStatusData = [
            $stats['students_active'],
            $stats['students_graduate'],
            $stats['students_inactive'],
            $stats['students_dropped'],
        ];

        return view('system.dashboard', compact(
            'stats', 'recentVisits', 'recentPermits', 'recentIncidents',
            'studentArrivalLabels', 'studentArrivalData',
            'incidentLabels', 'incidentHeavy', 'incidentLight',
            'occupancyLabels', 'occupancyCurrent', 'occupancyCap',
            'permitDayLabels', 'permitDayData',
            'teacherLabels', 'teacherData',
            'studentStatusLabels', 'studentStatusData',
        ));
    }

    public function features()
    {
        return view('system.features');
    }

    public function monitoring()
    {
        return view('system.monitoring');
    }

    public function maintenance()
    {
        return view('system.maintenance');
    }

    public function config()
    {
        return view('system.config');
    }

    public function devtools()
    {
        return view('system.devtools');
    }
}
