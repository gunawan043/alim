<?php

namespace App\Console\Commands;

use App\Services\Academic\StudyGroupRoomService;
use Illuminate\Console\Command;

class SyncStudyGroupRooms extends Command
{
    protected $signature = 'alim:sync-study-group-rooms {--school= : ID sekolah tertentu}';

    protected $description = 'Sinkronkan rombel ke ruang kelas Sarpras (termasuk backfill data lama)';

    public function handle(StudyGroupRoomService $rooms): int
    {
        $result = $rooms->syncAll($this->option('school') ?: null);

        $this->info("Sinkronisasi ruang selesai: {$result['created']} dibuat, {$result['updated']} diperbarui.");

        return self::SUCCESS;
    }
}
