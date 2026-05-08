<?php

namespace App\Http\Controllers;

use App\Models\Dormitory;
use App\Models\DormitoryWing;
use App\Models\DormitoryRoom;
use Illuminate\Http\Request;

class DormitoryRoomController extends Controller
{
    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $query = DormitoryRoom::with(['wing', 'residents.student'])
            ->where('dormitory_id', $asramaUuid);

        if ($request->filled('wing_id')) {
            $query->where('wing_id', $request->wing_id);
        }
        if ($request->filled('search')) {
            $query->where(fn($q) => $q
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%")
            );
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $rooms = $query->orderBy('code')->paginate(15)->withQueryString();

        $wings = DormitoryWing::where('dormitory_id', $asramaUuid)
            ->where('is_active', true)
            ->orderBy('name')->get();

        return view('dormitory.rooms.index', compact('dormitory', 'rooms', 'wings', 'userId'));
    }

    public function create(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $wings = DormitoryWing::where('dormitory_id', $asramaUuid)
            ->where('is_active', true)
            ->orderBy('name')->get();

        return view('dormitory.rooms.create', compact('dormitory', 'wings', 'userId'));
    }

    public function store(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $data = $request->validate([
            'wing_id'       => 'nullable|exists:dormitory_wings,id',
            'code'          => 'required|string|max:20|unique:dormitory_rooms,code',
            'name'          => 'nullable|string|max:100',
            'floor'         => 'nullable|integer|min:0',
            'capacity'      => 'nullable|integer|min:1',
            'room_type'     => 'nullable|in:reguler,khusus,isolasi,musyrif',
            'facility_notes'=> 'nullable|string',
            'is_active'     => 'boolean',
        ]);

        $data['dormitory_id'] = $asramaUuid;
        $data['is_active'] = $request->boolean('is_active', true);
        $data['gender'] = $dormitory->gender;

        DormitoryRoom::create($data);

        return redirect()->route('user.asrama.rooms.show', [
            'userId' => $userId,
            'asramaUuid' => $asramaUuid,
            'roomUuid' => DormitoryRoom::latest()->first()->id,
        ])->with('success', 'Kamar berhasil disimpan.');
    }

    public function show(string $userId, string $asramaUuid, string $roomUuid)
    {
        $room = DormitoryRoom::with([
            'dormitory',
            'wing',
            'residents' => fn($q) => $q->where('is_active', true)->with('student'),
        ])->where('dormitory_id', $asramaUuid)
          ->findOrFail($roomUuid);

        $dormitory = $room->dormitory;
        $activeResidents = $room->residents;

        $stats = [
            'total_residents' => $activeResidents->count(),
            'capacity'        => $room->capacity,
            'occupancy_rate' => $room->capacity > 0
                ? round($activeResidents->count() / $room->capacity * 100, 1)
                : 0,
        ];

        return view('dormitory.rooms.show', compact('room', 'dormitory', 'userId', 'stats'));
    }

    public function edit(string $userId, string $asramaUuid, string $roomUuid)
    {
        $room = DormitoryRoom::where('dormitory_id', $asramaUuid)->findOrFail($roomUuid);
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $wings = DormitoryWing::where('dormitory_id', $asramaUuid)
            ->where('is_active', true)
            ->orderBy('name')->get();

        return view('dormitory.rooms.edit', compact('room', 'dormitory', 'wings', 'userId'));
    }

    public function update(Request $request, string $userId, string $asramaUuid, string $roomUuid)
    {
        $room = DormitoryRoom::where('dormitory_id', $asramaUuid)->findOrFail($roomUuid);

        $data = $request->validate([
            'wing_id'       => 'nullable|exists:dormitory_wings,id',
            'code'          => 'required|string|max:20|unique:dormitory_rooms,code,' . $roomUuid,
            'name'          => 'nullable|string|max:100',
            'floor'         => 'nullable|integer|min:0',
            'capacity'      => 'nullable|integer|min:1',
            'room_type'     => 'nullable|in:reguler,khusus,isolasi,musyrif',
            'facility_notes'=> 'nullable|string',
            'is_active'     => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $room->update($data);

        return redirect()->route('user.asrama.rooms.show', [
            'userId' => $userId,
            'asramaUuid' => $asramaUuid,
            'roomUuid' => $roomUuid,
        ])->with('success', 'Kamar berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $asramaUuid, string $roomUuid)
    {
        $room = DormitoryRoom::where('dormitory_id', $asramaUuid)->findOrFail($roomUuid);
        $room->delete();

        return redirect()->route('user.asrama.rooms.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Kamar berhasil dihapus.');
    }
}
