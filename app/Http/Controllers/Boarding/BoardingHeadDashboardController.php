<?php

namespace App\Http\Controllers\Boarding;

use App\Http\Controllers\Controller;
use App\Models\Dormitory;
use App\Models\DormitoryPermit;
use App\Models\DormitoryViolation;
use App\Models\DormitoryVisitLog;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BoardingHeadDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $today = Carbon::today();

        $activeAsrama = Dormitory::where('is_active', true)->get();
        $totalSantri = Student::whereHas('activeDormitoryResident')->where('status', 'active')->count();

        $pendingApprovals = [
            'permits' => DormitoryPermit::where('status', 'pending')->count(),
            'visits' => DormitoryVisitLog::where('status', 'pending')->count(),
            'room_moves' => \App\Models\DormitoryRoomMove::where('status', 'pending')->count(),
        ];

        $pendingTotal = array_sum($pendingApprovals);

        $overduePermits = DormitoryPermit::where('status', 'approved')
            ->whereNotNull('expected_return_date')
            ->where('expected_return_date', '<', $today->toDateString())
            ->count();

        $violationsMonth = DormitoryViolation::whereBetween('violation_date', [
            $today->copy()->subMonth(),
            $today,
        ])->count();

        $recentPermits = DormitoryPermit::with(['student', 'dormitory'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('dashboards.boarding-head', [
            'today' => $today,
            'activeAsrama' => $activeAsrama,
            'totalSantri' => $totalSantri,
            'pendingApprovals' => $pendingApprovals,
            'pendingTotal' => $pendingTotal,
            'overduePermits' => $overduePermits,
            'violationsMonth' => $violationsMonth,
            'recentPermits' => $recentPermits,
        ]);
    }
}
