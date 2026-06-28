<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Centralized observability for academic cascade jobs.
 *
 * Responsibilities:
 *  1. Structured log lines (parsed by Sentry / CloudWatch / Logtail / etc.)
 *  2. Optional file-based counter (untuk development & monitoring sederhana
 *     di server yang tidak punya APM). Production sebaiknya meng-inject
 *     adapter (Datadog / Prometheus / Sentry) via container binding.
 *
 * Untuk integrasi APM: replace increment() dengan adapter masing-masing
 * (e.g. `app('datadog')->increment('alim.cascade.job.failed', 1, $tags)`).
 */
class AcademicCascadeMetrics
{
    /**
     * Dipanggil dari Job::failed() setiap kali job gagal permanen.
     * Tag: stage ('provision'|'deactivate'), error_class, attempt.
     */
    public function recordJobFailed(string $stage, string $jobClass, \Throwable $error, array $context = []): void
    {
        $payload = [
            'event' => 'cascade.job.failed',
            'stage' => $stage,
            'job' => $jobClass,
            'error_class' => get_class($error),
            'error_message' => $error->getMessage(),
            'context' => $context,
        ];

        // Structured log: aggregator (Sentry/CloudWatch/Logtail) bisa
        // filter "cascade.job.failed" untuk trigger alert.
        Log::error('cascade.job.failed', $payload);

        $this->incrementFileCounter('cascade.job.failed', $stage);
    }

    /**
     * Dipanggil dari Job::handle() setelah batch selesai.
     * Untuk tracking throughput & success rate.
     */
    public function recordJobCompleted(string $stage, string $jobClass, array $result = []): void
    {
        $payload = [
            'event' => 'cascade.job.completed',
            'stage' => $stage,
            'job' => $jobClass,
            'result' => $result,
        ];

        Log::info('cascade.job.completed', $payload);

        $this->incrementFileCounter('cascade.job.completed', $stage);
    }

    /**
     * Alert ke tim operasional (Slack/Discord webhook).
     * Dipanggil dari listener JobFailed untuk trigger manual.
     *
     * @param  string  $webhookUrl  Optional override (default: env ALERT_WEBHOOK_URL)
     */
    public function alertOps(string $message, array $context = [], ?string $webhookUrl = null): void
    {
        $url = $webhookUrl ?? env('ALERT_WEBHOOK_URL');
        if (! $url) {
            // Tidak ada webhook konfigurasi: log saja, jangan throw.
            Log::warning('cascade.ops.alert_skipped (no ALERT_WEBHOOK_URL)', [
                'message' => $message,
                'context' => $context,
            ]);

            return;
        }

        try {
            $payload = [
                'text' => "[alim-cascade] {$message}",
                'context' => $context,
                'env' => app()->environment(),
                'ts' => now()->toIso8601String(),
            ];

            // Fire-and-forget; tidak boleh memblokir queue worker.
            // HTTP facade: timeout pendek, SSL pakai config global,
            // mock-able via Http::fake() di testing.
            Http::timeout(2)
                ->connectTimeout(1)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->withBody(json_encode($payload), 'application/json')
                ->post($url);
        } catch (\Throwable $e) {
            Log::error('cascade.ops.alert_failed', [
                'message' => $message,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * File-based counter fallback. Berguna di development atau untuk
     * environment tanpa APM. Production sebaiknya pakai adapter APM.
     *
     * @param  string  $event  e.g. 'cascade.job.failed'
     * @param  string  $label  e.g. 'provision' | 'deactivate'
     */
    protected function incrementFileCounter(string $event, string $label): void
    {
        try {
            $dir = storage_path('app/metrics');
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $file = $dir.'/'.date('Y-m-d').'.counter';
            $line = date('H:i:s')." | {$event} | {$label}\n";
            @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // Jangan sampai metric collection failure menggagalkan job.
        }
    }
}
