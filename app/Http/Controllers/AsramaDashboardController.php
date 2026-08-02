<?php

namespace App\Http\Controllers;

use App\Models\Dormitory;
use App\Models\DormitoryViolation;
use App\Models\Student;

class AsramaDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Total asrama aktif
        $totalDormitories = Dormitory::where('is_active', true)->count();

        // Total santri di asrama
        $totalSantri = Student::whereHas('activeDormitoryResident')->where('status', 'active')->count();

        // Room moves pending
        $pendingRoomMoves = \App\Models\DormitoryRoomMove::where('status', 'pending')->count();

        // Violations this month
        $thisMonth = date('m');
        $thisYear = date('Y');
        $violationsThisMonth = DormitoryViolation::whereMonth('created_at', $thisMonth)
            ->whereYear('created_at', $thisYear)
            ->count();

        // Recent violations
        $recentViolations = DormitoryViolation::with('student')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('asrama.dashboard', compact(
            'user',
            'totalDormitories',
            'totalSantri',
            'pendingRoomMoves',
            'violationsThisMonth',
            'recentViolations'
        ));
    }
}
