<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\ProcurementRequest;
use App\Models\VendorCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProcurementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:vendor');
    }

    public function index(Request $request): View
    {
        $vendor = $request->user();
        $vendorName = $vendor->name;

        $requests = ProcurementRequest::where('vendor_name', $vendorName)
            ->with('items')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('vendor.procurement.index', compact('requests'));
    }

    public function create(): View
    {
        $categories = VendorCategory::where('is_active', true)->orderBy('name')->get();

        return view('vendor.procurement.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $vendor = $request->user();
        $vendorName = $vendor->name;

        $validated = $request->validate([
            'purpose' => 'required|string|max:255',
            'urgency' => 'required|in:'.implode(',', ['rendah', 'normal', 'tinggi', 'mendesak']),
            'budget_source' => 'required|string|max:255',
            'total_estimated_budget' => 'required|numeric|min:0',
            'procurement_method' => 'required|in:pengadaan_langsung,tender,minta_penawaran,pemilihan_terbatas',
            'delivery_date' => 'required|date|after:today',
            'payment_terms' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.category_id' => 'nullable|exists:vendor_categories,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'required|string|max:50',
            'items.*.estimated_unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $pr = new ProcurementRequest([
            'work_unit_id' => $vendorName,
            'request_number' => 'PR-V-'.date('Ymd').'-'.strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6)),
            'request_date' => now()->toDateString(),
            'requested_by' => $vendorName,
            'purpose' => $validated['purpose'],
            'urgency' => $validated['urgency'],
            'budget_source' => $validated['budget_source'],
            'total_estimated_budget' => $validated['total_estimated_budget'],
            'status' => 'draft',
            'vendor_name' => $vendorName,
            'delivery_date' => $validated['delivery_date'],
            'procurement_method' => $validated['procurement_method'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $pr->save();

        foreach ($validated['items'] as $itemData) {
            $pr->items()->create([
                'item_name' => $itemData['item_name'],
                'category_id' => $itemData['category_id'] ?? null,
                'quantity' => $itemData['quantity'],
                'unit' => $itemData['unit'],
                'estimated_unit_price' => $itemData['estimated_unit_price'],
                'subtotal' => $itemData['quantity'] * $itemData['estimated_unit_price'],
                'notes' => $itemData['notes'] ?? null,
            ]);
        }

        return redirect()->route('vendor.procurement.show', $pr->id)
            ->with('success', 'Permintaan pengadaan berhasil disimpan sebagai draft.');
    }

    public function show(Request $request, string $id): View
    {
        $vendor = $request->user();
        $request = ProcurementRequest::where('id', $id)
            ->where('vendor_name', $vendor->name)
            ->with('items.category')
            ->firstOrFail();

        return view('vendor.procurement.show', compact('request'));
    }

    public function updateStatus(Request $request, string $id): RedirectResponse
    {
        $vendor = $request->user();
        $pr = ProcurementRequest::where('id', $id)
            ->where('vendor_name', $vendor->name)
            ->firstOrFail();

        $validated = $request->validate([
            'action' => 'required|in:submit,update_delivery',
            'delivery_date' => 'nullable|date|after:today',
            'tracking_number' => 'nullable|string|max:100',
        ]);

        if ($validated['action'] === 'submit') {
            $pr->update([
                'status' => 'pending',
                'approved_by' => null,
            ]);
        }

        if ($validated['action'] === 'update_delivery') {
            if ($validated['delivery_date'] ?? null) {
                $pr->update(['delivery_date' => $validated['delivery_date']]);
            }
            if ($validated['tracking_number'] ?? null) {
                $pr->update(['received_date' => now()->toDateString()]);
            }
        }

        return back()->with('success', 'Permintaan berhasil diperbarui.');
    }
}
