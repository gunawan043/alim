<?php

declare(strict_types=1);

namespace App\Authorization\Jobs;

use App\Authorization\Contracts\PermissionCacheManager;
use App\Authorization\Models\PermissionSnapshot;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class BuildSnapshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of times the job may be retried.
     */
    public int $tries = 3;

    /**
     * Seconds to wait before retrying. Exponential backoff:
     *   Attempt 1: wait 5s
     *   Attempt 2: wait 30s
     *   Attempt 3: wait 120s
     */
    public array $backoff = [5, 30, 120];

    /**
     * Seconds to wait before the job is considered expired.
     */
    public int $timeout = 120;

    public function __construct(
        public readonly int|string $userId,
    ) {}

    public function afterCommit(): bool
    {
        return true;
    }

    public function handle(): void
    {
        $user = User::query()->find($this->userId);

        if ($user === null) {
            // Subject no longer exists — prune cache.
            $this->pruneCache();

            return;
        }

        // Retrieve all unique scope identifiers for this user.
        $rows = PermissionSnapshot::query()
            ->where('user_id', $this->userId)
            ->where('is_current', true)
            ->get(['scope_school_id', 'scope_key']);

        foreach ($rows as $row) {
            if ($row->scope_school_id === null) {
                continue;
            }

            // Skip historical sentinel rows (e.g. 'global', 'unknown') written
            // before sentinel elimination. They have no real tenant to rebuild for.
            if (! $this->isValidSchoolId((string) $row->scope_school_id)) {
                continue;
            }

            try {
                $schoolId = (string) $row->scope_school_id;
                $academicYearId = $this->resolveAcademicYear($user, $schoolId);

                $context = new \App\Authorization\ValueObjects\OrganizationContext(
                    schoolId: $schoolId,
                    academicYearId: $academicYearId,
                    roleDimension: 'default',
                );

                $rebuilder = app(\App\Authorization\Services\SnapshotRebuildService::class);
                $rebuilder->rebuild($user, $context, 'job');
            } catch (\Throwable $e) {
                \Log::error('BuildSnapshotJob: rebuild failed for scope', [
                    'user_id' => $this->userId,
                    'school_id' => $row->scope_school_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('BuildSnapshotJob failed permanently', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }

    private function pruneCache(): void
    {
        try {
            $cache = app(PermissionCacheManager::class);
            $cache->forgetUser($this->userId);
        } catch (\Throwable) {
            // Best-effort.
        }
    }

    private function resolveAcademicYear(User $user, string $schoolId): string
    {
        if (isset($user->active_academic_year_id)) {
            return (string) $user->active_academic_year_id;
        }

        return (string) config('authorization.default_academic_year_id', 'global');
    }

    /**
     * A valid school ID must be a non-empty string that is not a known sentinel.
     * Sentinel values were historically used as placeholders for "no tenant"
     * and must never be treated as legitimate school IDs.
     */
    private function isValidSchoolId(string $schoolId): bool
    {
        if ($schoolId === '') {
            return false;
        }

        return ! in_array($schoolId, ['global', 'unknown', 'all', '*'], true);
    }
}
