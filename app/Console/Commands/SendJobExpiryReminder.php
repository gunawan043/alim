<?php

namespace App\Console\Commands;

use App\Models\RecruitmentJob;
use App\Services\NotificationUniversalService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendJobExpiryReminder extends Command
{
    protected $signature = 'recruitment:send-expiry-reminder 
                            {--days=1 : Jumlah hari sebelum expired}
                            {--channel=all : Channel notifikasi (email, whatsapp, all)}';

    protected $description = 'Send reminders for jobs that will expire soon';

    protected $notificationService;

    public function __construct(NotificationUniversalService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle()
    {
        $daysBefore = (int) $this->option('days');
        $channel = $this->option('channel');

        $targetDate = Carbon::today()->addDays($daysBefore);

        $this->info("🔍 Mencari lowongan yang akan expired pada {$targetDate->format('d/m/Y')}...");

        $jobs = RecruitmentJob::where('status', 'aktif')
            ->whereDate('tanggal_selesai', $targetDate)
            ->with(['workUnit', 'creator'])
            ->get();

        if ($jobs->isEmpty()) {
            $this->info('✅ Tidak ada lowongan yang perlu reminder.');

            return 0;
        }

        $this->warn("📊 Ditemukan {$jobs->count()} lowongan yang akan expired:");

        $bar = $this->output->createProgressBar($jobs->count());
        $bar->start();

        $stats = [
            'reminded' => 0,
            'errors' => 0,
        ];

        foreach ($jobs as $job) {
            try {
                // Reminder untuk pembuat lowongan
                $sendOptions = [
                    'module' => 'recruitment',
                    'reference_type' => RecruitmentJob::class,
                    'reference_id' => $job->id,
                    'reference_code' => $job->kode_lowongan,
                    'type' => 'warning',
                    'action' => 'expiry_reminder',
                    'title' => "Reminder: Lowongan Akan Ditutup dalam {$daysBefore} Hari",
                    'message' => "Lowongan {$job->judul} akan ditutup pada {$job->tanggal_selesai->format('d/m/Y')}. Saat ini terdapat {$job->applications()->count()} pelamar.",
                    'data' => [
                        'job_code' => $job->kode_lowongan,
                        'job_title' => $job->judul,
                        'expiry_date' => $job->tanggal_selesai->format('Y-m-d'),
                        'days_left' => $daysBefore,
                        'applicant_count' => $job->applications()->count(),
                    ],
                    'action_url' => "/recruitment/jobs/{$job->id}",
                    'action_text' => 'Kelola Lowongan',
                    'priority' => 'high',
                ];

                // Set channel
                if ($channel != 'all') {
                    $sendOptions["send_{$channel}"] = true;
                } else {
                    $sendOptions['send_email'] = true;
                    $sendOptions['send_whatsapp'] = false; // Optional
                }

                $this->notificationService->send($job->created_by, $sendOptions);

                // Reminder untuk admin/HR
                $this->notificationService->sendToRole('admin', [
                    'module' => 'recruitment',
                    'type' => 'info',
                    'action' => 'job_expiry_reminder',
                    'title' => 'Reminder: Lowongan Akan Expired',
                    'message' => "Lowongan {$job->judul} oleh {$job->creator->name} akan expired dalam {$daysBefore} hari.",
                    'data' => [
                        'job_title' => $job->judul,
                        'creator' => $job->creator->name,
                        'expiry_date' => $job->tanggal_selesai->format('Y-m-d'),
                    ],
                    'action_url' => "/recruitment/jobs/{$job->id}",
                    'priority' => 'medium',
                ]);

                $stats['reminded']++;

            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error("Gagal kirim reminder job {$job->id}: ".$e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Reminder terkirim untuk {$stats['reminded']} lowongan.");

        return 0;
    }
}
