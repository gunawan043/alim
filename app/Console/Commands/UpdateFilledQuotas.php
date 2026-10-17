<?php

namespace App\Console\Commands;

use App\Models\RecruitmentJob;
use Illuminate\Console\Command;

class UpdateFilledQuotas extends Command
{
    protected $signature = 'recruitment:update-filled-quotas 
                            {--job-id= : Update specific job only}';

    protected $description = 'Update filled quotas for recruitment jobs';

    public function handle()
    {
        $jobId = $this->option('job-id');

        $query = RecruitmentJob::query();

        if ($jobId) {
            $query->where('id', $jobId);
        } else {
            $query->where('status', 'aktif');
        }

        $jobs = $query->get();

        $this->info("📊 Mengupdate kuota terisi untuk {$jobs->count()} lowongan...");

        $bar = $this->output->createProgressBar($jobs->count());
        $bar->start();

        $updated = 0;

        foreach ($jobs as $job) {
            $acceptedCount = $job->applications()
                ->where('status', 'diterima')
                ->count();

            if ($job->kuota_terisi != $acceptedCount) {
                $job->kuota_terisi = $acceptedCount;
                $job->save();

                // Auto close if quota full
                if ($job->kuota_terisi >= $job->kuota && $job->status == 'aktif') {
                    $job->status = 'ditutup';
                    $job->closed_reason = 'kuota_penuh';
                    $job->closed_at = now();
                    $job->save();

                    $this->notificationService->send($job->created_by, [
                        'module' => 'recruitment',
                        'type' => 'success',
                        'action' => 'job_full',
                        'title' => 'Lowongan Ditutup (Kuota Penuh)',
                        'message' => "Lowongan {$job->judul} otomatis ditutup karena kuota telah terpenuhi.",
                        'action_url' => "/recruitment/jobs/{$job->id}",
                        'priority' => 'high',
                    ]);
                }

                $updated++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ {$updated} lowongan diupdate.");

        return 0;
    }
}
