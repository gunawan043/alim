<?php

namespace App\Http\Controllers\Api\Sarpras;

use App\Events\Sarpras\AssetQrScanned;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sarpras\QrScanRequest;
use App\Models\Asset;
use App\Models\AssetMaintenanceLog;
use App\Models\QrScanHistory;
use App\Models\RepairCostHistory;
use App\Models\RepairRequest;
use App\Models\StockOpnameItem;
use App\Models\WorkOrder;
use App\Services\Sarpras\AssetEventLogger;
use App\Services\Sarpras\AssetPassportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetPassportController extends Controller
{
    public function __construct(
        protected readonly AssetPassportService $passport,
        protected readonly AssetEventLogger $logger,
    ) {}

    /**
     * Handle QR code scan — mobile or web scanner.
     */
    public function qrScan(QrScanRequest $request): JsonResponse
    {
        if (! canPermission('sarpras_aset_view')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $qrToken = $request->input('qr_token');

        $asset = Asset::with([
            'category', 'workUnit.parent', 'building', 'room',
            'procurement',
        ])
        ->where('id', $qrToken)
        ->orWhere('asset_code', $qrToken)
        ->firstOrFail();

        $user = $request->user();

        // Record scan via service
        $scan = $this->logger->logScan($asset, $user, [
            'scan_type' => $request->input('scan_type'),
            'lookup_value' => $qrToken,
            'source' => 'web_scanner',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'condition' => $request->input('condition'),
            'purpose' => $request->input('notes'),
            'session_id' => $request->input('session_id'),
        ]);

        event(new AssetQrScanned($asset, $scan, $user));

        $passportData = $this->passport->getForAsset($asset);

        return response()->json([
            'success' => true,
            'data' => $passportData,
            'scan' => [
                'id' => $scan->id,
                'scanned_at' => $scan->created_at?->toIso8601String(),
                'scanned_by' => $scan->scannedBy?->name ?? 'anonymous',
                'condition' => $request->input('condition'),
            ],
        ]);
    }

    /**
     * Web — Asset Passport Detail (deeper than QR scan payload).
     */
    public function passportDetail(Request $request, string $assetId): JsonResponse
    {
        if (! canPermission('sarpras_aset_view')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $asset = Asset::with(['category', 'workUnit.parent', 'building', 'room'])
            ->findOrFail($assetId);

        $passport = $this->passport->buildFull($asset);

        return response()->json([
            'success' => true,
            'data' => $passport,
        ]);
    }

    /**
     * Public QR lookup (no auth — for kiosk / poster scan).
     */
    public function qrPublicLookup(string $token): JsonResponse
    {
        $asset = Asset::with(['category', 'workUnit', 'room'])
            ->where('id', $token)
            ->orWhere('asset_code', $token)
            ->firstOrFail();

        $data = [
            'identity' => [
                'asset_code' => $asset->asset_code,
                'asset_name' => $asset->asset_name,
                'category' => $asset->category?->name ?? '-',
            ],
            'location' => [
                'work_unit' => $asset->workUnit?->name ?? '-',
                'room' => $asset->room?->room_name ?? '-',
            ],
            'status' => $asset->status,
            'condition' => $asset->condition,
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * QR Scan History (paginated).
     */
    public function scanHistory(Request $request): JsonResponse
    {
        if (! canPermission('sarpras_qr_view')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $user = $request->user();
        $query = QrScanHistory::with(['asset', 'scannedBy'])
            ->orderByDesc('created_at');

        if (! canPermission('sarpras_all_access')) {
            $query->where('scanned_by', $user->id);
        }

        if ($request->filled('asset_id')) {
            $query->where('asset_id', $request->asset_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate($request->per_page ?? 50),
        ]);
    }

    /**
     * Repair Cost History for an asset.
     */
    public function costHistory(Request $request, string $assetId): JsonResponse
    {
        if (! canPermission('sarpras_laporan_view')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $costs = RepairCostHistory::where('asset_id', $assetId)
            ->orderByDesc('incurred_date')
            ->get();

        $summary = [
            'total_amount' => (float) $costs->sum('amount'),
            'count' => $costs->count(),
            'by_category' => $costs->groupBy('cost_category')->map(fn ($items, $cat) => [
                'category' => $cat,
                'count' => $items->count(),
                'total' => (float) $items->sum('amount'),
            ])->values()->toArray(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'items' => $costs,
            ],
        ]);
    }
}
