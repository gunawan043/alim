<?php

namespace App\Http\Controllers\Boarding;

use App\Http\Controllers\Controller;
use App\Models\Dormitory;
use App\Models\DormitoryPermit;
use App\Models\DormitoryReturn;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BoardingEducationDashboardController extends Controller
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

        $returnsToday = DormitoryReturn::whereDate('expected_return_at', $today->toDateString())->count();
        $returnPending = DormitoryReturn::where('status', 'pending')->count();

        $recentPermits = DormitoryPermit::with(['student', 'dormitory'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('dashboards.boarding-education', [
            'today' => $today,
            'activeAsrama' => $activeAsrama,
            'totalSantri' => $totalSantri,
            'permitPending' => $permitPending,
            'permitsToday' => $permitsToday,
            'overduePermits' => $overduePermits,
            'returnsToday' => $returnsToday,
            'returnPending' => $returnPending,
            'recentPermits' => $recentPermits,
        ]);
    }
}
