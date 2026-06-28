<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RecordLifecycleAuditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $backoff = 60;

    public function __construct(public readonly array $payload) {}

    public function handle(): void
    {
        try {
            DB::table('student_lifecycle_audits')->insert([
                'id' => (string) Str::uuid(),
                'event' => $this->payload['event'],
                'student_id' => $this->payload['student_id'],
                'school_id' => $this->payload['school_id'],
                'actor_id' => $this->payload['actor_id'] ?? null,
                'payload' => json_encode($this->payload['payload'] ?? []),
                'occurred_at' => $this->payload['occurred_at'] ?? now()->toDateTimeString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('RecordLifecycleAuditJob failed', [
                'payload' => $this->payload,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
