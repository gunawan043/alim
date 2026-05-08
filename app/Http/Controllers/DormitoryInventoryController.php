<?php

namespace App\Http\Controllers;

use App\Models\Dormitory;
use App\Models\DormitoryRoom;
use App\Models\DormitoryInventory;
use Illuminate\Http\Request;

class DormitoryInventoryController extends Controller
{
    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $query = DormitoryInventory::with(['room', 'checkedBy'])
            ->where('dormitory_id', $asramaUuid);

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($sq) => $sq
                ->where('item_name', 'like', "%{$q}%")
                ->orWhere('item_code', 'like', "%{$q}%")
            );
        }

        $inventories = $query->orderBy('room_id')->orderBy('item_name')->paginate(20)->withQueryString();
        $rooms = DormitoryRoom::where('dormitory_id', $asramaUuid)->where('is_active', true)->orderBy('code')->get();

        $stats = [
            'total'      => DormitoryInventory::where('dormitory_id', $asramaUuid)->count(),
            'baik'       => DormitoryInventory::where('dormitory_id', $asramaUuid)->where('condition', 'baik')->count(),
            'rusak'      => DormitoryInventory::where('dormitory_id', $asramaUuid)->where('condition', 'rusak')->count(),
            'perbaikan'  => DormitoryInventory::where('dormitory_id', $asramaUuid)->where('condition', 'perbaikan')->count(),
        ];

        return view('dormitory.inventories.index', [
            'dormitory'  => $dormitory,
            'inventories'=> $inventories,
            'rooms'      => $rooms,
            'userId'     => $userId,
            'stats'      => $stats,
        ]);
    }

    public function create(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = \App\Models\AcademicYear::where('is_active', true)->first();

        $rooms = DormitoryRoom::where('dormitory_id', $asramaUuid)
            ->where('is_active', true)
            ->withCount(['residents' => fn($q) => $activeYear
                ? $q->where('academic_year_id', $activeYear->id)->where('is_active', true)
                : fn($q) => $q->whereRaw('1=0')
            ])
            ->orderBy('code')->get();

        return view('dormitory.inventories.create', compact('dormitory', 'rooms', 'userId'));
    }

    public function store(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $data = $request->validate([
            'room_id'         => 'required|exists:dormitory_rooms,id',
            'item_name'       => 'required|string|max:191',
            'item_code'       => 'nullable|string|max:100',
            'quantity'        => 'required|integer|min:1',
            'condition'      => 'required|in:baik,rusak,perbaikan,hibahan',
            'last_checked_at' => 'nullable|date',
            'notes'          => 'nullable|string',
        ]);

        $data['dormitory_id'] = $asramaUuid;
        $data['checked_by'] = auth()->id();
        $data['last_checked_at'] = $data['last_checked_at'] ?? now();

        DormitoryInventory::create($data);

        return redirect()->route('user.asrama.inventories.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Item inventaris berhasil ditambahkan.');
    }

    public function edit(Request $request, string $userId, string $asramaUuid, string $itemUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $item = DormitoryInventory::where('dormitory_id', $asramaUuid)->findOrFail($itemUuid);
        $rooms = DormitoryRoom::where('dormitory_id', $asramaUuid)->where('is_active', true)->orderBy('code')->get();

        return view('dormitory.inventories.edit', compact('dormitory', 'item', 'rooms', 'userId'));
    }

    public function update(Request $request, string $userId, string $asramaUuid, string $itemUuid)
    {
        $item = DormitoryInventory::where('dormitory_id', $asramaUuid)->findOrFail($itemUuid);

        $data = $request->validate([
            'room_id'         => 'required|exists:dormitory_rooms,id',
            'item_name'       => 'required|string|max:191',
            'item_code'       => 'nullable|string|max:100',
            'quantity'        => 'required|integer|min:0',
            'condition'      => 'required|in:baik,rusak,perbaikan,hibahan',
            'last_checked_at' => 'nullable|date',
            'notes'          => 'nullable|string',
        ]);

        $data['checked_by'] = auth()->id();
        $item->update($data);

        return redirect()->route('user.asrama.inventories.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Item inventaris berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $asramaUuid, string $itemUuid)
    {
        $item = DormitoryInventory::where('dormitory_id', $asramaUuid)->findOrFail($itemUuid);
        $item->delete();

        return back()->with('success', 'Item inventaris berhasil dihapus.');
    }
}
