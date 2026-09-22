<?php

declare(strict_types=1);

namespace App\Observers;

use App\Authorization\Jobs\BuildSnapshotJob;
use App\Models\DormitoryStaffAssignment;

class DormitoryStaffAssignmentObserver
{
    public function created(DormitoryStaffAssignment $assignment): void
    {
        $this->dispatchRebuild($assignment->user_id);
    }

    public function updated(DormitoryStaffAssignment $assignment): void
    {
        $this->dispatchRebuild($assignment->user_id);
    }

    public function deleted(DormitoryStaffAssignment $assignment): void
    {
        $this->dispatchRebuild($assignment->user_id);
    }

    private function dispatchRebuild(string $userId): void
    {
        BuildSnapshotJob::dispatch($userId)
            ->onQueue(config('authorization.rebuild_queue.name', 'authorization-rebuild'))
            ->afterCommit();
    }
}
