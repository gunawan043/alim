<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\Asset;
use App\Models\AssetBuilding;
use App\Models\AssetLoan;
use App\Models\AssetMaintenanceSchedule;
use App\Models\AssetRoom;
use App\Models\ProcurementRequest;
use App\Models\RoomBooking;
use App\Models\School;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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

        // Versioned cache key: when sarpras data mutates, bumping the global version invalidates all dashboard caches
        $sarprasVersion = Cache::rememberForever('sarpras_dashboard_version', fn () => 1);
        $statsCacheKey = "sarpras_dashboard_stats:v{$sarprasVersion}:".($filterSchoolId ?? 'all').':'.($viewAll ? '1' : '0');
        $stats = Cache::remember($statsCacheKey, now()->addMinutes(5), function () use ($filterSchoolId) {
            return $this->computeDashboardStats($filterSchoolId);
        });

        extract($stats);

        // ===================================================================
        // JADWAL PEMELIHARAAN (cached separately, used for upcoming list)
        // ===================================================================
        $jadwalMaintenance = Cache::remember(
            'sarpras_dashboard_maintenance:v'.$sarprasVersion.($filterSchoolId ?? 'all'),
            now()->addMinutes(5),
            fn () => AssetMaintenanceSchedule::query()
                ->when($filterSchoolId, fn ($q) => $q->where('school_id', $filterSchoolId))
                ->where('is_active', true)
                ->whereDate('next_maintenance_date', '<=', Carbon::today()->addDays(7))
                ->orderBy('next_maintenance_date')
                ->with(['asset', 'room', 'building'])
                ->limit(10)
                ->get()
        );

        // ===================================================================
        // AKTIVITAS TERAKHIR (audit log-style)
        // ===================================================================
        $recentAssets = Asset::query()
            ->when($filterSchoolId, fn ($q) => $q->where('school_id', $filterSchoolId))
            ->where('is_active', true)
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        $recentLoans = AssetLoan::query()
            ->when($filterSchoolId, fn ($q) => $q->where('school_id', $filterSchoolId))
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

    private function computeDashboardStats(?string $filterSchoolId): array
    {
        $gedungQuery = AssetBuilding::query()
            ->when($filterSchoolId, fn ($q) => $q->where('school_id', $filterSchoolId))
            ->where('is_active', true);

        $totalGedung = (clone $gedungQuery)->count();
        $gedungBaik = (clone $gedungQuery)->where('structure_condition', 'baik')->count();
        $gedungRusakRingan = (clone $gedungQuery)->where('structure_condition', 'rusak_ringan')->count();
        $gedungRusakBerat = (clone $gedungQuery)->whereIn('structure_condition', ['rusak_sedang', 'rusak_berat'])->count();

        $ruanganQuery = AssetRoom::query()
            ->when($filterSchoolId, fn ($q) => $q->where('school_id', $filterSchoolId))
            ->where('is_active', true);

        $totalRuangan = (clone $ruanganQuery)->count();
        $ruanganBaik = (clone $ruanganQuery)->where('condition', 'baik')->count();
        $ruanganRusak = (clone $ruanganQuery)->whereIn('condition', ['rusak_ringan', 'rusak_sedang', 'rusak_berat'])->count();

        $asetQuery = Asset::query()
            ->when($filterSchoolId, fn ($q) => $q->where('school_id', $filterSchoolId))
            ->where('is_active', true);

        $totalAset = (clone $asetQuery)->count();
        $asetBaik = (clone $asetQuery)->where('condition', 'baik')->count();
        $asetRusakRingan = (clone $asetQuery)->where('condition', 'rusak_ringan')->count();
        $asetRusakSedang = (clone $asetQuery)->where('condition', 'rusak_sedang')->count();
        $asetRusakBerat = (clone $asetQuery)->where('condition', 'rusak_berat')->count();
        $asetHilang = (clone $asetQuery)->where('condition', 'hilang')->count();

        $nilaiAset = (clone $asetQuery)->sum('current_value') ?? (clone $asetQuery)->sum('acquisition_price') ?? 0;

        $asetTersedia = (clone $asetQuery)->where('status', 'tersedia')->count();
        $asetDipinjam = (clone $asetQuery)->where('status', 'dipinjam')->count();
        $asetPerbaikan = (clone $asetQuery)->where('status', 'dalam_perbaikan')->count();

        $pinjamanQuery = AssetLoan::query()
            ->when($filterSchoolId, fn ($q) => $q->where('school_id', $filterSchoolId));

        $pinjamanAktif = (clone $pinjamanQuery)->where('status', 'dipinjam')->count();
        $pinjamanPending = (clone $pinjamanQuery)->where('status', 'pending')->count();
        $pinjamanTerlambat = (clone $pinjamanQuery)
            ->where('status', 'dipinjam')
            ->whereDate('expected_return_date', '<', Carbon::today())
            ->count();

        $maintenanceQuery = AssetMaintenanceSchedule::query()
            ->when($filterSchoolId, fn ($q) => $q->where('school_id', $filterSchoolId))
            ->where('is_active', true);

        $overdueMaintenance = (clone $maintenanceQuery)
            ->whereDate('next_maintenance_date', '<', Carbon::today())
            ->count();

        $bookingQuery = RoomBooking::query()
            ->when($filterSchoolId, fn ($q) => $q->whereHas('room', fn ($q2) => $q2->where('school_id', $filterSchoolId)));

        $bookingHariIni = (clone $bookingQuery)
            ->whereDate('booking_date', Carbon::today())
            ->whereIn('status', ['approved', 'dipinjam'])
            ->count();

        $pengadaanQuery = ProcurementRequest::query()
            ->when($filterSchoolId, fn ($q) => $q->where('school_id', $filterSchoolId));

        $pengadaanPending = (clone $pengadaanQuery)->where('status', 'pending')->count();
        $pengadaanApproved = (clone $pengadaanQuery)->where('status', 'approved')->count();

        $chartKondisi = [
            'labels' => ['Baik', 'Rusak Ringan', 'Rusak Sedang', 'Rusak Berat', 'Hilang'],
            'data' => [$asetBaik, $asetRusakRingan, $asetRusakSedang, $asetRusakBerat, $asetHilang],
            'colors' => ['#198754', '#ffc107', '#fd7e14', '#dc3545', '#6c757d'],
        ];

        $chartStatus = [
            'labels' => ['Tersedia', 'Dipinjam', 'Dalam Perbaikan'],
            'data' => [$asetTersedia, $asetDipinjam, $asetPerbaikan],
            'colors' => ['#198754', '#0d6efd', '#fd7e14'],
        ];

        return compact(
            'totalGedung', 'gedungBaik', 'gedungRusakRingan', 'gedungRusakBerat',
            'totalRuangan', 'ruanganBaik', 'ruanganRusak',
            'totalAset', 'asetBaik', 'asetRusakRingan', 'asetRusakSedang', 'asetRusakBerat', 'asetHilang',
            'nilaiAset',
            'asetTersedia', 'asetDipinjam', 'asetPerbaikan',
            'pinjamanAktif', 'pinjamanPending', 'pinjamanTerlambat',
            'overdueMaintenance', 'bookingHariIni',
            'pengadaanPending', 'pengadaanApproved',
            'chartKondisi', 'chartStatus',
        );
    }
}
