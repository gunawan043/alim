<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\Asset;
use App\Models\AssetRoom;
use App\Models\AssetCategory;
use App\Models\AssetPhoto;
use App\Models\School;
use App\Imports\AssetImport;
use App\Exports\AssetTemplateExport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class SarprasAsetController extends SarprasBaseController
{
    public function __construct()
    {
        view()->share('userId', request()->route('userId') ?? (auth()->check() ? auth()->id() : null));
    }


    public function index(Request $request)
    {
        $query = Asset::with(['room', 'room.school', 'category']);

        if (!$this->canViewAll($request)) {
            $query = $this->scopeToSchool($request, $query);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
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
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('room_name')->get();
        $categories = AssetCategory::where('is_active', true)->orderBy('name')->get();

        return view('sarpras.aset.create', compact('rooms', 'categories', 'schoolId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id'                => 'nullable|exists:asset_rooms,id',
            'asset_category_id'     => 'required|exists:asset_categories,id',
            'asset_name'             => 'required|string|max:191',
            'asset_code'            => 'nullable|string|max:50|unique:assets,asset_code',
            'brand'                 => 'nullable|string|max:100',
            'model'                 => 'nullable|string|max:100',
            'serial_number'         => 'nullable|string|max:100',
            'color'                 => 'nullable|string|max:50',
            'specification'         => 'nullable|string',
            'acquisition_date'      => 'nullable|date',
            'acquisition_price'    => 'nullable|numeric|min:0',
            'acquisition_source'    => 'nullable|in:' . implode(',', Asset::ACQUISITION_SOURCE_OPTIONS),
            'funding_source'        => 'nullable|string|max:100',
            'condition'             => 'required|in:' . implode(',', Asset::CONDITION_OPTIONS),
            'status'                => 'nullable|in:' . implode(',', Asset::STATUS_OPTIONS),
            'is_bookable'           => 'boolean',
            'notes'                 => 'nullable|string',
            'is_active'             => 'boolean',
        ]);

        $validated['is_bookable'] = $request->boolean('is_bookable', true);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['status'] = $validated['status'] ?? 'tersedia';
        $validated['created_by'] = auth()->id();
        $validated['current_value'] = $validated['acquisition_price'] ?? null;

        if (!empty($validated['room_id'])) {
            $room = AssetRoom::find($validated['room_id']);
            $validated['work_unit_id'] = $room->work_unit_id;
            $validated['school_id'] = $room->school_id;
        }

        $asset = Asset::create($validated);

        // Handle multiple photo uploads
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('assets/photos', 'public');
                AssetPhoto::create([
                    'asset_id'    => $asset->id,
                    'photo_path' => $path,
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }

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
        $aset = Asset::findOrFail($id);
        $this->authorizeAssetAccess($aset, $request);

        $schoolId = $request->attributes->get('schoolContextId');
        $rooms = AssetRoom::where('is_active', true)
            ->when($aset->school_id, fn($q) => $q->where('school_id', $aset->school_id))
            ->orderBy('room_name')->get();
        $categories = AssetCategory::where('is_active', true)->orderBy('name')->get();

        return view('sarpras.aset.edit', compact('aset', 'rooms', 'categories'));
    }

    public function update(Request $request, string $id)
    {
        $aset = Asset::findOrFail($id);
        $this->authorizeAssetAccess($aset, $request);

        $validated = $request->validate([
            'room_id'                => 'nullable|exists:asset_rooms,id',
            'asset_category_id'     => 'required|exists:asset_categories,id',
            'asset_name'             => 'required|string|max:191',
            'asset_code'            => ['nullable', 'string', 'max:50', Rule::unique('assets', 'asset_code')->ignore($aset->id)],
            'brand'                 => 'nullable|string|max:100',
            'model'                 => 'nullable|string|max:100',
            'serial_number'         => 'nullable|string|max:100',
            'color'                 => 'nullable|string|max:50',
            'specification'         => 'nullable|string',
            'acquisition_date'      => 'nullable|date',
            'acquisition_price'    => 'nullable|numeric|min:0',
            'acquisition_source'    => 'nullable|in:' . implode(',', Asset::ACQUISITION_SOURCE_OPTIONS),
            'funding_source'        => 'nullable|string|max:100',
            'condition'             => 'required|in:' . implode(',', Asset::CONDITION_OPTIONS),
            'status'                => 'nullable|in:' . implode(',', Asset::STATUS_OPTIONS),
            'is_bookable'           => 'boolean',
            'notes'                 => 'nullable|string',
            'is_active'             => 'boolean',
        ]);

        $validated['is_bookable'] = $request->boolean('is_bookable', true);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['status'] = $validated['status'] ?? 'tersedia';
        $validated['current_value'] = $validated['acquisition_price'] ?? null;

        if (!empty($validated['room_id'])) {
            $room = AssetRoom::find($validated['room_id']);
            $validated['work_unit_id'] = $room->work_unit_id;
            $validated['school_id'] = $room->school_id;
        }

        $aset->update($validated);

        // Handle new photo uploads
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('assets/photos', 'public');
                AssetPhoto::create([
                    'asset_id'    => $aset->id,
                    'photo_path' => $path,
                    'caption'    => $request->input('photo_caption'),
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }

        return redirect()->route('sarpras.aset.show', $aset->id)
            ->with('success', 'Aset berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id)
    {
        $aset = Asset::findOrFail($id);
        $this->authorizeAssetAccess($aset, $request);

        $aset->delete();

        return redirect()->route('sarpras.aset.index')
            ->with('success', 'Aset berhasil dihapus.');
    }

    public function importForm(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $rooms = AssetRoom::where('is_active', true)
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('room_name')->get();
        $categories = AssetCategory::where('is_active', true)->orderBy('name')->get();

        return view('sarpras.aset.import', compact('rooms', 'categories', 'schoolId'));
    }

    public function importProcess(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file.required' => 'File Excel wajib diupload.',
            'file.mimes'    => 'File harus berekstensi .xlsx, .xls, atau .csv.',
            'file.max'      => 'Ukuran file maksimal 5MB.',
        ]);

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
                    ->with('success', "Berhasil mengimport {$createdCount} aset. " . count($errors) . " baris dilewati karena error.")
                    ->with('import_errors', $errors);
            }
            return redirect()->route('sarpras.aset.import')
                ->with('error', 'Gagal mengimport. Silakan perbaiki file sesuai panduan.')
                ->with('import_errors', $errors);

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errMsgs = [];
            foreach ($failures as $failure) {
                $errMsgs[] = "Baris {$failure->row()}: " . implode(', ', $failure->errors());
            }
            return redirect()->route('sarpras.aset.import')
                ->with('error', 'Validasi gagal. Perbaiki data Excel Anda.')
                ->with('import_errors', $errMsgs);

        } catch (\Throwable $e) {
            Log::error('AssetImport error: ' . $e->getMessage());
            return redirect()->route('sarpras.aset.import')
                ->with('error', 'Gagal memproses file: ' . $e->getMessage());
        }
    }

    public function template(Request $request)
    {
        $exporter = new AssetTemplateExport();
        return $exporter->download('template_import_aset.xlsx');
    }

    // === AJAX ===
    public function addPhoto(Request $request, string $id)
    {
        $aset = Asset::findOrFail($id);
        $this->authorizeAssetAccess($aset, $request);

        $request->validate([
            'photo' => 'required|image|max:5120',
            'caption' => 'nullable|string|max:255',
        ]);

        $path = $request->file('photo')->store('assets/photos', 'public');
        $photo = AssetPhoto::create([
            'asset_id'    => $aset->id,
            'photo_path' => $path,
            'caption'    => $request->caption,
            'uploaded_by' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'photo' => $photo]);
    }

    public function deletePhoto(Request $request, string $id, string $photoId)
    {
        $aset = Asset::findOrFail($id);
        $this->authorizeAssetAccess($aset, $request);

        $photo = AssetPhoto::where('asset_id', $aset->id)->where('id', $photoId)->firstOrFail();
        \Storage::disk('public')->delete($photo->photo_path);
        $photo->delete();

        return response()->json(['success' => true]);
    }
}