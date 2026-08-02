<?php

namespace App\Http\Controllers;

use App\Models\Dormitory;
use App\Models\DormitoryPermit;
use App\Models\DormitoryVisitLog;
use App\Models\Student;

class WaliAsramaDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $userId = $user->id;

        // Total asrama
        $totalDormitories = Dormitory::where('is_active', true)->count();

        // Permohonan izin santri
        $pendingPermits = DormitoryPermit::where('status', 'pending')->count();
        $approvedPermits = DormitoryPermit::where('status', 'approved')->count();

        // Kunjungan santri
        $pendingVisits = DormitoryVisitLog::where('status', 'pending')->count();

        // Santri aktif di asrama (ini placeholder, actual data dari relasi wali)
        $totalSantri = Student::whereHas('activeDormitoryResident')->where('status', 'active')->count();

        return view('wali-asrama.dashboard', compact(
            'user',
            'totalDormitories',
            'pendingPermits',
            'approvedPermits',
            'pendingVisits',
            'totalSantri'
        ));
    }
}
