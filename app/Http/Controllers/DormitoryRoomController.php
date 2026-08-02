<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dormitory\StoreRoomRequest;
use App\Models\Dormitory;
use App\Models\DormitoryPermit;
use App\Models\DormitoryRoom;
use App\Models\DormitoryWing;
use App\Models\User;
use App\Services\Asrama\RoomSupervisorService;
use Illuminate\Http\Request;

class DormitoryRoomController extends Controller
{
    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $query = DormitoryRoom::with(['wing', 'residents.student', 'activeSupervisor.user'])
            ->where('dormitory_id', $asramaUuid);

        if ($request->filled('wing_id')) {
            $query->where('wing_id', $request->wing_id);
        }
        if ($request->filled('search')) {
            $query->where(fn ($q) => $q
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

        $occupied = DormitoryRoom::where('dormitory_id', $asramaUuid)
            ->whereHas('residents', fn ($q) => $q->where('is_active', true))
            ->withCount(['residents as active_residents_count' => fn ($q) => $q->where('is_active', true)])
            ->get()
            ->sum('active_residents_count');

        $totalCapacity = DormitoryRoom::where('dormitory_id', $asramaUuid)->sum('capacity');

        return view('dormitory.rooms.index', compact('dormitory', 'rooms', 'wings', 'userId', 'occupied', 'totalCapacity'));
    }

    public function create(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $wings = DormitoryWing::where('dormitory_id', $asramaUuid)
            ->where('is_active', true)
            ->orderBy('name')->get();

        $waliKamarCandidates = User::query()
            ->when($dormitory->work_unit_id, fn ($q) => $q->whereHas('workUnits', fn ($qq) => $qq->where('gtk_work_unit.work_unit_id', $dormitory->work_unit_id)))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('dormitory.rooms.create', compact('dormitory', 'wings', 'waliKamarCandidates', 'userId'));
    }

    public function store(StoreRoomRequest $request, string $userId, string $asramaUuid, RoomSupervisorService $service)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $data = $request->validated();

        try {
            $room = \DB::transaction(function () use ($request, $asramaUuid, $dormitory, $data, $service) {
                $payload = $data;
                $payload['dormitory_id'] = $asramaUuid;
                $payload['is_active'] = $request->boolean('is_active', true);
                $payload['gender'] = $dormitory->gender;

                $waliKamarUserId = $payload['wali_kamar_user_id'];
                unset($payload['wali_kamar_user_id']);

                $room = DormitoryRoom::create($payload);

                $service->assign(
                    userId: $waliKamarUserId,
                    roomId: $room->id,
                    actorId: $request->user()?->id,
                );

                return $room;
            });
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan kamar: '.$e->getMessage());
        }

        return redirect()->route('user.asrama.rooms.show', [
            'userId' => $userId,
            'asramaUuid' => $asramaUuid,
            'roomUuid' => $room->id,
        ])->with('success', 'Kamar berhasil disimpan.');
    }

    public function show(string $userId, string $asramaUuid, string $roomUuid)
    {
        $room = DormitoryRoom::with([
            'dormitory',
            'wing',
            'residents' => fn ($q) => $q->where('is_active', true)->with('student'),
            'supervisors' => fn ($q) => $q->with('user')->orderByDesc('start_date'),
            'activeSupervisor.user',
        ])->where('dormitory_id', $asramaUuid)
            ->findOrFail($roomUuid);

        $dormitory = $room->dormitory;
        $activeResidents = $room->residents;

        $residentIds = $activeResidents->pluck('student_id')->all();

        $activePermits = DormitoryPermit::with(['student'])
            ->whereIn('student_id', $residentIds)
            ->whereIn('status', ['approved', 'overdue'])
            ->whereNull('actual_return_datetime')
            ->get()
            ->keyBy('student_id');

        $stats = [
            'total_residents' => $activeResidents->count(),
            'capacity' => $room->capacity,
            'occupancy_rate' => $room->capacity > 0
                ? round($activeResidents->count() / $room->capacity * 100, 1)
                : 0,
            'on_permit' => $activePermits->count(),
            'in_dormitory' => $activeResidents->count() - $activePermits->count(),
        ];

        return view('dormitory.rooms.show', compact('room', 'dormitory', 'userId', 'stats', 'activeResidents', 'activePermits'));
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
            'wing_id' => 'nullable|exists:dormitory_wings,id',
            'code' => 'required|string|max:20|unique:dormitory_rooms,code,'.$roomUuid,
            'name' => 'nullable|string|max:100',
            'floor' => 'nullable|integer|min:0',
            'capacity' => 'nullable|integer|min:1',
            'room_type' => 'nullable|in:reguler,khusus,isolasi,musyrif',
            'facility_notes' => 'nullable|string',
            'is_active' => 'boolean',
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
