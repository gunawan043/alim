<?php

namespace App\Http\Controllers\Sarpras;

use App\Http\Controllers\Controller;
use App\Models\Sparepart;
use App\Models\SparepartCategory;
use App\Models\Warehouse;
use App\Models\Vendor;
use App\Models\Unit;
use App\Services\Sarpras\AutomationSuggestionService;
use App\Services\Sarpras\StockManagementService;
use Illuminate\Http\Request;

class SarprasSparepartController extends Controller
{
    public function __construct(
        protected StockManagementService $stockService,
        protected AutomationSuggestionService $automation,
    ) {}

    public function index(Request $request)
    {
        $query = Sparepart::query()
            ->with(['category', 'unit', 'primaryVendor', 'warehouse']);

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($w) use ($q) {
                $w->where('part_number', 'like', "%{$q}%")
                  ->orWhere('name', 'like', "%{$q}%")
                  ->orWhere('barcode', 'like', "%{$q}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        if ($request->filled('low_stock')) {
            $query->whereColumn('stock', '<=', 'reorder_point');
        }

        if ($request->filled('dead_stock')) {
            $query->whereDoesntHave('stockMovements', function ($q) {
                $q->where('occurred_at', '>=', now()->subMonths(6));
            });
        }

        $spareparts = $query->orderBy('name')
            ->paginate($request->input('per_page', 25));

        return view('sarpras.sparepart.index', [
            'spareparts' => $spareparts,
            'categories' => SparepartCategory::orderBy('name')->get(),
            'warehouses' => Warehouse::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('sarpras.sparepart.create', [
            'categories' => SparepartCategory::orderBy('name')->get(),
            'vendors' => Vendor::where('status', 'active')->orderBy('name')->get(),
            'warehouses' => Warehouse::orderBy('name')->get(),
            'units' => Unit::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'part_number' => 'required|string|max:100|unique:spareparts,part_number',
            'name' => 'required|string|max:200',
            'category_id' => 'required|integer|exists:sparepart_categories,id',
            'unit_id' => 'required|integer|exists:units,id',
            'primary_vendor_id' => 'nullable|integer|exists:vendors,id',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'bin_id' => 'nullable|integer|exists:warehouse_bins,id',
            'barcode' => 'nullable|string|max:100',
            'min_stock' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'reorder_point' => 'nullable|numeric|min:0',
            'reorder_quantity' => 'nullable|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'lead_time_days' => 'nullable|integer|min:0',
            'weight_kg' => 'nullable|numeric|min:0',
            'is_hazardous' => 'nullable|boolean',
            'is_consumable' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        Sparepart::create($validated + ['stock' => 0, 'rating_avg' => 0]);

        return redirect()->route('sarpras.sparepart.index')->with('success', 'Sparepart ditambahkan');
    }

    public function show(Sparepart $sparepart)
    {
        $sparepart->load(['category', 'unit', 'primaryVendor', 'warehouse', 'bin']);

        $movements = $sparepart->stockMovements()
            ->orderByDesc('occurred_at')
            ->limit(50)
            ->get();

        $reservations = $sparepart->reservations()
            ->where('status', 'active')
            ->get();

        return view('sarpras.sparepart.show', [
            'sparepart' => $sparepart,
            'movements' => $movements,
            'reservations' => $reservations,
        ]);
    }

    public function edit(Sparepart $sparepart)
    {
        return view('sarpras.sparepart.edit', [
            'sparepart' => $sparepart,
            'categories' => SparepartCategory::orderBy('name')->get(),
            'vendors' => Vendor::where('status', 'active')->orderBy('name')->get(),
            'warehouses' => Warehouse::orderBy('name')->get(),
            'units' => Unit::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Sparepart $sparepart)
    {
        $validated = $request->validate([
            'part_number' => 'required|string|max:100|unique:spareparts,part_number,' . $sparepart->id,
            'name' => 'required|string|max:200',
            'category_id' => 'required|integer|exists:sparepart_categories,id',
            'unit_id' => 'required|integer|exists:units,id',
            'primary_vendor_id' => 'nullable|integer|exists:vendors,id',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'bin_id' => 'nullable|integer|exists:warehouse_bins,id',
            'barcode' => 'nullable|string|max:100',
            'min_stock' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'reorder_point' => 'nullable|numeric|min:0',
            'reorder_quantity' => 'nullable|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'lead_time_days' => 'nullable|integer|min:0',
        ]);

        $sparepart->update($validated);

        return redirect()->route('sarpras.sparepart.show', $sparepart)->with('success', 'Sparepart diperbarui');
    }

    public function receive(Request $request, Sparepart $sparepart)
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'bin_id' => 'nullable|integer|exists:warehouse_bins,id',
            'reason' => 'nullable|string|max:200',
        ]);

        $movement = $this->stockService->receive(
            $sparepart->id,
            $validated['quantity'],
            $validated['warehouse_id'],
            $validated['bin_id'] ?? null,
            auth()->user(),
            $validated['reason'] ?? ''
        );

        return back()->with('success', 'Stok bertambah: ' . $validated['quantity'] . ' ' . ($sparepart->unit?->symbol ?? ''));
    }

    public function adjust(Request $request, Sparepart $sparepart)
    {
        $validated = $request->validate([
            'new_stock' => 'required|numeric|min:0',
            'reason' => 'required|string|max:255',
        ]);

        $movement = $this->stockService->adjust(
            $sparepart->id,
            (float) $validated['new_stock'],
            auth()->user(),
            $validated['reason']
        );

        return back()->with('success', 'Penyesuaian tersimpan');
    }

    public function lowStock(Request $request)
    {
        $recommendations = $this->automation->detectLowStock();
        return view('sarpras.sparepart.low_stock', [
            'recommendations' => $recommendations,
        ]);
    }

    public function deadStock(Request $request)
    {
        $deadStock = Sparepart::where('is_active', true)
            ->whereDoesntHave('stockMovements', function ($q) {
                $q->where('occurred_at', '>=', now()->subMonths(6));
            })
            ->with(['category', 'warehouse'])
            ->get();

        return view('sarpras.sparepart.dead_stock', [
            'spareparts' => $deadStock,
        ]);
    }
}