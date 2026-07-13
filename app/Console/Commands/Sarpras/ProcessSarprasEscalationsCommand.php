<?php

namespace App\Console\Commands\Sarpras;

use App\Services\Sarpras\Automation\SlAService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessSarprasEscalationsCommand extends Command
{
    protected $signature = 'sarpras:process-escalations
                            {--dry-run : Tampilkan hasil tanpa mengeksekusi escalation}';

    protected $description = 'Re-evaluate SLA trackers and auto-escalate overdue items';

    protected SlAService $sla;

    public function __construct(SlAService $sla)
    {
        parent::__construct();
        $this->sla = $sla;
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no events will be dispatched.');
        }

        $changes = $dryRun ? 0 : $this->sla->evaluateAll();
        $escalated = $dryRun ? 0 : $this->sla->runAutoEscalation();

        $this->info("SLA status changes: {$changes}");
        $this->info("Auto-escalations: {$escalated}");

        Log::info('sarpras:process-escalations', [
            'status_changes' => $changes,
            'auto_escalations' => $escalated,
            'dry_run' => $dryRun,
        ]);

        return self::SUCCESS;
    }
}