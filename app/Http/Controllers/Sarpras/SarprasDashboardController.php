<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\Asset;
use App\Models\AssetBuilding;
use App\Models\AssetRoom;
use App\Models\AssetLoan;
use App\Models\AssetMaintenanceSchedule;
use App\Models\AssetMaintenanceLog;
use App\Models\RoomBooking;
use App\Models\ProcurementRequest;
use App\Models\School;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SarprasDashboardController extends SarprasBaseController
{
    public function __construct()
    {
        view()->share('userId', request()->route('userId') ?? (auth()->check() ? auth()->id() : null));
    }


    public function index(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $viewAll = $this->canViewAll($request);

        // Filter untuk query
        $filterSchoolId = $viewAll ? $request->get('school_id') : $schoolId;

        // ===================================================================
        // STATISTIK GEDUNG
        // ===================================================================
        $gedungQuery = AssetBuilding::query();
        if ($filterSchoolId) {
            $gedungQuery->where('school_id', $filterSchoolId);
        }
        $gedungQuery->where('is_active', true);

        $totalGedung = (clone $gedungQuery)->count();
        $gedungBaik = (clone $gedungQuery)->where('structure_condition', 'baik')->count();
        $gedungRusakRingan = (clone $gedungQuery)->where('structure_condition', 'rusak_ringan')->count();
        $gedungRusakBerat = (clone $gedungQuery)->whereIn('structure_condition', ['rusak_sedang', 'rusak_berat'])->count();

        // ===================================================================
        // STATISTIK RUANGAN
        // ===================================================================
        $ruanganQuery = AssetRoom::query();
        if ($filterSchoolId) {
            $ruanganQuery->where('school_id', $filterSchoolId);
        }
        $ruanganQuery->where('is_active', true);

        $totalRuangan = (clone $ruanganQuery)->count();
        $ruanganBaik = (clone $ruanganQuery)->where('condition', 'baik')->count();
        $ruanganRusak = (clone $ruanganQuery)->whereIn('condition', ['rusak_ringan', 'rusak_sedang', 'rusak_berat'])->count();

        // ===================================================================
        // STATISTIK ASET
        // ===================================================================
        $asetQuery = Asset::query();
        if ($filterSchoolId) {
            $asetQuery->where('school_id', $filterSchoolId);
        }
        $asetQuery->where('is_active', true);

        $totalAset = (clone $asetQuery)->count();
        $asetBaik = (clone $asetQuery)->where('condition', 'baik')->count();
        $asetRusakRingan = (clone $asetQuery)->where('condition', 'rusak_ringan')->count();
        $asetRusakSedang = (clone $asetQuery)->where('condition', 'rusak_sedang')->count();
        $asetRusakBerat = (clone $asetQuery)->where('condition', 'rusak_berat')->count();
        $asetHilang = (clone $asetQuery)->where('condition', 'hilang')->count();

        $nilaiAset = (clone $asetQuery)->sum('current_value') ?? (clone $asetQuery)->sum('acquisition_price') ?? 0;

        // Aset per status
        $asetTersedia = (clone $asetQuery)->where('status', 'tersedia')->count();
        $asetDipinjam = (clone $asetQuery)->where('status', 'dipinjam')->count();
        $asetPerbaikan = (clone $asetQuery)->where('status', 'dalam_perbaikan')->count();

        // ===================================================================
        // PEMINJAMAN AKTIF
        // ===================================================================
        $pinjamanQuery = AssetLoan::query();
        if ($filterSchoolId) {
            $pinjamanQuery->where('school_id', $filterSchoolId);
        }

        $pinjamanAktif = (clone $pinjamanQuery)->where('status', 'dipinjam')->count();
        $pinjamanPending = (clone $pinjamanQuery)->where('status', 'pending')->count();
        $pinjamanTerlambat = (clone $pinjamanQuery)
            ->where('status', 'dipinjam')
            ->whereDate('expected_return_date', '<', Carbon::today())
            ->count();

        // ===================================================================
        // JADWAL PEMELIHARAAN
        // ===================================================================
        $maintenanceQuery = AssetMaintenanceSchedule::query();
        if ($filterSchoolId) {
            $maintenanceQuery->where('school_id', $filterSchoolId);
        }
        $maintenanceQuery->where('is_active', true);

        $jadwalMaintenance = (clone $maintenanceQuery)
            ->whereDate('next_maintenance_date', '<=', Carbon::today()->addDays(7))
            ->orderBy('next_maintenance_date')
            ->with(['asset', 'room', 'building'])
            ->limit(10)
            ->get();

        $overdueMaintenance = (clone $maintenanceQuery)
            ->whereDate('next_maintenance_date', '<', Carbon::today())
            ->count();

        // ===================================================================
        // BOOKING RUANGAN - konflik hari ini
        // ===================================================================
        $bookingQuery = RoomBooking::query();
        if ($filterSchoolId) {
            $bookingQuery->whereHas('room', fn($q) => $q->where('school_id', $filterSchoolId));
        }

        $bookingHariIni = (clone $bookingQuery)
            ->whereDate('booking_date', Carbon::today())
            ->whereIn('status', ['approved', 'dipinjam'])
            ->with('room', 'user')
            ->count();

        // ===================================================================
        // PENGADAAN
        // ===================================================================
        $pengadaanQuery = ProcurementRequest::query();
        if ($filterSchoolId) {
            $pengadaanQuery->where('school_id', $filterSchoolId);
        }

        $pengadaanPending = (clone $pengadaanQuery)->where('status', 'pending')->count();
        $pengadaanApproved = (clone $pengadaanQuery)->where('status', 'approved')->count();

        // ===================================================================
        // DATA CHART - Kondisi Aset
        // ===================================================================
        $chartKondisi = [
            'labels' => ['Baik', 'Rusak Ringan', 'Rusak Sedang', 'Rusak Berat', 'Hilang'],
            'data' => [$asetBaik, $asetRusakRingan, $asetRusakSedang, $asetRusakBerat, $asetHilang],
            'colors' => ['#198754', '#ffc107', '#fd7e14', '#dc3545', '#6c757d'],
        ];

        // ===================================================================
        // DATA CHART - Status Aset
        // ===================================================================
        $chartStatus = [
            'labels' => ['Tersedia', 'Dipinjam', 'Dalam Perbaikan'],
            'data' => [$asetTersedia, $asetDipinjam, $asetPerbaikan],
            'colors' => ['#198754', '#0d6efd', '#fd7e14'],
        ];

        // ===================================================================
        // AKTIVITAS TERAKHIR (audit log-style)
        // ===================================================================
        $recentAssets = Asset::query()
            ->when($filterSchoolId, fn($q) => $q->where('school_id', $filterSchoolId))
            ->where('is_active', true)
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        $recentLoans = AssetLoan::query()
            ->when($filterSchoolId, fn($q) => $q->where('school_id', $filterSchoolId))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->with('asset', 'borrower')
            ->get();

        // Schools untuk filter (jika user bisa view all)
        $schools = $viewAll ? School::orderBy('name')->get() : collect();

        return view('sarpras.dashboard.index', compact(
            'totalGedung', 'gedungBaik', 'gedungRusakRingan', 'gedungRusakBerat',
            'totalRuangan', 'ruanganBaik', 'ruanganRusak',
            'totalAset', 'asetBaik', 'asetRusakRingan', 'asetRusakSedang', 'asetRusakBerat', 'asetHilang',
            'nilaiAset',
            'asetTersedia', 'asetDipinjam', 'asetPerbaikan',
            'pinjamanAktif', 'pinjamanPending', 'pinjamanTerlambat',
            'jadwalMaintenance', 'overdueMaintenance',
            'bookingHariIni',
            'pengadaanPending', 'pengadaanApproved',
            'chartKondisi', 'chartStatus',
            'recentAssets', 'recentLoans',
            'schools', 'viewAll', 'filterSchoolId'
        ));
    }
}
