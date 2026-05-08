<?php

namespace App\Jobs;

use App\Models\Todo;
use App\Models\NotificationUniversal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendTodoReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Todo $todo;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(Todo $todo)
    {
        $this->todo = $todo;
    }

    public function handle(): void
    {
        try {
            // Skip if todo is already completed or deleted
            if (!in_array($this->todo->status, ['belum_mulai', 'sedang_berjalan', 'ditunda'])) {
                return;
            }

            $this->todo->load(['owner', 'delegatedByUser']);

            $dueText = $this->todo->due_date
                ? $this->todo->due_date->format('d/m/Y')
                : 'tanpa tenggat';

            $priorityLabel = $this->todo->priorityLabel;

            $subject = $this->todo->is_overdue
                ? "[ALIM] ⚠️ Tugas Terlambat: {$this->todo->title}"
                : "[ALIM] ⏰ Reminder Todo: {$this->todo->title}";

            $viewData = [
                'todo'      => $this->todo,
                'owner'     => $this->todo->owner,
                'dueText'   => $dueText,
                'isOverdue' => $this->todo->is_overdue,
                'priority'  => $priorityLabel,
                'url'       => route('user.todos.index', ['id' => $this->todo->id]),
            ];

            Mail::send('emails.todo-reminder', $viewData, function ($message) use ($subject) {
                $message->to($this->todo->owner->email, $this->todo->owner->name)
                        ->subject($subject);
            });

            Log::info("SendTodoReminderJob: email terkirim untuk todo {$this->todo->id}");
        } catch (\Exception $e) {
            Log::error("SendTodoReminderJob gagal untuk todo {$this->todo->id}: " . $e->getMessage(), [
                'todo_id'   => $this->todo->id,
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendTodoReminderJob gagal permanen untuk todo {$this->todo->id}: " . $exception->getMessage());
    }
}
