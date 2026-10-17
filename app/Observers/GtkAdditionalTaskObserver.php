<?php

namespace App\Observers;

use App\Models\GtkAdditionalTask;
use App\Services\NotificationUniversalService;
use Illuminate\Support\Facades\Log;

/**
 * Notifikasi individu untuk GTK saat tugas tambahan (modul Personalia) ditetapkan/diubah.
 */
class GtkAdditionalTaskObserver
{
    public function created(GtkAdditionalTask $task): void
    {
        $this->notify($task, 'created');
    }

    public function updated(GtkAdditionalTask $task): void
    {
        if ($task->wasChanged(['nama_tugas', 'hours_per_week', 'decree_id'])) {
            $this->notify($task, 'updated');
        }
    }

    private function notify(GtkAdditionalTask $task, string $event): void
    {
        try {
            if (! $task->user_id || auth()->id() === $task->user_id) {
                return;
            }

            app(NotificationUniversalService::class)->send($task->user_id, [
                'module' => 'personalia',
                'reference_type' => GtkAdditionalTask::class,
                'reference_id' => $task->id,
                'type' => 'info',
                'action' => 'gtk_task_'.$event,
                'priority' => 'medium',
                'title' => $event === 'created' ? 'Tugas Tambahan Baru' : 'Tugas Tambahan Diperbarui',
                'message' => 'Tugas tambahan Anda: "'.$task->nama_tugas.'"'.($task->hours_per_week ? " ({$task->hours_per_week} JP/pekan)" : '').'.',
                'action_url' => route('user.gtk-additional-tasks.index', ['userId' => $task->user_id]),
                'action_text' => 'Lihat Tugas Tambahan',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Notif tugas tambahan GTK gagal: '.$e->getMessage());
        }
    }
}
