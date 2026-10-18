<?php

namespace App\Http\Controllers\Sarpras;

use App\Exports\AssetTemplateExport;
use App\Http\Requests\Sarpras\UserAssetStoreRequest;
use App\Http\Requests\Sarpras\UserAssetUpdateRequest;
use App\Http\Requests\Sarpras\UserDamageReportStoreRequest;
use App\Http\Requests\Sarpras\UserProcurementRequestStoreRequest;
use App\Http\Requests\Sarpras\UserRoomStoreRequest;
use App\Imports\AssetImport;
use App\Models\Asset;
use App\Models\AssetBuilding;
use App\Models\AssetCategory;
use App\Models\AssetDamageReport;
use App\Models\AssetLoan;
use App\Models\AssetRoom;
use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\School;
use App\Models\User;
use App\Services\Sarpras\PhotoProofService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class SarprasUserController extends SarprasBaseController
{
    public function __construct(protected PhotoProofService $photos)
    {
        parent::__construct();
    }

    /**
     * Dashboard untuk user satuan kerja
     * Fitur: ruangan, aset, laporan kerusakan, permintaan pengadaan
     */
    public function dashboard(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $user = auth()->user();

        // Stats ruangan & aset
        $totalRuang = AssetRoom::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('is_active', true)->count();
        $totalAset = Asset::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('is_active', true)->count();
        $asetRusak = Asset::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('is_active', true)->whereIn('condition', ['rusak_ringan', 'rusak_sedang', 'rusak_berat'])->count();

        // My activities
        $myDamageReports = AssetDamageReport::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('reported_by', $user->id)->orderBy('created_at', 'desc')->limit(5)->get();
        $myProcurements = ProcurementRequest::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('requested_by', $user->id)->orderBy('created_at', 'desc')->limit(5)->get();
        $myLoans = AssetLoan::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('borrower_id', $user->id)->orderBy('created_at', 'desc')->limit(5)->get();

        $pendingDamage = AssetDamageReport::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('reported_by', $user->id)->where('status', 'pending')->count();
        $pendingProcurement = ProcurementRequest::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('requested_by', $user->id)->where('status', 'pending')->count();

        // Ruangan milik satuan kerja ini
        $rooms = AssetRoom::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when(! $this->isCentralSarpras() && ! $this->isUnitSarprasOfficer(), fn ($q) => $q->whereIn('id', $this->responsibleRoomIds()))
            ->where('is_active', true)->with('building')->orderBy('room_name')->get();

        // Aset milik satuan kerja ini
        $allAssets = Asset::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)->with('room.building', 'category')->orderBy('asset_name')->get();

        // Untuk dashboard: asset terbaru (limit 10) + total nilai
        $recentAssets = Asset::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)->with('room.building', 'category')->orderBy('created_at', 'desc')->limit(10)->get();
        $totalNilai = Asset::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('is_active', true)->sum('current_value');

        // Untuk form kerusakan
        $damagedAssets = Asset::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)->whereIn('condition', ['rusak_ringan', 'rusak_sedang', 'rusak_berat'])
            ->with('room.building')->orderBy('asset_name')->get();

        // Data pendukung form
        $buildings = AssetBuilding::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('is_active', true)->orderBy('building_name')->get();
        $categories = AssetCategory::where('is_active', true)->orderBy('name')->get();

        return view('sarpras.user.dashboard', compact(
            'rooms', 'recentAssets', 'totalRuang', 'totalAset', 'asetRusak',
            'myDamageReports', 'myProcurements',
            'pendingDamage', 'pendingProcurement',
            'damagedAssets', 'buildings', 'categories', 'totalNilai'
        ));
    }

    // ============================================================
    // RUANGAN
    // ============================================================
    public function storeRoom(UserRoomStoreRequest $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $school = $schoolId ? School::find($schoolId) : null;

        $validated = $request->validated();

        $validated['is_active'] = true;
        $validated['school_id'] = $schoolId;
        $validated['work_unit_id'] = $school?->work_unit_id;

        AssetRoom::create($validated);

        return redirect()->route('sarpras.user.ruang.index', ['userId' => $request->route('userId')])
            ->with('success', 'Ruang berhasil ditambahkan.');
    }

    public function updateRoom(string $userId, UserRoomStoreRequest $request, string $id)
    {
        $room = AssetRoom::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        if (! $this->canAccess($request, $room, 'school_id')) {
            return back()->with('error', 'Anda tidak memiliki akses ke resource ini.');
        }
        if (! $this->isCentralSarpras() && ! $this->isUnitSarprasOfficer()) {
            $this->authorizeResponsibleRoom($room->id, $request);
        }

        $validated = $request->validated();

        $room->update($validated);

        return redirect()->route('sarpras.user.ruang.index', ['userId' => $request->route('userId')])
            ->with('success', 'Ruang berhasil diperbarui.');
    }

    public function destroyRoom(string $userId, Request $request, string $id)
    {
        $room = AssetRoom::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        if (! $this->canAccess($request, $room, 'school_id')) {
            return back()->with('error', 'Anda tidak memiliki akses ke resource ini.');
        }
        if (! $this->isCentralSarpras() && ! $this->isUnitSarprasOfficer()) {
            $this->authorizeResponsibleRoom($room->id, $request);
        }
        if ($room->assets()->count() > 0) {
            return back()->with('error', 'Ruang tidak bisa dihapus karena masih memiliki aset.');
        }

        $room->delete();

        return redirect()->route('sarpras.user.ruang.index', ['userId' => $request->route('userId')])
            ->with('success', 'Ruang berhasil dihapus.');
    }

    public function indexRoom(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $userId = $request->route('userId');
        $rooms = AssetRoom::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)->with('building')->orderBy('room_name')->get();
        $buildings = AssetBuilding::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('is_active', true)->orderBy('building_name')->get();

        return view('sarpras.user.ruang.index', compact('rooms', 'buildings', 'userId'));
    }

    public function createRoom(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $userId = $request->route('userId');
        $buildings = AssetBuilding::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('is_active', true)->orderBy('building_name')->get();

        return view('sarpras.user.ruang.create', compact('buildings', 'userId'));
    }

    // ── KERUSAKAN ─────────────────────────────────────────
    public function indexKerusakan(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $userId = $request->route('userId');
        $user = auth()->user();

        $query = AssetDamageReport::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('reported_by', $user->id)
            ->with(['asset.room.building', 'reviewer'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->search;
                $q->where(fn ($sub) => $sub->where('report_number', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%")
                    ->orWhereHas('asset', fn ($a) => $a->where('asset_name', 'like', "%{$s}%")->orWhere('asset_code', 'like', "%{$s}%")));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('damage_level'), fn ($q) => $q->where('damage_level', $request->damage_level));

        $stats = [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'progress' => (clone $query)->whereIn('status', ['reviewed', 'scheduled', 'in_progress'])->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
        ];
        $reports = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $damagedQuery = Asset::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)->whereIn('condition', ['rusak_ringan', 'rusak_sedang', 'rusak_berat'])
            ->with('room.building');
        if (! $this->isCentralSarpras()) {
            $damagedQuery = $this->scopeToResponsibility($damagedQuery, $request);
        }
        $damagedAssets = $damagedQuery->orderBy('asset_name')->get();

        return view('sarpras.user.kerusakan.index', compact('reports', 'damagedAssets', 'userId', 'stats'));
    }

    public function createKerusakan(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $userId = $request->route('userId');
        $damagedQuery = Asset::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)->whereIn('condition', ['rusak_ringan', 'rusak_sedang', 'rusak_berat'])
            ->with('room.building');
        if (! $this->isCentralSarpras()) {
            $damagedQuery = $this->scopeToResponsibility($damagedQuery, $request);
        }
        $damagedAssets = $damagedQuery->orderBy('asset_name')->get();

        $selectedAssetId = $request->query('asset_id');
        if ($selectedAssetId && ! $damagedAssets->contains('id', $selectedAssetId)) {
            $selectedAsset = Asset::with('room.building')->find($selectedAssetId);
            if ($selectedAsset && ($this->isCentralSarpras() || $this->canAccess($request, $selectedAsset))) {
                $damagedAssets->prepend($selectedAsset);
            }
        }

        return view('sarpras.user.kerusakan.create', compact('damagedAssets', 'userId'));
    }

    public function createActionRequest(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $assets = Asset::with('room.building')
            ->where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('asset_name')
            ->get();

        $selectedAssetId = $request->query('asset_id');
        $action = $request->query('action', 'perbaikan');
        if (! in_array($action, ['perbaikan', 'perawatan', 'pengecekan'], true)) {
            $action = 'perbaikan';
        }

        return view('sarpras.user.action-request.create', compact('assets', 'selectedAssetId', 'action'));
    }

    public function storeActionRequest(Request $request)
    {
        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'action_type' => 'required|in:perbaikan,perawatan,pengecekan',
            'description' => 'required|string|max:2000',
            'priority' => 'required|in:low,normal,high,urgent',
        ], [
            'asset_id.required' => 'Aset wajib dipilih.',
            'description.required' => 'Uraian permintaan wajib diisi.',
        ]);

        $asset = Asset::findOrFail($validated['asset_id']);
        $label = ucfirst($validated['action_type']);
        $repair = app(\App\Services\Sarpras\RepairRequestWorkflow::class)->submitDamageReport(
            $asset,
            auth()->user(),
            'Permintaan '.$label.': '.$asset->asset_name,
            $validated['description'],
            $validated['priority']
        );

        return redirect()->route('sarpras.user.kerusakan.index')
            ->with('success', 'Permintaan '.strtolower($label).' dikirim dengan nomor '.$repair->request_number.'. URT akan menindaklanjuti.');
    }

    // ── PENGADAAN ─────────────────────────────────────────
    public function indexPengadaan(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $userId = $request->route('userId');
        $user = auth()->user();
        $procurements = ProcurementRequest::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('requested_by', $user->id)
            ->with('items')
            ->orderBy('created_at', 'desc')->get();

        return view('sarpras.user.pengadaan.index', compact('procurements', 'userId'));
    }

    public function createPengadaan(Request $request)
    {
        $userId = $request->route('userId');

        return view('sarpras.user.pengadaan.create', compact('userId'));
    }

    public function showRoom(string $userId, Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $userId = $request->route('userId');
        $room = AssetRoom::with(['building', 'school', 'assets.category', 'assets.room'])
            ->where('is_active', true)
            ->findOrFail($id);

        if (! $this->canAccess($request, $room, 'school_id')) {
            return back()->with('error', 'Anda tidak memiliki akses ke resource ini.');
        }
        if (! $this->isCentralSarpras() && ! $this->isUnitSarprasOfficer()) {
            $this->authorizeResponsibleRoom($room->id, $request);
        }

        return view('sarpras.ruang.show', ['ruang' => $room, 'userId' => $userId]);
    }

    // ============================================================
    // ASET — index, create, show
    // ============================================================
    public function indexAsset(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $query = Asset::query()
            ->where('is_active', true)
            ->with('room.building', 'category', 'workUnit');

        if (! $this->isCentralSarpras()) {
            $query = $this->scopeToResponsibility($query, $request);
        } elseif ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $stats = [
            'total' => (clone $query)->count(),
            'good' => (clone $query)->where('condition', 'baik')->count(),
            'damaged' => (clone $query)->whereIn('condition', ['rusak_ringan', 'rusak_sedang', 'rusak_berat'])->count(),
            'value' => (clone $query)->sum('acquisition_price'),
        ];

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q->where('asset_name', 'like', "%{$search}%")
                ->orWhere('asset_code', 'like', "%{$search}%")
                ->orWhere('brand', 'like', "%{$search}%"));
        }
        if ($request->filled('category_id')) {
            $query->where('asset_category_id', $request->category_id);
        }
        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }

        $allAssets = $query->orderBy('asset_name')->paginate(20)->withQueryString();
        $categories = AssetCategory::where('is_active', true)->orderBy('name')->get();
        $buildings = AssetBuilding::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)->orderBy('building_name')->get();

        return view('sarpras.user.aset.index', compact('allAssets', 'categories', 'buildings', 'stats'));
    }

    public function createAsset(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $categories = AssetCategory::where('is_active', true)->orderBy('name')->get();
        $rooms = AssetRoom::with('building')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when(! $this->isCentralSarpras() && $this->isUnitSarprasOfficer(), fn ($q) => $q->whereIn('work_unit_id', $this->accessibleWorkUnitIds()))
            ->when(! $this->isCentralSarpras() && ! $this->isUnitSarprasOfficer(), fn ($q) => $q->whereIn('id', $this->responsibleRoomIds()))
            ->where('is_active', true)->orderBy('room_name')->get();
        $buildings = AssetBuilding::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)->orderBy('building_name')->get();

        return view('sarpras.user.aset.create', compact('categories', 'rooms', 'buildings'));
    }

    public function showAsset(string $userId, Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $userId = $request->route('userId');
        $aset = Asset::with('room.building', 'category', 'school', 'workUnit')->findOrFail($id);
        if (! $this->canAccess($request, $aset)) {
            abort(403);
        }
        if (! $this->isCentralSarpras() && ! $this->isUnitSarprasOfficer()) {
            $this->authorizeResponsibleRoom($aset->room_id, $request);
        }

        return view('sarpras.user.aset.show', compact('aset', 'userId'));
    }

    public function storeAsset(UserAssetStoreRequest $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $school = $schoolId ? School::find($schoolId) : null;

        $validated = $request->validated();

        $validated['is_active'] = true;
        $validated['status'] = $validated['status'] ?? 'tersedia';
        $validated['quantity'] = $validated['quantity'] ?? 1;
        $validated['unit'] = $validated['unit'] ?? 'unit';
        $validated['verification_status'] = 'pending';
        $validated['verified_by'] = null;
        $validated['verified_at'] = null;
        $validated['current_value'] = $validated['acquisition_price'] ?? null;
        $validated['created_by'] = auth()->id();
        $validated['school_id'] = $schoolId;
        $validated['work_unit_id'] = $school?->work_unit_id;

        if (! empty($validated['useful_life_years']) && ! empty($validated['acquisition_price'])) {
            $validated['depreciation_per_year'] = $validated['depreciation_per_year']
                ?? round(((float) $validated['acquisition_price']) / max(1, (int) $validated['useful_life_years']), 2);
        }

        if (! empty($validated['room_id'])) {
            $room = AssetRoom::findOrFail($validated['room_id']);
            $this->authorizeWorkUnitAccess($room->work_unit_id, $request);
            $this->authorizeResponsibleRoom($room->id, $request);
            $validated['work_unit_id'] = $room->work_unit_id;
            $validated['school_id'] = $room->school_id;
        } elseif (! $this->isCentralSarpras() && ! $this->isUnitSarprasOfficer()) {
            return back()->withInput()->with('error', 'Wali kelas/wali kamar wajib memilih ruang yang menjadi tanggung jawabnya.');
        }

        Asset::create($validated);

        return redirect()->route('sarpras.user.aset.index', ['userId' => $request->route('userId')])
            ->with('success', 'Aset berhasil ditambahkan dan menunggu verifikasi Unit Rumah Tangga.');
    }

    public function updateAsset(string $userId, UserAssetUpdateRequest $request, string $id)
    {
        $asset = Asset::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        if (! $this->canAccess($request, $asset)) {
            abort(403);
        }
        if (! $this->isCentralSarpras() && ! $this->isUnitSarprasOfficer()) {
            $this->authorizeResponsibleRoom($asset->room_id, $request);
        }

        $validated = $request->validated();

        if (! empty($validated['useful_life_years']) && ! empty($validated['acquisition_price'])) {
            $validated['depreciation_per_year'] = $validated['depreciation_per_year']
                ?? round(((float) $validated['acquisition_price']) / max(1, (int) $validated['useful_life_years']), 2);
        }

        if (! empty($validated['room_id'])) {
            $room = AssetRoom::find($validated['room_id']);
            $this->authorizeWorkUnitAccess($room->work_unit_id, $request);
            $this->authorizeResponsibleRoom($room->id, $request);
            $validated['work_unit_id'] = $room->work_unit_id;
            $validated['school_id'] = $room->school_id;
        }

        $asset->update($validated);

        return redirect()->route('sarpras.user.aset.show', ['userId' => $request->route('userId'), 'id' => $id])
            ->with('success', 'Aset berhasil diperbarui.');
    }

    public function editAsset(string $userId, Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $userId = $request->route('userId');
        $asetData = Asset::with('room.building', 'category', 'school', 'workUnit')->findOrFail($id);

        if (! $this->canAccess($request, $asetData)) {
            abort(403);
        }
        if (! $this->isCentralSarpras() && ! $this->isUnitSarprasOfficer()) {
            $this->authorizeResponsibleRoom($asetData->room_id, $request);
        }

        $categories = AssetCategory::where('is_active', true)->orderBy('name')->get();
        $rooms = AssetRoom::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when(! $this->isCentralSarpras() && $this->isUnitSarprasOfficer(), fn ($q) => $q->whereIn('work_unit_id', $this->accessibleWorkUnitIds()))
            ->when(! $this->isCentralSarpras() && ! $this->isUnitSarprasOfficer(), fn ($q) => $q->whereIn('id', $this->responsibleRoomIds()))
            ->where('is_active', true)->orderBy('room_name')->get();
        $buildings = AssetBuilding::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('is_active', true)->orderBy('building_name')->get();

        return view('sarpras.user.aset.edit', compact('asetData', 'categories', 'rooms', 'buildings', 'userId'));
    }

    public function destroyAsset(string $userId, Request $request, string $id)
    {
        $asset = Asset::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        if (! $this->canAccess($request, $asset)) {
            abort(403);
        }
        if (! $this->isCentralSarpras() && ! $this->isUnitSarprasOfficer()) {
            $this->authorizeResponsibleRoom($asset->room_id, $request);
        }

        $asset->delete();

        return redirect()->route('sarpras.user.aset.index', ['userId' => $request->route('userId')])
            ->with('success', 'Aset berhasil dihapus.');
    }

    // ============================================================
    // IMPORT ASET
    // ============================================================
    public function importForm(string $userId, Request $request, ?string $roomId = null)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $userId = $request->route('userId');

        $rooms = AssetRoom::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when(! $this->isCentralSarpras() && ! $this->isUnitSarprasOfficer(), fn ($q) => $q->whereIn('id', $this->responsibleRoomIds()))
            ->where('is_active', true)->orderBy('room_name')->get();
        $categories = AssetCategory::where('is_active', true)->orderBy('name')->get();

        $forcedRoom = $roomId
            ? AssetRoom::with('school')
                ->when(! $this->isCentralSarpras() && ! $this->isUnitSarprasOfficer(), fn ($q) => $q->whereIn('id', $this->responsibleRoomIds()))
                ->find($roomId)
            : null;

        return view('sarpras.user.import', compact('rooms', 'categories', 'schoolId', 'forcedRoom', 'userId'));
    }

    public function importProcess(string $userId, UserAssetImportRequest $request, ?string $roomId = null)
    {

        $schoolId = $request->attributes->get('schoolContextId');
        $userId = $request->route('userId');

        try {
            $allowedRoomIds = (! $this->isCentralSarpras() && ! $this->isUnitSarprasOfficer())
                ? $this->responsibleRoomIds()
                : null;
            $preview = $request->boolean('preview');
            $import = new AssetImport($userId, $schoolId, $roomId, $this->isCentralSarpras(), $allowedRoomIds, $preview);
            Excel::import($import, $request->file('file'));

            $errors = $import->getErrors();
            $createdCount = $import->getSuccessCount();

            if ($preview) {
                return redirect()->route('sarpras.user.aset.import', ['userId' => $userId])
                    ->with($errors === [] ? 'success' : 'warning', "Validasi selesai: {$createdCount} baris siap diimport, ".count($errors).' baris bermasalah. Tidak ada data yang disimpan.')
                    ->with('import_errors', $errors);
            }

            if ($createdCount > 0 && empty($errors)) {
                return redirect()->route('sarpras.user.aset.index', ['userId' => $userId])
                    ->with('success', "Berhasil mengimport {$createdCount} aset.");
            }
            if ($createdCount > 0) {
                return redirect()->route('sarpras.user.aset.index', ['userId' => $userId])
                    ->with('success', "Berhasil mengimport {$createdCount} aset. ".count($errors).' baris dilewati karena error.')
                    ->with('import_errors', $errors);
            }
            $redirectRoute = $roomId ? 'sarpras.user.aset.import.room' : 'sarpras.user.aset.import';
            $redirectParams = $roomId ? ['userId' => $userId, 'roomId' => $roomId] : ['userId' => $userId];

            return redirect()->route($redirectRoute, $redirectParams)
                ->with('error', 'Gagal mengimport. Silakan perbaiki file sesuai panduan.')
                ->with('import_errors', $errors);

        } catch (ValidationException $e) {
            $failures = $e->failures();
            $errMsgs = [];
            foreach ($failures as $failure) {
                $errMsgs[] = "Baris {$failure->row()}: ".implode(', ', $failure->errors());
            }
            $redirectRoute = $roomId ? 'sarpras.user.aset.import.room' : 'sarpras.user.aset.import';
            $redirectParams = $roomId ? ['userId' => $userId, 'roomId' => $roomId] : ['userId' => $userId];

            return redirect()->route($redirectRoute, $redirectParams)
                ->with('error', 'Validasi gagal. Perbaiki data Excel Anda.')
                ->with('import_errors', $errMsgs);

        } catch (\Throwable $e) {
            Log::error('AssetImport error: '.$e->getMessage());
            $redirectRoute = $roomId ? 'sarpras.user.aset.import.room' : 'sarpras.user.aset.import';
            $redirectParams = $roomId ? ['userId' => $userId, 'roomId' => $roomId] : ['userId' => $userId];

            return redirect()->route($redirectRoute, $redirectParams)
                ->with('error', 'Gagal memproses file: '.$e->getMessage());
        }
    }

    public function importTemplate(string $userId, Request $request)
    {
        $exporter = new AssetTemplateExport;

        return $exporter->download('template_import_aset.xlsx');
    }

    // ============================================================
    // LAPORAN KERUSAKAN
    // ============================================================
    public function storeDamageReport(UserDamageReportStoreRequest $request)
    {
        $validated = $request->validated();
        $reportedAsset = Asset::findOrFail($validated['asset_id']);

        if (! $this->isCentralSarpras() && ! $this->isUnitSarprasOfficer()) {
            $this->authorizeResponsibleRoom($reportedAsset->room_id, $request);
        }

        $schoolId = $request->attributes->get('schoolContextId');
        $school = $schoolId ? School::find($schoolId) : null;

        $photos = [];
        $photoUrls = array_values(array_filter($validated['photo_urls'] ?? []));
        $index = 0;
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $stored = $this->photos->store($photo, $photoUrls[$index] ?? null, 'sarpras/damage-reports');
                if (! empty($stored['url'])) {
                    $photos[] = $stored['url'];
                } elseif (! empty($stored['path'])) {
                    $photos[] = $stored['path'];
                }
                $index++;
            }
        }
        foreach (array_slice($photoUrls, $index) as $url) {
            $photos[] = $url;
        }

        $report = AssetDamageReport::create([
            'report_number' => AssetDamageReport::generateReportNumber(),
            'asset_id' => $validated['asset_id'],
            'reported_by' => auth()->id(),
            'damage_level' => $validated['damage_level'],
            'description' => $validated['description'],
            'reporter_notes' => $validated['notes'] ?? null,
            'photos' => $photos ?: null,
            'school_id' => $schoolId,
            'work_unit_id' => $school?->work_unit_id ?? $reportedAsset->work_unit_id,
            'status' => 'pending',
        ]);

        // Laporan terstruktur langsung masuk antrean repair request URT.
        try {
            $asset = $reportedAsset;
            $priority = match ($validated['damage_level']) {
                'berat' => 'high', 'sedang' => 'normal', default => 'low',
            };
            $repair = app(\App\Services\Sarpras\RepairRequestWorkflow::class)->submitDamageReport(
                $asset,
                auth()->user(),
                'Laporan kerusakan: '.$asset->asset_name,
                trim($report->report_number.' — '.$validated['description'].' '.($validated['notes'] ?? '')),
                $priority,
            );
            $report->update(['repair_request_id' => $repair->id]);
        } catch (\Throwable $e) {
            \Log::warning('Auto repair request dari laporan kerusakan gagal: '.$e->getMessage());
        }

        return redirect()->route('sarpras.user.kerusakan.index', ['userId' => $request->route('userId')])
            ->with('success', 'Laporan kerusakan berhasil dikirim dan masuk antrean perbaikan URT.');
    }

    // ============================================================
    // PERMINTAAN PENGADAAN
    // ============================================================
    public function storeProcurementRequest(UserProcurementRequestStoreRequest $request)
    {
        $validated = $request->validated();

        $schoolId = $request->attributes->get('schoolContextId');
        $school = $schoolId ? School::find($schoolId) : null;

        $procurement = ProcurementRequest::create([
            'request_number' => 'PROC-USER-'.date('Ymd').'-'.strtoupper(Str::random(5)),
            'request_date' => Carbon::today()->format('Y-m-d'),
            'purpose' => $validated['purpose'],
            'urgency' => $validated['urgency'],
            'total_estimated_budget' => ($validated['estimated_price'] ?? 0) * $validated['quantity'],
            'requested_by' => auth()->id(),
            'status' => 'pending',
            'school_id' => $schoolId,
            'work_unit_id' => $school?->work_unit_id,
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        ProcurementRequestItem::create([
            'procurement_request_id' => $procurement->id,
            'item_name' => $validated['item_name'],
            'quantity' => $validated['quantity'],
            'unit' => $validated['unit'] ?? null,
            'estimated_price_per_unit' => $validated['estimated_price'] ?? 0,
            'total_estimated_price' => ($validated['estimated_price'] ?? 0) * $validated['quantity'],
        ]);

        return redirect()->route('sarpras.user.pengadaan.index', ['userId' => $request->route('userId')])
            ->with('success', 'Permintaan pengadaan berhasil diajukan.');
    }
}
