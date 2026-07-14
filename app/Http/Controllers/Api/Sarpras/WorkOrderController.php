<?php

namespace App\Http\Controllers\Api\Sarpras;

use App\Events\Sarpras\WorkOrderAssigned;
use App\Events\Sarpras\WorkOrderProgressAdded;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sarpras\CreateWorkOrderFromRepairRequest;
use App\Http\Requests\Sarpras\RecordRepairCostRequest;
use App\Http\Requests\Sarpras\UpdateWorkOrderProgressRequest;
use App\Models\Asset;
use App\Models\RepairCostHistory;
use App\Models\SparePartUsage;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderProgress;
use App\Services\Sarpras\AssetEventLogger;
use App\Services\Sarpras\RepairRequestWorkflow;
use App\Services\SarprasCacheInvalidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkOrderController extends Controller
{
    public function __construct(
        protected readonly RepairRequestWorkflow $workflow,
        protected readonly AssetEventLogger $logger,
        protected readonly SarprasCacheInvalidator $cacheInvalidator,
    ) {}

    /**
     * List work orders (with filters).
     */
    public function index(Request $request): JsonResponse
    {
        if (! canPermission('sarpras_maintenance_view')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $query = WorkOrder::with([
            'asset:id,asset_code,asset_name,category_id',
            'asset.category:id,name',
            'assignee:id,name',
            'repairRequest:id,request_number,title',
        ]);

        $user = $request->user();
        if (! canPermission('sarpras_all_access')) {
            $query->where('assignee_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('assignee_id')) {
            $query->where('assignee_id', $request->assignee_id);
        }
        if ($request->filled('asset_id')) {
            $query->where('asset_id', $request->asset_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('scheduled_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('scheduled_date', '<=', $request->date_to);
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderByDesc('created_at')->paginate($request->per_page ?? 20),
        ]);
    }

    /**
     * Generate WO from a verified Repair Request.
     */
    public function generateFromRepair(CreateWorkOrderFromRepairRequest $request): JsonResponse
    {
        if (! canPermission('sarpras_maintenance_create')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $asset = Asset::findOrFail($request->asset_id);
        $assignee = User::findOrFail($request->assignee_id);

        $wo = $this->workflow->generateWorkOrder(
            repair: \App\Models\RepairRequest::findOrFail($request->input('repair_request_id')),
            actor: $request->user(),
            scope: $request->scope_of_work,
            scheduledDate: $request->scheduled_date,
            assignee: $assignee,
        );

        // Log asset event
        $this->logger->logAssetEvent(
            asset: $asset,
            eventType: 'work_order_generated',
            eventDetail: "WO generated: {$wo->order_number} for {$asset->asset_code}",
            actor: $request->user(),
        );

        // Dispatch WorkOrderAssigned event (workflow doesn't dispatch it for generateWorkOrder).
        event(new WorkOrderAssigned($wo, $assignee, $request->user()));

        $this->cacheInvalidator->invalidateWorkOrder($wo);

        return response()->json([
            'success' => true,
            'data' => $wo->load(['asset', 'assignee', 'repairRequest']),
        ], 201);
    }

    /**
     * WO Detail with full timeline.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        if (! canPermission('sarpras_maintenance_view')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $wo = WorkOrder::with([
            'asset', 'asset.category', 'assignee', 'repairRequest',
            'progressSteps' => fn ($q) => $q->orderByDesc('created_at'),
            'progressSteps.author:id,name',
            'sparePartUsages',
            'sparePartUsages.sparePart:id,name,unit,stock',
            'costHistories',
        ])->findOrFail($id);

        // Calculate costs
        $costs = $wo->costHistories;
        $costSummary = [
            'labor' => (float) $costs->where('cost_category', 'labor')->sum('amount'),
            'transport' => (float) $costs->where('cost_category', 'transport')->sum('amount'),
            'sparepart' => (float) $costs->where('cost_category', 'sparepart')->sum('amount'),
            'vendor' => (float) $costs->where('cost_category', 'vendor_service')->sum('amount'),
            'other' => (float) $costs->where('cost_category', 'other')->sum('amount'),
            'total' => (float) $costs->sum('amount'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'work_order' => $wo,
                'progress_percent' => $wo->progressPercent(),
                'cost_summary' => $costSummary,
                'available_transitions' => \App\Services\Sarpras\StateMachineRegistry::getNextStates(
                    \App\Services\Sarpras\StateMachineRegistry::WORK_ORDER,
                    $wo->status,
                ),
                'timeline' => $wo->progressSteps->map(fn ($s) => [
                    'id' => $s->id,
                    'type' => $s->step_type,
                    'title' => $s->title,
                    'description' => $s->description,
                    'author' => $s->author?->name,
                    'progress_percent' => $s->progress_percent,
                    'occurred_at' => $s->created_at?->toIso8601String(),
                    'photos' => $s->photo_paths,
                ]),
            ],
        ]);
    }

    /**
     * Add a progress step (notes, photo, sparepart, cost) to WO.
     */
    public function addProgress(UpdateWorkOrderProgressRequest $request, string $id): JsonResponse
    {
        if (! canPermission('sarpras_maintenance_edit')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $wo = WorkOrder::findOrFail($id);

        // Validate state transition
        if ($request->filled('status')) {
            \App\Services\Sarpras\StateMachineRegistry::assertValidTransition(
                \App\Services\Sarpras\StateMachineRegistry::WORK_ORDER,
                $wo->status,
                $request->status,
            );
        }

        DB::transaction(function () use ($wo, $request) {
            // Update status if requested
            if ($request->filled('status')) {
                $wo->status = $request->status;
                if ($request->status === 'working' && ! $wo->actual_start) {
                    $wo->actual_start = now();
                }
                if ($request->status === 'completed') {
                    $wo->actual_end = now();
                }
                $wo->save();
            }

            // Upload photos if any
            $photoPaths = $this->uploadPhotos($request->file('photos', []));

            // Create progress step
            $progress = WorkOrderProgress::create([
                'work_order_id' => $wo->id,
                'step_type' => $request->progress_type,
                'title' => $request->input('title') ?? $this->makeTitle($request->progress_type),
                'description' => $request->notes,
                'progress_percent' => $request->input('progress_percent', 0),
                'photo_paths' => $photoPaths,
                'author_id' => $request->user()->id,
                'recorded_at' => now(),
            ]);

            event(new WorkOrderProgressAdded($wo, $progress, $request->user()));
        });

        $this->cacheInvalidator->invalidateWorkOrder($wo->fresh());

        return response()->json([
            'success' => true,
            'data' => $wo->fresh()->load('progressSteps'),
        ], 201);
    }

    /**
     * Record a cost against a Work Order.
     */
    public function recordCost(RecordRepairCostRequest $request, string $id): JsonResponse
    {
        if (! canPermission('sarpras_maintenance_edit')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $wo = WorkOrder::with('asset')->findOrFail($id);

        $cost = DB::transaction(function () use ($wo, $request) {
            $cost = RepairCostHistory::create([
                'work_order_id' => $wo->id,
                'asset_id' => $wo->asset_id,
                'cost_category' => $request->cost_category,
                'description' => $request->description,
                'amount' => $request->amount,
                'incurred_date' => $request->incurred_date,
                'document_number' => $request->document_number,
                'vendor_name' => $request->vendor_name,
                'recorded_by' => $request->user()->id,
            ]);

            $wo->total_cost = (float) $wo->costHistories()->sum('amount');
            $wo->save();

            $this->logger->logAssetEvent(
                asset: $wo->refresh(),
                eventType: 'cost_recorded',
                eventDetail: "Cost recorded: Rp{$request->amount} ({$request->cost_category}) for WO {$wo->order_number}",
                actor: $request->user(),
                metadata: ['cost_id' => $cost->id],
            );

            event(new \App\Events\Sarpras\RepairCostRecorded($wo, $cost));

            return $cost;
        });

        $this->cacheInvalidator->invalidateWorkOrder($wo->fresh());

        return response()->json([
            'success' => true,
            'data' => $cost,
        ], 201);
    }

    /**
     * Record sparepart usage against a Work Order.
     */
    public function recordSparePart(Request $request, string $id): JsonResponse
    {
        if (! canPermission('sarpras_maintenance_edit')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $validated = $request->validate([
            'spare_part_id' => 'required|uuid|exists:spare_parts,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $wo = WorkOrder::findOrFail($id);

        $usage = DB::transaction(function () use ($wo, $validated, $request) {
            $usage = SparePartUsage::create([
                'work_order_id' => $wo->id,
                'spare_part_id' => $validated['spare_part_id'],
                'quantity' => $validated['quantity'],
                'unit_price' => $validated['unit_price'] ?? 0,
                'total_price' => $validated['quantity'] * ($validated['unit_price'] ?? 0),
                'notes' => $validated['notes'] ?? null,
                'used_by' => $request->user()->id,
                'used_at' => now(),
            ]);

            if ($validated['unit_price'] ?? 0) {
                RepairCostHistory::create([
                    'work_order_id' => $wo->id,
                    'asset_id' => $wo->asset_id,
                    'cost_category' => 'sparepart',
                    'description' => 'Spare part: '.($usage->sparePart?->name ?? '-')." ({$validated['quantity']} unit)",
                    'amount' => $usage->total_price,
                    'incurred_date' => now(),
                    'recorded_by' => $request->user()->id,
                ]);
            }

            return $usage;
        });

        $this->cacheInvalidator->invalidateWorkOrder($wo);

        return response()->json([
            'success' => true,
            'data' => $usage->load('sparePart'),
        ], 201);
    }

    /**
     * Transition a WO to a new status (state machine driven).
     */
    public function transition(Request $request, string $id): JsonResponse
    {
        if (! canPermission('sarpras_maintenance_edit')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $validated = $request->validate([
            'to' => 'required|in:assigned,accepted,working,waiting_sparepart,completed,closed,cancelled',
            'notes' => 'nullable|string|max:2000',
        ]);

        $wo = WorkOrder::findOrFail($id);

        \App\Services\Sarpras\StateMachineRegistry::assertValidTransition(
            \App\Services\Sarpras\StateMachineRegistry::WORK_ORDER,
            $wo->status,
            $validated['to'],
        );

        DB::transaction(function () use ($wo, $validated, $request) {
            $wo->status = $validated['to'];
            if ($validated['to'] === 'working' && ! $wo->actual_start) {
                $wo->actual_start = now();
            }
            if ($validated['to'] === 'completed') {
                $wo->actual_end = now();
                $wo->completion_notes = $validated['notes'] ?? $wo->completion_notes;
            }
            $wo->save();

            WorkOrderProgress::create([
                'work_order_id' => $wo->id,
                'step_type' => 'status_change',
                'title' => "Status: {$wo->status}",
                'description' => $validated['notes'] ?? null,
                'author_id' => $request->user()->id,
                'recorded_at' => now(),
            ]);

            // If completing, close the RepairRequest
            if ($validated['to'] === 'completed' && $wo->repair_request_id) {
                $repair = \App\Models\RepairRequest::find($wo->repair_request_id);
                if ($repair && in_array($repair->status, ['assigned', 'in_progress'])) {
                    $this->workflow->completeRepair($repair, $request->user(), $wo->completion_notes ?? '', 'baik', (float) $wo->total_cost);
                }
            }
        });

        $this->cacheInvalidator->invalidateWorkOrder($wo->fresh());

        return response()->json([
            'success' => true,
            'data' => $wo->fresh(),
        ]);
    }

    /**
     * Stats: per-technician, per-type, per-status.
     */
    public function stats(Request $request): JsonResponse
    {
        if (! canPermission('sarpras_maintenance_view')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $user = $request->user();
        $query = WorkOrder::query();

        if (! canPermission('sarpras_all_access')) {
            $query->where('assignee_id', $user->id);
        }

        $byStatus = $query->clone()->groupBy('status')
            ->selectRaw('status, COUNT(*) as count')->pluck('count', 'status')->toArray();

        $byType = $query->clone()->groupBy('type')
            ->selectRaw('type, COUNT(*) as count')->pluck('count', 'type')->toArray();

        // Mean completion time (closed WOs)
        $meanDays = $query->clone()->where('status', 'closed')
            ->whereNotNull('actual_start')->whereNotNull('actual_end')
            ->selectRaw('AVG(DATEDIFF(actual_end, actual_start)) as avg_days')
            ->value('avg_days');

        // Top technicians by completed WOs (last 90 days)
        $topTechs = $query->clone()
            ->where('status', 'closed')
            ->where('updated_at', '>=', now()->subDays(90))
            ->groupBy('assignee_id')
            ->selectRaw('assignee_id, COUNT(*) as completed_count')
            ->orderByDesc('completed_count')
            ->limit(10)
            ->with('assignee:id,name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'by_status' => $byStatus,
                'by_type' => $byType,
                'mean_completion_days' => round((float) ($meanDays ?? 0), 2),
                'top_technicians_90d' => $topTechs,
            ],
        ]);
    }

    /* ===================================================================
     *  Helpers
     * =================================================================== */

    protected function uploadPhotos(array $files): array
    {
        $paths = [];
        foreach ($files as $file) {
            if ($file && $file->isValid()) {
                $paths[] = $file->store('work-orders/progress', 'public');
            }
        }

        return $paths;
    }

    protected function makeTitle(string $type): string
    {
        return match ($type) {
            'note' => 'Catatan',
            'photo' => 'Foto',
            'sparepart' => 'Penggunaan Sparepart',
            'cost' => 'Pencatatan Biaya',
            default => 'Update',
        };
    }
}
