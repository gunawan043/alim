<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\DormitoryPermit;
use App\Models\Security\SecurityGuestLog;
use App\Models\Security\SecurityIncident;
use App\Models\Security\SecurityPatrolSchedule;
use App\Models\Security\SecurityTeam;
use App\Models\Security\SecurityVehicleLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SecurityDashboardController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $today = today();

        $guestInside = SecurityGuestLog::query()->inside()->count();
        $guestToday = SecurityGuestLog::query()->whereDate('check_in_at', $today)->count();
        $guestOutToday = SecurityGuestLog::query()
            ->whereDate('check_out_at', $today)
            ->count();

        $vehicleInside = SecurityVehicleLog::query()->inside()->count();
        $vehicleToday = SecurityVehicleLog::query()->whereDate('check_in_at', $today)->count();

        $studentsOutside = DormitoryPermit::query()
            ->where('status', DormitoryPermit::STATUS_PICKED_UP)
            ->count();

        $studentsLate = DormitoryPermit::query()
            ->where(function ($query) {
                $query->where('status', DormitoryPermit::STATUS_OVERDUE)
                    ->orWhere(function ($inner) {
                        $inner->where('status', DormitoryPermit::STATUS_PICKED_UP)
                            ->where('expected_return_datetime', '<', now());
                    });
            })
            ->count();

        $openIncidents = SecurityIncident::query()->open()->count();
        $criticalIncidents = SecurityIncident::query()
            ->open()
            ->whereIn('severity', ['berat', 'kritis'])
            ->count();

        $patrolToday = SecurityPatrolSchedule::query()->today()->count();
        $patrolDone = SecurityPatrolSchedule::query()
            ->today()
            ->where('status', SecurityPatrolSchedule::STATUS_DONE)
            ->count();

        // Grafik tamu 7 hari terakhir.
        $guestTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $guestTrend[] = [
                'label' => $date->translatedFormat('d M'),
                'value' => SecurityGuestLog::query()->whereDate('check_in_at', $date)->count(),
            ];
        }

        $maxTrend = max(1, collect($guestTrend)->max('value'));

        $recentIncidents = SecurityIncident::query()
            ->with(['type', 'post', 'reporter'])
            ->latest('occurred_at')
            ->limit(5)
            ->get();

        $todayPatrols = SecurityPatrolSchedule::query()
            ->with(['team', 'shift', 'post', 'logs'])
            ->today()
            ->orderBy('code')
            ->get();

        $guestInsideList = SecurityGuestLog::query()
            ->with(['post', 'recorder'])
            ->inside()
            ->latest('check_in_at')
            ->limit(6)
            ->get();

        $teamCount = SecurityTeam::query()->where('is_active', true)->count();

        // Rekap 7 hari kunjungan tamu untuk ringkasan tabel.
        $weeklySummary = DB::table('security_guest_logs')
            ->selectRaw('DATE(check_in_at) as day, COUNT(*) as total')
            ->where('check_in_at', '>=', $today->copy()->subDays(6)->startOfDay())
            ->groupBy('day')
            ->orderByDesc('day')
            ->get();

        return view('security.dashboard.index', compact(
            'userId',
            'guestInside',
            'guestToday',
            'guestOutToday',
            'vehicleInside',
            'vehicleToday',
            'studentsOutside',
            'studentsLate',
            'openIncidents',
            'criticalIncidents',
            'patrolToday',
            'patrolDone',
            'guestTrend',
            'maxTrend',
            'recentIncidents',
            'todayPatrols',
            'guestInsideList',
            'teamCount',
            'weeklySummary',
        ));
    }
}
