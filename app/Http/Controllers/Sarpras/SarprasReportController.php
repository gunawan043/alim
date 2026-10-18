<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\Asset;
use App\Models\AssetLoan;
use App\Models\AssetMaintenanceLog;
use App\Models\AssetCategory;
use App\Models\AssetRoom;
use App\Models\School;
use App\Models\WorkUnit;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Facades\Excel;
use Dompdf\Dompdf;

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

        if (! $viewAll && $schoolId) {
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
            ->withCount('assets')
            ->where('is_active', true);

        if (! $viewAll && $schoolId) {
            $query->where('school_id', $schoolId);
        }
        if ($request->filled('school_id') && $viewAll) {
            $query->where('school_id', $request->school_id);
        }

        $rooms = $query->orderBy('room_name')->get();

        return $this->renderPdf('sarpras.laporan.pdf.inventaris-per-ruang', compact('rooms'), 'landscape', 'laporan-inventaris-'.date('Ymd').'.pdf');
    }

    /**
     * Buku Peminjaman Barang (form manual URT).
     */
    public function bukuPeminjaman(Request $request)
    {
        $query = $this->loanReportQuery($request);
        $loans = $query->paginate(25)->withQueryString();
        $schools = $this->canViewAll($request) ? School::orderBy('name')->get() : collect();
        $summary = [
            'total' => (clone $query)->count(),
            'dipinjam' => (clone $query)->whereIn('status', ['approved', 'dipinjam'])->count(),
            'kembali' => (clone $query)->where('status', 'dikembalikan')->count(),
            'terlambat' => (clone $query)->where('status', 'terlambat')->count(),
        ];

        return view('sarpras.laporan.buku-peminjaman', compact('loans', 'schools', 'summary'));
    }

    public function bukuPeminjamanPdf(Request $request)
    {
        $loans = $this->loanReportQuery($request)->get();

        return $this->renderPdf(
            'sarpras.laporan.pdf.buku-peminjaman',
            compact('loans'),
            'landscape',
            'buku-peminjaman-barang-'.date('Ymd').'.pdf'
        );
    }

    private function loanReportQuery(Request $request)
    {
        $query = AssetLoan::with([
            'asset.category', 'asset.room.building',
            'borrower.gtkWorkUnits.workUnit', 'approver', 'returnedToUser',
        ]);

        $schoolId = $request->attributes->get('schoolContextId');
        if (! $this->canViewAll($request) && $schoolId) {
            $query->where('school_id', $schoolId);
        } elseif ($request->filled('school_id') && $this->canViewAll($request)) {
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

        return $query->orderByDesc('loan_date')->orderByDesc('created_at');
    }

    /**
     * Laporan Kondisi Aset
     */
    public function kondisiAset(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $viewAll = $this->canViewAll($request);

        $query = Asset::where('is_active', true);

        if (! $viewAll && $schoolId) {
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
            'baik' => (clone $query)->where('condition', 'baik')->count(),
            'rusak_ringan' => (clone $query)->where('condition', 'rusak_ringan')->count(),
            'rusak_sedang' => (clone $query)->where('condition', 'rusak_sedang')->count(),
            'rusak_berat' => (clone $query)->where('condition', 'rusak_berat')->count(),
            'hilang' => (clone $query)->where('condition', 'hilang')->count(),
            'total' => $assets->count(),
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

        if (! $viewAll && $schoolId) {
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
            'baik' => $assets->where('condition', 'baik')->count(),
            'rusak_ringan' => $assets->where('condition', 'rusak_ringan')->count(),
            'rusak_sedang' => $assets->where('condition', 'rusak_sedang')->count(),
            'rusak_berat' => $assets->where('condition', 'rusak_berat')->count(),
            'hilang' => $assets->where('condition', 'hilang')->count(),
            'total' => $assets->count(),
        ];

        return $this->renderPdf('sarpras.laporan.pdf.kondisi-aset', compact('assets', 'summary'), 'portrait', 'laporan-kondisi-aset-'.date('Ymd').'.pdf');
    }

    /**
     * Laporan Peminjaman
     */
    public function peminjaman(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $viewAll = $this->canViewAll($request);

        $query = AssetLoan::with(['asset', 'borrower']);

        if (! $viewAll && $schoolId) {
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
            'pending' => $loans->where('status', 'pending')->count(),
            'approved' => $loans->where('status', 'approved')->count(),
            'dipinjam' => $loans->where('status', 'dipinjam')->count(),
            'dikembalikan' => $loans->where('status', 'dikembalikan')->count(),
            'terlambat' => $loans->where('status', 'terlambat')->count(),
            'total' => $loans->count(),
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

        if (! $viewAll && $schoolId) {
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

        if (! $viewAll && $schoolId) {
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
     * Buku Induk Inventaris Aset per satuan kerja.
     */
    public function bukuInduk(Request $request)
    {
        $query = $this->inventoryQuery($request);
        $assets = $query->paginate(50)->withQueryString();

        $summaryQuery = $this->inventoryQuery($request);
        $summary = [
            'items' => (clone $summaryQuery)->count(),
            'quantity' => (clone $summaryQuery)->sum('quantity'),
            'value' => (clone $summaryQuery)->sum('acquisition_price'),
            'depreciation' => (clone $summaryQuery)->sum('depreciation_per_year'),
        ];

        $workUnits = WorkUnit::orderBy('name')->get();
        $rooms = AssetRoom::with('building')->where('is_active', true)->orderBy('room_name')->get();
        $categories = AssetCategory::where('is_active', true)->orderBy('name')->get();

        return view('sarpras.laporan.buku-induk', compact('assets', 'summary', 'workUnits', 'rooms', 'categories'));
    }

    public function bukuIndukPdf(Request $request)
    {
        $assets = $this->inventoryQuery($request)->get();
        $filters = $request->only(['work_unit_id', 'room_id', 'category_id', 'condition', 'search']);
        $workUnit = $request->filled('work_unit_id') ? WorkUnit::find($request->work_unit_id) : null;

        return $this->renderPdf('sarpras.laporan.pdf.buku-induk', compact('assets', 'workUnit', 'filters'), 'landscape', 'buku-induk-inventaris-'.date('Ymd').'.pdf');
    }

    /**
     * Kartu Inventaris Ruangan (KIR).
     */
    public function kartuInventarisRuang(Request $request)
    {
        $rooms = $this->kirRoomsQuery($request)->get();
        $workUnits = WorkUnit::orderBy('name')->get();
        $allRooms = AssetRoom::with('building')->where('is_active', true)->orderBy('room_name')->get();

        $rooms->each(function ($room) {
            $room->groupedAssets = $this->groupKirAssets($room->assets);
        });

        return view('sarpras.laporan.kir', compact('rooms', 'workUnits', 'allRooms'));
    }

    public function kartuInventarisRuangPdf(Request $request)
    {
        $rooms = $this->kirRoomsQuery($request)->get();
        $rooms->each(function ($room) {
            $room->groupedAssets = $this->groupKirAssets($room->assets);
        });
        $workUnit = $request->filled('work_unit_id') ? WorkUnit::find($request->work_unit_id) : null;

        return $this->renderPdf('sarpras.laporan.pdf.kir', compact('rooms', 'workUnit'), 'landscape', 'kartu-inventaris-ruangan-'.date('Ymd').'.pdf');
    }

    private function inventoryQuery(Request $request)
    {
        $query = Asset::with(['category', 'room.building', 'room.responsibleUser', 'workUnit', 'school'])
            ->where('is_active', true);

        if (! $this->isCentralSarpras() && ! $this->canViewAll($request)) {
            $query = $this->scopeToResponsibility($query, $request, 'room_id');
        } elseif ($request->attributes->get('schoolContextId') && ! $this->canViewAll($request)) {
            $query->where('school_id', $request->attributes->get('schoolContextId'));
        }

        if ($request->filled('work_unit_id')) {
            $query->where('work_unit_id', $request->work_unit_id);
        }
        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }
        if ($request->filled('category_id')) {
            $query->where('asset_category_id', $request->category_id);
        }
        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q
                ->where('asset_name', 'like', "%{$s}%")
                ->orWhere('asset_code', 'like', "%{$s}%")
                ->orWhere('brand', 'like', "%{$s}%"));
        }

        return $query->orderBy('work_unit_id')
            ->orderBy('room_id')
            ->orderBy('work_unit_sequence')
            ->orderBy('asset_name');
    }

    private function kirRoomsQuery(Request $request)
    {
        $query = AssetRoom::with([
            'building', 'school', 'workUnit.primarySarprasOfficer.user', 'responsibleUser',
            'assets' => fn ($q) => $q->where('is_active', true)->with('category')->orderBy('asset_name'),
        ])->where('is_active', true);

        if (! $this->isCentralSarpras() && ! $this->canViewAll($request)) {
            $query = $this->scopeToResponsibility($query, $request, 'id');
        } elseif ($request->attributes->get('schoolContextId') && ! $this->canViewAll($request)) {
            $query->where('school_id', $request->attributes->get('schoolContextId'));
        }

        if ($request->filled('work_unit_id')) {
            $query->where('work_unit_id', $request->work_unit_id);
        }
        if ($request->filled('room_id')) {
            $query->where('id', $request->room_id);
        }

        return $query->orderBy('building_id')->orderBy('room_name');
    }

    private function renderPdf(string $view, array $data, string $orientation, string $filename)
    {
        $pdf = new Dompdf([
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
        ]);
        $pdf->loadHtml(view($view, $data)->render());
        $pdf->setPaper('A4', $orientation);
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function groupKirAssets($assets)
    {
        return $assets->groupBy(fn ($asset) => implode('|', [
            $asset->asset_name,
            $asset->brand ?? '',
            $asset->asset_category_id,
            $asset->acquisition_year ?? '',
            $asset->asset_code ?? '',
        ]))->map(function ($group) {
            $first = $group->first();

            return [
                'asset' => $first,
                'quantity' => $group->sum('quantity'),
                'baik' => $group->filter(fn ($a) => $a->kir_condition === 'B')->sum('quantity'),
                'kurang_baik' => $group->filter(fn ($a) => $a->kir_condition === 'KB')->sum('quantity'),
                'rusak_berat' => $group->filter(fn ($a) => $a->kir_condition === 'RB')->sum('quantity'),
                'notes' => $group->pluck('notes')->filter()->unique()->implode('; '),
            ];
        })->values();
    }

    /**
     * Export Excel Data Aset
     */
    public function exportExcel(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $viewAll = $this->canViewAll($request);

        $query = Asset::with(['room', 'room.building', 'category', 'school']);

        if (! $viewAll && $schoolId) {
            $query->where('school_id', $schoolId);
        }
        if ($request->filled('school_id') && $viewAll) {
            $query->where('school_id', $request->school_id);
        }

        $assets = $query->orderBy('asset_name')->get();

        return Excel::download(
            new class($assets) implements FromCollection
            {
                public function __construct($assets)
                {
                    $this->assets = $assets;
                }

                public function collection()
                {
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
            'laporan-aset-'.date('Ymd').'.xlsx'
        );
    }
}
