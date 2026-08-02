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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class SarprasUserController extends SarprasBaseController
{
    public function __construct()
    {
        view()->share('userId', request()->route('userId') ?? (auth()->check() ? auth()->id() : null));
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

    public function updateRoom(UserRoomStoreRequest $request, string $id)
    {
        $room = AssetRoom::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        if (! $this->canAccess($request, $room, 'school_id')) {
            return back()->with('error', 'Anda tidak memiliki akses ke resource ini.');
        }

        $validated = $request->validated();

        $room->update($validated);

        return redirect()->route('sarpras.user.ruang.index', ['userId' => $request->route('userId')])
            ->with('success', 'Ruang berhasil diperbarui.');
    }

    public function destroyRoom(Request $request, string $id)
    {
        $room = AssetRoom::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        if (! $this->canAccess($request, $room, 'school_id')) {
            return back()->with('error', 'Anda tidak memiliki akses ke resource ini.');
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
        $reports = AssetDamageReport::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('reported_by', $user->id)
            ->with('asset')
            ->orderBy('created_at', 'desc')->get();
        $damagedAssets = Asset::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)->whereIn('condition', ['rusak_ringan', 'rusak_sedang', 'rusak_berat'])
            ->with('room.building')->orderBy('asset_name')->get();

        return view('sarpras.user.kerusakan.index', compact('reports', 'damagedAssets', 'userId'));
    }

    public function createKerusakan(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $userId = $request->route('userId');
        $damagedAssets = Asset::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)->whereIn('condition', ['rusak_ringan', 'rusak_sedang', 'rusak_berat'])
            ->with('room.building')->orderBy('asset_name')->get();

        return view('sarpras.user.kerusakan.create', compact('damagedAssets', 'userId'));
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

    public function showRoom(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $userId = $request->route('userId');
        $room = AssetRoom::with(['building', 'school', 'assets.category', 'assets.room'])
            ->where('is_active', true)
            ->findOrFail($id);

        if (! $this->canAccess($request, $room, 'school_id')) {
            return back()->with('error', 'Anda tidak memiliki akses ke resource ini.');
        }

        return view('sarpras.ruang.show', ['ruang' => $room, 'userId' => $userId]);
    }

    // ============================================================
    // ASET — index, create, show
    // ============================================================
    public function indexAsset(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $allAssets = Asset::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->with('room.building', 'category')
            ->orderBy('asset_name')->get();
        $categories = AssetCategory::where('is_active', true)->orderBy('name')->get();
        $buildings = AssetBuilding::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('is_active', true)->orderBy('building_name')->get();

        return view('sarpras.user.aset.index', compact('allAssets', 'categories', 'buildings'));
    }

    public function createAsset(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $categories = AssetCategory::where('is_active', true)->orderBy('name')->get();
        $rooms = AssetRoom::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('is_active', true)->orderBy('room_name')->get();
        $buildings = AssetBuilding::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('is_active', true)->orderBy('building_name')->get();

        return view('sarpras.user.aset.create', compact('categories', 'rooms', 'buildings'));
    }

    public function showAsset(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $userId = $request->route('userId');
        $aset = Asset::with('room.building', 'category', 'school')->findOrFail($id);
        if ($schoolId && $aset->school_id !== $schoolId) {
            abort(403);
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
        $validated['current_value'] = $validated['acquisition_price'] ?? null;
        $validated['created_by'] = auth()->id();
        $validated['school_id'] = $schoolId;
        $validated['work_unit_id'] = $school?->work_unit_id;

        if (! empty($validated['room_id'])) {
            $room = AssetRoom::find($validated['room_id']);
            $validated['work_unit_id'] = $room->work_unit_id;
            $validated['school_id'] = $room->school_id;
        }

        Asset::create($validated);

        return redirect()->route('sarpras.user.aset.index', ['userId' => $request->route('userId')])
            ->with('success', 'Aset berhasil ditambahkan.');
    }

    public function updateAsset(UserAssetUpdateRequest $request, string $id)
    {
        $asset = Asset::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $asset->school_id !== $schoolId) {
            abort(403);
        }

        $validated = $request->validated();

        $validated['current_value'] = $validated['acquisition_price'] ?? null;

        if (! empty($validated['room_id'])) {
            $room = AssetRoom::find($validated['room_id']);
            $validated['work_unit_id'] = $room->work_unit_id;
            $validated['school_id'] = $room->school_id;
        }

        $asset->update($validated);

        return redirect()->route('sarpras.user.aset.show', ['userId' => $request->route('userId'), 'id' => $id])
            ->with('success', 'Aset berhasil diperbarui.');
    }

    public function editAsset(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $userId = $request->route('userId');
        $asetData = Asset::with('room.building', 'category', 'school')->findOrFail($id);

        if ($schoolId && $asetData->school_id !== $schoolId) {
            abort(403);
        }

        $categories = AssetCategory::where('is_active', true)->orderBy('name')->get();
        $rooms = AssetRoom::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('is_active', true)->orderBy('room_name')->get();
        $buildings = AssetBuilding::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('is_active', true)->orderBy('building_name')->get();

        return view('sarpras.user.aset.edit', compact('asetData', 'categories', 'rooms', 'buildings', 'userId'));
    }

    public function destroyAsset(Request $request, string $id)
    {
        $asset = Asset::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $asset->school_id !== $schoolId) {
            abort(403);
        }

        $asset->delete();

        return redirect()->route('sarpras.user.aset.index', ['userId' => $request->route('userId')])
            ->with('success', 'Aset berhasil dihapus.');
    }

    // ============================================================
    // IMPORT ASET
    // ============================================================
    public function importForm(Request $request, ?string $roomId = null)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $userId = $request->route('userId');

        $rooms = AssetRoom::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)->orderBy('room_name')->get();
        $categories = AssetCategory::where('is_active', true)->orderBy('name')->get();

        $forcedRoom = $roomId ? AssetRoom::with('school')->find($roomId) : null;

        return view('sarpras.user.import', compact('rooms', 'categories', 'schoolId', 'forcedRoom', 'userId'));
    }

    public function importProcess(UserAssetImportRequest $request, ?string $roomId = null)
    {

        $schoolId = $request->attributes->get('schoolContextId');
        $userId = $request->route('userId');

        try {
            $import = new AssetImport($userId, $schoolId, $roomId);
            Excel::import($import, $request->file('file'));

            $errors = $import->getErrors();
            $createdCount = $import->getSuccessCount();

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

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
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

    public function importTemplate(Request $request)
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

        $schoolId = $request->attributes->get('schoolContextId');
        $school = $schoolId ? School::find($schoolId) : null;

        AssetDamageReport::create([
            'report_number' => AssetDamageReport::generateReportNumber(),
            'asset_id' => $validated['asset_id'],
            'reported_by' => auth()->id(),
            'damage_level' => $validated['damage_level'],
            'description' => $validated['description'],
            'reporter_notes' => $validated['notes'] ?? null,
            'school_id' => $schoolId,
            'work_unit_id' => $school?->work_unit_id,
            'status' => 'pending',
        ]);

        return redirect()->route('sarpras.user.kerusakan.index', ['userId' => $request->route('userId')])
            ->with('success', 'Laporan kerusakan berhasil dikirim.');
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
