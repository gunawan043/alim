<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\AssetMovement;
use App\Services\Sarpras\MovementWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Multi-stage AssetMovement workflow controller.
 * This runs in addition to the legacy AssetLocationHistory controller —
 * the older one logs instantaneous moves, this one manages multi-stage transfers.
 */
class SarprasAssetMovementController extends SarprasBaseController
{
    public function __construct(protected MovementWorkflow $workflow) {}

    public function index(Request $request)
    {
        $query = AssetMovement::with(['asset', 'fromRoom', 'toRoom', 'requester', 'approver']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('asset_id')) {
            $query->where('asset_id', $request->asset_id);
        }

        $movements = $query->latest()->paginate(15);

        return view('sarpras.movements.index', compact('movements'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'asset_id' => 'required|uuid',
            'to_room_id' => 'nullable|uuid',
            'to_holder_id' => 'nullable|uuid',
            'reason' => 'required|string|max:255',
            'justification' => 'nullable|string|max:2000',
        ]);

        $movement = $this->workflow->request(Auth::user(), $data);

        return response()->json([
            'success' => true,
            'movement' => $movement->only([
                'id', 'movement_number', 'status', 'asset_id', 'to_room_id', 'reason',
            ]),
        ], 201);
    }

    public function show(string $id)
    {
        $movement = AssetMovement::with([
            'asset', 'fromRoom', 'toRoom', 'requester', 'approver', 'carrier', 'receiver', 'verifier',
            'approvals.user', 'photos',
        ])->findOrFail($id);

        return view('sarpras.movements.show', compact('movement'));
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'notes' => 'nullable|string',
            'carrier_id' => 'nullable|uuid',
        ]);

        $movement = AssetMovement::findOrFail($id);
        $updated = $this->workflow->approve($movement, Auth::user(), $data);

        return response()->json(['success' => true, 'movement' => $updated]);
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['reason' => 'required|string']);

        $movement = AssetMovement::findOrFail($id);
        $updated = $this->workflow->reject($movement, Auth::user(), $data['reason']);

        return response()->json(['success' => true, 'movement' => $updated]);
    }

    public function startTransit(Request $request, string $id): JsonResponse
    {
        $movement = AssetMovement::findOrFail($id);
        $updated = $this->workflow->startTransit($movement, Auth::user(), $request->all());

        return response()->json(['success' => true, 'movement' => $updated]);
    }

    public function confirmReceived(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'condition_after' => 'nullable|string',
        ]);

        $movement = AssetMovement::findOrFail($id);
        $updated = $this->workflow->confirmReceived($movement, Auth::user(), $data);

        return response()->json(['success' => true, 'movement' => $updated]);
    }

    public function verify(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['notes' => 'nullable|string']);

        $movement = AssetMovement::findOrFail($id);
        $updated = $this->workflow->verify($movement, Auth::user(), $data);

        return response()->json(['success' => true, 'movement' => $updated]);
    }

    public function complete(string $id): JsonResponse
    {
        $movement = AssetMovement::findOrFail($id);
        $updated = $this->workflow->complete($movement, Auth::user());

        return response()->json(['success' => true, 'movement' => $updated]);
    }

    public function snapshot(string $id): JsonResponse
    {
        $movement = AssetMovement::with(['asset', 'approvals.user', 'photos'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'snapshot' => $this->workflow->snapshotFor($movement),
        ]);
    }
}