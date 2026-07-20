<?php

namespace App\Http\Controllers\Sarpras;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Services\Sarpras\SarprasNotificationService;
use App\Services\SarprasCacheInvalidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Kepala Sarpras final sign-off after Work Order completion.
 * Closes the work order and pushes a completion event into the asset's passport history.
 */
class SarprasKepalaApprovalController extends Controller
{
    public function __construct(
        protected SarprasNotificationService $notifier,
        protected SarprasCacheInvalidator $cacheInvalidator,
    ) {}

    public function index()
    {
        $pending = WorkOrder::with(['asset', 'technician', 'repairRequest'])
            ->where('status', 'completed')
            ->latest()
            ->paginate(20);

        return view('sarpras.kepala.index', compact('pending'));
    }

    public function show(string $id)
    {
        $order = WorkOrder::with(['asset', 'technician', 'repairRequest', 'progressNotes.user'])
            ->findOrFail($id);

        return view('sarpras.kepala.show', compact('order'));
    }

    public function approve(Request $request, string $id): RedirectResponse
    {
        $order = WorkOrder::findOrFail($id);

        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $order->update([
            'status' => 'closed',
            'kepala_approved_at' => now(),
            'kepala_approved_by' => Auth::id(),
            'kepala_notes' => $request->input('notes'),
        ]);

        // If linked to a repair request, close the request and put the asset back
        if ($order->repairRequest) {
            $order->repairRequest->update([
                'status' => \App\Models\RepairRequest::STATUS_CLOSED,
            ]);
        }
        if ($order->asset) {
            $order->asset->update([
                'current_status' => 'in_service',
            ]);
        }

        $this->notifier->dispatchRepairStatusChange($order->repairRequest ?? $order);

        $this->cacheInvalidator->invalidateAll();

        return redirect()
            ->route('sarpras.kepala.index')
            ->with('success', "Work Order {$order->wo_number} telah disetujui Kepala Sarpras.");
    }
}
