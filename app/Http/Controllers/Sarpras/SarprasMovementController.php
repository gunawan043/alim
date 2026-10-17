<?php

namespace App\Http\Controllers\Sarpras;

use App\Http\Requests\Sarpras\AssetMovementStoreRequest;
use App\Models\Asset;
use App\Models\AssetLocationHistory;
use App\Models\AssetRoom;
use App\Services\Sarpras\AssetEventLogger;
use Illuminate\Http\Request;

class SarprasMovementController extends SarprasBaseController
{
    public function __construct(protected AssetEventLogger $eventLogger)
    {
        view()->share('userId', request()->route('userId') ?? (auth()->check() ? auth()->id() : null));
    }

    public function index(Request $request)
    {
        $query = AssetLocationHistory::with(['asset', 'fromRoom', 'toRoom', 'mover']);

        if (! $this->canViewAll($request)) {
            $query->whereHas('asset', fn ($q) => $this->scopeToSchool($request, $q));
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('asset', fn ($q) => $q->where('asset_name', 'like', "%{$s}%"));
        }
        if ($request->filled('asset_id')) {
            $query->where('asset_id', $request->asset_id);
        }

        $histories = $query->orderBy('moved_date', 'desc')->paginate(15)->withQueryString();
        $assets = Asset::where('is_active', true)->orderBy('asset_name')->get();

        return view('sarpras.perpindahan.index', compact('histories', 'assets'));
    }

    public function create(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $assets = Asset::where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'tersedia')
            ->orderBy('asset_name')->get();
        $rooms = AssetRoom::where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('room_name')->get();

        return view('sarpras.perpindahan.create', compact('assets', 'rooms'));
    }

    public function store(AssetMovementStoreRequest $request)
    {
        $validated = $request->validated();

        $asset = Asset::findOrFail($validated['asset_id']);

        $validated['from_room_id'] = $asset->room_id;
        $validated['moved_by'] = auth()->id();

        AssetLocationHistory::create($validated);

        // Update lokasi aset
        $asset->update(['room_id' => $validated['to_room_id']]);

        try {
            $fromRoom = AssetRoom::find($validated['from_room_id']);
            $toRoom = AssetRoom::find($validated['to_room_id']);
            $this->eventLogger->logMoved($asset, $fromRoom?->room_name, $toRoom?->room_name, auth()->id());
        } catch (\Throwable $e) {
            report($e);
        }
        $this->bumpDashboardCache();

        return redirect()->route('sarpras.perpindahan.index')
            ->with('success', 'Riwayat perpindahan berhasil dicatat.');
    }

    public function byAsset(Request $request, string $assetId)
    {
        $asset = Asset::findOrFail($assetId);
        $this->authorizeAssetAccess($asset, $request);

        $histories = AssetLocationHistory::where('asset_id', $assetId)
            ->orderBy('moved_date', 'desc')
            ->with(['fromRoom', 'toRoom', 'mover'])
            ->get();

        return view('sarpras.perpindahan.by-asset', compact('asset', 'histories'));
    }

    public function show(Request $request, string $id)
    {
        $history = AssetLocationHistory::with(['asset', 'fromRoom', 'toRoom', 'mover', 'asset.room.building'])
            ->findOrFail($id);

        return view('sarpras.perpindahan.show', compact('history'));
    }
}
