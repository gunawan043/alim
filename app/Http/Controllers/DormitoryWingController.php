<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dormitory\StoreWingRequest;
use App\Models\AssetBuilding;
use App\Models\Dormitory;
use App\Models\DormitoryWing;
use Illuminate\Http\Request;

class DormitoryWingController extends Controller
{
    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $query = DormitoryWing::with(['assetBuilding', 'sarprasBuilding', 'rooms.residents'])
            ->where('dormitory_id', $asramaUuid);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('code', 'like', "%{$request->search}%")
                    ->orWhereHas('assetBuilding', fn ($bq) => $bq->where('building_name', 'like', "%{$request->search}%"))
                    ->orWhereHas('sarprasBuilding', fn ($bq) => $bq->where('name', 'like', "%{$request->search}%"));
            });
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $wings = $query->orderBy('name')->paginate(15)->withQueryString();

        // Gedung ditetapkan URT lewat modul Sarpras (asset_buildings).
        $buildings = $this->availableBuildings($dormitory);

        return view('dormitory.wings.index', compact('dormitory', 'wings', 'buildings', 'userId'));
    }

    public function create(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $buildings = $this->availableBuildings($dormitory);

        return view('dormitory.wings.create', compact('dormitory', 'buildings', 'userId'));
    }

    public function store(StoreWingRequest $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $data = $request->validated();

        $building = AssetBuilding::findOrFail($data['asset_building_id']);

        $data['dormitory_id'] = $asramaUuid;
        $data['asset_building_id'] = $building->id;
        $data['sarpras_building_id'] = null;
        $data['code'] = filled($data['code'] ?? null) ? $data['code'] : $building->building_code;
        $data['name'] = $building->building_name.' — Lantai '.($data['floor'] ?? 1);
        $data['is_active'] = $request->boolean('is_active', true);
        // Kolom gender blok hanya menerima putra/putri; asrama campuran ikut gender default.
        $data['gender'] = in_array($dormitory->gender, ['putra', 'putri'], true) ? $dormitory->gender : 'putra';

        $wing = DormitoryWing::create($data);

        return redirect()->route('user.asrama.wings.show', [
            'userId' => $userId,
            'asramaUuid' => $asramaUuid,
            'wingUuid' => $wing->id,
        ])->with('success', 'Lantai blok berhasil disimpan.');
    }

    public function show(string $userId, string $asramaUuid, string $wingUuid)
    {
        $wing = DormitoryWing::with(['dormitory', 'supervisor', 'assetBuilding', 'sarprasBuilding', 'rooms.residents.student'])
            ->where('dormitory_id', $asramaUuid)
            ->findOrFail($wingUuid);

        $dormitory = $wing->dormitory;

        $stats = [
            'total_rooms' => $wing->rooms()->count(),
            'total_residents' => $wing->rooms->flatMap->residents->filter(fn ($r) => $r->is_active)->count(),
            'capacity' => $wing->capacity,
        ];

        return view('dormitory.wings.show', compact('wing', 'dormitory', 'userId', 'stats'));
    }

    public function edit(string $userId, string $asramaUuid, string $wingUuid)
    {
        $wing = DormitoryWing::with(['assetBuilding', 'sarprasBuilding'])->where('dormitory_id', $asramaUuid)->findOrFail($wingUuid);
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $buildings = $this->availableBuildings($dormitory);

        return view('dormitory.wings.edit', compact('wing', 'dormitory', 'buildings', 'userId'));
    }

    public function update(StoreWingRequest $request, string $userId, string $asramaUuid, string $wingUuid)
    {
        $wing = DormitoryWing::where('dormitory_id', $asramaUuid)->findOrFail($wingUuid);

        $data = $request->validated();

        $data['is_active'] = $request->boolean('is_active', true);

        // Nama blok selalu diturunkan dari gedung Sarpras + lantai.
        if (! empty($data['asset_building_id'])) {
            $building = AssetBuilding::findOrFail($data['asset_building_id']);
            $floor = $data['floor'] ?? $wing->floor ?? 1;
            $data['name'] = $building->building_name.' — Lantai '.$floor;
            $data['sarpras_building_id'] = null;
            $data['code'] = filled($data['code'] ?? null) ? $data['code'] : ($wing->code ?: $building->building_code);
        }

        $wing->update($data);

        return redirect()->route('user.asrama.wings.show', [
            'userId' => $userId,
            'asramaUuid' => $asramaUuid,
            'wingUuid' => $wingUuid,
        ])->with('success', 'Lantai blok berhasil diperbarui.');
    }

    /**
     * Gedung asrama dikelola URT lewat modul Sarpras (asset_buildings).
     * Asrama hanya boleh memilih dari daftar ini, tidak membuat gedung baru.
     */
    private function availableBuildings(Dormitory $dormitory)
    {
        return AssetBuilding::query()
            ->where('is_active', true)
            ->where('building_type', 'asrama')
            ->where(fn ($q) => $q->whereNull('school_id')->orWhere('school_id', $dormitory->school_id))
            ->orderBy('building_name')
            ->get();
    }

    public function destroy(Request $request, string $userId, string $asramaUuid, string $wingUuid)
    {
        $wing = DormitoryWing::where('dormitory_id', $asramaUuid)->findOrFail($wingUuid);
        $wing->delete();

        return redirect()->route('user.asrama.wings.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Blok berhasil dihapus.');
    }
}
