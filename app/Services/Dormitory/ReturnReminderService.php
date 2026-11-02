<?php

namespace App\Services\Dormitory;

use App\Domain\Services\BoardingTimelineService;
use App\Models\BoardingTimelineEvent;
use App\Models\Dormitory;
use App\Models\DormitoryNotificationSetting;
use App\Models\DormitoryPermit;
use App\Services\Notifications\GuardianWhatsAppNotifier;
use App\Services\NotificationUniversalService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;

/**
 * Pengingat otomatis santri yang belum kembali ke asrama.
 *
 * Dijalankan oleh scheduler setiap menit; pengaturan per asrama menentukan
 * interval pengulangan, jumlah maksimal, kanal notifikasi, dan eskalasi.
 * Wali santri menerima WhatsApp / push / email / notifikasi portal sesuai
 * kanal yang dipilih pihak asrama.
 */
class ReturnReminderService
{
    private const ACTIVE_STATUSES = [
        DormitoryPermit::STATUS_APPROVED,
        DormitoryPermit::STATUS_PICKED_UP,
        DormitoryPermit::STATUS_OVERDUE,
    ];

    public function __construct(
        private readonly GuardianWhatsAppNotifier $whatsapp,
        private readonly NotificationUniversalService $notifications,
        private readonly BoardingTimelineService $timeline,
    ) {}

    /**
     * Kirim pengingat yang sudah jatuh tempo + proses eskalasi.
     *
     * @return array{reminders: int, escalations: int}
     */
    public function process(?CarbonInterface $now = null): array
    {
        $now = $now ? \Carbon\CarbonImmutable::instance($now) : now()->toImmutable();
        $sent = 0;
        $escalated = 0;

        Dormitory::query()->orderBy('name')->each(function (Dormitory $dormitory) use ($now, &$sent, &$escalated) {
            $setting = DormitoryNotificationSetting::forDormitory($dormitory->id);

            if (! $setting->is_enabled) {
                return;
            }

            $sent += $this->sendDueReminders($dormitory, $setting, $now);
            $escalated += $this->escalate($dormitory, $setting, $now);
        });

        return ['reminders' => $sent, 'escalations' => $escalated];
    }

    /**
     * Pengingat H-1: dikirim pada jam yang dipilih asrama untuk izin yang
     * batas kembalinya jatuh besok.
     */
    public function processHeadStartReminders(?CarbonInterface $now = null): int
    {
        $now = $now ? \Carbon\CarbonImmutable::instance($now) : now()->toImmutable();
        $sent = 0;

        Dormitory::query()->orderBy('name')->each(function (Dormitory $dormitory) use ($now, &$sent) {
            $setting = DormitoryNotificationSetting::forDormitory($dormitory->id);

            if (! $setting->is_enabled || ! $setting->h1_reminder_enabled) {
                return;
            }

            // Jendela 5 menit agar toleran terhadap keterlambatan scheduler;
            // kolom return_reminder_sent_at mencegah pengiriman ganda.
            $scheduledAt = \Carbon\CarbonImmutable::parse($now->format('Y-m-d').' '.$setting->h1_reminder_time);

            if ($now->lessThan($scheduledAt) || $now->greaterThanOrEqualTo($scheduledAt->addMinutes(5))) {
                return;
            }

            $tomorrowStart = $now->addDay()->startOfDay();
            $tomorrowEnd = $now->addDay()->endOfDay();

            $permits = DormitoryPermit::with(['student'])
                ->where('dormitory_id', $dormitory->id)
                ->whereIn('status', self::ACTIVE_STATUSES)
                ->whereNull('actual_return_datetime')
                ->whereNull('return_reminder_sent_at')
                ->whereBetween('expected_return_datetime', [$tomorrowStart, $tomorrowEnd])
                ->get();

            foreach ($permits as $permit) {
                if (! $permit->student) {
                    continue;
                }

                try {
                    if ($setting->hasChannel('whatsapp')) {
                        $this->whatsapp->notifyReturnReminder($permit);
                    }

                    $this->notifyGuardians($permit, $setting, $now, headStart: true);

                    $permit->forceFill(['return_reminder_sent_at' => $now])->save();
                    $sent++;
                } catch (\Throwable $e) {
                    Log::warning('Pengingat H-1 asrama gagal: '.$e->getMessage());
                }
            }
        });

        return $sent;
    }

    private function sendDueReminders(Dormitory $dormitory, DormitoryNotificationSetting $setting, CarbonInterface $now): int
    {
        $interval = max(1, (int) $setting->interval_minutes);
        $maxReminders = max(1, (int) $setting->max_reminders);
        $sent = 0;

        $permits = DormitoryPermit::with(['student'])
            ->where('dormitory_id', $dormitory->id)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->whereNull('actual_return_datetime')
            ->whereNotNull('expected_return_datetime')
            ->where('expected_return_datetime', '<=', $now)
            ->where('overdue_notified_count', '<', $maxReminders)
            ->get();

        foreach ($permits as $permit) {
            if (! $permit->student) {
                continue;
            }

            // Jeda antar pengingat: kirim lagi hanya setelah interval terlewati.
            if ($permit->overdue_notified_at
                && $permit->overdue_notified_at->copy()->addMinutes($interval)->isFuture()) {
                continue;
            }

            try {
                if ($setting->hasChannel('whatsapp')) {
                    $this->whatsapp->notifyOverdue($permit);
                }

                $this->notifyGuardians($permit, $setting, $now);

                $permit->increment('overdue_notified_count');
                $permit->forceFill(['overdue_notified_at' => $now])->save();
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Pengingat kembali asrama gagal: '.$e->getMessage());
            }
        }

        return $sent;
    }

    private function escalate(Dormitory $dormitory, DormitoryNotificationSetting $setting, CarbonInterface $now): int
    {
        $maxReminders = max(1, (int) $setting->max_reminders);
        $threshold = max(1, (int) $setting->escalate_after_minutes);

        $permits = DormitoryPermit::with(['student'])
            ->where('dormitory_id', $dormitory->id)
            ->whereIn('status', [DormitoryPermit::STATUS_APPROVED, DormitoryPermit::STATUS_PICKED_UP])
            ->whereNull('actual_return_datetime')
            ->whereNotNull('expected_return_datetime')
            ->where('overdue_notified_count', '>=', $maxReminders)
            ->where('expected_return_datetime', '<=', $now->subMinutes($threshold))
            ->whereNull('escalation_triggered_at')
            ->get();

        $escalated = 0;

        foreach ($permits as $permit) {
            $permit->forceFill([
                'status' => DormitoryPermit::STATUS_OVERDUE,
                'escalation_triggered_at' => $now,
            ])->save();

            $this->timeline->record(
                studentId: $permit->student_id,
                eventType: BoardingTimelineEvent::TYPE_LEAVE_OVERDUE,
                dormitoryId: $permit->dormitory_id,
                subjectRefs: [
                    'permit_id' => $permit->id,
                    'permit_type' => $permit->permit_type,
                    'expected_return_datetime' => $permit->expected_return_datetime,
                ],
                payload: [
                    'overdue_notified_count' => $permit->overdue_notified_count,
                    'overdue_minutes' => $permit->expected_return_datetime
                        ? $now->diffInMinutes($permit->expected_return_datetime)
                        : null,
                ],
                sourceSystem: 'dormitory.return-reminder-job',
            );

            if ($setting->notify_staff && $permit->student) {
                try {
                    $this->notifications->notifyDormitoryStaff($dormitory->id, [
                        'module' => 'dormitory',
                        'reference_type' => 'dormitory_permit',
                        'reference_id' => $permit->id,
                        'type' => 'danger',
                        'action' => 'permit_escalated',
                        'title' => 'Eskalasi: santri belum kembali',
                        'message' => $permit->student->name.' belum kembali ke asrama hingga '
                            .($permit->expected_return_datetime?->format('d/m/Y H:i') ?? '-')
                            .'. Sudah '.$permit->overdue_notified_count.' kali diingatkan.',
                        'data' => [
                            'student_id' => $permit->student_id,
                            'student_name' => $permit->student->name,
                            'permit_id' => $permit->id,
                            'dormitory_id' => $dormitory->id,
                        ],
                        'send_whatsapp' => $setting->hasChannel('whatsapp'),
                        'send_push' => $setting->hasChannel('push'),
                        'send_email' => $setting->hasChannel('email'),
                        'priority' => 'high',
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Notifikasi eskalasi petugas asrama gagal: '.$e->getMessage());
                }
            }

            $escalated++;
        }

        return $escalated;
    }

    private function notifyGuardians(
        DormitoryPermit $permit,
        DormitoryNotificationSetting $setting,
        CarbonInterface $now,
        bool $headStart = false,
    ): void {
        $student = $permit->student;

        if (! $student) {
            return;
        }

        $mahrom = $student->mahroms()->where('is_primary', true)->first()
            ?? $student->mahroms()->where('is_active', true)->first();

        $guardianName = $mahrom?->name ?: 'Wali';
        $estimasi = $permit->expected_return_datetime?->format('d/m/Y H:i') ?? '-';
        $dormitoryName = $permit->dormitory?->name ?? 'Asrama';

        $message = $this->renderMessage($setting->message_template, [
            'santri' => $student->name,
            'wali' => $guardianName,
            'estimasi' => $estimasi,
            'asrama' => $dormitoryName,
        ], $headStart);

        $this->notifications->notifyGuardiansForStudents([$student->id], [
            'module' => 'dormitory',
            'reference_type' => 'dormitory_permit',
            'reference_id' => $permit->id,
            'type' => $headStart ? 'warning' : 'danger',
            'action' => $headStart ? 'permit_return_reminder' : 'overdue_permit',
            'title' => ($headStart ? 'Pengingat Kembali — ' : 'Terlambat Pulang — ').$student->name,
            'message' => $message,
            'data' => [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'dormitory_id' => $permit->dormitory_id,
                'permit_id' => $permit->id,
                'expected_return_datetime' => $permit->expected_return_datetime?->toIso8601String(),
                'overdue_count' => $permit->overdue_notified_count,
                'head_start' => $headStart,
            ],
            'send_whatsapp' => false, // WA dikirim via GuardianWhatsAppNotifier (mode delivery).
            'send_push' => $setting->hasChannel('push'),
            'send_email' => $setting->hasChannel('email'),
            'send_in_app' => $setting->hasChannel('in_app'),
            'priority' => 'high',
        ]);
    }

    /**
     * @param  array<string, string>  $vars
     */
    private function renderMessage(?string $template, array $vars, bool $headStart): string
    {
        $default = $headStart
            ? "Bpk/Ibu {wali}, mengingatkan {santri} dijadwalkan kembali ke {asrama} pada {estimasi}.\nMohon konfirmasi bila ada perubahan."
            : "Bpk/Ibu {wali}, {santri} belum kembali ke {asrama} hingga batas waktu {estimasi}.\nMohon konfirmasi keberadaan ananda.";

        $text = filled($template) ? $template : $default;

        foreach ($vars as $key => $value) {
            $text = str_replace('{'.$key.'}', (string) $value, $text);
        }

        return $text;
    }
}
