<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dormitory\StoreInventoryRequest;
use App\Models\AssetCategory;
use App\Models\Dormitory;
use App\Models\DormitoryInventory;
use App\Models\DormitoryRoom;
use Illuminate\Http\Request;

class DormitoryInventoryController extends Controller
{
    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $query = DormitoryInventory::with(['room', 'checkedBy', 'category'])
            ->where('dormitory_id', $asramaUuid);

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('condition')) {
            // ubah 'hibahan' (lama) menjadi 'hilang' agar sesuai enum DB
            $condition = $request->condition == 'hibahan' ? 'hilang' : $request->condition;
            $query->where('condition', $condition);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($sq) => $sq
                ->where('item_name', 'like', "%{$q}%")
                ->orWhere('item_code', 'like', "%{$q}%")
            );
        }

        // Sorting
        $validSorts = ['name', 'room_name', 'quantity', 'category_id', 'condition', 'last_checked_at'];
        $sort = $request->input('sort');
        $order = strtolower($request->input('order', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (in_array($sort, $validSorts)) {
            if ($sort === 'category_id') {
                // Join untuk bisa urut berdasarkan nama kategori
                $query->leftJoin('asset_categories', 'dormitory_inventory.category_id', '=', 'asset_categories.id');
                $query->orderBy('asset_categories.name', $order);
            } elseif ($sort === 'room_name') {
                // Sort berdasarkan nama kamar (via join ke room table)
                $query->leftJoin('dormitory_rooms', 'dormitory_inventory.room_id', '=', 'dormitory_rooms.id');
                $query->orderBy('dormitory_rooms.name', $order);
            } elseif ($sort === 'quantity') {
                // Sort berdasarkan jumlah (numeric)
                $query->orderBy('quantity', $order);
            } else {
                $colMap = [
                    'name' => 'item_name',
                    'condition' => 'condition',
                    'last_checked_at' => 'last_checked_at',
                ];
                $query->orderBy($colMap[$sort], $order);
            }
        } else {
            // default sort: room_id, then item_name
            $query->orderBy('room_id')->orderBy('item_name');
        }

        $inventories = $query->paginate(20)->withQueryString();
        $rooms = DormitoryRoom::where('dormitory_id', $asramaUuid)->where('is_active', true)->orderBy('code')->get();

        $stats = [
            'total' => DormitoryInventory::where('dormitory_id', $asramaUuid)->count(),
            'baik' => DormitoryInventory::where('dormitory_id', $asramaUuid)->where('condition', 'baik')->count(),
            'rusak' => DormitoryInventory::where('dormitory_id', $asramaUuid)->where('condition', 'rusak')->count(),
            'perbaikan' => DormitoryInventory::where('dormitory_id', $asramaUuid)->where('condition', 'perbaikan')->count(),
        ];

        // Filter kategori: hilangkan Seni dan Musik dari dropdown
        $categories = AssetCategory::where('is_active', true)
            ->whereNotIn('name', ['Seni', 'Musik'])
            ->orderBy('name')
            ->get();

        return view('dormitory.inventories.index', [
            'dormitory' => $dormitory,
            'inventories' => $inventories,
            'rooms' => $rooms,
            'categories' => $categories,
            'userId' => $userId,
            'stats' => $stats,
        ]);
    }

    public function create(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = \App\Models\AcademicYear::where('is_active', true)->first();

        $rooms = DormitoryRoom::where('dormitory_id', $asramaUuid)
            ->where('is_active', true)
            ->withCount(['residents' => fn ($q) => $activeYear
                ? $q->where('academic_year_id', $activeYear->id)->where('is_active', true)
                : fn ($q) => $q->whereRaw('1=0'),
            ])
            ->orderBy('code')->get();

        // Fetch active asset categories for dropdown
        $categories = AssetCategory::where('is_active', true)->orderBy('name')->get();

        return view('dormitory.inventories.create', compact('dormitory', 'rooms', 'userId', 'categories'));
    }

    public function store(StoreInventoryRequest $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $data = $request->validated();

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
        $categories = AssetCategory::where('is_active', true)->orderBy('name')->get();

        return view('dormitory.inventories.edit', compact('dormitory', 'item', 'rooms', 'userId', 'categories'));
    }

    public function update(StoreInventoryRequest $request, string $userId, string $asramaUuid, string $itemUuid)
    {
        $item = DormitoryInventory::where('dormitory_id', $asramaUuid)->findOrFail($itemUuid);

        $data = $request->validated();

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
