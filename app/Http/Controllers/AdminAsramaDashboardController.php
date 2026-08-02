<?php

namespace App\Http\Controllers;

use App\Models\Dormitory;
use App\Models\DormitoryPermit;
use App\Models\DormitoryVisitLog;
use App\Models\Student;

class AdminAsramaDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Total asrama
        $totalDormitories = Dormitory::where('is_active', true)->count();
        $totalRooms = \App\Models\DormitoryRoom::whereHas('dormitory', fn ($q) => $q->where('is_active', true))->count();

        // Total santri
        $totalSantri = Student::whereHas('activeDormitoryResident')->where('status', 'active')->count();

        // Occupancy rate
        $occupiedRooms = \App\Models\DormitoryRoom::whereHas('dormitory', fn ($q) => $q->where('is_active', true))
            // ->where('room_status', 'occupied')
            ->count();

        // Permits pending
        $permitPending = DormitoryPermit::where('status', 'pending')->count();
        $permitToday = DormitoryPermit::whereDate('created_at', now()->toDateString())->count();

        // Visit pending
        $visitPending = DormitoryVisitLog::where('status', 'pending')->count();

        // Recent permits (last 5)
        $recentPermits = DormitoryPermit::with(['student'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin-asrama.dashboard', compact(
            'user',
            'totalDormitories',
            'totalRooms',
            'totalSantri',
            'occupiedRooms',
            'permitPending',
            'permitToday',
            'visitPending',
            'recentPermits'
        ));
    }
}
