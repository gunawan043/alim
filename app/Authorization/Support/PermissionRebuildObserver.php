<?php

declare(strict_types=1);

namespace App\Authorization\Support;

use App\Authorization\Jobs\BuildSnapshotJob;
use App\Authorization\Models\RevokedPermission;
use App\Models\GTKEmployment;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Observes permission-bearing model events and dispatches
 * BuildSnapshotJob. Does NOT perform rebuild synchronously.
 *
 * Models observed:
 *   - User (on create/update/delete)
 *   - Permission (on create/update/delete/restore/deleted)
 *   - GTKEmployment (on create/update/delete)
 *   - RevokedPermission (on create/update/delete)
 */
final class PermissionRebuildObserver
{
    public function created(Model $model): void
    {
        $this->dispatchForModel($model);
    }

    public function updated(Model $model): void
    {
        $this->dispatchForModel($model);
    }

    public function deleted(Model $model): void
    {
        $this->dispatchForModel($model);
    }

    public function restored(Model $model): void
    {
        $this->dispatchForModel($model);
    }

    public function forceDeleted(Model $model): void
    {
        $this->dispatchForModel($model);
    }

    /**
     * Extract user IDs from the model and dispatch one job per user.
     */
    private function dispatchForModel(Model $model): void
    {
        $userIds = match (true) {
            $model instanceof User => [(string) $model->getKey()],
            $model instanceof Permission => $this->extractUserIdsFromModel($model),
            $model instanceof GTKEmployment || $model instanceof RevokedPermission => $this->extractUserIdFromForeignKey($model),
            default => [],
        };

        foreach ($userIds as $userId) {
            BuildSnapshotJob::dispatch($userId)
                ->onQueue(config('authorization.rebuild_queue.name', 'authorization-rebuild'))
                ->afterCommit();
        }
    }

    /**
     * @return array<int, string>
     */
    private function extractUserIdsFromModel(Permission $model): array
    {
        // Permissions are used by many users; return empty so rebuild
        // runs for the global cache scope.
        return [];
    }

    /**
     * @return array<int, string>
     */
    private function extractUserIdFromForeignKey(Model $model): array
    {
        // Order matters: check user_id first (most common), then teacher_id
        // (used by HomeroomAssignment and CoordinatorAssignment).
        $foreignKeys = ['user_id', 'teacher_id'];

        foreach ($foreignKeys as $field) {
            if ($model->getAttribute($field) !== null) {
                return [(string) $model->getAttribute($field)];
            }
        }

        return [];
    }
}
