<?php

namespace App\Http\Controllers;

use App\Models\BoardingPolicy;
use App\Models\Dormitory;
use App\Models\DormitoryPermit;
use App\Models\DormitoryReturn;
use App\Models\DormitoryViolation;
use App\Models\DormitoryVisitLog;
use App\Models\Student;
use App\Models\ViolationPoint;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengasuhDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $today = Carbon::today();

        $activeAsrama = Dormitory::where('is_active', true)->get();
        $totalSantri = Student::whereHas('activeDormitoryResident')->where('status', 'active')->count();

        $permitPending = DormitoryPermit::where('status', 'pending')->count();
        $permitsToday = DormitoryPermit::whereDate('departure_date', $today->toDateString())->count();
        $overduePermits = DormitoryPermit::where('status', 'approved')
            ->whereNotNull('expected_return_date')
            ->where('expected_return_date', '<', $today->toDateString())
            ->count();

        $visitsToday = DormitoryVisitLog::whereDate('expected_arrival_datetime', $today)->count();
        $returnsToday = DormitoryReturn::whereDate('expected_return_at', $today)->count();

        $violationsWeek = DormitoryViolation::whereBetween('violation_date', [
            $today->copy()->subWeek(),
            $today,
        ])->count();

        $criticalPoints = ViolationPoint::where('points', '>=', 50)->count();

        $recentPermits = DormitoryPermit::with(['student', 'dormitory'])
            ->whereIn('status', ['pending', 'approved', 'overdue'])
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        $recentViolations = DormitoryViolation::with(['student'])
            ->orderBy('violation_date', 'desc')
            ->limit(5)
            ->get();

        $policies = BoardingPolicy::where('is_active', true)
            ->orderBy('effective_from', 'desc')->limit(5)->get();

        return view('dormitory.dashboard.pengasuh', [
            'activeAsrama' => $activeAsrama,
            'totalSantri' => $totalSantri,
            'permitPending' => $permitPending,
            'permitsToday' => $permitsToday,
            'overduePermits' => $overduePermits,
            'visitsToday' => $visitsToday,
            'returnsToday' => $returnsToday,
            'violationsWeek' => $violationsWeek,
            'criticalPoints' => $criticalPoints,
            'recentPermits' => $recentPermits,
            'recentViolations' => $recentViolations,
            'policies' => $policies,
            'today' => $today,
        ]);
    }
}
