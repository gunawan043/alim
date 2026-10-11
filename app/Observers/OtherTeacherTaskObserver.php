<?php

namespace App\Observers;

use App\Models\OtherTeacherTask;
use App\Models\StudyGroup;
use App\Services\NotificationUniversalService;
use Illuminate\Support\Facades\Log;

/**
 * Notifikasi individu untuk guru saat tugas tambahan ditetapkan/diubah/dihapus.
 */
class OtherTeacherTaskObserver
{
    public function created(OtherTeacherTask $task): void
    {
        $this->syncHomeroom($task, 'created');
        $this->notify($task, 'created');
    }

    public function updated(OtherTeacherTask $task): void
    {
        if ($task->wasChanged(['task_name', 'study_group_id', 'teacher_id', 'is_active'])) {
            $this->syncHomeroom($task, 'updated');
        }

        if ($task->wasChanged(['task_name', 'study_group_id', 'weekly_hours', 'is_active'])) {
            $this->notify($task, 'updated');
        }
    }

    public function deleted(OtherTeacherTask $task): void
    {
        $this->syncHomeroom($task, 'deleted');
        $this->notify($task, 'deleted');
    }

    /**
     * Tugas "Wali Kelas" langsung terhubung ke rombel:
     * set/clear `study_groups.homeroom_teacher_id` mengikuti penugasan.
     */
    private function syncHomeroom(OtherTeacherTask $task, string $event): void
    {
        try {
            if (! str_contains(mb_strtolower((string) $task->task_name), 'wali kelas')) {
                return;
            }

            if ($event === 'deleted') {
                if ($task->study_group_id) {
                    $group = StudyGroup::find($task->study_group_id);
                    if ($group && (string) $group->homeroom_teacher_id === (string) $task->teacher_id) {
                        $group->forceFill(['homeroom_teacher_id' => null])->save();
                    }
                }

                return;
            }

            // Pindah rombel: bersihkan wali lama bila guru sama.
            if ($event === 'updated' && $task->wasChanged('study_group_id')) {
                $oldGroupId = $task->getOriginal('study_group_id');
                if ($oldGroupId && (string) $oldGroupId !== (string) $task->study_group_id) {
                    $old = StudyGroup::find($oldGroupId);
                    if ($old && (string) $old->homeroom_teacher_id === (string) $task->teacher_id) {
                        $old->forceFill(['homeroom_teacher_id' => null])->save();
                    }
                }
            }

            if ($task->study_group_id && $task->teacher_id) {
                $group = StudyGroup::find($task->study_group_id);
                $group?->forceFill(['homeroom_teacher_id' => $task->teacher_id])->save();
            }
        } catch (\Throwable $e) {
            Log::warning('Sinkron wali kelas gagal: '.$e->getMessage());
        }
    }

    private function notify(OtherTeacherTask $task, string $event): void
    {
        try {
            if (! $task->teacher_id || auth()->id() === $task->teacher_id) {
                return;
            }

            $title = match ($event) {
                'created' => 'Tugas Tambahan Baru',
                'deleted' => 'Tugas Tambahan Dihapus',
                default => 'Tugas Tambahan Diperbarui',
            };

            $rombel = $task->studyGroup?->name;
            $message = match ($event) {
                'deleted' => "Tugas tambahan \"{$task->task_name}\" telah dihapus".($rombel ? " dari {$rombel}" : '').'.',
                default => "Tugas tambahan Anda: \"{$task->task_name}\"".($rombel ? " — {$rombel}" : '')." ({$task->weekly_hours} JP/pekan).",
            };

            app(NotificationUniversalService::class)->send($task->teacher_id, [
                'module' => 'kurikulum',
                'reference_type' => OtherTeacherTask::class,
                'reference_id' => $task->id,
                'type' => 'info',
                'action' => 'other_task_'.$event,
                'priority' => 'medium',
                'title' => $title,
                'message' => $message,
                'action_url' => route('user.other-teacher-tasks.index', ['userId' => $task->teacher_id]),
                'action_text' => 'Lihat Tugas Saya',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Notif tugas tambahan gagal: '.$e->getMessage());
        }
    }
}
