<?php

namespace App\Http\Controllers\Api\Sarpras;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\StockOpnameItem;
use App\Models\StockOpnameSession;
use App\Services\Sarpras\AssetEventLogger;
use App\Services\Sarpras\AssetStatusTransitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockOpnameController extends Controller
{
    public function __construct(
        protected readonly AssetStatusTransitionService $transition,
        protected readonly AssetEventLogger $logger,
    ) {}

    /**
     * List stock opname sessions.
     */
    public function sessions(Request $request): JsonResponse
    {
        if (! canPermission('sarpras_all_access')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $query = StockOpnameSession::with(['creator:id,name', 'workUnit:id,name'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('work_unit_id')) {
            $query->where('work_unit_id', $request->work_unit_id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate($request->per_page ?? 20),
        ]);
    }

    /**
     * Create a new opname session.
     */
    public function createSession(Request $request): JsonResponse
    {
        if (! canPermission('sarpras_peminjaman_approve')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'work_unit_id' => 'nullable|uuid|exists:work_units,id',
            'category_id' => 'nullable|uuid|exists:asset_categories,id',
            'scheduled_at' => 'required|date',
            'officers' => 'required|array|min:1',
            'officers.*' => 'uuid|exists:users,id',
            'notes' => 'nullable|string|max:5000',
        ]);

        $session = DB::transaction(function () use ($validated, $request) {
            $session = StockOpnameSession::create([
                'title' => $validated['title'],
                'work_unit_id' => $validated['work_unit_id'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'scheduled_at' => $validated['scheduled_at'],
                'status' => 'planned',
                'officers' => $validated['officers'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
                'session_code' => 'OPN-'.now()->format('Ymd').'-'.Str::upper(Str::random(4)),
            ]);

            // Pre-populate with target assets
            $assetQuery = Asset::query();
            if ($session->work_unit_id) {
                $assetQuery->where('work_unit_id', $session->work_unit_id);
            }
            if ($session->category_id) {
                $assetQuery->where('category_id', $session->category_id);
            }
            $assets = $assetQuery->where('status', '!=', 'disposed')->get();

            foreach ($assets as $asset) {
                StockOpnameItem::create([
                    'session_id' => $session->id,
                    'asset_id' => $asset->id,
                    'expected_status' => $asset->status,
                    'expected_condition' => $asset->condition,
                    'observed_status' => 'pending',
                    'observed_condition' => null,
                ]);
            }

            return $session;
        });

        return response()->json([
            'success' => true,
            'data' => $session->load('items'),
        ], 201);
    }

    /**
     * Show a session with all items.
     */
    public function showSession(Request $request, string $id): JsonResponse
    {
        if (! canPermission('sarpras_all_access')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $session = StockOpnameSession::with([
            'items.asset', 'items.asset.category', 'items.officer:id,name',
            'creator:id,name',
        ])->findOrFail($id);

        $progress = [
            'total_items' => $session->items->count(),
            'found' => $session->items->where('observed_status', 'found')->count(),
            'missing' => $session->items->where('observed_status', 'missing')->count(),
            'damaged' => $session->items->where('observed_status', 'damaged')->count(),
            'mismatch' => $session->items->where('observed_status', 'location_mismatch')->count(),
            'pending' => $session->items->where('observed_status', 'pending')->count(),
            'completion_percent' => $session->items->count() > 0
                ? round(($session->items->where('observed_status', '!=', 'pending')->count() / $session->items->count()) * 100, 2)
                : 0,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'session' => $session,
                'progress' => $progress,
            ],
        ]);
    }

    /**
     * Record observation for a single asset in a session.
     */
    public function recordObservation(Request $request, string $sessionId, string $itemId): JsonResponse
    {
        if (! canPermission('sarpras_qr_audit')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $validated = $request->validate([
            'observed_status' => 'required|in:found,missing,damaged,location_mismatch',
            'observed_condition' => 'nullable|in:baik,rusak_ringan,rusak_berat,total',
            'actual_location' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'photo' => 'nullable|image|max:5120',
        ]);

        $item = StockOpnameItem::where('session_id', $sessionId)
            ->where('id', $itemId)
            ->firstOrFail();

        // Prevent double-scan: item already observed in this session
        if ($item->observed_status !== null && $item->observed_status !== 'pending') {
            return response()->json([
                'success' => false,
                'error' => 'Item ini sudah pernah dicatat pengamatannya pada sesi ini.',
            ], 422);
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('opname-evidence', 'public');
        }

        DB::transaction(function () use ($item, $validated, $photoPath, $request) {
            $item->update([
                'observed_status' => $validated['observed_status'],
                'observed_condition' => $validated['observed_condition'] ?? null,
                'actual_location' => $validated['actual_location'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'photo_path' => $photoPath,
                'observed_at' => now(),
                'officer_id' => $request->user()->id,
            ]);

            // If marked missing/damaged, update asset condition/status via state machine
            $asset = $item->asset;
            if ($validated['observed_condition']) {
                $asset->condition = $validated['observed_condition'];
            }
            if ($validated['observed_status'] === 'missing') {
                $this->transition->transition($asset, 'lost', $request->user()->id, 'Stock opname: asset reported missing');
            } elseif ($validated['observed_status'] === 'damaged') {
                $this->transition->transition($asset, 'damaged', $request->user()->id, 'Stock opname: asset confirmed damaged');
            }
        });

        return response()->json([
            'success' => true,
            'data' => $item->fresh(['asset', 'officer:id,name']),
        ]);
    }

    /**
     * Close a session.
     */
    public function closeSession(Request $request, string $id): JsonResponse
    {
        if (! canPermission('sarpras_qr_audit')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $session = StockOpnameSession::findOrFail($id);

        if ($session->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'error' => 'Hanya sesi dengan status in_progress yang dapat ditutup.',
            ], 422);
        }

        $session->update([
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => $session->fresh(),
        ]);
    }

    /**
     * Quick scan during opname — auto-creates observation row.
     */
    public function qrScan(Request $request, string $sessionId): JsonResponse
    {
        if (! canPermission('sarpras_qr_audit')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $validated = $request->validate([
            'qr_token' => 'required|string',
            'condition' => 'nullable|in:baik,rusak_ringan,rusak_berat,total',
            'notes' => 'nullable|string|max:1000',
        ]);

        $asset = Asset::where('id', $validated['qr_token'])
            ->orWhere('asset_code', $validated['qr_token'])
            ->firstOrFail();

        $item = StockOpnameItem::where('session_id', $sessionId)
            ->where('asset_id', $asset->id)
            ->firstOrFail();

        // Prevent duplicate QR scan in the same session
        if ($item->observed_status !== null && $item->observed_status !== 'pending') {
            return response()->json([
                'success' => false,
                'error' => 'Aset ini sudah pernah di-scan pada sesi ini.',
            ], 422);
        }

        $item->update([
            'observed_status' => 'found',
            'observed_condition' => $validated['condition'] ?? $item->expected_condition,
            'observed_at' => now(),
            'officer_id' => $request->user()->id,
            'notes' => $validated['notes'] ?? $item->notes,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'asset' => $asset,
                'item' => $item,
            ],
        ]);
    }

    /**
     * Variance report — what was missing/damaged/misplaced.
     */
    public function varianceReport(Request $request, string $id): JsonResponse
    {
        if (! canPermission('sarpras_laporan_view')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $session = StockOpnameSession::with(['items.asset', 'items.asset.category'])
            ->findOrFail($id);

        $variance = [
            'missing' => $session->items->where('observed_status', 'missing')->values(),
            'damaged' => $session->items->where('observed_status', 'damaged')->values(),
            'mismatched_location' => $session->items->where('observed_status', 'location_mismatch')->values(),
            'expected_vs_actual' => $session->items->map(function ($i) {
                return [
                    'asset_code' => $i->asset?->asset_code,
                    'asset_name' => $i->asset?->asset_name,
                    'expected_status' => $i->expected_status,
                    'observed_status' => $i->observed_status,
                    'expected_condition' => $i->expected_condition,
                    'observed_condition' => $i->observed_condition,
                    'notes' => $i->notes,
                    'has_variance' => $i->expected_status !== $i->observed_status
                        || ($i->observed_condition && $i->expected_condition !== $i->observed_condition),
                ];
            })->values(),
        ];

        $stats = [
            'total_items' => $session->items->count(),
            'variance_count' => $variance['expected_vs_actual']->where('has_variance', true)->count(),
            'variance_percent' => $session->items->count() > 0
                ? round($variance['expected_vs_actual']->where('has_variance', true)->count() / $session->items->count() * 100, 2)
                : 0,
            'missing_count' => $variance['missing']->count(),
            'damaged_count' => $variance['damaged']->count(),
            'mismatched_count' => $variance['mismatched_location']->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'session' => $session,
                'stats' => $stats,
                'variance' => $variance,
            ],
        ]);
    }
}
