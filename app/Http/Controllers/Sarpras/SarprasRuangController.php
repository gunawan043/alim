<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\AssetRoom;
use App\Models\AssetBuilding;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SarprasRuangController extends SarprasBaseController
{
    public function __construct()
    {
        view()->share('userId', request()->route('userId') ?? (auth()->check() ? auth()->id() : null));
    }


    public function index(Request $request)
    {
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
        $schools = $this->canViewAll($request) ? School::orderBy('name')->get() : collect();

        return view('sarpras.ruang.index', compact('ruangs', 'schools'));
    }

    public function create(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $schools = $schoolId ? School::where('id', $schoolId)->get() : School::orderBy('name')->get();
        $gedungs = AssetBuilding::where('is_active', true)
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('building_name')->get();

        return view('sarpras.ruang.create', compact('schools', 'gedungs', 'schoolId'));
    }

    public function store(Request $request)
    {
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
            'is_bookable'                 => 'boolean',
            'booking_requires_approval'    => 'boolean',
            'notes'                       => 'nullable|string',
            'is_active'                   => 'boolean',
        ]);

        $validated['is_bookable'] = $request->boolean('is_bookable', false);
        $validated['booking_requires_approval'] = $request->boolean('booking_requires_approval', true);
        $validated['is_active'] = $request->boolean('is_active', true);

        $school = School::find($validated['school_id']);
        $validated['work_unit_id'] = $school->work_unit_id;

        AssetRoom::create($validated);

        return redirect()->route('sarpras.ruang.index')
            ->with('success', 'Ruang berhasil ditambahkan.');
    }

    public function show(Request $request, string $id)
    {
        $ruang = AssetRoom::with(['school', 'building', 'assets', 'responsibleUser'])->findOrFail($id);
        $this->authorizeRoomAccess($ruang, $request);

        return view('sarpras.ruang.show', compact('ruang'));
    }

    public function edit(Request $request, string $id)
    {
        $ruang = AssetRoom::findOrFail($id);
        $this->authorizeRoomAccess($ruang, $request);

        $schoolId = $request->attributes->get('schoolContextId');
        $schools = $schoolId ? School::where('id', $schoolId)->get() : School::orderBy('name')->get();
        $gedungs = AssetBuilding::where('is_active', true)
            ->when($ruang->school_id, fn($q) => $q->where('school_id', $ruang->school_id))
            ->orderBy('building_name')->get();

        return view('sarpras.ruang.edit', compact('ruang', 'schools', 'gedungs'));
    }

    public function update(Request $request, string $id)
    {
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
            'is_bookable'                 => 'boolean',
            'booking_requires_approval'   => 'boolean',
            'notes'                       => 'nullable|string',
            'is_active'                   => 'boolean',
        ]);

        $validated['is_bookable'] = $request->boolean('is_bookable', false);
        $validated['booking_requires_approval'] = $request->boolean('booking_requires_approval', true);
        $validated['is_active'] = $request->boolean('is_active', true);

        $ruang->update($validated);

        return redirect()->route('sarpras.ruang.show', $ruang->id)
            ->with('success', 'Ruang berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id)
    {
        $ruang = AssetRoom::with('assets')->findOrFail($id);
        $this->authorizeRoomAccess($ruang, $request);

        if ($ruang->assets()->count() > 0) {
            return back()->with('error', 'Ruang tidak bisa dihapus karena masih memiliki aset/inventaris.');
        }

        $ruang->delete();

        return redirect()->route('sarpras.ruang.index')
            ->with('success', 'Ruang berhasil dihapus.');
    }
}