<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\WorkOrder;
use App\Services\Sarpras\WorkOrderExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Technician Workspace — used by the "teknisi" role to manage their active work orders.
 * Thin wrapper around WorkOrderExecutionService.
 */
class SarprasTechnicianWorkspaceController extends SarprasBaseController
{
    public function __construct(protected WorkOrderExecutionService $execution) {}

    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $orders = WorkOrder::where('assigned_to', $user->id)
            ->with(['asset.room', 'progressNotes'])
            ->orderByRaw("FIELD(status, 'in_progress', 'paused', 'assigned')")
            ->latest()
            ->paginate(15);

        return view('sarpras.technician.dashboard', compact('orders'));
    }

    public function show(Request $request, string $orderId)
    {
        $order = WorkOrder::with([
            'asset.room', 'asset.photos', 'progressNotes.user', 'pauseEvents.user',
        ])->findOrFail($orderId);

        return view('sarpras.technician.show', compact('order'));
    }

    public function start(Request $request, string $orderId): JsonResponse
    {
        $order = WorkOrder::findOrFail($orderId);
        $updated = $this->execution->start($order, Auth::user(), $request->all());
        return response()->json(['success' => true, 'order' => $updated]);
    }

    public function pause(Request $request, string $orderId): JsonResponse
    {
        $data = $request->validate([
            'reason_code' => 'required|string|max:50',
            'reason_text' => 'nullable|string',
        ]);

        $order = WorkOrder::findOrFail($orderId);
        $event = $this->execution->pause($order, Auth::user(), $data['reason_code'], $data['reason_text'] ?? null);

        return response()->json(['success' => true, 'event' => $event]);
    }

    public function resume(Request $request, string $orderId): JsonResponse
    {
        $order = WorkOrder::findOrFail($orderId);
        $updated = $this->execution->resume($order, Auth::user(), $request->input('notes'));

        return response()->json(['success' => true, 'order' => $updated]);
    }

    public function complete(Request $request, string $orderId): JsonResponse
    {
        $order = WorkOrder::findOrFail($orderId);
        $updated = $this->execution->complete($order, Auth::user(), $request->all());

        return response()->json(['success' => true, 'order' => $updated]);
    }

    public function logNote(Request $request, string $orderId): JsonResponse
    {
        $data = $request->validate([
            'note' => 'required|string',
            'note_type' => 'nullable|string',
        ]);

        $order = WorkOrder::findOrFail($orderId);
        $note = $this->execution->logNote($order, Auth::user(), $data['note'], $data['note_type'] ?? 'comment');

        return response()->json(['success' => true, 'note' => $note]);
    }

    public function snapshot(Request $request, string $orderId): JsonResponse
    {
        $order = WorkOrder::with(['asset', 'progressNotes', 'pauseEvents'])->findOrFail($orderId);
        return response()->json([
            'success' => true,
            'snapshot' => $this->execution->syncSnapshot($order),
        ]);
    }
}