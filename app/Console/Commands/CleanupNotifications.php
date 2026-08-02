<?php

namespace App\Console\Commands;

use App\Services\NotificationUniversalService;
use Illuminate\Console\Command;

class CleanupNotifications extends Command
{
    protected $signature = 'notifications:cleanup {--days=30 : Days to keep archived notifications}';

    protected $description = 'Clean up old archived notifications';

    protected $notificationService;

    public function __construct(NotificationUniversalService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle()
    {
        $days = $this->option('days');
        $deleted = $this->notificationService->cleanup($days);

        $this->info("Cleaned up {$deleted} old archived notifications.");
    }
}
