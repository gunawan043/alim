<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\JadwalKbm;
use App\Models\NotificationPreference;
use App\Models\TeacherClassAttendance;
use App\Services\NotificationUniversalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Notifikasi saat jam pelajaran selesai.
 *
 * Dijalankan setiap menit. Guru yang sudah check-in tetapi belum check-out
 * menerima pengingat untuk melakukan scan keluar.
 */
class NotifyLessonEnded extends Command
{
    protected $signature = 'alim:notify-lesson-ended';

    protected $description = 'Kirim notifikasi ke guru saat jam pelajaran selesai dan belum scan keluar.';

    public function handle(NotificationUniversalService $notifier): int
    {
        $now = now();
        $today = $now->toDateString();
        $from = $now->copy()->subMinute();
        $to = $now->copy()->addMinute();

        $schedules = JadwalKbm::query()
            ->with(['subject:id,name', 'studyGroup:id,name'])
            ->where('day_of_week', $now->dayOfWeekIso)
            ->where('is_active', true)
            ->whereNotNull('teacher_id')
            ->whereBetween('end_time', [$from->format('H:i:s'), $to->format('H:i:s')])
            ->get();

        if ($schedules->isEmpty()) {
            $this->info('Notifikasi akhir pelajaran terkirim: 0.');

            return self::SUCCESS;
        }

        $attendance = TeacherClassAttendance::query()
            ->whereIn('jadwal_kbm_id', $schedules->pluck('id'))
            ->whereDate('attendance_date', $today)
            ->get()
            ->keyBy('jadwal_kbm_id');

        $preferences = NotificationPreference::query()
            ->whereIn('user_id', $schedules->pluck('teacher_id')->filter()->unique()->all())
            ->get()
            ->keyBy('user_id');

        $sent = 0;

        foreach ($schedules as $jadwal) {
            $cacheKey = sprintf('lesson-ended:%s:%s', $jadwal->id, $today);

            if (! Cache::add($cacheKey, true, $now->copy()->endOfDay())) {
                continue;
            }

            $att = $attendance->get($jadwal->id);
            if (! $att || ! $att->actual_time_in || $att->actual_time_out) {
                continue;
            }

            $pref = $preferences->get($jadwal->teacher_id);
            $prefData = $pref
                ? $pref->only(['email_enabled', 'whatsapp_enabled', 'push_enabled', 'lesson_reminder'])
                : NotificationPreference::defaults();

            if (! ($prefData['lesson_reminder'] ?? true)) {
                continue;
            }

            $end = substr((string) $jadwal->end_time, 0, 5);
            $subject = $jadwal->subject?->name ?? 'Jam Pelajaran';
            $group = $jadwal->studyGroup?->name ?? '-';

            $notifier->send($jadwal->teacher_id, [
                'module' => 'teacher-attendance',
                'reference_type' => JadwalKbm::class,
                'reference_id' => $jadwal->id,
                'type' => 'info',
                'action' => 'lesson_end',
                'title' => 'Jam Pelajaran Selesai: '.$subject,
                'message' => 'Jadwal '.$group.' berakhir pukul '.$end.'. Jangan lupa scan keluar.',
                'action_url' => route('user.teacher-qr.scan', ['userId' => $jadwal->teacher_id]),
                'action_text' => 'Scan Keluar',
                'priority' => 'high',
                'send_email' => (bool) ($prefData['email_enabled'] ?? true),
                'send_whatsapp' => (bool) ($prefData['whatsapp_enabled'] ?? true),
                'send_push' => (bool) ($prefData['push_enabled'] ?? true),
                'template_params' => [$subject, $group, $end],
                'data' => [
                    'jadwal_kbm_id' => $jadwal->id,
                    'study_group' => $group,
                    'subject' => $subject,
                    'end_time' => $end,
                    'screen' => 'teacher-qr',
                ],
            ]);

            $sent++;
        }

        $this->info("Notifikasi akhir pelajaran terkirim: {$sent}.");

        return self::SUCCESS;
    }
}
