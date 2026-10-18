<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\Asset;
use App\Models\AssetBuilding;
use App\Models\AssetRoom;
use App\Models\DormitoryInventory;
use App\Models\RoomSupervisor;
use Illuminate\Http\Request;

class SarprasDataAuditController extends SarprasBaseController
{
    public function index(Request $request)
    {
        $assets = Asset::query();
        $rooms = AssetRoom::query();
        $buildings = AssetBuilding::query();

        if (! $this->isCentralSarpras() && ! $this->canViewAll($request)) {
            $assets = $this->scopeToAccessibleWorkUnits($assets, $request);
            $rooms = $this->scopeToAccessibleWorkUnits($rooms, $request);
            $buildings = $this->scopeToAccessibleWorkUnits($buildings, $request);
        }

        $stats = [
            'assets' => (clone $assets)->count(),
            'pending' => (clone $assets)->where('verification_status', 'pending')->count(),
            'no_room' => (clone $assets)->whereNull('room_id')->count(),
            'no_price' => (clone $assets)->where(fn ($q) => $q->whereNull('acquisition_price')->orWhere('acquisition_price', 0))->count(),
            'no_pic' => (clone $rooms)->whereNull('responsible_user_id')->count(),
            'no_building' => (clone $rooms)->whereNull('building_id')->count(),
            'empty_buildings' => (clone $buildings)->doesntHave('rooms')->count(),
            'unsynced_dorm' => DormitoryInventory::whereNull('asset_id')->count(),
        ];

        $noRoom = (clone $assets)->with('workUnit')->whereNull('room_id')->latest()->limit(20)->get();
        $noPrice = (clone $assets)->with(['room', 'category'])->where(fn ($q) => $q->whereNull('acquisition_price')->orWhere('acquisition_price', 0))->latest()->limit(20)->get();
        $noPic = (clone $rooms)->with(['building', 'workUnit'])->whereNull('responsible_user_id')->orderBy('room_name')->limit(20)->get();
        $noBuildingRooms = (clone $rooms)->with('workUnit')->whereNull('building_id')->orderBy('room_name')->limit(20)->get();
        $emptyBuildings = (clone $buildings)->doesntHave('rooms')->with('workUnit')->orderBy('building_name')->limit(20)->get();

        $duplicateNames = (clone $assets)
            ->select('asset_name', 'room_id', \DB::raw('COUNT(*) as total'))
            ->with('room')
            ->groupBy('asset_name', 'room_id')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        $unsyncedDorm = DormitoryInventory::with(['dormitory', 'room'])
            ->whereNull('asset_id')
            ->latest()
            ->limit(20)
            ->get();

        $pendingAssets = (clone $assets)->with(['room.building', 'workUnit'])->where('verification_status', 'pending')->latest()->limit(20)->get();

        return view('sarpras.audit.index', compact(
            'stats', 'noRoom', 'noPrice', 'noPic', 'noBuildingRooms', 'emptyBuildings',
            'duplicateNames', 'unsyncedDorm', 'pendingAssets'
        ));
    }
}
