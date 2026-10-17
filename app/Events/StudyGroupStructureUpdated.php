<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudyGroupStructureUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ?string $studyGroupId,
        public ?string $schoolId = null,
        public ?string $academicYearId = null,
        public ?string $gradeLevelId = null,
        public string $change = 'updated',
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('gtk-analysis')];
    }
}
