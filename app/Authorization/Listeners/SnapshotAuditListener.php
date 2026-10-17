<?php

declare(strict_types=1);

namespace App\Authorization\Listeners;

use App\Authorization\Events\SnapshotArchived;
use App\Authorization\Events\SnapshotCreated;
use App\Authorization\Models\SnapshotAuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

final class SnapshotAuditListener implements ShouldQueue
{
    public function handleSnapshotCreated(SnapshotCreated $event): void
    {
        $this->write(
            userId: $event->userId,
            scopeKey: (string) $event->bag->getMetadata()->scopeKey,
            event: $event->trigger,
            fingerprint: $event->bag->getFingerprint(),
            status: 'created',
            error: null,
        );
    }

    public function handleSnapshotArchived(SnapshotArchived $event): void
    {
        $this->write(
            userId: $event->userId,
            scopeKey: $event->scopeKey,
            event: 'snapshot.archive',
            fingerprint: '',
            status: $event->filterStatus?->value ?? 'all',
            error: null,
        );
    }

    private function write(
        int|string $userId,
        string $scopeKey,
        string $event,
        string $fingerprint,
        string $status,
        ?string $error,
    ): void {
        try {
            SnapshotAuditLog::query()->create([
                'user_id' => $userId,
                'scope_key' => $scopeKey,
                'event' => $event,
                'fingerprint' => $fingerprint !== '' ? $fingerprint : str_pad('', 64, '0'),
                'status' => $status,
                'error' => $error,
                'created_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('authorization.snapshot.audit.failed', [
                'error' => $e->getMessage(),
                'event' => $event,
                'user_id' => (string) $userId,
                'scope_key' => $scopeKey,
            ]);
        }
    }
}
