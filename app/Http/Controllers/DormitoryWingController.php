<?php

namespace App\Http\Controllers;

use App\Models\Dormitory;
use App\Models\DormitoryWing;
use App\Models\User;
use Illuminate\Http\Request;

class DormitoryWingController extends Controller
{
    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $query = DormitoryWing::with(['supervisor', 'rooms'])
            ->where('dormitory_id', $asramaUuid);

        if ($request->filled('search')) {
            $query->where(fn($q) => $q
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%")
            );
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $wings = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('dormitory.wings.index', compact('dormitory', 'wings', 'userId'));
    }

    public function create(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $supervisors = User::whereHas('employment')
            ->whereHas('employment', fn($q) => $q->where('school_id', $dormitory->school_id))
            ->orderBy('name')->get();

        return view('dormitory.wings.create', compact('dormitory', 'supervisors', 'userId'));
    }

    public function store(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $data = $request->validate([
            'code'          => 'required|string|max:20',
            'name'          => 'required|string|max:100',
            'floor'         => 'nullable|integer|min:0',
            'capacity'      => 'nullable|integer|min:0',
            'supervisor_id' => 'nullable|exists:users,id',
            'is_active'     => 'boolean',
            'notes'         => 'nullable|string',
        ]);

        $data['dormitory_id'] = $asramaUuid;
        $data['is_active'] = $request->boolean('is_active', true);
        $data['gender'] = $dormitory->gender;

        DormitoryWing::create($data);

        return redirect()->route('user.asrama.wings.show', [
            'userId' => $userId,
            'asramaUuid' => $asramaUuid,
            'wingUuid' => DormitoryWing::latest()->first()->id,
        ])->with('success', 'Gedung berhasil disimpan.');
    }

    public function show(string $userId, string $asramaUuid, string $wingUuid)
    {
        $wing = DormitoryWing::with(['dormitory', 'supervisor', 'rooms.residents.student'])
            ->where('dormitory_id', $asramaUuid)
            ->findOrFail($wingUuid);

        $dormitory = $wing->dormitory;

        $stats = [
            'total_rooms'    => $wing->rooms()->count(),
            'total_residents'=> $wing->rooms->flatMap->residents->filter(fn($r) => $r->is_active)->count(),
            'capacity'       => $wing->capacity,
        ];

        return view('dormitory.wings.show', compact('wing', 'dormitory', 'userId', 'stats'));
    }

    public function edit(string $userId, string $asramaUuid, string $wingUuid)
    {
        $wing = DormitoryWing::where('dormitory_id', $asramaUuid)->findOrFail($wingUuid);
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $supervisors = User::whereHas('employment')
            ->whereHas('employment', fn($q) => $q->where('school_id', $dormitory->school_id))
            ->orderBy('name')->get();

        return view('dormitory.wings.edit', compact('wing', 'dormitory', 'supervisors', 'userId'));
    }

    public function update(Request $request, string $userId, string $asramaUuid, string $wingUuid)
    {
        $wing = DormitoryWing::where('dormitory_id', $asramaUuid)->findOrFail($wingUuid);

        $data = $request->validate([
            'code'          => 'required|string|max:20',
            'name'          => 'required|string|max:100',
            'floor'         => 'nullable|integer|min:0',
            'capacity'      => 'nullable|integer|min:0',
            'supervisor_id' => 'nullable|exists:users,id',
            'is_active'     => 'boolean',
            'notes'         => 'nullable|string',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $wing->update($data);

        return redirect()->route('user.asrama.wings.show', [
            'userId' => $userId,
            'asramaUuid' => $asramaUuid,
            'wingUuid' => $wingUuid,
        ])->with('success', 'Gedung berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $asramaUuid, string $wingUuid)
    {
        $wing = DormitoryWing::where('dormitory_id', $asramaUuid)->findOrFail($wingUuid);
        $wing->delete();

        return redirect()->route('user.asrama.wings.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Gedung berhasil dihapus.');
    }
}
