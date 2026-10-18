<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\AssetBuilding;
use App\Models\AssetCategory;
use App\Models\AssetRoom;
use App\Models\Dormitory;
use App\Models\DormitoryInventory;
use App\Models\DormitoryRoom;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncDormitoryInventoryToAssets extends Command
{
    protected $signature = 'sarpras:sync-dormitory-inventory {--dry-run : Tampilkan tanpa menyimpan}';

    protected $description = 'Sinkronkan inventaris asrama ke master assets/asset_rooms Sarpras (satu sumber data).';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $category = AssetCategory::firstOrCreate(
            ['name' => 'Inventaris Asrama'],
            ['code' => 'INV-ASR', 'is_active' => 1]
        );
        $createdBy = User::query()->value('id');
        $createdAssets = 0;
        $createdRooms = 0;

        DB::beginTransaction();
        try {
            foreach (Dormitory::with(['rooms'])->get() as $dorm) {
                $building = AssetBuilding::firstOrCreate(
                    ['building_code' => Str::limit('DORM-'.$dorm->code, 30, '')],
                    [
                        'work_unit_id' => $dorm->work_unit_id,
                        'school_id' => $dorm->school_id,
                        'building_name' => $dorm->name,
                        'building_type' => 'asrama',
                        'structure_condition' => 'baik',
                        'ownership_status' => 'milik_sendiri',
                        'is_active' => 1,
                        'created_by' => $createdBy,
                    ]
                );

                foreach ($dorm->rooms as $room) {
                    $assetRoom = AssetRoom::firstOrCreate(
                        ['room_code' => Str::limit('DORM-'.$dorm->code.'-'.$room->code, 30, '')],
                        [
                            'work_unit_id' => $dorm->work_unit_id,
                            'school_id' => $dorm->school_id,
                            'building_id' => $building->id,
                            'dormitory_room_id' => $room->id,
                            'room_name' => $room->name ?? $room->code,
                            'floor' => $room->floor ?? 1,
                            'room_type' => 'lainnya',
                            'capacity' => $room->capacity,
                            'condition' => 'baik',
                            'responsible_user_id' => \App\Models\RoomSupervisor::active()
                                ->where('room_id', $room->id)->value('user_id') ?? $dorm->head_id,
                            'is_active' => 1,
                        ]
                    );
                    $createdRooms += $assetRoom->wasRecentlyCreated ? 1 : 0;

                    DormitoryInventory::withTrashed()
                        ->where('dormitory_id', $dorm->id)
                        ->where('room_id', $room->id)
                        ->whereNull('asset_id')
                        ->each(function (DormitoryInventory $item) use ($assetRoom, $category, $createdBy, &$createdAssets, $dry) {
                            if ($dry) { $createdAssets++; return; }

                            $asset = Asset::create([
                                'work_unit_id' => $assetRoom->work_unit_id,
                                'school_id' => $assetRoom->school_id,
                                'asset_category_id' => $item->category_id ?: $category->id,
                                'room_id' => $assetRoom->id,
                                'asset_code' => 'ASR-'.Str::upper(Str::limit(Str::uuid()->toString(), 12, '')),
                                'asset_name' => $item->item_name ?: 'Inventaris Asrama',
                                'condition' => in_array($item->condition, ['baik', 'rusak_ringan', 'rusak_sedang', 'rusak_berat', 'hilang'], true)
                                    ? $item->condition : 'baik',
                                'status' => 'tersedia',
                                'verification_status' => 'verified',
                                'verified_at' => now(),
                                'created_by' => $createdBy,
                                'notes' => 'Sinkron inventaris asrama. Jumlah: '.($item->quantity ?? 1).($item->notes ? ' | '.$item->notes : ''),
                                'is_active' => 1,
                            ]);

                            $item->forceFill(['asset_id' => $asset->id])->save();
                            $createdAssets++;
                        });
                }
            }
            if ($dry) { DB::rollBack(); } else { DB::commit(); }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Gagal sinkron: '.$e->getMessage());
            return self::FAILURE;
        }

        $this->info(($dry ? '[DRY RUN] ' : '').'Selesai: '.$createdAssets.' aset, '.$createdRooms.' ruang asrama dipetakan.');
        return self::SUCCESS;
    }
}
