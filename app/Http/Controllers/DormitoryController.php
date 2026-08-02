<?php

namespace App\Http\Controllers;

use App\Models\Dormitory;
use App\Models\DormitoryRoom;
use App\Models\DormitoryWing;
use App\Models\School;
use Illuminate\Http\Request;

class DormitoryController extends Controller
{
    /**
     * GET /{userId}/asrama/profil-saya
     *
     * Auto-resolve asrama yang dipimpin user login (Dormitory.head_id)
     * dan redirect ke halaman profil/show asrama tersebut.
     * Fallback ke asrama pertama di school context jika user bukan kepala asrama.
     */
    public function myProfile(Request $request, string $userId)
    {
        $authId = auth()->id();
        $dormitory = Dormitory::with(['school', 'head'])
            ->withCount([
                'wings',
                'rooms',
                'residents as total_residents' => fn ($q) => $q->where('is_active', true),
            ])
            ->where('head_id', $authId)
            ->first();

        if (! $dormitory) {
            $schoolId = $request->attributes->get('schoolContextId');
            if ($schoolId) {
                $dormitory = Dormitory::with(['school', 'head'])
                    ->withCount([
                        'wings',
                        'rooms',
                        'residents as total_residents' => fn ($q) => $q->where('is_active', true),
                    ])
                    ->where('school_id', $schoolId)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->first();
            }

            // Last fallback for unscoped Admin Asrama: show the first active dormitory.
            // This mirrors the sidebar's behavior so the menu never dead-ends on 404.
            if (! $dormitory) {
                $dormitory = Dormitory::with(['school', 'head'])
                    ->withCount([
                        'wings',
                        'rooms',
                        'residents as total_residents' => fn ($q) => $q->where('is_active', true),
                    ])
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->first();
            }
        }

        abort_if(
            ! $dormitory,
            404,
            'Belum ada data asrama. Hubungi administrator untuk menambahkan asrama.'
        );

        return view('dormitory.my-profile', compact('dormitory', 'userId'));
    }

    /**
     * GET /{userId}/asrama
     */
    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $query = Dormitory::with(['school', 'head', 'wings', 'rooms'])
            ->withCount(['residents as total_residents' => fn ($q) => $q->where('is_active', true)]);

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        } elseif ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($sq) => $sq
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
            'total' => Dormitory::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->count(),
            'active' => Dormitory::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('is_active', true)->count(),
            'putra' => Dormitory::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('gender', 'putra')->where('is_active', true)->count(),
            'putri' => Dormitory::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('gender', 'putri')->where('is_active', true)->count(),
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
            'school_id' => $schoolId ? 'sometimes|exists:schools,id' : 'required|exists:schools,id',
            'code' => 'required|string|max:20|unique:dormitories,code',
            'name' => 'required|string|max:255',
            'gender' => 'required|in:putra,putri,campuran',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'capacity' => 'nullable|integer|min:1',
            'total_rooms' => 'nullable|integer|min:0',
            'total_wings' => 'nullable|integer|min:0',
            'head_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $dormitory = Dormitory::create($data);

        return redirect()->route('user.asrama.show', [
            'userId' => $userId,
            'asramaUuid' => $dormitory->id,
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
            'total_rooms' => $dormitory->rooms()->count(),
            'total_wings' => $dormitory->wings()->count(),
        ];

        // Occupancy per wing
        $wingStats = $dormitory->wings->map(fn ($wing) => [
            'wing' => $wing,
            'rooms' => $wing->rooms->count(),
            'residents' => $wing->rooms->flatMap->residents->filter(fn ($r) => $r->is_active)->count(),
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
            'school_id' => 'required|exists:schools,id',
            'code' => 'required|string|max:20|unique:dormitories,code,'.$asramaUuid,
            'name' => 'required|string|max:255',
            'gender' => 'required|in:putra,putri,campuran',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'capacity' => 'nullable|integer|min:1',
            'total_rooms' => 'nullable|integer|min:0',
            'total_wings' => 'nullable|integer|min:0',
            'head_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
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
            ->withCount(['residents as current_occupancy' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'capacity']);

        return response()->json($rooms);
    }
}
