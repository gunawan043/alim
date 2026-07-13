<?php

namespace App\Http\Controllers\Api\Mobile\V1\Sarpras;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\RepairRequest;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\Sarpras\AssetEventLogger;
use App\Services\Sarpras\AssetPassportService;
use App\Services\Sarpras\IllegalStateTransitionException;
use App\Services\Sarpras\RepairRequestWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AssetPassportApiController extends Controller
{
    public function __construct(
        private AssetPassportService $passport,
        private AssetEventLogger $eventLogger,
        private RepairRequestWorkflow $repairWorkflow
    ) {}

    public function show(Request $request, string $lookup): JsonResponse
    {
        if (! canPermission('sarpras_aset_view')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        try {
            $passport = $this->passport->findByLookup(
                lookupValue: $lookup,
                scannedBy: $request->user()?->id,
                source: 'mobile_app',
                purpose: $request->input('purpose'),
            );

            if (! $passport) {
                return response()->json([
                    'success' => false,
                    'error' => 'asset_not_found',
                    'message' => "Aset dengan kode/UUID '{$lookup}' tidak ditemukan.",
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $passport,
            ]);
        } catch (\Throwable $e) {
            Log::error('AssetPassportApi::show failed', [
                'lookup' => $lookup,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'server_error',
                'message' => 'Terjadi kesalahan saat memuat passport.',
            ], 500);
        }
    }

    public function submitDamageReport(Request $request, string $lookup): JsonResponse
    {
        if (! canPermission('sarpras_aset_create')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        try {
            $validated = $request->validate([
                'title' => 'nullable|string|max:255',
                'description' => 'required|string|max:2000',
                'severity' => 'nullable|in:low,medium,high',
                'priority' => 'nullable|in:low,normal,high,urgent',
                'photo' => 'nullable|image|max:5120',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'validation_failed',
                'errors' => $e->errors(),
            ], 422);
        }

        $asset = Asset::where(function ($q) use ($lookup) {
            $q->where('id', $lookup)->orWhere('asset_code', $lookup);
        })->first();

        if (! $asset) {
            return response()->json([
                'success' => false,
                'error' => 'asset_not_found',
            ], 404);
        }

        try {
            $reporter = $request->user();
            $repair = $this->repairWorkflow->submitDamageReport(
                $asset,
                $reporter,
                $validated['title'] ?? 'Laporan kerusakan '.$asset->asset_code,
                $validated['description'],
                $validated['priority'] ?? 'normal'
            );

            return response()->json([
                'success' => true,
                'message' => 'Laporan kerusakan berhasil dikirim.',
                'data' => [
                    'repair_request_id' => $repair->id,
                    'request_number' => $repair->request_number,
                    'status' => $repair->status,
                    'asset_id' => $asset->id,
                ],
            ], 201);
        } catch (IllegalStateTransitionException $e) {
            return response()->json([
                'success' => false,
                'error' => 'workflow_error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('AssetPassportApi::submitDamageReport failed', [
                'lookup' => $lookup,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'server_error',
                'message' => 'Terjadi kesalahan saat mengirim laporan.',
            ], 500);
        }
    }

    /* ====================================================================
     *  New workflow endpoints exposed to mobile / 3rd-party callers
     * ==================================================================== */

    public function listRepairs(Request $request): JsonResponse
    {
        if (! canPermission('sarpras_maintenance_view')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $query = RepairRequest::query()->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($assetId = $request->query('asset_id')) {
            $query->where('asset_id', $assetId);
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate(20),
        ]);
    }

    public function verifyRepair(Request $request, string $repairId): JsonResponse
    {
        if (! canPermission('sarpras_maintenance_edit')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $validated = $request->validate([
            'approved' => 'required|boolean',
            'notes' => 'nullable|string|max:1000',
            'rejected_reason' => 'nullable|string|max:500',
        ]);

        $repair = RepairRequest::findOrFail($repairId);
        $this->repairWorkflow->verify(
            $repair,
            $request->user(),
            (bool) $validated['approved'],
            $validated['notes'] ?? null,
            $validated['rejected_reason'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Status laporan diperbarui.',
        ]);
    }

    public function generateWorkOrder(Request $request, string $repairId): JsonResponse
    {
        if (! canPermission('sarpras_maintenance_create')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $validated = $request->validate([
            'scope_of_work' => 'required|string|max:2000',
            'scheduled_date' => 'nullable|date',
            'assignee_id' => 'nullable|integer|exists:users,id',
        ]);

        $repair = RepairRequest::findOrFail($repairId);
        $order = $this->repairWorkflow->generateWorkOrder(
            $repair,
            $request->user(),
            $validated['scope_of_work'],
            $validated['scheduled_date'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $order,
        ], 201);
    }

    public function assignTechnician(Request $request, string $orderId): JsonResponse
    {
        if (! canPermission('sarpras_maintenance_edit')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $validated = $request->validate([
            'technician_id' => 'required|integer|exists:users,id',
        ]);

        $order = WorkOrder::findOrFail($orderId);
        $technician = User::findOrFail($validated['technician_id']);

        $this->repairWorkflow->assignTechnician($order, $request->user(), $technician);

        return response()->json([
            'success' => true,
            'message' => 'Teknisi berhasil ditugaskan.',
        ]);
    }

    public function acceptOrder(Request $request, string $orderId): JsonResponse
    {
        if (! canPermission('sarpras_maintenance_edit')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $order = WorkOrder::findOrFail($orderId);
        $this->repairWorkflow->acceptWorkOrder($order, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Order diterima.',
        ]);
    }

    public function startWork(Request $request, string $orderId): JsonResponse
    {
        if (! canPermission('sarpras_maintenance_edit')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $order = WorkOrder::findOrFail($orderId);
        $this->repairWorkflow->startWork($order, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Pengerjaan dimulai.',
        ]);
    }

    public function completeOrder(Request $request, string $orderId): JsonResponse
    {
        if (! canPermission('sarpras_maintenance_edit')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $validated = $request->validate([
            'completion_notes' => 'required|string|max:2000',
            'condition_after' => 'required|string|max:50',
            'total_cost' => 'nullable|numeric|min:0',
        ]);

        $order = WorkOrder::findOrFail($orderId);
        $this->repairWorkflow->completeRepair(
            $order,
            $request->user(),
            $validated['completion_notes'],
            $validated['condition_after'],
            (float) ($validated['total_cost'] ?? 0)
        );

        return response()->json([
            'success' => true,
            'message' => 'Perbaikan selesai dicatat.',
        ]);
    }

    public function verifyByPic(Request $request, string $repairId): JsonResponse
    {
        if (! canPermission('sarpras_maintenance_edit')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $repair = RepairRequest::findOrFail($repairId);
        $this->repairWorkflow->verifyByPic($repair, $request->user(), $validated['notes'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'PIC verifikasi tersimpan.',
        ]);
    }
}
