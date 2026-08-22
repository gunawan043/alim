<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeacherCheckedOut implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $schoolId,
        public string $teacherId,
        public string $teacherName,
        public string $studyGroupCode,
        public string $studyGroupName,
        public string $statusKeluar,    // selesai / keluar_cepat
        public int $earlyLeaveMinutes,
        public int $durationMinutes,
        public string $actualTimeIn,
        public string $actualTimeOut,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("waka-teacher-absensi.{$this->schoolId}");
    }

    public function broadcastAs(): string
    {
        return 'teacher.checked.out';
    }
}
