<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\AssetBuilding;
use App\Models\School;
use App\Models\AssetRoom;
use App\Models\WorkUnit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SarprasGedungController extends SarprasBaseController
{
    public function __construct()
    {
        view()->share('userId', request()->route('userId') ?? (auth()->check() ? auth()->id() : null));
    }


    public function index(Request $request)
    {
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
        $schools = $this->canViewAll($request) ? School::orderBy('name')->get() : collect();

        return view('sarpras.gedung.index', compact('gedungs', 'schools'));
    }

    public function create(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $schools = $schoolId ? School::where('id', $schoolId)->get() : School::orderBy('name')->get();

        return view('sarpras.gedung.create', compact('schools', 'schoolId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_id'            => 'required|exists:schools,id',
            'building_name'        => 'required|string|max:191',
            'building_code'        => 'nullable|string|max:30|unique:asset_buildings,building_code',
            'building_type'        => 'required|in:' . implode(',', AssetBuilding::BUILDING_TYPE_OPTIONS),
            'total_floors'         => 'nullable|integer|min:1|max:20',
            'building_area'        => 'nullable|numeric|min:0',
            'build_year'           => 'nullable|integer|min:1900|max:2100',
            'renovation_year'      => 'nullable|integer|min:1900|max:2100',
            'structure_condition'  => 'required|in:' . implode(',', AssetBuilding::CONDITION_OPTIONS),
            'ownership_status'     => 'nullable|in:' . implode(',', AssetBuilding::OWNERSHIP_OPTIONS),
            'imb_number'           => 'nullable|string|max:100',
            'imb_date'             => 'nullable|date',
            'notes'                => 'nullable|string',
            'is_active'            => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['created_by'] = auth()->id();

        $school = School::find($validated['school_id']);
        $validated['work_unit_id'] = $school->work_unit_id;

        AssetBuilding::create($validated);

        return redirect()->route('sarpras.gedung.index')
            ->with('success', 'Gedung berhasil ditambahkan.');
    }

    public function show(Request $request, string $id)
    {
        $gedung = AssetBuilding::with(['school', 'rooms', 'creator'])->findOrFail($id);
        $this->authorizeBuildingAccess($gedung, $request);

        return view('sarpras.gedung.show', compact('gedung'));
    }

    public function edit(Request $request, string $id)
    {
        $gedung = AssetBuilding::findOrFail($id);
        $this->authorizeBuildingAccess($gedung, $request);

        $schoolId = $request->attributes->get('schoolContextId');
        $schools = $schoolId ? School::where('id', $schoolId)->get() : School::orderBy('name')->get();

        return view('sarpras.gedung.edit', compact('gedung', 'schools'));
    }

    public function update(Request $request, string $id)
    {
        $gedung = AssetBuilding::findOrFail($id);
        $this->authorizeBuildingAccess($gedung, $request);

        $validated = $request->validate([
            'school_id'            => 'required|exists:schools,id',
            'building_name'        => 'required|string|max:191',
            'building_code'        => ['nullable', 'string', 'max:30', Rule::unique('asset_buildings', 'building_code')->ignore($gedung->id)],
            'building_type'        => 'required|in:' . implode(',', AssetBuilding::BUILDING_TYPE_OPTIONS),
            'total_floors'         => 'nullable|integer|min:1|max:20',
            'building_area'        => 'nullable|numeric|min:0',
            'build_year'           => 'nullable|integer|min:1900|max:2100',
            'renovation_year'      => 'nullable|integer|min:1900|max:2100',
            'structure_condition'  => 'required|in:' . implode(',', AssetBuilding::CONDITION_OPTIONS),
            'ownership_status'     => 'nullable|in:' . implode(',', AssetBuilding::OWNERSHIP_OPTIONS),
            'imb_number'           => 'nullable|string|max:100',
            'imb_date'             => 'nullable|date',
            'notes'                => 'nullable|string',
            'is_active'            => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $gedung->update($validated);

        return redirect()->route('sarpras.gedung.show', $gedung->id)
            ->with('success', 'Gedung berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id)
    {
        $gedung = AssetBuilding::with('rooms')->findOrFail($id);
        $this->authorizeBuildingAccess($gedung, $request);

        if ($gedung->rooms()->count() > 0) {
            return back()->with('error', 'Gedung tidak bisa dihapus karena masih memiliki ruang.');
        }

        $gedung->delete();

        return redirect()->route('sarpras.gedung.index')
            ->with('success', 'Gedung berhasil dihapus.');
    }
}