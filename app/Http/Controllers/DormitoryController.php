<?php

namespace App\Http\Controllers;

use App\Models\Dormitory;
use App\Models\DormitoryWing;
use App\Models\DormitoryRoom;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DormitoryController extends Controller
{
    /**
     * GET /{userId}/asrama
     */
    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $query = Dormitory::with(['school', 'head', 'wings', 'rooms'])
            ->withCount(['residents as total_residents' => fn($q) => $q->where('is_active', true)]);

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        } elseif ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($sq) => $sq
                ->where('name', 'like', "%{$q}%")
                ->orWhere('code', 'like', "%{$q}%")
            );
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $dormitories = $query->orderBy('name')->paginate(15)->withQueryString();
        $schools = School::orderBy('name')->get();

        // Stats
        $stats = [
            'total'     => Dormitory::when($schoolId, fn($q) => $q->where('school_id', $schoolId))->count(),
            'active'    => Dormitory::when($schoolId, fn($q) => $q->where('school_id', $schoolId))->where('is_active', true)->count(),
            'putra'     => Dormitory::when($schoolId, fn($q) => $q->where('school_id', $schoolId))->where('gender', 'putra')->where('is_active', true)->count(),
            'putri'     => Dormitory::when($schoolId, fn($q) => $q->where('school_id', $schoolId))->where('gender', 'putri')->where('is_active', true)->count(),
        ];

        return view('dormitory.index', compact('dormitories', 'schools', 'userId', 'stats'));
    }

    public function create(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $schools = $schoolId
            ? School::where('id', $schoolId)->get()
            : School::orderBy('name')->get();

        return view('dormitory.create', compact('schools', 'userId'));
    }

    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $data = $request->validate([
            'school_id'    => $schoolId ? 'sometimes|exists:schools,id' : 'required|exists:schools,id',
            'code'         => 'required|string|max:20|unique:dormitories,code',
            'name'         => 'required|string|max:255',
            'gender'       => 'required|in:putra,putri',
            'address'      => 'nullable|string',
            'phone'        => 'nullable|string|max:20',
            'capacity'     => 'nullable|integer|min:1',
            'total_rooms'  => 'nullable|integer|min:0',
            'total_wings'  => 'nullable|integer|min:0',
            'head_id'      => 'nullable|exists:users,id',
            'is_active'    => 'boolean',
            'notes'        => 'nullable|string',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        Dormitory::create($data);

        return redirect()->route('user.asrama.show', [
            'userId' => $userId,
            'asramaUuid' => Dormitory::latest()->first()->id,
        ])->with('success', 'Data asrama berhasil disimpan.');
    }

    public function show(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::with([
            'school',
            'head',
            'wings.rooms.residents.student',
            'wings.supervisor',
            'rooms.residents.student',
        ])->findOrFail($asramaUuid);

        $activeYear = \App\Models\AcademicYear::where('is_active', true)->first();

        // Stats per asrama
        $stats = [
            'total_residents' => $dormitory->residents()->where('is_active', true)->count(),
            'total_capacity' => $dormitory->capacity,
            'occupancy_rate' => $dormitory->capacity > 0
                ? round($dormitory->residents()->where('is_active', true)->count() / $dormitory->capacity * 100, 1)
                : 0,
            'total_rooms'    => $dormitory->rooms()->count(),
            'total_wings'    => $dormitory->wings()->count(),
        ];

        // Occupancy per wing
        $wingStats = $dormitory->wings->map(fn($wing) => [
            'wing'    => $wing,
            'rooms'   => $wing->rooms->count(),
            'residents' => $wing->rooms->flatMap->residents->filter(fn($r) => $r->is_active)->count(),
        ]);

        return view('dormitory.show', compact('dormitory', 'userId', 'stats', 'wingStats', 'activeYear'));
    }

    public function edit(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $schoolId = $request->attributes->get('schoolContextId');
        $schools = $schoolId
            ? School::where('id', $schoolId)->get()
            : School::orderBy('name')->get();

        return view('dormitory.edit', compact('dormitory', 'schools', 'userId'));
    }

    public function update(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $data = $request->validate([
            'school_id'    => 'required|exists:schools,id',
            'code'         => 'required|string|max:20|unique:dormitories,code,' . $asramaUuid,
            'name'         => 'required|string|max:255',
            'gender'       => 'required|in:putra,putri',
            'address'      => 'nullable|string',
            'phone'        => 'nullable|string|max:20',
            'capacity'     => 'nullable|integer|min:1',
            'total_rooms'  => 'nullable|integer|min:0',
            'total_wings'  => 'nullable|integer|min:0',
            'head_id'      => 'nullable|exists:users,id',
            'is_active'    => 'boolean',
            'notes'        => 'nullable|string',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $dormitory->update($data);

        return redirect()->route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Data asrama berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $dormitory->delete();

        return redirect()->route('user.asrama.index', ['userId' => $userId])
            ->with('success', 'Asrama berhasil dihapus.');
    }

    // ── WING MANAGEMENT ─────────────────────────────────────────

    public function wingStore(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $data = $request->validate([
            'code'         => 'required|string|max:20',
            'name'         => 'required|string|max:100',
            'floor'        => 'nullable|integer|min:0',
            'gender'       => 'nullable|in:putra,putri',
            'capacity'     => 'nullable|integer|min:0',
            'total_rooms'  => 'nullable|integer|min:0',
            'supervisor_id' => 'nullable|exists:users,id',
            'is_active'    => 'boolean',
            'notes'        => 'nullable|string',
        ]);

        $data['dormitory_id'] = $asramaUuid;
        $data['is_active'] = $request->boolean('is_active', true);
        $data['gender'] = $data['gender'] ?? $dormitory->gender;

        DormitoryWing::create($data);

        return back()->with('success', 'Gedung berhasil ditambahkan.');
    }

    public function wingUpdate(Request $request, string $userId, string $asramaUuid, string $wingUuid)
    {
        $wing = DormitoryWing::where('dormitory_id', $asramaUuid)->findOrFail($wingUuid);

        $data = $request->validate([
            'code'         => 'required|string|max:20',
            'name'         => 'required|string|max:100',
            'floor'        => 'nullable|integer|min:0',
            'gender'       => 'nullable|in:putra,putri',
            'capacity'     => 'nullable|integer|min:0',
            'total_rooms'  => 'nullable|integer|min:0',
            'supervisor_id' => 'nullable|exists:users,id',
            'is_active'    => 'boolean',
            'notes'        => 'nullable|string',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $wing->update($data);

        return back()->with('success', 'Gedung berhasil diperbarui.');
    }

    public function wingDestroy(Request $request, string $userId, string $asramaUuid, string $wingUuid)
    {
        $wing = DormitoryWing::where('dormitory_id', $asramaUuid)->findOrFail($wingUuid);
        $wing->delete();

        return back()->with('success', 'Gedung berhasil dihapus.');
    }

    // ── ROOM MANAGEMENT ─────────────────────────────────────────

    public function roomStore(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $data = $request->validate([
            'wing_id'       => 'nullable|exists:dormitory_wings,id',
            'code'          => 'required|string|max:20|unique:dormitory_rooms,code',
            'name'          => 'nullable|string|max:100',
            'floor'         => 'nullable|integer|min:0',
            'gender'        => 'nullable|in:putra,putri',
            'capacity'      => 'nullable|integer|min:1',
            'room_type'     => 'nullable|in:reguler,khusus,isolasi,musyrif',
            'facility_notes'=> 'nullable|string',
            'is_active'     => 'boolean',
        ]);

        $data['dormitory_id'] = $asramaUuid;
        $data['is_active'] = $request->boolean('is_active', true);
        $data['gender'] = $data['gender'] ?? $dormitory->gender;

        DormitoryRoom::create($data);

        return back()->with('success', 'Kamar berhasil ditambahkan.');
    }

    public function roomUpdate(Request $request, string $userId, string $asramaUuid, string $roomUuid)
    {
        $room = DormitoryRoom::where('dormitory_id', $asramaUuid)->findOrFail($roomUuid);

        $data = $request->validate([
            'wing_id'       => 'nullable|exists:dormitory_wings,id',
            'code'          => 'required|string|max:20|unique:dormitory_rooms,code,' . $roomUuid,
            'name'          => 'nullable|string|max:100',
            'floor'         => 'nullable|integer|min:0',
            'gender'        => 'nullable|in:putra,putri',
            'capacity'      => 'nullable|integer|min:1',
            'room_type'     => 'nullable|in:reguler,khusus,isolasi,musyrif',
            'facility_notes'=> 'nullable|string',
            'is_active'     => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $room->update($data);

        return back()->with('success', 'Kamar berhasil diperbarui.');
    }

    public function roomDestroy(Request $request, string $userId, string $asramaUuid, string $roomUuid)
    {
        $room = DormitoryRoom::where('dormitory_id', $asramaUuid)->findOrFail($roomUuid);
        $room->delete();

        return back()->with('success', 'Kamar berhasil dihapus.');
    }

    // ── API HELPERS ──────────────────────────────────────────────

    public function apiWingsByDormitory(Request $request)
    {
        $dormitoryId = $request->get('dormitory_id');
        $wings = DormitoryWing::where('dormitory_id', $dormitoryId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return response()->json($wings);
    }

    public function apiRoomsByWing(Request $request)
    {
        $wingId = $request->get('wing_id');
        $rooms = DormitoryRoom::where('wing_id', $wingId)
            ->where('is_active', true)
            ->withCount(['residents as current_occupancy' => fn($q) => $q->where('is_active', true)])
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'capacity']);

        return response()->json($rooms);
    }
}