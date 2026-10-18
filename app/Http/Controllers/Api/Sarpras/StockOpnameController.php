<?php

namespace App\Http\Controllers\Api\Sarpras;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetRoom;
use App\Models\School;
use App\Models\StockOpnameItem;
use App\Models\StockOpnameOfficer;
use App\Models\StockOpnameSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockOpnameController extends Controller
{
    public function sessions(Request $request): JsonResponse
    {
        if (! canPermission('sarpras_all_access')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $query = StockOpnameSession::with(['creator:id,name', 'workUnit:id,name', 'room:id,room_name'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('work_unit_id')) {
            $query->where('work_unit_id', $request->work_unit_id);
        }

        return response()->json(['success' => true, 'data' => $query->paginate($request->integer('per_page', 20))]);
    }

    public function createSession(Request $request): JsonResponse
    {
        if (! canPermission('sarpras_all_access')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:191',
            'work_unit_id' => 'required|uuid|exists:work_units,id',
            'room_id' => 'nullable|uuid|exists:asset_rooms,id',
            'category_id' => 'nullable|uuid|exists:asset_categories,id',
            'scheduled_date' => 'required|date',
            'officers' => 'required|array|min:1',
            'officers.*' => 'uuid|exists:users,id',
            'description' => 'nullable|string|max:2000',
        ]);

        $assetQuery = Asset::query()
            ->where('work_unit_id', $validated['work_unit_id'])
            ->where('is_active', true)
            ->whereNotIn('status', ['disposed', 'dihapus']);

        if (! empty($validated['room_id'])) {
            $assetQuery->where('room_id', $validated['room_id']);
        }
        if (! empty($validated['category_id'])) {
            $assetQuery->where('asset_category_id', $validated['category_id']);
        }

        $assets = $assetQuery->get();

        $session = DB::transaction(function () use ($validated, $assets, $request) {
            $schoolId = ! empty($validated['room_id'])
                ? AssetRoom::query()->whereKey($validated['room_id'])->value('school_id')
                : School::query()->where('work_unit_id', $validated['work_unit_id'])->value('id');

            $session = StockOpnameSession::create([
                'session_code' => 'SO-'.now()->format('Ymd').'-'.Str::upper(Str::random(4)),
                'work_unit_id' => $validated['work_unit_id'],
                'school_id' => $schoolId,
                'room_id' => $validated['room_id'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'scheduled_date' => $validated['scheduled_date'],
                'status' => StockOpnameSession::STATUS_PLANNED,
                'created_by' => $request->user()->id,
            ]);

            foreach (array_unique($validated['officers']) as $userId) {
                StockOpnameOfficer::create([
                    'session_id' => $session->id,
                    'user_id' => $userId,
                    'role' => $userId === $request->user()->id ? 'lead' : 'officer',
                ]);
            }

            foreach ($assets as $asset) {
                StockOpnameItem::create([
                    'session_id' => $session->id,
                    'asset_id' => $asset->id,
                    'expected_status' => $asset->status,
                    'expected_condition' => $asset->condition,
                    'expected_room_id' => $asset->room_id,
                    'observed_status' => 'pending',
                ]);
            }

            return $session;
        });

        return response()->json(['success' => true, 'data' => $session->load('items', 'officers')], 201);
    }

    public function showSession(Request $request, string $id): JsonResponse
    {
        if (! canPermission('sarpras_all_access')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $session = StockOpnameSession::with(['items.asset.category', 'items.asset.room', 'officers.user:id,name', 'creator:id,name'])
            ->findOrFail($id);

        $items = $session->items;
        $progress = [
            'total_items' => $items->count(),
            'found' => $items->where('observed_status', 'found')->count(),
            'missing' => $items->where('observed_status', 'missing')->count(),
            'damaged' => $items->where('observed_status', 'damaged')->count(),
            'mismatch' => $items->where('observed_status', 'moved')->count(),
            'pending' => $items->where('observed_status', 'pending')->count(),
        ];
        $progress['completion_percent'] = $progress['total_items'] > 0
            ? round(($progress['total_items'] - $progress['pending']) / $progress['total_items'] * 100, 2)
            : 0;

        return response()->json(['success' => true, 'data' => ['session' => $session, 'progress' => $progress]]);
    }

    public function startSession(Request $request, string $id): JsonResponse
    {
        if (! canPermission('sarpras_all_access')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $session = StockOpnameSession::findOrFail($id);
        abort_unless($session->status === StockOpnameSession::STATUS_PLANNED, 422, 'Sesi tidak dapat dimulai.');

        $session->update(['status' => StockOpnameSession::STATUS_IN_PROGRESS, 'started_date' => today()]);

        return response()->json(['success' => true, 'data' => $session->fresh()]);
    }

    public function recordObservation(Request $request, string $sessionId, string $itemId): JsonResponse
    {
        if (! canPermission('sarpras_qr_audit') && ! canPermission('sarpras_all_access')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $validated = $request->validate([
            'observed_status' => 'required|in:found,missing,damaged,moved',
            'observed_condition' => 'nullable|in:baik,rusak_ringan,rusak_sedang,rusak_berat',
            'observed_room_id' => 'nullable|uuid|exists:asset_rooms,id',
            'actual_location' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'photo' => 'nullable|image|max:5120',
        ]);

        $session = StockOpnameSession::findOrFail($sessionId);
        abort_unless($session->status === StockOpnameSession::STATUS_IN_PROGRESS, 422, 'Sesi belum berjalan.');

        $item = StockOpnameItem::with('asset')->where('session_id', $session->id)->findOrFail($itemId);
        $photoPath = $request->hasFile('photo') ? $request->file('photo')->store('opname-evidence', 'public') : null;

        DB::transaction(function () use ($item, $validated, $photoPath, $request) {
            $condition = $validated['observed_condition'] ?? $item->expected_condition ?? 'baik';
            $roomId = $validated['observed_room_id'] ?? $item->asset->room_id;

            $item->update([
                'observed_status' => $validated['observed_status'],
                'observed_condition' => $condition,
                'observed_room_id' => $roomId,
                'actual_location' => $validated['actual_location'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'photo_path' => $photoPath,
                'recorded_by' => $request->user()->id,
                'scanned_at' => now(),
            ]);

            $assetUpdate = ['condition' => $condition];
            if ($validated['observed_status'] === 'missing') {
                $assetUpdate = ['condition' => 'hilang', 'status' => 'hilang'];
            } elseif ($validated['observed_status'] === 'moved' && $roomId) {
                $assetUpdate['room_id'] = $roomId;
            } elseif ($validated['observed_status'] === 'damaged') {
                $assetUpdate['condition'] = $validated['observed_condition'] ?? 'rusak_sedang';
            }

            $item->asset->update($assetUpdate);
        });

        return response()->json(['success' => true, 'data' => $item->fresh(['asset', 'recorder:id,name'])]);
    }

    public function closeSession(Request $request, string $id): JsonResponse
    {
        if (! canPermission('sarpras_all_access')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $session = StockOpnameSession::findOrFail($id);
        abort_unless($session->status === StockOpnameSession::STATUS_IN_PROGRESS, 422, 'Hanya sesi berjalan yang dapat ditutup.');

        $session->update([
            'status' => StockOpnameSession::STATUS_CLOSED,
            'closed_date' => today(),
            'closed_by' => $request->user()->id,
        ]);

        return response()->json(['success' => true, 'data' => $session->fresh()]);
    }

    public function qrScan(Request $request, string $sessionId): JsonResponse
    {
        if (! canPermission('sarpras_qr_audit') && ! canPermission('sarpras_all_access')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $validated = $request->validate([
            'qr_token' => 'required|string',
            'condition' => 'nullable|in:baik,rusak_ringan,rusak_sedang,rusak_berat',
            'notes' => 'nullable|string|max:1000',
        ]);

        $session = StockOpnameSession::findOrFail($sessionId);
        abort_unless($session->status === StockOpnameSession::STATUS_IN_PROGRESS, 422, 'Sesi belum berjalan.');

        $asset = Asset::where('asset_code', $validated['qr_token'])->orWhere('id', $validated['qr_token'])->firstOrFail();
        $item = StockOpnameItem::where('session_id', $session->id)->where('asset_id', $asset->id)->firstOrFail();

        $item->update([
            'observed_status' => 'found',
            'observed_condition' => $validated['condition'] ?? $asset->condition,
            'observed_room_id' => $asset->room_id,
            'recorded_by' => $request->user()->id,
            'scanned_at' => now(),
            'notes' => $validated['notes'] ?? $item->notes,
        ]);

        return response()->json(['success' => true, 'data' => ['asset' => $asset, 'item' => $item]]);
    }

    public function varianceReport(Request $request, string $id): JsonResponse
    {
        if (! canPermission('sarpras_laporan_view') && ! canPermission('sarpras_all_access')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $session = StockOpnameSession::with(['items.asset.category'])->findOrFail($id);
        $items = $session->items;

        return response()->json([
            'success' => true,
            'data' => [
                'session' => $session,
                'stats' => [
                    'total_items' => $items->count(),
                    'missing_count' => $items->where('observed_status', 'missing')->count(),
                    'damaged_count' => $items->where('observed_status', 'damaged')->count(),
                    'mismatched_count' => $items->where('observed_status', 'moved')->count(),
                    'pending_count' => $items->where('observed_status', 'pending')->count(),
                ],
                'variance' => [
                    'missing' => $items->where('observed_status', 'missing')->values(),
                    'damaged' => $items->where('observed_status', 'damaged')->values(),
                    'mismatched_location' => $items->where('observed_status', 'moved')->values(),
                ],
            ],
        ]);
    }
}
