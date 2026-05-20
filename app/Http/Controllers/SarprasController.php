<?php

namespace App\Http\Controllers;

use App\Models\AssetRoom;
use App\Models\Asset;
use App\Models\AssetBuilding;
use App\Models\AssetCategory;
use App\Models\School;
use App\Models\GtkWorkUnit;
use App\Imports\AssetImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AssetTemplateExport;

class SarprasController extends Controller
{
    // ========================================================================
    // AKSES KONTROL
    // - sarpras_all_access / Super Admin / Admin Sarpras → semua data
    // - Unit Rumah Tangga (PAH-ADM-003) → semua data
    // - User biasa → scoped ke schoolContextId
    // ========================================================================

    private function canViewAll(Request $request): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->can('sarpras_all_access')) return true;
        if ($user->can('inventory_view')) return true; // fallback

        $hasRumahTangga = GtkWorkUnit::where('user_id', $user->id)
            ->whereHas('workUnit', fn($q) => $q->where('code', 'PAH-ADM-003'))
            ->exists();

        return $hasRumahTangga;
    }

    private function scopeToSchool(Request $request, $query)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        return $query;
    }

    private function authorizeRoomAccess(AssetRoom $room, Request $request): void
    {
        if ($this->canViewAll($request)) return;
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $room->school_id !== $schoolId) {
            abort(403, 'Anda tidak memiliki akses ke ruang ini.');
        }
    }

    // ========================================================================
    // GEDUNG
    // ========================================================================

    public function gedungIndex(Request $request, string $userId)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $query = AssetBuilding::with('school');

        if (!$this->canViewAll($request)) {
            $query = $this->scopeToSchool($request, $query);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('building_name', 'like', "%{$s}%")->orWhere('building_code', 'like', "%{$s}%"));
        }
        if ($request->filled('building_type')) {
            $query->where('building_type', $request->building_type);
        }
        if ($request->filled('condition')) {
            $query->where('structure_condition', $request->condition);
        }
        if ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }

        $gedungs = $query->orderBy('building_name')->paginate(15)->withQueryString();
        $schools = School::orderBy('name')->get();

        return view('sarpras.gedung.index', compact('gedungs', 'schools', 'userId'));
    }

    public function gedungCreate(Request $request, string $userId)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $schoolId = $request->attributes->get('schoolContextId');
        $schools = $schoolId ? School::where('id', $schoolId)->get() : School::orderBy('name')->get();

        return view('sarpras.gedung.create', compact('schools', 'userId', 'schoolId'));
    }

    public function gedungStore(Request $request, string $userId)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $validated = $request->validate([
            'school_id'             => 'required|exists:schools,id',
            'building_name'         => 'required|string|max:191',
            'building_code'        => 'nullable|string|max:30|unique:asset_buildings,building_code',
            'building_type'        => 'required|in:' . implode(',', AssetBuilding::BUILDING_TYPE_OPTIONS),
            'total_floors'         => 'nullable|integer|min:1|max:20',
            'building_area'        => 'nullable|numeric|min:0',
            'build_year'           => 'nullable|integer|min:1900|max:2100',
            'structure_condition'  => 'required|in:' . implode(',', AssetBuilding::CONDITION_OPTIONS),
            'ownership_status'     => 'nullable|in:' . implode(',', AssetBuilding::OWNERSHIP_OPTIONS),
            'is_active'            => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['created_by'] = auth()->id();

        // work_unit_id dari school
        $school = School::find($validated['school_id']);
        $validated['work_unit_id'] = $school->work_unit_id;

        AssetBuilding::create($validated);

        return redirect()
            ->route('sarpras.gedung.index', ['userId' => $userId])
            ->with('success', 'Gedung berhasil ditambahkan.');
    }

    public function gedungShow(string $userId, string $id)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $gedung = AssetBuilding::with(['school', 'rooms'])->findOrFail($id);
        $this->authorizeBuildingAccess($gedung, request());

        return view('sarpras.gedung.show', compact('gedung', 'userId'));
    }

    public function gedungEdit(string $userId, string $id)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $gedung = AssetBuilding::findOrFail($id);
        $this->authorizeBuildingAccess($gedung, request());

        $schoolId = request()->attributes->get('schoolContextId');
        $schools = $schoolId ? School::where('id', $schoolId)->get() : School::orderBy('name')->get();

        return view('sarpras.gedung.edit', compact('gedung', 'schools', 'userId'));
    }

    public function gedungUpdate(Request $request, string $userId, string $id)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $gedung = AssetBuilding::findOrFail($id);
        $this->authorizeBuildingAccess($gedung, $request);

        $validated = $request->validate([
            'school_id'             => 'required|exists:schools,id',
            'building_name'         => 'required|string|max:191',
            'building_code'        => ['nullable', 'string', 'max:30', Rule::unique('asset_buildings', 'building_code')->ignore($gedung->id)],
            'building_type'        => 'required|in:' . implode(',', AssetBuilding::BUILDING_TYPE_OPTIONS),
            'total_floors'         => 'nullable|integer|min:1|max:20',
            'building_area'        => 'nullable|numeric|min:0',
            'build_year'           => 'nullable|integer|min:1900|max:2100',
            'structure_condition'  => 'required|in:' . implode(',', AssetBuilding::CONDITION_OPTIONS),
            'ownership_status'     => 'nullable|in:' . implode(',', AssetBuilding::OWNERSHIP_OPTIONS),
            'is_active'            => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $gedung->update($validated);

        return redirect()
            ->route('sarpras.gedung.show', ['userId' => $userId, 'id' => $gedung->id])
            ->with('success', 'Gedung berhasil diperbarui.');
    }

    public function gedungDestroy(string $userId, string $id)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $gedung = AssetBuilding::with('rooms')->findOrFail($id);
        $this->authorizeBuildingAccess($gedung, request());

        if ($gedung->rooms()->count() > 0) {
            return back()->with('error', 'Gedung tidak bisa dihapus karena masih memiliki ruang.');
        }

        $gedung->delete();

        return redirect()
            ->route('sarpras.gedung.index', ['userId' => $userId])
            ->with('success', 'Gedung berhasil dihapus.');
    }

    private function authorizeBuildingAccess(AssetBuilding $building, Request $request): void
    {
        if ($this->canViewAll($request)) return;
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $building->school_id !== $schoolId) {
            abort(403, 'Anda tidak memiliki akses ke gedung ini.');
        }
    }

    // ========================================================================
    // RUANG
    // ========================================================================

    public function ruangIndex(Request $request, string $userId)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $query = AssetRoom::with(['school', 'building']);

        if (!$this->canViewAll($request)) {
            $query = $this->scopeToSchool($request, $query);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('room_name', 'like', "%{$s}%")->orWhere('room_code', 'like', "%{$s}%"));
        }
        if ($request->filled('room_type')) {
            $query->where('room_type', $request->room_type);
        }
        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }
        if ($request->filled('is_bookable')) {
            $query->where('is_bookable', $request->boolean('is_bookable'));
        }

        $ruangs = $query->orderBy('room_name')->paginate(15)->withQueryString();
        $schools = School::orderBy('name')->get();

        return view('sarpras.ruang.index', compact('ruangs', 'schools', 'userId'));
    }

    public function ruangCreate(Request $request, string $userId)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $schoolId = $request->attributes->get('schoolContextId');
        $schools = $schoolId ? School::where('id', $schoolId)->get() : School::orderBy('name')->get();
        $gedungs = AssetBuilding::where('is_active', true)
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('building_name')->get();

        return view('sarpras.ruang.create', compact('schools', 'gedungs', 'userId', 'schoolId'));
    }

    public function ruangStore(Request $request, string $userId)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $validated = $request->validate([
            'school_id'                   => 'required|exists:schools,id',
            'building_id'                 => 'nullable|exists:asset_buildings,id',
            'room_name'                   => 'required|string|max:191',
            'room_code'                   => 'nullable|string|max:30|unique:asset_rooms,room_code',
            'room_type'                   => 'required|in:' . implode(',', AssetRoom::ROOM_TYPE_OPTIONS),
            'floor'                       => 'nullable|integer|min:0|max:20',
            'room_area'                   => 'nullable|numeric|min:0',
            'capacity'                    => 'nullable|integer|min:0',
            'condition'                   => 'required|in:' . implode(',', AssetRoom::CONDITION_OPTIONS),
            'facilities'                  => 'nullable|string',
            'is_bookable'                => 'boolean',
            'booking_requires_approval'   => 'boolean',
            'is_active'                  => 'boolean',
            'notes'                      => 'nullable|string',
        ]);

        $validated['is_bookable'] = $request->boolean('is_bookable', false);
        $validated['booking_requires_approval'] = $request->boolean('booking_requires_approval', true);
        $validated['is_active'] = $request->boolean('is_active', true);

        // work_unit_id dari school
        $school = School::find($validated['school_id']);
        $validated['work_unit_id'] = $school->work_unit_id;

        AssetRoom::create($validated);

        return redirect()
            ->route('sarpras.ruang.index', ['userId' => $userId])
            ->with('success', 'Ruang berhasil ditambahkan.');
    }

    public function ruangShow(string $userId, string $id)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $ruang = AssetRoom::with(['school', 'building', 'assets'])->findOrFail($id);
        $this->authorizeRoomAccess($ruang, request());

        return view('sarpras.ruang.show', compact('ruang', 'userId'));
    }

    public function ruangEdit(string $userId, string $id)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $ruang = AssetRoom::findOrFail($id);
        $this->authorizeRoomAccess($ruang, request());

        $schoolId = request()->attributes->get('schoolContextId');
        $schools = $schoolId ? School::where('id', $schoolId)->get() : School::orderBy('name')->get();
        $gedungs = AssetBuilding::where('is_active', true)
            ->when($ruang->school_id, fn($q) => $q->where('school_id', $ruang->school_id))
            ->orderBy('building_name')->get();

        return view('sarpras.ruang.edit', compact('ruang', 'schools', 'gedungs', 'userId'));
    }

    public function ruangUpdate(Request $request, string $userId, string $id)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $ruang = AssetRoom::findOrFail($id);
        $this->authorizeRoomAccess($ruang, $request);

        $validated = $request->validate([
            'school_id'                   => 'required|exists:schools,id',
            'building_id'                 => 'nullable|exists:asset_buildings,id',
            'room_name'                   => 'required|string|max:191',
            'room_code'                   => ['nullable', 'string', 'max:30', Rule::unique('asset_rooms', 'room_code')->ignore($ruang->id)],
            'room_type'                   => 'required|in:' . implode(',', AssetRoom::ROOM_TYPE_OPTIONS),
            'floor'                       => 'nullable|integer|min:0|max:20',
            'room_area'                   => 'nullable|numeric|min:0',
            'capacity'                    => 'nullable|integer|min:0',
            'condition'                   => 'required|in:' . implode(',', AssetRoom::CONDITION_OPTIONS),
            'facilities'                  => 'nullable|string',
            'is_bookable'                => 'boolean',
            'booking_requires_approval'   => 'boolean',
            'is_active'                  => 'boolean',
            'notes'                      => 'nullable|string',
        ]);

        $validated['is_bookable'] = $request->boolean('is_bookable', false);
        $validated['booking_requires_approval'] = $request->boolean('booking_requires_approval', true);
        $validated['is_active'] = $request->boolean('is_active', true);

        $ruang->update($validated);

        return redirect()
            ->route('sarpras.ruang.show', ['userId' => $userId, 'id' => $ruang->id])
            ->with('success', 'Ruang berhasil diperbarui.');
    }

    public function ruangDestroy(string $userId, string $id)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $ruang = AssetRoom::with('assets')->findOrFail($id);
        $this->authorizeRoomAccess($ruang, request());

        if ($ruang->assets()->count() > 0) {
            return back()->with('error', 'Ruang tidak bisa dihapus karena masih memiliki aset/inventaris.');
        }

        $ruang->delete();

        return redirect()
            ->route('sarpras.ruang.index', ['userId' => $userId])
            ->with('success', 'Ruang berhasil dihapus.');
    }

    // ========================================================================
    // ASET / INVENTARIS (SARANA PRASARANA)
    // ========================================================================

    public function asetIndex(Request $request, string $userId)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $query = Asset::with(['room', 'room.school', 'category']);

        if (!$this->canViewAll($request)) {
            $query = $this->scopeToSchool($request, $query);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('asset_name', 'like', "%{$s}%")->orWhere('asset_code', 'like', "%{$s}%")->orWhere('brand', 'like', "%{$s}%"));
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

        return view('sarpras.aset.index', compact('asets', 'rooms', 'categories', 'userId'));
    }

    public function asetCreate(Request $request, string $userId)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $schoolId = $request->attributes->get('schoolContextId');
        $rooms = AssetRoom::where('is_active', true)
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('room_name')->get();
        $categories = AssetCategory::where('is_active', true)->orderBy('name')->get();

        return view('sarpras.aset.create', compact('rooms', 'categories', 'userId', 'schoolId'));
    }

    public function asetStore(Request $request, string $userId)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $validated = $request->validate([
            'room_id'               => 'nullable|exists:asset_rooms,id',
            'asset_category_id'    => 'required|exists:asset_categories,id',
            'asset_name'           => 'required|string|max:191',
            'asset_code'           => 'nullable|string|max:50|unique:assets,asset_code',
            'brand'                => 'nullable|string|max:100',
            'model'                => 'nullable|string|max:100',
            'serial_number'        => 'nullable|string|max:100',
            'color'                => 'nullable|string|max:50',
            'specification'        => 'nullable|string',
            'acquisition_date'      => 'nullable|date',
            'acquisition_price'    => 'nullable|numeric|min:0',
            'acquisition_source'    => 'nullable|in:' . implode(',', Asset::ACQUISITION_SOURCE_OPTIONS),
            'funding_source'       => 'nullable|string|max:100',
            'condition'           => 'required|in:' . implode(',', Asset::CONDITION_OPTIONS),
            'status'               => 'nullable|in:' . implode(',', Asset::STATUS_OPTIONS),
            'is_bookable'         => 'boolean',
            'is_active'           => 'boolean',
            'notes'               => 'nullable|string',
        ]);

        $validated['is_bookable'] = $request->boolean('is_bookable', true);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['status'] = $validated['status'] ?? 'tersedia';
        $validated['created_by'] = auth()->id();

        // work_unit_id & school_id dari room jika ada
        if (!empty($validated['room_id'])) {
            $room = AssetRoom::find($validated['room_id']);
            $validated['work_unit_id'] = $room->work_unit_id;
            $validated['school_id'] = $room->school_id;
        }

        Asset::create($validated);

        return redirect()
            ->route('sarpras.aset.index', ['userId' => $userId])
            ->with('success', 'Aset berhasil ditambahkan.');
    }

    public function asetShow(string $userId, string $id)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $aset = Asset::with(['room', 'room.school', 'category', 'creator'])->findOrFail($id);
        $this->authorizeAsetAccess($aset, request());

        return view('sarpras.aset.show', compact('aset', 'userId'));
    }

    public function asetEdit(string $userId, string $id)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $aset = Asset::findOrFail($id);
        $this->authorizeAsetAccess($aset, request());

        $schoolId = request()->attributes->get('schoolContextId');
        $rooms = AssetRoom::where('is_active', true)
            ->when($aset->school_id, fn($q) => $q->where('school_id', $aset->school_id))
            ->orderBy('room_name')->get();
        $categories = AssetCategory::where('is_active', true)->orderBy('name')->get();

        return view('sarpras.aset.edit', compact('aset', 'rooms', 'categories', 'userId'));
    }

    public function asetUpdate(Request $request, string $userId, string $id)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $aset = Asset::findOrFail($id);
        $this->authorizeAsetAccess($aset, $request);

        $validated = $request->validate([
            'room_id'               => 'nullable|exists:asset_rooms,id',
            'asset_category_id'    => 'required|exists:asset_categories,id',
            'asset_name'           => 'required|string|max:191',
            'asset_code'           => ['nullable', 'string', 'max:50', Rule::unique('assets', 'asset_code')->ignore($aset->id)],
            'brand'                => 'nullable|string|max:100',
            'model'                => 'nullable|string|max:100',
            'serial_number'        => 'nullable|string|max:100',
            'color'                => 'nullable|string|max:50',
            'specification'        => 'nullable|string',
            'acquisition_date'      => 'nullable|date',
            'acquisition_price'    => 'nullable|numeric|min:0',
            'acquisition_source'    => 'nullable|in:' . implode(',', Asset::ACQUISITION_SOURCE_OPTIONS),
            'funding_source'       => 'nullable|string|max:100',
            'condition'           => 'required|in:' . implode(',', Asset::CONDITION_OPTIONS),
            'status'               => 'nullable|in:' . implode(',', Asset::STATUS_OPTIONS),
            'is_bookable'         => 'boolean',
            'is_active'           => 'boolean',
            'notes'               => 'nullable|string',
        ]);

        $validated['is_bookable'] = $request->boolean('is_bookable', true);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['status'] = $validated['status'] ?? 'tersedia';

        if (!empty($validated['room_id'])) {
            $room = AssetRoom::find($validated['room_id']);
            $validated['work_unit_id'] = $room->work_unit_id;
            $validated['school_id'] = $room->school_id;
        }

        $aset->update($validated);

        return redirect()
            ->route('sarpras.aset.show', ['userId' => $userId, 'id' => $aset->id])
            ->with('success', 'Aset berhasil diperbarui.');
    }

    public function asetDestroy(string $userId, string $id)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $aset = Asset::findOrFail($id);
        $this->authorizeAsetAccess($aset, request());

        $aset->delete();

        return redirect()
            ->route('sarpras.aset.index', ['userId' => $userId])
            ->with('success', 'Aset berhasil dihapus.');
    }

    // ─── IMPORT ───────────────────────────────────────────────────────────

    public function asetImportForm(Request $request, string $userId)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $schoolId = $request->attributes->get('schoolContextId');
        $rooms = AssetRoom::where('is_active', true)
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('room_name')->get();
        $categories = AssetCategory::where('is_active', true)->orderBy('name')->get();

        $forcedRoom = null;
        if ($request->filled('room_id')) {
            $forcedRoom = AssetRoom::find($request->room_id);
            if ($forcedRoom) {
                $this->authorizeRoomAccess($forcedRoom, $request);
            }
        }

        return view('sarpras.aset.import', compact('rooms', 'categories', 'userId', 'schoolId', 'forcedRoom'));
    }

    public function asetImportProcess(Request $request, string $userId)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file.required' => 'File Excel wajib diupload.',
            'file.mimes'    => 'File harus berekstensi .xlsx, .xls, atau .csv.',
            'file.max'      => 'Ukuran file maksimal 5MB.',
        ]);

        $schoolId = $request->attributes->get('schoolContextId');
        $forcedRoomId = $request->input('room_id');

        try {
            $import = new AssetImport($userId, $schoolId, $forcedRoomId);
            Excel::import($import, $request->file('file'));

            $errors = $import->getErrors();
            $createdCount = $import->getSuccessCount();

            if ($createdCount > 0 && empty($errors)) {
                return redirect()
                    ->route('sarpras.aset.index', ['userId' => $userId])
                    ->with('success', "Berhasil mengimport {$createdCount} aset.");
            }

            // Some succeeded, some failed
            if ($createdCount > 0) {
                return redirect()
                    ->route('sarpras.aset.index', ['userId' => $userId])
                    ->with('success', "Berhasil mengimport {$createdCount} aset. " . count($errors) . " baris dilewati karena error.")
                    ->with('import_errors', $errors);
            }

            // All failed
            return redirect()
                ->route('sarpras.aset.import', ['userId' => $userId])
                ->with('error', 'Gagal mengimport. Silakan perbaiki file sesuai panduan.')
                ->with('import_errors', $errors);

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errMsgs = [];
            foreach ($failures as $failure) {
                $errMsgs[] = "Baris {$failure->row()}: " . implode(', ', $failure->errors());
            }
            return redirect()
                ->route('sarpras.aset.import', ['userId' => $userId])
                ->with('error', 'Validasi gagal. Perbaiki data Excel Anda.')
                ->with('import_errors', $errMsgs);

        } catch (\Throwable $e) {
            Log::error('AssetImport error: ' . $e->getMessage());
            return redirect()
                ->route('sarpras.aset.import', ['userId' => $userId])
                ->with('error', 'Gagal memproses file: ' . $e->getMessage());
        }
    }

    public function asetTemplate(Request $request, string $userId)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $roomName = null;
        if ($request->filled('room_id')) {
            $room = AssetRoom::find($request->room_id);
            $roomName = $room?->room_name;
        }

        $exporter = new AssetTemplateExport($roomName);
        $filename = $roomName
            ? 'template_import_' . \Illuminate\Support\Str::slug($roomName, '_') . '.xlsx'
            : 'template_import_aset.xlsx';

        return $exporter->download($filename);
    }

    // ─── KATEGORI (AJAX) ───────────────────────────────────────────────

    public function kategoriStore(Request $request, string $userId)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $validated = $request->validate([
            'name'  => 'required|string|max:191|unique:asset_categories,name',
            'code'  => 'nullable|string|max:30|unique:asset_categories,code',
            'asset_type' => 'required|in:tidak_bergerak,bergerak,habis_pakai',
            'depreciation_years' => 'nullable|integer|min:0|max:100',
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.unique'   => 'Nama kategori sudah ada.',
            'code.unique'  => 'Kode kategori sudah ada.',
        ]);

        $validated['is_active'] = true;
        $category = AssetCategory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil ditambahkan.',
            'category' => [
                'id'   => $category->id,
                'name' => $category->name,
                'code' => $category->code,
            ],
        ]);
    }

    private function authorizeAsetAccess(Asset $aset, Request $request): void
    {
        if ($this->canViewAll($request)) return;
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $aset->school_id !== $schoolId) {
            abort(403, 'Anda tidak memiliki akses ke aset ini.');
        }
    }
}
