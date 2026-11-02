<?php

namespace App\Services\Asrama;

use App\Models\AssetBuilding;
use App\Models\AssetRoom;
use App\Models\Dormitory;
use App\Models\DormitoryRoom;
use App\Models\RoomSupervisor;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Sinkronisasi kamar asrama → ruang Sarpras.
 *
 * Mengikuti pola rombel → ruang kelas (StudyGroupRoomService): setiap kamar
 * asrama otomatis punya satu AssetRoom sehingga data ruang di Sarpras selalu
 * lengkap tanpa input ganda. Relasi dijaga lewat kolom dormitory_room_id,
 * bukan room_code, agar aman saat kode kamar diubah.
 */
class DormitoryRoomAssetService
{
    /**
     * Buat/perbarui ruang Sarpras untuk satu kamar asrama.
     */
    public function sync(DormitoryRoom $room): ?AssetRoom
    {
        $dormitory = $room->dormitory ?: Dormitory::find($room->dormitory_id);

        if (! $dormitory?->work_unit_id) {
            return null;
        }

        $building = $this->resolveBuilding($dormitory, $room);

        if (! $building) {
            return null;
        }

        $assetRoom = AssetRoom::firstOrNew(['dormitory_room_id' => $room->id]);
        $isNew = ! $assetRoom->exists;

        $assetRoom->fill([
            'work_unit_id' => $dormitory->work_unit_id,
            'school_id' => $dormitory->school_id,
            'building_id' => $building->id,
            'room_name' => $room->name ?: ('Kamar '.$room->code),
            'floor' => $room->floor ?: 1,
            'room_type' => 'lainnya',
            'capacity' => $room->capacity,
            'responsible_user_id' => $this->responsibleUserId($dormitory, $room),
            'is_active' => (bool) $room->is_active,
        ]);

        if ($isNew) {
            $assetRoom->room_code = $this->uniqueRoomCode($dormitory, $room);
            $assetRoom->condition = 'baik';
            $assetRoom->is_bookable = false;
            $assetRoom->booking_requires_approval = true;
            $assetRoom->notes = 'Kamar Asrama — '.$dormitory->name;
        }

        $assetRoom->save();

        return $assetRoom;
    }

    /**
     * Backfill seluruh kamar asrama yang belum punya ruang Sarpras.
     *
     * @return array{created:int,updated:int}
     */
    public function syncAll(?string $dormitoryId = null): array
    {
        $created = 0;
        $updated = 0;

        DormitoryRoom::query()
            ->when($dormitoryId, fn ($query) => $query->where('dormitory_id', $dormitoryId))
            ->chunkById(100, function ($rooms) use (&$created, &$updated) {
                foreach ($rooms as $room) {
                    $exists = AssetRoom::where('dormitory_room_id', $room->id)->exists();
                    $assetRoom = $this->sync($room);

                    if (! $assetRoom) {
                        continue;
                    }

                    $exists ? $updated++ : $created++;
                }
            });

        return ['created' => $created, 'updated' => $updated];
    }

    /**
     * Hapus ruang Sarpras saat kamar asrama dihapus.
     */
    public function deleteFor(DormitoryRoom $room): void
    {
        AssetRoom::where('dormitory_room_id', $room->id)->delete();
    }

    /**
     * Gedung induk: pakai gedung Sarpras milik blok, atau gedung asrama
     * otomatis (pola sama dengan sarpras:sync-dormitory-inventory).
     */
    private function resolveBuilding(Dormitory $dormitory, DormitoryRoom $room): ?AssetBuilding
    {
        $wing = $room->wing;

        if ($wing?->asset_building_id) {
            $building = AssetBuilding::find($wing->asset_building_id);

            if ($building) {
                return $building;
            }
        }

        $createdBy = auth()->id() ?? $dormitory->head_id ?? User::query()->value('id');

        if (! $createdBy) {
            return null;
        }

        return AssetBuilding::firstOrCreate(
            ['building_code' => Str::limit('DORM-'.$dormitory->code, 30, '')],
            [
                'work_unit_id' => $dormitory->work_unit_id,
                'school_id' => $dormitory->school_id,
                'building_name' => $dormitory->name,
                'building_type' => 'asrama',
                'structure_condition' => 'baik',
                'ownership_status' => 'milik_sendiri',
                'is_active' => true,
                'created_by' => $createdBy,
            ],
        );
    }

    private function responsibleUserId(Dormitory $dormitory, DormitoryRoom $room): ?string
    {
        return RoomSupervisor::query()
            ->active()
            ->where('room_id', $room->id)
            ->value('user_id')
            ?? $dormitory->head_id;
    }

    /**
     * Kode ruang unik (kolom room_code maksimal 30 karakter & unik global).
     */
    private function uniqueRoomCode(Dormitory $dormitory, DormitoryRoom $room): string
    {
        $base = Str::limit('DORM-'.$dormitory->code.'-'.$room->code, 30, '');

        $taken = AssetRoom::where('room_code', $base)
            ->where(function ($query) use ($room) {
                $query->whereNull('dormitory_room_id')
                    ->orWhere('dormitory_room_id', '!=', $room->id);
            })
            ->exists();

        if (! $taken) {
            return $base;
        }

        return Str::limit($base, 24, '').'-'.substr(sha1($room->id), 0, 4);
    }
}
