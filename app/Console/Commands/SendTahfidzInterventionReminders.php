<?php

namespace App\Console\Commands;

use App\Models\TahfidzIntervention;
use App\Services\NotificationUniversalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendTahfidzInterventionReminders extends Command
{
    protected $signature = 'tahfidz:send-intervention-reminders
                            {--email : Kirim juga melalui email}
                            {--whatsapp : Kirim juga melalui WhatsApp}
                            {--push : Kirim juga melalui push notification}
                            {--dry-run : Tampilkan jumlah tanpa mengirim}';

    protected $description = 'Kirim pengingat tindak lanjut Tahfidz yang melewati batas waktu';

    public function handle(NotificationUniversalService $notifications): int
    {
        $interventions = TahfidzIntervention::with(['student:id,name', 'group:id,name,teacher_id,coordinator_id,work_unit_id', 'assignedTo:id,name'])
            ->where('status', 'open')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->orderBy('due_date')
            ->get();

        if ($interventions->isEmpty()) {
            $this->info('Tidak ada tindak lanjut terlambat.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info($interventions->count().' tindak lanjut terlambat ditemukan.');

            return self::SUCCESS;
        }

        $sent = 0;
        $skipped = 0;

        foreach ($interventions as $intervention) {
            $recipients = collect([
                $intervention->assigned_to,
                $intervention->group?->teacher_id,
                $intervention->group?->coordinator_id,
            ])->filter()->unique()->values();

            foreach ($recipients as $recipientId) {
                $cacheKey = 'tahfidz:intervention-reminder:'.$intervention->id.':'.$recipientId.':'.now()->toDateString();
                if (! Cache::add($cacheKey, true, now()->endOfDay())) {
                    $skipped++;

                    continue;
                }

                $notifications->send($recipientId, [
                    'module' => 'education',
                    'reference_type' => 'tahfidz_intervention',
                    'reference_id' => $intervention->id,
                    'type' => 'warning',
                    'action' => 'tahfidz_intervention',
                    'title' => 'Tindak Lanjut Tahfidz Terlambat',
                    'message' => 'Tindak lanjut untuk '.($intervention->student?->name ?? 'santri').' ('.$intervention->type_label.') melewati batas waktu '.$intervention->due_date?->format('d/m/Y').'.',
                    'action_url' => route('user.tahfidz.interventions.index', [
                        'userId' => $recipientId,
                        'overdue' => 1,
                    ]),
                    'action_text' => 'Buka Tindak Lanjut',
                    'priority' => 'high',
                    'work_unit_id' => $intervention->group?->work_unit_id,
                    'send_email' => (bool) $this->option('email'),
                    'send_whatsapp' => (bool) $this->option('whatsapp'),
                    'send_push' => (bool) $this->option('push'),
                    'data' => [
                        'intervention_id' => $intervention->id,
                        'student_id' => $intervention->student_id,
                        'due_date' => $intervention->due_date?->toDateString(),
                    ],
                ]);
                $sent++;
            }
        }

        $this->info("Pengingat tindak lanjut: {$sent} terkirim, {$skipped} dilewati karena sudah dikirim hari ini.");

        return self::SUCCESS;
    }
}
