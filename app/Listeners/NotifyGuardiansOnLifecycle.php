<?php

namespace App\Listeners;

use App\Jobs\SendLifecycleNotificationJob;
use App\Models\WaliSantri;
use App\Support\LifecycleMessage;

class NotifyGuardiansOnLifecycle
{
    public function handle(object $event): void
    {
        if (!method_exists(LifecycleMessage::class, 'forEvent')) {
            return;
        }

        try {
            $message = LifecycleMessage::forEvent($event);
        } catch (\Throwable) {
            return;
        }

        if ($message === null) {
            return;
        }

        $student = $event->student;
        $waliIds = WaliSantri::where('student_id', $student->id)
            ->where('status', WaliSantri::STATUS_ACTIVE)
            ->pluck('user_id');

        foreach ($waliIds as $userId) {
            \Illuminate\Support\Facades\DB::afterCommit(function () use ($userId, $message, $event, $student) {
                SendLifecycleNotificationJob::dispatch($userId, $message, [
                    'event' => $event::class,
                    'student_id' => $student->id,
                    'school_id' => $student->school_id,
                ]);
            });
        }
    }
}
