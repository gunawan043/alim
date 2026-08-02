<?php

namespace App\Console\Commands;

use App\Models\RecruitmentApplication;
use App\Models\RecruitmentJob;
use App\Services\NotificationUniversalService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoCloseExpiredJobs extends Command
{
    protected $signature = 'recruitment:close-expired-jobs 
                            {--dry-run : Jalankan tanpa benar-benar menutup lowongan}
                            {--days=0 : Jumlah hari setelah expired untuk ditutup}';

    protected $description = 'Auto close recruitment jobs that have passed the end date';

    protected $notificationService;

    public function __construct(NotificationUniversalService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle()
    {
        $this->info('🔍 Mencari lowongan yang sudah expired...');

        $dryRun = $this->option('dry-run');
        $extraDays = (int) $this->option('days');

        // Tentukan tanggal cutoff
        $cutoffDate = Carbon::today()->subDays($extraDays);

        // Cari lowongan yang sudah expired
        $expiredJobs = RecruitmentJob::where('status', 'aktif')
            ->where('tanggal_selesai', '<', $cutoffDate)
            ->with(['workUnit', 'creator'])
            ->get();

        if ($expiredJobs->isEmpty()) {
            $this->info('✅ Tidak ada lowongan expired yang perlu ditutup.');

            return 0;
        }

        $this->warn("📊 Ditemukan {$expiredJobs->count()} lowongan expired:");

        // Tampilkan tabel preview
        $this->table(
            ['ID', 'Kode', 'Judul', 'Tanggal Selesai', 'Pelamar'],
            $expiredJobs->map(function ($job) {
                return [
                    $job->id,
                    $job->kode_lowongan,
                    $job->judul,
                    $job->tanggal_selesai->format('d/m/Y'),
                    $job->applications()->count(),
                ];
            })
        );

        if ($dryRun) {
            $this->warn('🚧 DRY RUN MODE - Tidak ada perubahan yang dilakukan');

            return 0;
        }

        // Konfirmasi jika lebih dari 10 lowongan
        if ($expiredJobs->count() > 10) {
            if (! $this->confirm("⚠️ Akan menutup {$expiredJobs->count()} lowongan. Lanjutkan?")) {
                $this->info('❌ Dibatalkan');

                return 0;
            }
        }

        $bar = $this->output->createProgressBar($expiredJobs->count());
        $bar->start();

        $stats = [
            'closed' => 0,
            'notified' => 0,
            'errors' => 0,
        ];

        foreach ($expiredJobs as $job) {
            try {
                \DB::beginTransaction();

                // Update status lowongan
                $oldStatus = $job->status;
                $job->status = 'ditutup';
                $job->closed_at = now();
                $job->closed_reason = 'otomatis_expired';
                $job->save();

                // Kirim notifikasi ke pembuat lowongan
                $this->notificationService->send(
                    $job->created_by,
                    [
                        'module' => 'recruitment',
                        'reference_type' => RecruitmentJob::class,
                        'reference_id' => $job->id,
                        'reference_code' => $job->kode_lowongan,
                        'type' => 'warning',
                        'action' => 'auto_closed',
                        'title' => 'Lowongan Otomatis Ditutup',
                        'message' => "Lowongan {$job->judul} telah otomatis ditutup karena melewati batas waktu pendaftaran.",
                        'data' => [
                            'job_code' => $job->kode_lowongan,
                            'job_title' => $job->judul,
                            'expired_date' => $job->tanggal_selesai->format('Y-m-d'),
                            'total_applicants' => $job->applications()->count(),
                        ],
                        'action_url' => "/recruitment/jobs/{$job->id}",
                        'action_text' => 'Lihat Lowongan',
                        'priority' => 'medium',
                        'send_email' => true,
                    ]
                );

                // Kirim notifikasi ke semua pelamar yang statusnya masih dalam proses
                $activeApplications = $job->applications()
                    ->whereNotIn('status', ['diterima', 'ditolak', 'mengundurkan_diri'])
                    ->with('recruitmentProfile.user')
                    ->get();

                foreach ($activeApplications as $application) {
                    $this->notificationService->send(
                        $application->recruitmentProfile->user_id,
                        [
                            'module' => 'recruitment',
                            'reference_type' => RecruitmentApplication::class,
                            'reference_id' => $application->id,
                            'reference_code' => $application->no_lamaran,
                            'type' => 'info',
                            'action' => 'job_closed',
                            'title' => 'Lowongan Ditutup',
                            'message' => "Lowongan {$job->judul} yang Anda lamar telah ditutup. Status lamaran Anda masih dalam proses seleksi.",
                            'data' => [
                                'job_title' => $job->judul,
                                'application_status' => $application->status,
                            ],
                            'action_url' => "/recruitment/applications/{$application->id}",
                            'action_text' => 'Lihat Lamaran',
                            'priority' => 'medium',
                        ]
                    );
                    $stats['notified']++;
                }

                // Kirim notifikasi ke admin/HR
                $this->notificationService->sendToRole('admin', [
                    'module' => 'recruitment',
                    'type' => 'info',
                    'action' => 'job_auto_closed',
                    'title' => 'Lowongan Otomatis Ditutup',
                    'message' => "Lowongan {$job->judul} telah otomatis ditutup oleh sistem.",
                    'data' => [
                        'job_code' => $job->kode_lowongan,
                        'closed_at' => now()->format('Y-m-d H:i:s'),
                    ],
                    'action_url' => "/recruitment/jobs/{$job->id}",
                    'priority' => 'low',
                ]);

                // Log activity
                activity()
                    ->performedOn($job)
                    ->causedBy(null) // system
                    ->withProperties([
                        'old_status' => $oldStatus,
                        'new_status' => 'ditutup',
                        'reason' => 'expired',
                        'expired_date' => $job->tanggal_selesai,
                    ])
                    ->log('Lowongan otomatis ditutup karena expired');

                $stats['closed']++;

                \DB::commit();

            } catch (\Exception $e) {
                \DB::rollBack();
                $stats['errors']++;
                Log::error("Gagal menutup lowongan {$job->id}: ".$e->getMessage());
                $this->error("\n❌ Gagal menutup lowongan {$job->kode_lowongan}: ".$e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Tampilkan ringkasan
        $this->info('✅ Proses selesai!');
        $this->table(
            ['Status', 'Jumlah'],
            [
                ['Lowongan ditutup', $stats['closed']],
                ['Notifikasi terkirim', $stats['notified']],
                ['Error', $stats['errors']],
            ]
        );

        return 0;
    }
}
