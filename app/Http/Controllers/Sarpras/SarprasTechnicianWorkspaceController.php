<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\WorkOrder;
use App\Services\Sarpras\SarprasNotificationService;
use App\Services\Sarpras\WorkOrderExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Technician Workspace — used by the "teknisi" role to manage their active work orders.
 * Thin wrapper around WorkOrderExecutionService.
 */
class SarprasTechnicianWorkspaceController extends SarprasBaseController
{
    public function __construct(
        protected WorkOrderExecutionService $execution,
        protected SarprasNotificationService $notifier,
    ) {}

    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $query = WorkOrder::where('assignee_id', $user->id)
            ->with(['asset.room.building', 'room.building', 'building', 'maintenanceSchedule', 'progressSteps'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->search;
                $q->where(fn ($sub) => $sub->where('order_number', 'like', "%{$s}%")
                    ->orWhere('target_name', 'like', "%{$s}%")
                    ->orWhere('target_location', 'like', "%{$s}%"));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));

        $stats = [
            'active' => (clone $query)->whereIn('status', ['in_progress', 'paused', 'waiting_parts'])->count(),
            'assigned' => (clone $query)->whereIn('status', ['draft', 'dispatched'])->count(),
            'completed' => (clone $query)->whereIn('status', ['completed', 'closed'])->count(),
            'available' => WorkOrder::whereNull('assignee_id')->whereIn('status', ['draft', 'dispatched'])->count(),
        ];

        $orders = $query->orderByRaw("FIELD(status, 'in_progress', 'waiting_parts', 'dispatched', 'draft', 'completed', 'closed')")
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $available = WorkOrder::whereNull('assignee_id')
            ->with(['asset.room.building', 'room.building', 'building', 'maintenanceSchedule'])
            ->whereIn('status', ['draft', 'dispatched'])
            ->latest()
            ->limit(10)
            ->get();

        return view('sarpras.technician.dashboard', compact('orders', 'available', 'stats'));
    }

    public function show(string $userId, Request $request, string $orderId)
    {
        $order = WorkOrder::with([
            'asset.room.building', 'room.building', 'building', 'maintenanceSchedule', 'asset.photos', 'repairRequest', 'progressSteps.performer',
        ])->findOrFail($orderId);

        return view('sarpras.technician.show', compact('order'));
    }

    public function start(string $userId, Request $request, string $orderId): JsonResponse|RedirectResponse
    {
        $order = WorkOrder::findOrFail($orderId);
        $updated = $this->execution->start($order, Auth::user(), $request->all());

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'order' => $updated]);
        }

        return back()->with('success', 'Work order dimulai.');
    }

    public function pause(string $userId, Request $request, string $orderId): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'reason_code' => 'required|string|max:50',
            'reason_text' => 'nullable|string',
        ]);

        $order = WorkOrder::findOrFail($orderId);
        $event = $this->execution->pause($order, Auth::user(), $data['reason_code'], $data['reason_text'] ?? null);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'event' => $event]);
        }

        return back()->with('success', 'Work order dijeda.');
    }

    public function resume(string $userId, Request $request, string $orderId): JsonResponse|RedirectResponse
    {
        $order = WorkOrder::findOrFail($orderId);
        $updated = $this->execution->resume($order, Auth::user(), $request->input('notes'));

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'order' => $updated]);
        }

        return back()->with('success', 'Work order dilanjutkan.');
    }

    public function complete(string $userId, Request $request, string $orderId): JsonResponse|RedirectResponse
    {
        $order = WorkOrder::findOrFail($orderId);
        $updated = $this->execution->complete($order, Auth::user(), $request->all());

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'order' => $updated]);
        }

        return back()->with('success', 'Work order diselesaikan.');
    }

    public function logNote(string $userId, Request $request, string $orderId): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'note' => 'required|string',
            'note_type' => 'nullable|string',
        ]);

        $order = WorkOrder::findOrFail($orderId);
        $note = $this->execution->logNote($order, Auth::user(), $data['note'], $data['note_type'] ?? 'comment');

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'note' => $note]);
        }

        return back()->with('success', 'Catatan pekerjaan disimpan.');
    }

    public function snapshot(string $userId, Request $request, string $orderId): JsonResponse
    {
        $order = WorkOrder::with(['asset', 'progressNotes', 'pauseEvents'])->findOrFail($orderId);

        return response()->json([
            'success' => true,
            'snapshot' => $this->execution->syncSnapshot($order),
        ]);
    }

    public function claim(string $userId, Request $request, string $orderId): RedirectResponse
    {
        $order = WorkOrder::findOrFail($orderId);

        // Only unassigned or unclaimed work orders can be claimed
        if ($order->assignee_id && $order->assignee_id !== Auth::id()) {
            return redirect()
                ->route('sarpras.teknisi.show', ['orderId' => $order->id])
                ->withErrors(['claim' => 'Work Order sudah di-claim teknisi lain.']);
        }

        $order->update([
            'assignee_id' => Auth::id(),
            'status' => $order->status === 'draft' ? 'dispatched' : $order->status,
        ]);

        try {
            $this->notifier->dispatchWorkOrderCreated($order);
        } catch (\Throwable $e) {
            logger()->warning('sarpras.teknisi.claim.notification_failed', ['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('sarpras.teknisi.show', ['orderId' => $order->id])
            ->with('success', "Work Order {$order->order_number} berhasil di-claim.");
    }
}
