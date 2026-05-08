<?php

namespace App\Console\Commands;

use App\Models\Todo;
use App\Services\NotificationUniversalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendTodoReminderCommand extends Command
{
    protected $signature = 'todo:send-reminders
                            {--dry-run : Hanya tampilkan hasil tanpa mengirim notifikasi}';

    protected $description = 'Kirim reminder untuk todos yang sudah melewati reminder_at timestamp';

    protected NotificationUniversalService $notificationService;

    public function __construct(NotificationUniversalService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('⚠️  Mode DRY RUN — tidak ada notifikasi yang akan dikirim.');
        }

        $this->info('⏰ Memulai proses reminder todo...');

        $todos = Todo::needsReminder()
            ->with(['owner', 'delegatedByUser'])
            ->whereDoesntHave('comments', function ($q) {
                $q->where('created_at', '>=', now()->subHours(12));
            })
            ->get();

        if ($todos->isEmpty()) {
            $this->info('✅ Tidak ada todo yang perlu direminder.');
            return 0;
        }

        $this->warn("📊 Ditemukan {$todos->count()} todo yang perlu direminder.");

        $stats = ['sent' => 0, 'errors' => 0];

        $bar = $this->output->createProgressBar($todos->count());
        $bar->start();

        foreach ($todos as $todo) {
            try {
                if ($dryRun) {
                    $this->line("\n  → [DRY RUN] #{$todo->id}: {$todo->title}");
                    $bar->advance();
                    continue;
                }

                // Tentukan prioritas notifikasi berdasarkan urgency
                $priority = 'medium';
                $type = 'info';

                if ($todo->priority === 'mendesak') {
                    $priority = 'urgent';
                    $type = 'error';
                } elseif ($todo->is_overdue) {
                    $priority = 'high';
                    $type = 'warning';
                }

                $dueText = $todo->due_date
                    ? $todo->due_date->format('d/m/Y') . ($todo->due_time ? ' ' . $todo->due_time->format('H:i') : '')
                    : 'tanpa tenggat';

                // Kirim ke owner
                $this->notificationService->send($todo->owner_id, [
                    'module'        => 'todo',
                    'type'          => $type,
                    'title'         => $todo->is_overdue
                        ? '⏰ Tugas TERLAMBAT: ' . $todo->title
                        : '⏰ Reminder: ' . $todo->title,
                    'message'       => $todo->is_overdue
                        ? "Tugas '{$todo->title}' sudah melewati tenggat ({$dueText}). Segera selesaikan tugas ini."
                        : "Pengingat: tugas '{$todo->title}' memiliki tenggat pada {$dueText}. Prioritas: {$todo->priorityLabel}.",
                    'action'        => 'view',
                    'action_url'    => route('user.todos.index', ['userId' => $todo->owner_id]),
                    'action_text'   => 'Lihat Detail',
                    'reference_type' => Todo::class,
                    'reference_id'  => $todo->id,
                    'reference_code' => $todo->id,
                    'priority'      => $priority,
                    'data'          => [
                        'todo_id'    => $todo->id,
                        'title'      => $todo->title,
                        'due_date'   => $todo->due_date?->format('Y-m-d'),
                        'due_time'   => $todo->due_time?->format('H:i'),
                        'priority'   => $todo->priority,
                        'is_overdue' => $todo->is_overdue,
                        'status'     => $todo->status,
                        'reminder_at'=> $todo->reminder_at?->format('Y-m-d H:i:s'),
                    ],
                ]);

                // Dispatch email job if queue is configured
                dispatch(new \App\Jobs\SendTodoReminderJob($todo));

                $stats['sent']++;
            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error("Gagal kirim reminder todo {$todo->id}: " . $e->getMessage(), [
                    'todo_id' => $todo->id,
                    'exception' => $e,
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($dryRun) {
            $this->info("🔍 [DRY RUN] {$stats['sent']} todo akan direminder (tidak benar-benar dikirim).");
        } else {
            $this->info("✅ Reminder terkirim untuk {$stats['sent']} todo."
                . ($stats['errors'] > 0 ? " {$stats['errors']} gagal." : ''));
        }

        return $stats['errors'] > 0 ? 1 : 0;
    }
}
