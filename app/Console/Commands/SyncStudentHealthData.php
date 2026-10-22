<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Services\Uks\StudentHealthSyncService;
use Illuminate\Console\Command;

class SyncStudentHealthData extends Command
{
    protected $signature = 'uks:sync-student-health
                            {--school= : Batasi ke satu school_id}
                            {--force : Timpa data kesehatan yang sudah ada}';

    protected $description = 'Sinkronkan tinggi, berat, dan lingkar kepala santri ke data kesehatan UKS.';

    public function handle(StudentHealthSyncService $sync): int
    {
        $schoolId = $this->option('school');
        $force = (bool) $this->option('force');
        $processed = 0;
        $skipped = 0;

        $query = Student::query()
            ->where(function ($query) {
                $query->whereNotNull('height')
                    ->orWhereNotNull('weight')
                    ->orWhereNotNull('head_circumference');
            })
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->when(! $force, fn ($query) => $query->whereDoesntHave('healthRecord'));

        $query->chunkById(100, function ($students) use ($sync, &$processed, &$skipped) {
            foreach ($students as $student) {
                try {
                    $sync->sync($student);
                    $processed++;
                } catch (\Throwable $e) {
                    $skipped++;
                    $this->warn("Gagal memproses {$student->name}: {$e->getMessage()}");
                }
            }
        }, 'id');

        $this->info("Selesai: {$processed} santri disinkronkan, {$skipped} gagal.");

        return self::SUCCESS;
    }
}
