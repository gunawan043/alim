<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\AssetMovement;
use App\Models\WorkOrder;
use App\Services\Sarpras\MovementWorkflow;
use App\Services\Sarpras\WorkOrderExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Offline API contract — mobile workers push their queue when network returns.
 *
 * Each request is expected to be a batch of state changes:
 *   { operations: [ { type: "movement.in_transit", ref_id: "...", payload: {...}, occurred_at: ... }, ... ] }
 *
 * Returns per-operation success/failure so the client can reconcile its local store.
 */
class SarprasMobileSyncController extends SarprasBaseController
{
    public function __construct(
        protected MovementWorkflow $movementWorkflow,
        protected WorkOrderExecutionService $orderExecution,
    ) {}

    public function pull(Request $request): JsonResponse
    {
        $user = Auth::user();

        $orders = WorkOrder::where('assigned_to', $user->id)
            ->whereIn('status', ['assigned', 'in_progress', 'paused'])
            ->with(['asset', 'progressNotes', 'pauseEvents'])
            ->get();

        $movements = AssetMovement::where('requester_id', $user->id)
            ->orWhere('carrier_id', $user->id)
            ->orWhere('receiver_id', $user->id)
            ->whereIn('status', ['approved', 'in_transit'])
            ->with(['asset', 'approvals.user', 'photos'])
            ->get();

        return response()->json([
            'success' => true,
            'pulled_at' => now()->toIso8601String(),
            'data' => [
                'orders' => $orders->map(fn ($o) => $this->orderExecution->syncSnapshot($o)),
                'movements' => $movements->map(fn ($m) => $this->movementWorkflow->snapshotFor($m)),
            ],
        ]);
    }

    public function push(Request $request): JsonResponse
    {
        $ops = $request->input('operations', []);
        if (! is_array($ops)) {
            return response()->json(['success' => false, 'error' => 'operations must be an array'], 422);
        }

        $results = [];
        foreach ($ops as $index => $op) {
            try {
                $results[] = ['index' => $index, 'success' => true, 'result' => $this->dispatchOp($op)];
            } catch (\Throwable $e) {
                Log::warning('Offline sync op failed', ['op' => $op, 'err' => $e->getMessage()]);
                $results[] = ['index' => $index, 'success' => false, 'error' => $e->getMessage()];
            }
        }

        return response()->json([
            'success' => true,
            'pushed_at' => now()->toIso8601String(),
            'results' => $results,
        ]);
    }

    protected function dispatchOp(array $op): array
    {
        $type = $op['type'] ?? '';
        $user = Auth::user();
        $payload = $op['payload'] ?? [];

        return match ($type) {
            'movement.in_transit' => [
                'movement' => $this->movementWorkflow->startTransit(
                    AssetMovement::findOrFail($op['ref_id']),
                    $user,
                    $payload,
                )->id,
            ],
            'movement.received' => [
                'movement' => $this->movementWorkflow->confirmReceived(
                    AssetMovement::findOrFail($op['ref_id']),
                    $user,
                    $payload,
                )->id,
            ],
            'movement.verified' => [
                'movement' => $this->movementWorkflow->verify(
                    AssetMovement::findOrFail($op['ref_id']),
                    $user,
                    $payload,
                )->id,
            ],
            'workorder.note' => [
                'note' => $this->orderExecution->logNote(
                    WorkOrder::findOrFail($op['ref_id']),
                    $user,
                    $payload['note'] ?? '',
                    $payload['note_type'] ?? 'comment',
                )->id,
            ],
            'workorder.pause' => [
                'event' => $this->orderExecution->pause(
                    WorkOrder::findOrFail($op['ref_id']),
                    $user,
                    $payload['reason_code'] ?? 'other',
                    $payload['reason_text'] ?? null,
                )->id,
            ],
            default => throw new \InvalidArgumentException("Unknown op type: {$type}"),
        };
    }
}