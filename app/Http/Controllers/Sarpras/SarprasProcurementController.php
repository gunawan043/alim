<?php

namespace App\Http\Controllers\Sarpras;

use App\Http\Requests\Sarpras\ProcurementConvertRequest;
use App\Http\Requests\Sarpras\ProcurementReceiveRequest;
use App\Http\Requests\Sarpras\ProcurementRejectRequest;
use App\Http\Requests\Sarpras\ProcurementStoreRequest;
use App\Models\Asset;
use App\Models\AssetRoom;
use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\School;
use App\Services\Sarpras\AssetEventLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SarprasProcurementController extends SarprasBaseController
{
    public function __construct(protected AssetEventLogger $eventLogger)
    {
        view()->share('userId', request()->route('userId') ?? (auth()->check() ? auth()->id() : null));
    }

    public function index(Request $request)
    {
        $query = ProcurementRequest::with(['requester', 'approver', 'items']);

        if (! $this->canViewAll($request)) {
            $query = $this->scopeToSchool($request, $query);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('urgency')) {
            $query->where('urgency', $request->urgency);
        }

        $procurements = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        $schools = $this->canViewAll($request) ? School::orderBy('name')->get() : collect();

        return view('sarpras.pengadaan.index', compact('procurements', 'schools'));
    }

    public function create(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $rooms = AssetRoom::where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('room_name')->get();

        return view('sarpras.pengadaan.create', compact('rooms'));
    }

    public function store(ProcurementStoreRequest $request)
    {
        $validated = $request->validated();

        $schoolId = $request->attributes->get('schoolContextId');
        $school = $schoolId ? School::find($schoolId) : null;

        $procurement = ProcurementRequest::create([
            'request_number' => 'PROC-'.date('Ymd').'-'.strtoupper(Str::random(6)),
            'request_date' => $validated['request_date'],
            'purpose' => $validated['purpose'],
            'urgency' => $validated['urgency'],
            'budget_source' => $validated['budget_source'] ?? null,
            'total_estimated_budget' => $validated['total_estimated_budget'] ?? 0,
            'requested_by' => auth()->id(),
            'status' => 'pending',
            'work_unit_id' => $school?->work_unit_id,
            'school_id' => $schoolId,
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        foreach ($validated['items'] as $item) {
            $item['procurement_request_id'] = $procurement->id;
            $item['total_estimated_price'] = isset($item['estimated_price_per_unit'])
                ? $item['estimated_price_per_unit'] * $item['quantity']
                : null;
            ProcurementRequestItem::create($item);
        }
        $this->bumpDashboardCache();

        return redirect()->route('sarpras.pengadaan.index')
            ->with('success', 'Request pengadaan berhasil diajukan.');
    }

    public function show(Request $request, string $id)
    {
        $procurement = ProcurementRequest::with(['requester', 'approver', 'receiver', 'items.category', 'items.room.building'])
            ->findOrFail($id);
        $this->authorizeProcurementAccess($procurement, $request);

        return view('sarpras.pengadaan.show', compact('procurement'));
    }

    public function edit(Request $request, string $id)
    {
        $procurement = ProcurementRequest::with(['items.room.building', 'requester', 'school'])->findOrFail($id);
        $this->authorizeProcurementAccess($procurement, $request);

        if (in_array($procurement->status, ['delivered', 'completed'])) {
            return redirect()->route('sarpras.pengadaan.show', $id)
                ->with('error', 'Request yang sudah diproses tidak bisa diedit.');
        }

        return view('sarpras.pengadaan.edit', compact('procurement'));
    }

    public function update(Request $request, string $id)
    {
        $procurement = ProcurementRequest::findOrFail($id);
        $this->authorizeProcurementAccess($procurement, $request);

        if (in_array($procurement->status, ['delivered', 'completed'])) {
            return redirect()->route('sarpras.pengadaan.show', $id)
                ->with('error', 'Request yang sudah diproses tidak bisa diedit.');
        }

        $validated = $request->validated();

        $procurement->update($validated);
        $this->bumpDashboardCache();

        return redirect()->route('sarpras.pengadaan.show', $procurement->id)
            ->with('success', 'Request pengadaan berhasil diperbarui.');
    }

    public function receiveForm(Request $request, string $id)
    {
        $procurement = ProcurementRequest::with(['items', 'requester', 'school'])->findOrFail($id);
        $this->authorizeProcurementAccess($procurement, $request);

        if (! in_array($procurement->status, ['approved', 'ordered'])) {
            return redirect()->route('sarpras.pengadaan.show', $id)
                ->with('error', 'Pengadaan belum bisa diterima.');
        }

        return view('sarpras.pengadaan.receive', compact('procurement'));
    }

    public function approve(Request $request, string $id)
    {
        $procurement = ProcurementRequest::findOrFail($id);
        $this->authorizeProcurementAccess($procurement, $request);

        if ($procurement->status !== 'pending') {
            return back()->with('error', 'Pengadaan sudah diproses.');
        }

        $procurement->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        $this->bumpDashboardCache();

        return back()->with('success', 'Request pengadaan disetujui.');
    }

    public function convertForm(Request $request, string $id)
    {
        $procurement = ProcurementRequest::with(['items.room.building', 'items.category', 'requester', 'school'])->findOrFail($id);
        $this->authorizeProcurementAccess($procurement, $request);

        if ($procurement->status !== 'delivered') {
            return redirect()->route('sarpras.pengadaan.show', $id)
                ->with('error', 'Pengadaan belum diterima, tidak bisa dikonversi.');
        }

        return view('sarpras.pengadaan.convert', compact('procurement'));
    }

    public function reject(ProcurementRejectRequest $request, string $id)
    {
        $procurement = ProcurementRequest::findOrFail($id);
        $this->authorizeProcurementAccess($procurement, $request);

        $validated = $request->validated();

        $procurement->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);
        $this->bumpDashboardCache();

        return back()->with('success', 'Request pengadaan ditolak.');
    }

    public function receive(ProcurementReceiveRequest $request, string $id)
    {
        $procurement = ProcurementRequest::findOrFail($id);
        $this->authorizeProcurementAccess($procurement, $request);

        if (! in_array($procurement->status, ['approved', 'ordered'])) {
            return back()->with('error', 'Pengadaan belum bisa diterima.');
        }

        $validated = $request->validated();

        $procurement->update([
            'status' => 'delivered',
            'delivery_date' => $validated['delivery_date'],
            'received_by' => $validated['received_by'],
            'received_date' => Carbon::today(),
            'total_actual_cost' => $validated['total_actual_cost'] ?? null,
            'purchase_order_number' => $validated['purchase_order_number'] ?? null,
            'purchase_order_date' => $validated['purchase_order_date'] ?? null,
            'vendor_name' => $validated['vendor_name'] ?? null,
        ]);

        // Update item details if provided
        if (! empty($validated['items'])) {
            foreach ($validated['items'] as $itemData) {
                $item = ProcurementRequestItem::where('procurement_request_id', $procurement->id)
                    ->where('id', $itemData['id'])->first();
                if ($item) {
                    $item->update([
                        'actual_quantity_received' => $itemData['actual_quantity_received'] ?? $item->quantity,
                        'actual_price_per_unit' => $itemData['actual_price_per_unit'] ?? null,
                        'received_date' => $itemData['received_date'] ?? null,
                    ]);
                }
            }
        }
        $this->bumpDashboardCache();

        return back()->with('success', 'Barang berhasil diterima.');
    }

    public function convertToAsset(ProcurementConvertRequest $request, string $id)
    {
        $procurement = ProcurementRequest::findOrFail($id);
        $this->authorizeProcurementAccess($procurement, $request);

        if ($procurement->status !== 'delivered') {
            return back()->with('error', 'Pengadaan belum diterima, tidak bisa dikonversi ke aset.');
        }

        $validated = $request->validated();

        $converted = 0;
        foreach ($validated['items'] as $itemData) {
            $item = ProcurementRequestItem::where('procurement_request_id', $procurement->id)
                ->where('id', $itemData['item_id'])->first();
            if (! $item) {
                continue;
            }

            $roomId = $itemData['room_id'] ?? $item->room_id ?? null;
            $qty = $itemData['quantity'] ?? $item->actual_quantity_received ?? $item->quantity;

            for ($i = 0; $i < $qty; $i++) {
                $assetCode = 'AST-'.date('Ym').'-'.strtoupper(Str::random(6));

                $asset = Asset::create([
                    'asset_name' => $itemData['asset_name'].($qty > 1 ? ' ('.($i + 1).')' : ''),
                    'asset_code' => $assetCode,
                    'asset_category_id' => $item->asset_category_id,
                    'room_id' => $roomId,
                    'acquisition_date' => $procurement->delivery_date,
                    'acquisition_price' => $item->actual_price_per_unit ?? $item->estimated_price_per_unit,
                    'acquisition_source' => 'pembelian',
                    'funding_source' => $procurement->budget_source,
                    'supplier_name' => $procurement->vendor_name,
                    'condition' => 'baik',
                    'status' => 'tersedia',
                    'is_bookable' => true,
                    'is_active' => true,
                    'work_unit_id' => $procurement->work_unit_id,
                    'school_id' => $procurement->school_id,
                    'created_by' => auth()->id(),
                ]);

                try {
                    $this->eventLogger->log($asset, 'procurement_completed', [
                        'procurement_request_id' => $procurement->id,
                        'procurement_request_number' => $procurement->request_number,
                        'procurement_item_id' => $item->id,
                        'unit_price' => $item->actual_price_per_unit ?? $item->estimated_price_per_unit,
                        'vendor_name' => $procurement->vendor_name,
                    ], auth()->id());
                } catch (\Throwable $e) {
                    report($e);
                }

                $converted++;
            }
        }

        $procurement->update(['status' => 'completed']);
        $this->bumpDashboardCache();

        return redirect()->route('sarpras.pengadaan.index')
            ->with('success', "Berhasil mengkonversi {$converted} item menjadi aset.");
    }

    public function destroy(Request $request, string $id)
    {
        $procurement = ProcurementRequest::findOrFail($id);
        $this->authorizeProcurementAccess($procurement, $request);

        if (! in_array($procurement->status, ['draft', 'pending', 'rejected', 'cancelled'])) {
            return back()->with('error', 'Pengadaan yang sudah diproses tidak bisa dihapus.');
        }

        $procurement->items()->delete();
        $procurement->delete();
        $this->bumpDashboardCache();

        return redirect()->route('sarpras.pengadaan.index')
            ->with('success', 'Request pengadaan berhasil dihapus.');
    }
}
