<?php

namespace App\Http\Controllers\Api\Sarpras;

use App\Events\Sarpras\RepairRequestSubmitted;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sarpras\ReviewDamageReportRequest;
use App\Http\Requests\Sarpras\SubmitDamageReportRequest;
use App\Models\Asset;
use App\Models\RepairRequest;
use App\Services\Sarpras\AssetEventLogger;
use App\Services\Sarpras\RepairRequestWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RepairRequestController extends Controller
{
    public function __construct(
        protected readonly RepairRequestWorkflow $workflow,
        protected readonly AssetEventLogger $logger,
    ) {}

    /**
     * Submit a damage report from the asset passport or web form.
     */
    public function submit(SubmitDamageReportRequest $request): JsonResponse
    {
        if (! canPermission('sarpras_aset_create')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $asset = Asset::findOrFail($request->asset_id);

        $photoPath = null;
        if ($request->hasFile('damage_photo')) {
            $photoPath = $request->file('damage_photo')->store('damage-reports', 'public');
        }

        DB::transaction(function () use ($request, $asset, $photoPath) {
            $repair = $this->workflow->submitDamageReport(
                asset: $asset,
                reporter: $request->user(),
                title: $request->input('title') ?? "Laporan Kerusakan: {$asset->asset_code}",
                description: $request->description,
                priority: $this->priorityFromSeverity($request->severity),
                extra: [
                    'damage_type' => $request->damage_type,
                    'severity' => $request->severity,
                    'damage_location' => $request->damage_location,
                    'photo_path' => $photoPath,
                ],
            );

            $this->logger->logAssetEvent(
                asset: $asset,
                eventType: 'repair_request_submitted',
                eventDetail: "Damage report submitted: {$repair->request_number}",
                actor: $request->user(),
                metadata: ['repair_request_id' => $repair->id, 'severity' => $request->severity],
            );

            event(new RepairRequestSubmitted($repair, $asset, $request->user()));
        });

        return response()->json([
            'success' => true,
            'message' => 'Laporan kerusakan berhasil dikirim.',
            'data' => [
                'request_number' => RepairRequest::latest()->first()?->request_number,
                'status' => 'submitted',
                'estimated_review_at' => now()->addHours(24)->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * List repair requests (filtered).
     */
    public function index(Request $request): JsonResponse
    {
        if (! canPermission('sarpras_maintenance_view')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $query = RepairRequest::with([
            'asset:id,asset_code,asset_name,category_id',
            'asset.category:id,name',
            'reporter:id,name',
            'workOrders:id,work_order_id,order_number,status',
            'verifier:id,name',
        ])->orderByDesc('created_at');

        $user = $request->user();
        if (! canPermission('sarpras_all_access')) {
            $query->where('reported_by', $user->id)
                ->orWhereHas('workOrders', fn ($q) => $q->where('assignee_id', $user->id));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('asset_id')) {
            $query->where('asset_id', $request->asset_id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate($request->per_page ?? 20),
        ]);
    }

    /**
     * Show one repair request.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        if (! canPermission('sarpras_maintenance_view')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $repair = RepairRequest::with([
            'asset', 'asset.category', 'reporter', 'verifier',
            'workOrders', 'workOrders.assignee:id,name', 'workOrders.progressSteps',
            'costHistories',
        ])->findOrFail($id);

        $this->authorize('view', $repair);

        return response()->json([
            'success' => true,
            'data' => [
                'repair' => $repair,
                'available_transitions' => \App\Services\Sarpras\StateMachineRegistry::getNextStates(
                    \App\Services\Sarpras\StateMachineRegistry::REPAIR_REQUEST,
                    $repair->status,
                ),
                'cost_summary' => [
                    'total' => (float) $repair->costHistories->sum('amount'),
                    'count' => $repair->costHistories->count(),
                ],
            ],
        ]);
    }

    /**
     * Review a submitted report (PIC/admin only).
     */
    public function review(ReviewDamageReportRequest $request, string $id): JsonResponse
    {
        if (! canPermission('sarpras_maintenance_edit')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $repair = RepairRequest::findOrFail($id);

        $this->authorize('verify', $repair);

        $approved = $request->decision === 'verified';
        $this->workflow->verify(
            repair: $repair,
            actor: $request->user(),
            approved: $approved,
            notes: $request->review_notes,
        );

        $this->logger->logAssetEvent(
            asset: $repair->asset,
            eventType: 'repair_reviewed',
            eventDetail: "Repair {$repair->request_number} " . ($approved ? 'verified' : 'rejected'),
            actor: $request->user(),
        );

        return response()->json([
            'success' => true,
            'message' => $approved ? 'Laporan diverifikasi.' : 'Laporan ditolak.',
            'data' => $repair->fresh(['verifier']),
        ]);
    }

    /**
     * Map severity to priority enum.
     */
    protected function priorityFromSeverity(string $severity): string
    {
        return match ($severity) {
            'critical' => 'urgent',
            'high' => 'high',
            'medium' => 'normal',
            default => 'low',
        };
    }
}
