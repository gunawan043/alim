<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Asset;
use App\Models\AssetLoan;
use App\Models\AssetMaintenanceSchedule;
use App\Models\AssetRoom;
use Illuminate\Http\Request;

class KoordLaboratoriumDashboardController extends SatuanPendidikanDashboardController
{
    public function index(Request $request)
    {
        extract($this->resolveContext($request));

        $today = now()->toDateString();

        // Lab rooms — room_type == 'lab'
        $labAktif = AssetRoom::where('room_type', 'lab')
            ->where('is_active', true)
            ->count();

        // Aset rusak ringan — menggunakan Asset model
        $asetRusakRingan = Asset::where('condition', 'rusak_ringan')
            ->where('is_active', true)
            ->count();

        $peminjamanHariIni = AssetLoan::whereDate('loan_date', $today)
            ->count();

        $jadwalSore = AssetRoom::where('room_type', 'lab')
            ->where('is_active', true)
            ->whereHas('bookings', fn ($q) => $q->where('date', $today)
                ->where('start_time', '>=', '14:00'))
            ->count();

        $roomStatus = AssetRoom::where('is_active', true)
            ->selectRaw('condition, COUNT(*) as cnt')
            ->groupBy('condition')
            ->get();

        $maintenanceUrgent = AssetMaintenanceSchedule::where('is_active', true)
            ->whereDate('next_maintenance_date', '<=', now()->addDays(7))
            ->with(['asset'])
            ->limit(5)
            ->get();

        return view('dashboard-pendidikan.tugas-tambahan.koordinator-lab', compact(
            'user', 'labAktif', 'asetRusakRingan',
            'peminjamanHariIni', 'jadwalSore',
            'roomStatus', 'maintenanceUrgent'
        ));
    }
}
