<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dormitory\StoreWingRequest;
use App\Models\Dormitory;
use App\Models\DormitoryWing;
use App\Models\SarprasBuilding;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DormitoryWingController extends Controller
{
    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $query = DormitoryWing::with(['sarprasBuilding', 'rooms.residents'])
            ->where('dormitory_id', $asramaUuid);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('code', 'like', "%{$request->search}%")
                    ->orWhereHas('sarprasBuilding', fn ($bq) => $bq->where('name', 'like', "%{$request->search}%"));
            });
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $wings = $query->orderBy('name')->paginate(15)->withQueryString();

        // Data untuk modal "Tambah Blok" pada halaman daftar.
        $buildings = SarprasBuilding::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('school_id')->orWhere('school_id', $dormitory->school_id))
            ->orderBy('name')
            ->get();

        return view('dormitory.wings.index', compact('dormitory', 'wings', 'buildings', 'userId'));
    }

    public function create(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $buildings = SarprasBuilding::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('school_id')->orWhere('school_id', $dormitory->school_id))
            ->orderBy('name')
            ->get();

        return view('dormitory.wings.create', compact('dormitory', 'buildings', 'userId'));
    }

    public function store(StoreWingRequest $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $data = $request->validated();

        $building = SarprasBuilding::findOrFail($data['sarpras_building_id']);

        $data['dormitory_id'] = $asramaUuid;
        $data['code'] = $data['code'] ?? $building->code;
        $data['name'] = $building->name.' — Lantai '.($data['floor'] ?? 1);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['gender'] = $building->gender ?? $dormitory->gender;

        $wing = DormitoryWing::create($data);

        return redirect()->route('user.asrama.wings.show', [
            'userId' => $userId,
            'asramaUuid' => $asramaUuid,
            'wingUuid' => $wing->id,
        ])->with('success', 'Lantai blok berhasil disimpan.');
    }

    /**
     * Buat gedung Sarpras langsung dari form Asrama (quick-create).
     * Diperlukan karena tabel sarpras_buildings hanya bisa diisi dari sini;
     * tanpa ini dropdown Gedung pada form Lantai Blok tidak pernah terisi.
     */
    public function storeBuilding(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:50',
            'gender' => 'nullable|in:putra,putri,campur',
            'notes' => 'nullable|string|max:191',
        ], [
            'name.required' => 'Nama gedung wajib diisi.',
            'gender.in' => 'Jenis gedung harus putra, putri, atau campur.',
        ]);

        $building = SarprasBuilding::create([
            'school_id' => $dormitory->school_id,
            'code' => $validated['code'] ?? strtoupper(Str::slug($validated['name'], '')) ?: 'GDG-'.strtoupper(Str::random(4)),
            'name' => $validated['name'],
            'gender' => $validated['gender'] ?? 'campur',
            'notes' => $validated['notes'] ?? null,
            'is_active' => true,
            'created_by' => $request->user()?->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $building->id,
                'name' => $building->name,
                'code' => $building->code,
                'gender' => $building->gender,
            ], 201);
        }

        return redirect()
            ->route('user.asrama.wings.create', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Gedung berhasil ditambahkan.');
    }

    public function show(string $userId, string $asramaUuid, string $wingUuid)
    {
        $wing = DormitoryWing::with(['dormitory', 'supervisor', 'sarprasBuilding', 'rooms.residents.student'])
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
        $wing = DormitoryWing::with('sarprasBuilding')->where('dormitory_id', $asramaUuid)->findOrFail($wingUuid);
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $buildings = SarprasBuilding::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('school_id')->orWhere('school_id', $dormitory->school_id))
            ->orderBy('name')
            ->get();

        return view('dormitory.wings.edit', compact('wing', 'dormitory', 'buildings', 'userId'));
    }

    public function update(StoreWingRequest $request, string $userId, string $asramaUuid, string $wingUuid)
    {
        $wing = DormitoryWing::where('dormitory_id', $asramaUuid)->findOrFail($wingUuid);

        $data = $request->validated();

        $data['is_active'] = $request->boolean('is_active', true);

        // Rebuild name from building + floor if sarpras_building_id changed
        if (isset($data['sarpras_building_id'])) {
            $building = SarprasBuilding::find($data['sarpras_building_id']);
            $floor = $data['floor'] ?? $wing->floor ?? 1;
            $data['name'] = $building ? "{$building->name} — Lantai {$floor}" : $wing->name;
        }

        $wing->update($data);

        return redirect()->route('user.asrama.wings.show', [
            'userId' => $userId,
            'asramaUuid' => $asramaUuid,
            'wingUuid' => $wingUuid,
        ])->with('success', 'Lantai blok berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $asramaUuid, string $wingUuid)
    {
        $wing = DormitoryWing::where('dormitory_id', $asramaUuid)->findOrFail($wingUuid);
        $wing->delete();

        return redirect()->route('user.asrama.wings.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Blok berhasil dihapus.');
    }
}
