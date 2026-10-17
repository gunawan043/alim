<?php

namespace App\Console\Commands;

use App\Services\Dormitory\DormitoryActivityGenerator;
use Illuminate\Console\Command;

class GenerateDormitoryActivities extends Command
{
    protected $signature = 'dormitory:generate-activities {--days=7 : Jumlah hari ke depan}';

    protected $description = 'Bentuk sesi presensi kegiatan asrama dari jadwal rutin.';

    public function handle(DormitoryActivityGenerator $generator): int
    {
        $days = max(1, (int) $this->option('days'));
        $created = $generator->generateRange(now()->startOfDay(), $days);

        $this->info("Sesi kegiatan asrama dibuat: {$created}.");

        return self::SUCCESS;
    }
}
