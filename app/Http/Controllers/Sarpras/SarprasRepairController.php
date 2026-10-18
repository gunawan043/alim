<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\Asset;
use App\Models\RepairRequest;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SarprasRepairController extends SarprasBaseController
{
    public function index(Request $request)
    {
        $query = RepairRequest::with(['asset.room.building', 'asset.workUnit', 'reportedBy', 'assignedTo', 'workOrders'])
            ->withCount('workOrders');

        if (! $this->isCentralSarpras() && ! $this->canViewAll($request)) {
            $query->whereHas('asset', fn ($q) => $this->scopeToAccessibleWorkUnits($q, $request));
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q
                ->where('request_number', 'like', "%{$s}%")
                ->orWhere('title', 'like', "%{$s}%")
                ->orWhereHas('asset', fn ($a) => $a->where('asset_name', 'like', "%{$s}%")->orWhere('asset_code', 'like', "%{$s}%")));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $stats = [
            'total' => (clone $query)->count(),
            'waiting' => (clone $query)->whereIn('status', ['submitted', 'verification_pending', 'approval_pending'])->count(),
            'scheduled' => (clone $query)->whereIn('status', ['execution_pending', 'approved'])->count(),
            'done' => (clone $query)->whereIn('status', ['completed', 'closed'])->count(),
        ];

        $repairs = $query->orderByRaw("FIELD(priority, 'high', 'normal', 'low')")
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $technicians = User::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereHas('roles', fn ($r) => $r->whereIn('name', ['Teknisi', 'teknisi', 'technician', 'sarpras_teknisi']))
                    ->orWhereHas('gtkProfile');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('sarpras.repair.index', compact('repairs', 'stats', 'technicians'));
    }

    public function show(Request $request, string $id)
    {
        $repair = RepairRequest::with([
            'asset.room.building', 'asset.workUnit', 'reportedBy', 'assignedTo',
            'workOrders.assignee', 'workOrders.progressSteps',
        ])->findOrFail($id);

        if (! $this->isCentralSarpras() && ! $this->canViewAll($request)) {
            $this->authorizeWorkUnitAccess($repair->asset?->work_unit_id, $request);
        }

        $technicians = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $canManage = $this->isCentralSarpras();

        return view('sarpras.repair.show', compact('repair', 'technicians', 'canManage'));
    }

    public function storeWorkOrder(Request $request, string $id)
    {
        $this->authorizeCentralSarpras();

        $repair = RepairRequest::with('asset')->findOrFail($id);

        $validated = $request->validate([
            'assignee_id' => 'required|uuid|exists:users,id',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'scope_of_work' => 'required|string|max:2000',
            'type' => 'required|in:internal,vendor',
            'estimated_cost' => 'nullable|numeric|min:0',
        ]);

        $workOrder = DB::transaction(function () use ($repair, $validated) {
            $workOrder = WorkOrder::create([
                'repair_request_id' => $repair->id,
                'asset_id' => $repair->asset_id,
                'assignee_id' => $validated['assignee_id'],
                'type' => $validated['type'],
                'scope_of_work' => $validated['scope_of_work'],
                'scheduled_date' => $validated['scheduled_date'],
                'status' => 'dispatched',
                'total_cost' => $validated['estimated_cost'] ?? 0,
            ]);

            $repair->update([
                'assigned_to' => $validated['assignee_id'],
                'status' => RepairRequest::STATUS_EXECUTION_PENDING,
                'approved_at' => now(),
            ]);

            return $workOrder;
        });

        return redirect()->route('sarpras.repair.show', $repair->id)
            ->with('success', 'Work order '.$workOrder->order_number.' dibuat dan petugas sudah ditugaskan.');
    }
}
