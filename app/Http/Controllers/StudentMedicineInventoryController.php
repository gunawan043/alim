<?php

namespace App\Http\Controllers;

use App\Models\StudentMedicineInventory;
use Illuminate\Http\Request;

class StudentMedicineInventoryController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $query = StudentMedicineInventory::orderBy('medicine_name');

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($sq) => $sq
                ->where('medicine_name', 'like', "%{$q}%")
                ->orWhere('medicine_code', 'like', "%{$q}%")
            );
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $tab = $request->get('tab', 'all');

        if ($tab === 'low_stock') {
            $query->lowStock();
        } elseif ($tab === 'expiring') {
            $query->expiringSoon();
        } elseif ($tab === 'expired') {
            $query->expired();
        }

        $inventories = $query->paginate(15)->withQueryString();

        return view('health.medicine-inventory.index', compact('inventories', 'userId', 'tab'));
    }

    public function create(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        return view('health.medicine-inventory.create', compact('userId'));
    }

    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $validated = $request->validate([
            'medicine_name' => 'required|string|max:191',
            'medicine_code' => 'nullable|string|max:50',
            'category' => 'required|string|max:50',
            'generic_name' => 'nullable|string|max:191',
            'unit' => 'required|string|max:50',
            'current_stock' => 'nullable|numeric|min:0',
            'min_stock_alert' => 'nullable|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'storage_location' => 'nullable|string|max:191',
            'supplier' => 'nullable|string|max:191',
            'purchase_date' => 'nullable|date',
            'unit_price' => 'nullable|numeric|min:0',
            'dosage_info' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['school_id'] = $schoolId;

        StudentMedicineInventory::create($validated);

        return redirect()
            ->route('user.uks.medicine-inventory.index', ['userId' => $userId])
            ->with('success', 'Data obat berhasil disimpan.');
    }

    public function show(Request $request, string $userId, string $uuid)
    {
        $inventory = StudentMedicineInventory::findOrFail($uuid);

        return view('health.medicine-inventory.show', compact('inventory', 'userId'));
    }

    public function edit(Request $request, string $userId, string $uuid)
    {
        $inventory = StudentMedicineInventory::findOrFail($uuid);

        return view('health.medicine-inventory.edit', compact('inventory', 'userId'));
    }

    public function update(Request $request, string $userId, string $uuid)
    {
        $inventory = StudentMedicineInventory::findOrFail($uuid);

        $validated = $request->validate([
            'medicine_name' => 'required|string|max:191',
            'medicine_code' => 'nullable|string|max:50',
            'category' => 'required|string|max:50',
            'generic_name' => 'nullable|string|max:191',
            'unit' => 'required|string|max:50',
            'current_stock' => 'nullable|numeric|min:0',
            'min_stock_alert' => 'nullable|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'storage_location' => 'nullable|string|max:191',
            'supplier' => 'nullable|string|max:191',
            'purchase_date' => 'nullable|date',
            'unit_price' => 'nullable|numeric|min:0',
            'dosage_info' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $inventory->update($validated);

        return redirect()
            ->route('user.uks.medicine-inventory.show', ['userId' => $userId, 'uuid' => $uuid])
            ->with('success', 'Data obat berhasil diperbarui.');
    }

    public function destroy(string $userId, string $uuid)
    {
        $inventory = StudentMedicineInventory::findOrFail($uuid);
        $inventory->delete();

        return redirect()
            ->route('user.uks.medicine-inventory.index', ['userId' => $userId])
            ->with('success', 'Data obat berhasil dihapus.');
    }
}
