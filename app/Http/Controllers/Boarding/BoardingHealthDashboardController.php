<?php

namespace App\Http\Controllers\Boarding;

use App\Http\Controllers\Controller;
use App\Models\Dormitory;
use App\Models\DormitoryViolation;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BoardingHealthDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $today = Carbon::today();
        $schoolId = $request->attributes->get('schoolContextId');

        // Scope dormitories to current school context
        $activeAsrama = Dormitory::where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->get();

        // Scope students to current school and gender
        $totalSantri = Student::whereHas('activeDormitoryResident')
            ->where('status', 'active')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($request->attributes->get('schoolGender'), function ($q) use ($request) {
                $gender = $request->attributes->get('schoolGender');

                return $q->where('gender', $gender === 'putra' ? 'L' : 'P');
            })
            ->count();

        // Health-related violations (izin sakit, pelanggaran kesehatan)
        $healthViolations = DormitoryViolation::where(function ($q) {
            $q->where('description', 'like', '%sakit%')
                ->orWhere('description', 'like', '%kes%')
                ->orWhere('description', 'like', '%medis%');
        })->orWhereRaw('LOWER(description) LIKE ?', ['%medicine%'])
            ->when($schoolId, fn ($q) => $q->whereHas('student', fn ($sq) => $sq->where('school_id', $schoolId)))
            ->count();

        $violationsMonth = DormitoryViolation::whereBetween('violation_date', [
            $today->copy()->subMonth(),
            $today,
        ])
            ->when($schoolId, fn ($q) => $q->whereHas('student', fn ($sq) => $sq->where('school_id', $schoolId)))
            ->count();

        return view('dashboards.boarding-health', [
            'today' => $today,
            'activeAsrama' => $activeAsrama,
            'totalSantri' => $totalSantri,
            'healthViolations' => $healthViolations,
            'violationsMonth' => $violationsMonth,
        ]);
    }
}
