<?php

namespace App\Http\Controllers\Sarpras;

use App\Exports\AssetTemplateExport;
use App\Http\Requests\Sarpras\AsetStoreRequest;
use App\Http\Requests\Sarpras\AsetUpdateRequest;
use App\Http\Requests\Sarpras\AssetImportRequest;
use App\Imports\AssetImport;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetPhoto;
use App\Models\AssetRoom;
use App\Services\Sarpras\AssetEventLogger;
use App\Services\Sarpras\AssetStatusTransitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class SarprasAsetController extends SarprasBaseController
{
    public function __construct(
        public AssetEventLogger $eventLogger,
        public AssetStatusTransitionService $transition,
    ) {
        view()->share('userId', request()->route('userId') ?? (auth()->check() ? auth()->id() : null));
    }

    public function index(Request $request)
    {
        $query = Asset::with(['room', 'room.school', 'category']);

        if (! $this->canViewAll($request)) {
            $query = $this->scopeToSchool($request, $query);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q
                ->where('asset_name', 'like', "%{$s}%")
                ->orWhere('asset_code', 'like', "%{$s}%")
                ->orWhere('brand', 'like', "%{$s}%"));
        }
        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        $asets = $query->orderBy('asset_name')->paginate(15)->withQueryString();
        $rooms = AssetRoom::where('is_active', true)->orderBy('room_name')->get();
        $categories = AssetCategory::where('is_active', true)->orderBy('name')->get();

        return view('sarpras.aset.index', compact('asets', 'rooms', 'categories'));
    }

    public function create(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $rooms = AssetRoom::where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('room_name')->get();
        $categories = AssetCategory::where('is_active', true)->orderBy('name')->get();

        return view('sarpras.aset.create', compact('rooms', 'categories', 'schoolId'));
    }

    public function store(AsetStoreRequest $request)
    {
        $validated = $request->validated();

        $validated['is_bookable'] = $request->boolean('is_bookable', true);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['status'] = $aset->status; // preserve existing status, must go through workflow
        $validated['created_by'] = auth()->id();
        $validated['current_value'] = $validated['acquisition_price'] ?? null;

        if (! empty($validated['room_id'])) {
            $room = AssetRoom::find($validated['room_id']);
            $validated['work_unit_id'] = $room->work_unit_id;
            $validated['school_id'] = $room->school_id;
        }

        $asset = Asset::create($validated);

        // Asset lifecycle event
        try {
            $this->eventLogger->logCreated($asset, auth()->id());
        } catch (\Throwable $e) {
            Log::error('AssetEventLogger::logCreated failed for asset '.$asset->id.': '.$e->getMessage());
        }

        // Handle multiple photo uploads
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('assets/photos', 'public');
                $assetPhoto = AssetPhoto::create([
                    'asset_id' => $asset->id,
                    'photo_path' => $path,
                    'uploaded_by' => auth()->id(),
                ]);
                try {
                    $this->eventLogger->log($asset, 'photo_uploaded', [
                        'photo_id' => $assetPhoto->id,
                        'photo_path' => $path,
                    ], auth()->id());
                } catch (\Throwable $e) {
                    Log::error('AssetEventLogger::photo_uploaded failed for asset '.$asset->id.': '.$e->getMessage());
                }
            }
        }

        $this->bumpDashboardCache();

        return redirect()->route('sarpras.aset.index')
            ->with('success', 'Aset berhasil ditambahkan.');
    }

    public function show(Request $request, string $id)
    {
        $aset = Asset::with(['room', 'room.school', 'category', 'creator', 'photos'])
            ->findOrFail($id);
        $this->authorizeAssetAccess($aset, $request);

        // Riwayat peminjaman
        $riwayatPinjaman = $aset->loans()->with('borrower')->orderBy('created_at', 'desc')->limit(10)->get();

        // Riwayat maintenance
        $riwayatMaintenance = $aset->maintenanceLogs()->with('performer')->orderBy('maintenance_date', 'desc')->limit(10)->get();

        // Riwayat perpindahan
        $riwayatPerpindahan = $aset->locationHistories()->with(['fromRoom', 'toRoom', 'mover'])->orderBy('moved_date', 'desc')->limit(10)->get();

        return view('sarpras.aset.show', compact('aset', 'riwayatPinjaman', 'riwayatMaintenance', 'riwayatPerpindahan'));
    }

    public function edit(Request $request, string $id)
    {
        $aset = Asset::with(['room', 'room.school', 'category', 'creator'])->findOrFail($id);
        $this->authorizeAssetAccess($aset, $request);

        $schoolId = $request->attributes->get('schoolContextId');
        $rooms = AssetRoom::where('is_active', true)
            ->when($aset->school_id, fn ($q) => $q->where('school_id', $aset->school_id))
            ->orderBy('room_name')->get();
        $categories = AssetCategory::where('is_active', true)->orderBy('name')->get();

        return view('sarpras.aset.edit', compact('aset', 'rooms', 'categories'));
    }

    public function update(AsetUpdateRequest $request, string $id)
    {
        $aset = Asset::with(['room', 'creator'])->findOrFail($id);
        $this->authorizeAssetAccess($aset, $request);

        $validated = $request->validated();

        $validated['is_bookable'] = $request->boolean('is_bookable', true);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['status'] = $aset->status; // preserve existing status, must go through workflow
        $validated['current_value'] = $validated['acquisition_price'] ?? null;

        if (! empty($validated['room_id'])) {
            $room = AssetRoom::find($validated['room_id']);
            $validated['work_unit_id'] = $room->work_unit_id;
            $validated['school_id'] = $room->school_id;
        }

        $statusSebelum = $aset->status;
        $kondisiSebelum = $aset->condition;
        $lokasiSebelum = $aset->room_id;

        $aset->update($validated);

        // Enforce state machine on status changes — prevent direct status mutations
        if (($validated['status'] ?? null) !== null) {
            $this->transition->transition($aset, $validated['status'], auth()->id(), 'Admin status change');
        }

        // Asset lifecycle event — capture status / condition / location changes
        try {
            $perubahan = [];
            if ($statusSebelum !== $aset->status) {
                $perubahan['status'] = ['from' => $statusSebelum, 'to' => $aset->status];
            }
            if ($kondisiSebelum !== $aset->condition) {
                $perubahan['condition'] = ['from' => $kondisiSebelum, 'to' => $aset->condition];
            }
            if ($lokasiSebelum !== $aset->room_id) {
                $perubahan['room_id'] = ['from' => $lokasiSebelum, 'to' => $aset->room_id];
            }
            if (! empty($perubahan)) {
                $this->eventLogger->log($aset, 'status_change', $perubahan, auth()->id());
            } else {
                $this->eventLogger->log($aset, 'asset_updated', [
                    'updated_fields' => array_keys($validated),
                ], auth()->id());
            }
        } catch (\Throwable $e) {
            Log::error('AssetEventLogger::status_change failed for asset '.$aset->id.': '.$e->getMessage());
        }

        // Handle new photo uploads
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('assets/photos', 'public');
                $assetPhoto = AssetPhoto::create([
                    'asset_id' => $aset->id,
                    'photo_path' => $path,
                    'caption' => $request->input('photo_caption'),
                    'uploaded_by' => auth()->id(),
                ]);
                try {
                    $this->eventLogger->log($aset, 'photo_uploaded', [
                        'photo_id' => $assetPhoto->id,
                        'photo_path' => $path,
                    ], auth()->id());
                } catch (\Throwable $e) {
                    Log::error('AssetEventLogger::photo_uploaded failed for asset '.$aset->id.': '.$e->getMessage());
                }
            }
        }

        $this->bumpDashboardCache();

        return redirect()->route('sarpras.aset.show', $aset->id)
            ->with('success', 'Aset berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id)
    {
        $aset = Asset::findOrFail($id);
        $this->authorizeAssetAccess($aset, $request);

        // Asset lifecycle event — before delete
        try {
            $this->eventLogger->log($aset, 'asset_destroyed', [
                'asset_name' => $aset->asset_name,
                'asset_code' => $aset->asset_code,
                'deleted_at' => now()->toDateTimeString(),
            ], auth()->id());
        } catch (\Throwable $e) {
            Log::error('AssetEventLogger::asset_destroyed failed for asset '.$aset->id.': '.$e->getMessage());
        }

        $aset->delete();
        $this->bumpDashboardCache();

        return redirect()->route('sarpras.aset.index')
            ->with('success', 'Aset berhasil dihapus.');
    }

    public function importForm(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $rooms = AssetRoom::where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('room_name')->get();
        $categories = AssetCategory::where('is_active', true)->orderBy('name')->get();

        return view('sarpras.aset.import', compact('rooms', 'categories', 'schoolId'));
    }

    public function importProcess(AssetImportRequest $request)
    {

        $schoolId = $request->attributes->get('schoolContextId');

        try {
            $import = new AssetImport(auth()->id(), $schoolId);
            Excel::import($import, $request->file('file'));

            $errors = $import->getErrors();
            $createdCount = $import->getSuccessCount();

            if ($createdCount > 0 && empty($errors)) {
                return redirect()->route('sarpras.aset.index')
                    ->with('success', "Berhasil mengimport {$createdCount} aset.");
            }
            if ($createdCount > 0) {
                return redirect()->route('sarpras.aset.index')
                    ->with('success', "Berhasil mengimport {$createdCount} aset. ".count($errors).' baris dilewati karena error.')
                    ->with('import_errors', $errors);
            }

            return redirect()->route('sarpras.aset.import')
                ->with('error', 'Gagal mengimport. Silakan perbaiki file sesuai panduan.')
                ->with('import_errors', $errors);

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errMsgs = [];
            foreach ($failures as $failure) {
                $errMsgs[] = "Baris {$failure->row()}: ".implode(', ', $failure->errors());
            }

            return redirect()->route('sarpras.aset.import')
                ->with('error', 'Validasi gagal. Perbaiki data Excel Anda.')
                ->with('import_errors', $errMsgs);

        } catch (\Throwable $e) {
            Log::error('AssetImport error: '.$e->getMessage());

            return redirect()->route('sarpras.aset.import')
                ->with('error', 'Gagal memproses file: '.$e->getMessage());
        }
    }

    public function template(Request $request)
    {
        $exporter = new AssetTemplateExport;

        return $exporter->download('template_import_aset.xlsx');
    }

    // === AJAX ===
    public function addPhoto(AssetPhotoUploadRequest $request, string $id)
    {
        $aset = Asset::findOrFail($id);
        $this->authorizeAssetAccess($aset, $request);

        $validated = $request->validated();

        $photo = DB::transaction(function () use ($request, $aset) {
            $path = $request->file('photo')->store('assets/photos', 'public');

            return AssetPhoto::create([
                'asset_id' => $aset->id,
                'photo_path' => $path,
                'caption' => $request->caption,
                'uploaded_by' => auth()->id(),
            ]);
        });

        try {
            $this->eventLogger->log($aset, 'photo_uploaded', [
                'photo_id' => $photo->id,
                'photo_path' => $photo->photo_path,
            ], auth()->id());
        } catch (\Throwable $e) {
            Log::error('AssetEventLogger::photo_uploaded failed for asset '.$aset->id.': '.$e->getMessage());
        }

        return $this->ok(['photo' => $photo], 'Foto berhasil diunggah.');
    }

    public function deletePhoto(Request $request, string $id, string $photoId)
    {
        $aset = Asset::findOrFail($id);
        $this->authorizeAssetAccess($aset, $request);

        DB::transaction(function () use ($aset, $photoId) {
            $photo = AssetPhoto::where('asset_id', $aset->id)->where('id', $photoId)->firstOrFail();
            \Storage::disk('public')->delete($photo->photo_path);
            $photo->delete();
        });

        return $this->ok(null, 'Foto dihapus.');
    }
}
