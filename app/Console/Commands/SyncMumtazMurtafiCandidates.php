<?php

namespace App\Console\Commands;

use App\Services\Academic\MumtazMurtafiCandidateService;
use Illuminate\Console\Command;

class SyncMumtazMurtafiCandidates extends Command
{
    protected $signature = 'mumtaz:sync-candidates
                            {--school= : Filter UUID sekolah}
                            {--academic-year= : Filter UUID tahun ajaran; default semua}
                            {--user= : UUID user yang dicatat sebagai generator}';

    protected $description = 'Membuat kandidat Mumtaz Murtafi dari rapor yang sudah dipublikasikan.';

    public function handle(MumtazMurtafiCandidateService $service): int
    {
        $result = $service->syncPublishedRapors(
            $this->option('school'),
            $this->option('academic-year'),
            $this->option('user'),
        );

        $this->info("Selesai: {$result['candidates']} kandidat dari {$result['groups']} kelompok rapor.");

        if ($result['failed'] > 0) {
            $this->warn("{$result['failed']} kelompok gagal diproses. Periksa log aplikasi.");
        }

        return self::SUCCESS;
    }
}
