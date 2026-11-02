<?php

namespace App\Console\Commands;

use App\Services\Asrama\DormitoryRoomAssetService;
use Illuminate\Console\Command;

class SyncDormitoryRoomsToSarpras extends Command
{
    protected $signature = 'asrama:sync-rooms {--dormitory= : UUID asrama tertentu}';

    protected $description = 'Sinkronkan kamar asrama ke ruang Sarpras (asset_rooms) untuk data lama.';

    public function handle(DormitoryRoomAssetService $service): int
    {
        $result = $service->syncAll($this->option('dormitory'));

        $this->info("Selesai: {$result['created']} ruang baru, {$result['updated']} diperbarui.");

        return self::SUCCESS;
    }
}
