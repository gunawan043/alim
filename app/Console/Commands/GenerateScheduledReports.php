<?php

namespace App\Console\Commands;

use App\Models\ScheduledReport;
use App\Services\RecruitmentReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class GenerateScheduledReports extends Command
{
    protected $signature = 'recruitment:generate-scheduled-reports';

    protected $description = 'Generate and send scheduled reports';

    protected $reportService;

    public function __construct(RecruitmentReportService $reportService)
    {
        parent::__construct();
        $this->reportService = $reportService;
    }

    public function handle()
    {
        $this->info('🔍 Mencari report terjadwal...');

        $schedules = ScheduledReport::where('is_active', true)
            ->where('next_send_at', '<=', Carbon::now())
            ->get();

        if ($schedules->isEmpty()) {
            $this->info('✅ Tidak ada report yang perlu dikirim.');

            return 0;
        }

        $bar = $this->output->createProgressBar($schedules->count());
        $bar->start();

        $sent = 0;
        $failed = 0;

        foreach ($schedules as $schedule) {
            try {
                // Generate report
                $data = $this->reportService->getDashboardStats($schedule->frequency);

                // Kirim email ke semua recipients
                foreach ($schedule->recipients as $recipient) {
                    Mail::send('emails.scheduled-report', [
                        'schedule' => $schedule,
                        'data' => $data,
                    ], function ($message) use ($recipient, $schedule) {
                        $message->to($recipient)
                            ->subject("Report: {$schedule->name}");
                    });
                }

                // Update schedule
                $schedule->last_sent_at = Carbon::now();
                $schedule->next_send_at = $this->calculateNextSend($schedule->frequency);
                $schedule->save();

                $sent++;

            } catch (\Exception $e) {
                $failed++;
                \Log::error("Gagal kirim report {$schedule->id}: ".$e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ {$sent} report terkirim, {$failed} gagal.");

        return 0;
    }

    protected function calculateNextSend($frequency)
    {
        switch ($frequency) {
            case 'daily':
                return Carbon::tomorrow()->setTime(8, 0);
            case 'weekly':
                return Carbon::next(Carbon::MONDAY)->setTime(8, 0);
            case 'monthly':
                return Carbon::now()->addMonth()->firstOfMonth()->setTime(8, 0);
            default:
                return Carbon::now()->addDay();
        }
    }
}
