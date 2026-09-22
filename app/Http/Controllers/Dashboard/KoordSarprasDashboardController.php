<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Asset;
use App\Models\AssetBuilding;
use App\Models\AssetMaintenanceSchedule;
use App\Models\ProcurementRequest;
use Illuminate\Http\Request;

class KoordSarprasDashboardController extends SatuanPendidikanDashboardController
{
    public function index(Request $request)
    {
        extract($this->resolveContext($request));

        // Gedung baik — structure_condition == 'baik'
        $gedungBaik = AssetBuilding::where('structure_condition', 'baik')
            ->where('is_active', true)
            ->count();

        // Aset rusak berat
        $asetRusakBerat = Asset::where('condition', 'rusak_berat')
            ->where('is_active', true)
            ->count();

        $maintenanceJangkaDekat = AssetMaintenanceSchedule::where('is_active', true)
            ->whereDate('next_maintenance_date', '>=', now())
            ->whereDate('next_maintenance_date', '<=', now()->addDays(7))
            ->count();

        $pengadaanPending = ProcurementRequest::where('status', 'pending')
            ->count();

        $gedungStatus = AssetBuilding::where('is_active', true)
            ->selectRaw('structure_condition, COUNT(*) as cnt')
            ->groupBy('structure_condition')
            ->get();

        $jadwalMaintenance = AssetMaintenanceSchedule::where('is_active', true)
            ->whereDate('next_maintenance_date', '>=', now())
            ->orderBy('next_maintenance_date')
            ->limit(5)
            ->with(['asset', 'building'])
            ->get();

        $pengadaanList = ProcurementRequest::where('status', 'pending')
            ->with(['requester'])
            ->limit(5)
            ->get();

        return view('dashboard-pendidikan.tugas-tambahan.koordinator-sarpras', compact(
            'user', 'gedungBaik', 'asetRusakBerat',
            'maintenanceJangkaDekat', 'pengadaanPending',
            'gedungStatus', 'jadwalMaintenance', 'pengadaanList'
        ));
    }
}
