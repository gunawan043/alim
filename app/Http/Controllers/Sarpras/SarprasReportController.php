<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\Asset;
use App\Models\AssetBuilding;
use App\Models\AssetRoom;
use App\Models\AssetLoan;
use App\Models\AssetMaintenanceSchedule;
use App\Models\AssetMaintenanceLog;
use App\Models\School;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PDF;

class SarprasReportController extends SarprasBaseController
{
    public function __construct()
    {
        view()->share('userId', request()->route('userId') ?? (auth()->check() ? auth()->id() : null));
    }


    public function index(Request $request)
    {
        return view('sarpras.laporan.index');
    }

    /**
     * Laporan Inventaris Per Ruang
     */
    public function inventarisPerRuang(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $viewAll = $this->canViewAll($request);

        $query = AssetRoom::with(['school', 'building', 'assets.category'])
            ->where('is_active', true);

        if (!$viewAll && $schoolId) {
            $query->where('school_id', $schoolId);
        }
        if ($request->filled('school_id') && $viewAll) {
            $query->where('school_id', $request->school_id);
        }
        if ($request->filled('room_id')) {
            $query->where('id', $request->room_id);
        }

        $rooms = $query->orderBy('room_name')->get();
        $schools = $viewAll ? School::orderBy('name')->get() : collect();

        $totalAset = 0;
        $totalNilai = 0;
        foreach ($rooms as $room) {
            $room->loadCount('assets');
            $totalAset += $room->assets_count;
            $totalNilai += $room->assets->sum('acquisition_price') ?? 0;
        }

        return view('sarpras.laporan.inventaris-per-ruang', compact('rooms', 'schools', 'totalAset', 'totalNilai'));
    }

    /**
     * Download PDF Inventaris Per Ruang
     */
    public function inventarisPerRuangPdf(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $viewAll = $this->canViewAll($request);

        $query = AssetRoom::with(['school', 'building', 'assets'])
            ->where('is_active', true);

        if (!$viewAll && $schoolId) {
            $query->where('school_id', $schoolId);
        }
        if ($request->filled('school_id') && $viewAll) {
            $query->where('school_id', $request->school_id);
        }

        $rooms = $query->orderBy('room_name')->get();

        $pdf = PDF::loadView('sarpras.laporan.pdf.inventaris-per-ruang', compact('rooms'));
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('laporan-inventaris-' . date('Ymd') . '.pdf');
    }

    /**
     * Laporan Kondisi Aset
     */
    public function kondisiAset(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $viewAll = $this->canViewAll($request);

        $query = Asset::where('is_active', true);

        if (!$viewAll && $schoolId) {
            $query->where('school_id', $schoolId);
        }
        if ($request->filled('school_id') && $viewAll) {
            $query->where('school_id', $request->school_id);
        }
        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }

        $assets = $query->orderBy('condition')->orderBy('asset_name')->get();
        $schools = $viewAll ? School::orderBy('name')->get() : collect();

        // Summary
        $summary = [
            'baik'         => (clone $query)->where('condition', 'baik')->count(),
            'rusak_ringan'  => (clone $query)->where('condition', 'rusak_ringan')->count(),
            'rusak_sedang'  => (clone $query)->where('condition', 'rusak_sedang')->count(),
            'rusak_berat'  => (clone $query)->where('condition', 'rusak_berat')->count(),
            'hilang'       => (clone $query)->where('condition', 'hilang')->count(),
            'total'        => $assets->count(),
        ];

        return view('sarpras.laporan.kondisi-aset', compact('assets', 'schools', 'summary'));
    }

    /**
     * Download PDF Kondisi Aset
     */
    public function kondisiAsetPdf(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $viewAll = $this->canViewAll($request);

        $query = Asset::where('is_active', true);

        if (!$viewAll && $schoolId) {
            $query->where('school_id', $schoolId);
        }
        if ($request->filled('school_id') && $viewAll) {
            $query->where('school_id', $request->school_id);
        }
        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }

        $assets = $query->orderBy('condition')->orderBy('asset_name')->get();

        $summary = [
            'baik'        => $assets->where('condition', 'baik')->count(),
            'rusak_ringan' => $assets->where('condition', 'rusak_ringan')->count(),
            'rusak_sedang' => $assets->where('condition', 'rusak_sedang')->count(),
            'rusak_berat' => $assets->where('condition', 'rusak_berat')->count(),
            'hilang'      => $assets->where('condition', 'hilang')->count(),
            'total'       => $assets->count(),
        ];

        $pdf = PDF::loadView('sarpras.laporan.pdf.kondisi-aset', compact('assets', 'summary'));
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('laporan-kondisi-aset-' . date('Ymd') . '.pdf');
    }

    /**
     * Laporan Peminjaman
     */
    public function peminjaman(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $viewAll = $this->canViewAll($request);

        $query = AssetLoan::with(['asset', 'borrower']);

        if (!$viewAll && $schoolId) {
            $query->where('school_id', $schoolId);
        }
        if ($request->filled('school_id') && $viewAll) {
            $query->where('school_id', $request->school_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('loan_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('loan_date', '<=', $request->date_to);
        }

        $loans = $query->orderBy('created_at', 'desc')->get();
        $schools = $viewAll ? School::orderBy('name')->get() : collect();

        $summary = [
            'pending'     => $loans->where('status', 'pending')->count(),
            'approved'    => $loans->where('status', 'approved')->count(),
            'dipinjam'    => $loans->where('status', 'dipinjam')->count(),
            'dikembalikan' => $loans->where('status', 'dikembalikan')->count(),
            'terlambat'   => $loans->where('status', 'terlambat')->count(),
            'total'       => $loans->count(),
        ];

        return view('sarpras.laporan.peminjaman', compact('loans', 'schools', 'summary'));
    }

    /**
     * Laporan Pemeliharaan
     */
    public function pemeliharaan(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $viewAll = $this->canViewAll($request);

        $query = AssetMaintenanceLog::with(['asset', 'performer']);

        if (!$viewAll && $schoolId) {
            $query->where('school_id', $schoolId);
        }
        if ($request->filled('school_id') && $viewAll) {
            $query->where('school_id', $request->school_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('maintenance_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('maintenance_date', '<=', $request->date_to);
        }

        $logs = $query->orderBy('maintenance_date', 'desc')->get();
        $schools = $viewAll ? School::orderBy('name')->get() : collect();

        $totalBiaya = $logs->sum('actual_cost') ?? 0;

        return view('sarpras.laporan.pemeliharaan', compact('logs', 'schools', 'totalBiaya'));
    }

    /**
     * Laporan Nilai Aset
     */
    public function nilaiAset(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $viewAll = $this->canViewAll($request);

        $query = Asset::where('is_active', true);

        if (!$viewAll && $schoolId) {
            $query->where('school_id', $schoolId);
        }
        if ($request->filled('school_id') && $viewAll) {
            $query->where('school_id', $request->school_id);
        }

        $assets = $query->orderBy('asset_name')->get();
        $schools = $viewAll ? School::orderBy('name')->get() : collect();

        $totalPerolehan = $assets->sum('acquisition_price') ?? 0;
        $totalNilaiBuku = $assets->sum('current_value') ?? 0;
        $totalPenyusutan = $totalPerolehan - $totalNilaiBuku;

        return view('sarpras.laporan.nilai-aset', compact('assets', 'schools', 'totalPerolehan', 'totalNilaiBuku', 'totalPenyusutan'));
    }

    /**
     * Export Excel Data Aset
     */
    public function exportExcel(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $viewAll = $this->canViewAll($request);

        $query = Asset::with(['room', 'room.building', 'category', 'school']);

        if (!$viewAll && $schoolId) {
            $query->where('school_id', $schoolId);
        }
        if ($request->filled('school_id') && $viewAll) {
            $query->where('school_id', $request->school_id);
        }

        $assets = $query->orderBy('asset_name')->get();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new class($assets) implements \Maatwebsite\Excel\Concerns\FromCollection {
                public function __construct($assets) { $this->assets = $assets; }
                public function collection() {
                    $data = [['No', 'Kode', 'Nama Aset', 'Kategori', 'Ruang', 'Gedung', 'Kondisi', 'Status', 'Nilai Perolehan', 'Nilai Buku']];
                    foreach ($this->assets as $i => $a) {
                        $data[] = [
                            $i + 1,
                            $a->asset_code ?? '-',
                            $a->asset_name,
                            $a->category?->name ?? '-',
                            $a->room?->room_name ?? '-',
                            $a->room?->building?->building_name ?? '-',
                            ucfirst(str_replace('_', ' ', $a->condition ?? '-')),
                            ucfirst(str_replace('_', ' ', $a->status ?? '-')),
                            $a->acquisition_price ?? 0,
                            $a->current_value ?? 0,
                        ];
                    }
                    return collect($data);
                }
            },
            'laporan-aset-' . date('Ymd') . '.xlsx'
        );
    }
}
