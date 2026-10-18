<?php

namespace App\Http\Controllers\Sarpras;

use App\Http\Requests\Sarpras\GedungStoreRequest;
use App\Http\Requests\Sarpras\GedungUpdateRequest;
use App\Models\AssetBuilding;
use App\Models\School;
use App\Models\WorkUnit;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class SarprasGedungController extends SarprasBaseController
{
    public function __construct()
    {
        view()->share('userId', request()->route('userId') ?? (auth()->check() ? auth()->id() : null));
    }

    public function index(Request $request)
    {
        $query = AssetBuilding::with('school');

        if (! $this->canViewAll($request)) {
            $query = $this->scopeToSchool($request, $query);
        }

        $stats = [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->where('is_active', true)->count(),
            'good' => (clone $query)->where('structure_condition', 'baik')->count(),
            'rooms' => \App\Models\AssetRoom::query()->when(! $this->canViewAll($request), fn ($q) => $this->scopeToSchool($request, $q))->count(),
        ];

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('building_name', 'like', "%{$s}%")->orWhere('building_code', 'like', "%{$s}%"));
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
        $canManage = $this->isCentralSarpras();

        return view('sarpras.gedung.index', compact('gedungs', 'schools', 'canManage', 'stats'));
    }

    public function create(Request $request)
    {
        $this->authorizeCentralSarpras();
        $schoolId = $request->attributes->get('schoolContextId');
        $schools = $schoolId ? School::where('id', $schoolId)->get() : School::orderBy('name')->get();

        return view('sarpras.gedung.create', compact('schools', 'schoolId'));
    }

    public function store(GedungStoreRequest $request)
    {
        $this->authorizeCentralSarpras();
        $validated = $request->validated();

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['created_by'] = auth()->id();
        $validated['work_unit_id'] = $this->resolveBuildingWorkUnit($validated['school_id'] ?? null);

        abort_if(! $validated['work_unit_id'], 422, 'Satuan kerja pemilik gedung belum dapat ditentukan.');

        if (blank($validated['building_code'] ?? null)) {
            $validated['building_code'] = $this->generateBuildingCode();
        }

        AssetBuilding::create($validated);
        $this->bumpDashboardCache();

        return redirect()->route('sarpras.gedung.index')
            ->with('success', 'Gedung berhasil ditambahkan.');
    }

    public function show(string $userId, Request $request, string $id)
    {
        $gedung = AssetBuilding::with(['school', 'rooms', 'creator'])->findOrFail($id);
        $this->authorizeBuildingAccess($gedung, $request);

        return view('sarpras.gedung.show', ['gedung' => $gedung, 'canManage' => $this->isCentralSarpras()]);
    }

    public function edit(string $userId, Request $request, string $id)
    {
        $this->authorizeCentralSarpras();
        $gedung = AssetBuilding::findOrFail($id);
        $this->authorizeBuildingAccess($gedung, $request);

        $schoolId = $request->attributes->get('schoolContextId');
        $schools = $schoolId ? School::where('id', $schoolId)->get() : School::orderBy('name')->get();

        return view('sarpras.gedung.edit', compact('gedung', 'schools'));
    }

    public function update(string $userId, GedungUpdateRequest $request, string $id)
    {
        $this->authorizeCentralSarpras();
        $gedung = AssetBuilding::findOrFail($id);
        $this->authorizeBuildingAccess($gedung, $request);

        $validated = $request->validated();

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['work_unit_id'] = $this->resolveBuildingWorkUnit($validated['school_id'] ?? null) ?? $gedung->work_unit_id;

        if (blank($validated['building_code'] ?? null)) {
            $validated['building_code'] = $gedung->building_code;
        }

        $gedung->update($validated);
        $this->bumpDashboardCache();

        return redirect()->route('sarpras.gedung.show', ['id' => $gedung->id])
            ->with('success', 'Gedung berhasil diperbarui.');
    }

    public function destroy(string $userId, Request $request, string $id)
    {
        $this->authorizeCentralSarpras();
        $gedung = AssetBuilding::with('rooms')->findOrFail($id);
        $this->authorizeBuildingAccess($gedung, $request);

        if ($gedung->rooms()->count() > 0) {
            return back()->with('error', 'Gedung tidak bisa dihapus karena masih memiliki ruang.');
        }

        $gedung->delete();
        $this->bumpDashboardCache();

        return redirect()->route('sarpras.gedung.index')
            ->with('success', 'Gedung berhasil dihapus.');
    }

    private function resolveBuildingWorkUnit(?string $schoolId): ?string
    {
        if ($schoolId) {
            return School::query()->whereKey($schoolId)->value('work_unit_id');
        }

        return WorkUnit::query()
            ->where('code', 'DEP-URT')
            ->orWhere('name', 'like', '%Rumah Tangga%')
            ->value('id');
    }

    private function generateBuildingCode(): string
    {
        do {
            $code = 'GDG-'.strtoupper(Str::random(6));
        } while (AssetBuilding::query()->where('building_code', $code)->exists());

        return $code;
    }
}
