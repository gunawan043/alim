<?php

namespace App\Http\Controllers\Sarpras;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Sparepart;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SarprasPurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseOrder::query()->with(['vendor', 'items']);

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($w) use ($q) {
                $w->where('po_number', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->input('vendor_id'));
        }

        $pos = $query->orderByDesc('order_date')
            ->paginate($request->input('per_page', 25));

        return view('sarpras.po.index', [
            'pos' => $pos,
            'vendors' => Vendor::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('sarpras.po.create', [
            'vendors' => Vendor::where('status', 'active')->orderBy('name')->get(),
            'spareparts' => Sparepart::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|integer|exists:vendors,id',
            'order_date' => 'required|date',
            'expected_date' => 'nullable|date',
            'payment_term_days' => 'nullable|integer|min:0',
            'incoterms' => 'nullable|string|max:30',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.sparepart_id' => 'required|integer|exists:spareparts,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_pct' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_pct' => 'nullable|numeric|min:0|max:100',
            'items.*.warehouse_id' => 'nullable|integer|exists:warehouses,id',
        ]);

        return DB::transaction(function () use ($validated) {
            $subtotal = 0;
            $totalDiscount = 0;
            $totalTax = 0;
            foreach ($validated['items'] as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $discount = $lineTotal * (($item['discount_pct'] ?? 0) / 100);
                $tax = ($lineTotal - $discount) * (($item['tax_pct'] ?? 0) / 100);
                $subtotal += $lineTotal;
                $totalDiscount += $discount;
                $totalTax += $tax;
            }

            $po = PurchaseOrder::create([
                'po_number' => 'PO-'.now()->format('Ymd').'-'.strtoupper(Str::random(4)),
                'vendor_id' => $validated['vendor_id'],
                'order_date' => $validated['order_date'],
                'expected_date' => $validated['expected_date'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $totalDiscount,
                'tax' => $totalTax,
                'shipping' => 0,
                'total' => $subtotal - $totalDiscount + $totalTax,
                'currency' => 'IDR',
                'payment_term_days' => $validated['payment_term_days'] ?? 30,
                'incoterms' => $validated['incoterms'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'submitted',
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $discount = $lineTotal * (($item['discount_pct'] ?? 0) / 100);
                $tax = ($lineTotal - $discount) * (($item['tax_pct'] ?? 0) / 100);
                $netTotal = $lineTotal - $discount + $tax;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'sparepart_id' => $item['sparepart_id'],
                    'description' => Sparepart::find($item['sparepart_id'])->name,
                    'quantity' => $item['quantity'],
                    'received_quantity' => 0,
                    'unit_price' => $item['unit_price'],
                    'discount_pct' => $item['discount_pct'] ?? 0,
                    'tax_pct' => $item['tax_pct'] ?? 0,
                    'line_total' => $netTotal,
                    'warehouse_id' => $item['warehouse_id'] ?? null,
                ]);
            }

            return $po;
        });

        return redirect()->route('sarpras.po.index')->with('success', 'PO dibuat');
    }

    public function show(PurchaseOrder $po)
    {
        $po->load(['vendor', 'items.sparepart', 'items.warehouse']);

        return view('sarpras.po.show', [
            'po' => $po,
        ]);
    }

    public function approve(PurchaseOrder $po)
    {
        if (! in_array($po->status, ['submitted'])) {
            return back()->with('error', 'PO sudah dalam proses lain');
        }

        $po->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'PO disetujui');
    }

    public function cancel(PurchaseOrder $po)
    {
        if (in_array($po->status, ['received', 'cancelled'])) {
            return back()->with('error', 'Tidak dapat membatalkan PO ini');
        }
        $po->update(['status' => 'cancelled']);

        return back()->with('success', 'PO dibatalkan');
    }

    public function receive(Request $request, PurchaseOrder $po)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:purchase_order_items,id',
            'items.*.received_quantity' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $po) {
            $stockService = app(\App\Services\Sarpras\StockManagementService::class);

            foreach ($validated['items'] as $row) {
                $item = PurchaseOrderItem::find($row['id']);
                $newReceived = $item->received_quantity + $row['received_quantity'];
                if ($newReceived > $item->quantity) {
                    throw new \Exception("Received qty exceeds ordered qty for item {$item->id}");
                }
                $item->received_quantity = $newReceived;
                $item->save();

                if ($row['received_quantity'] > 0) {
                    $stockService->receive(
                        $item->sparepart_id,
                        (float) $row['received_quantity'],
                        $item->warehouse_id ?? $item->sparepart->warehouse_id,
                        $item->sparepart->bin_id ?? null,
                        auth()->user(),
                        'Received from PO '.$po->po_number
                    );
                }
            }

            $po->refresh();
            $po->status = $po->items->every(fn ($i) => $i->received_quantity >= $i->quantity) ? 'received' : 'partial';
            $po->received_date = $po->status === 'received' ? now() : null;
            $po->save();
        });

        return back()->with('success', 'Penerimaan tersimpan');
    }
}
