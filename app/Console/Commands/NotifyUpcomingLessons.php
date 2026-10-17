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
 * Pengingat jam pelajaran untuk guru.
 *
 * Dijalankan setiap menit oleh scheduler. Guru menerima notifikasi in-app
 * sekitar N menit (default 5) sebelum jam mengajarnya dimulai, kecuali:
 * - sudah melakukan absen masuk untuk jadwal tersebut, atau
 * - pengingat untuk jadwal itu sudah pernah dikirim hari ini.
 */
class NotifyUpcomingLessons extends Command
{
    protected $signature = 'alim:notify-lesson-reminders {--minutes=5 : Menit sebelum jam pelajaran dimulai}';

    protected $description = 'Kirim notifikasi pengingat ke guru N menit sebelum jam pelajaran dimulai.';

    public function handle(NotificationUniversalService $notifier): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $now = now();
        $today = $now->toDateString();
        $until = $now->copy()->addMinutes($minutes)->addSeconds(30);

        // Jam pelajaran tidak melewati tengah malam; lewati jika jendela melewati hari.
        if ($until->toDateString() !== $today) {
            $this->info('Pengingat jam pelajaran terkirim: 0.');

            return self::SUCCESS;
        }

        $schedules = JadwalKbm::query()
            ->with(['subject:id,name', 'studyGroup:id,name'])
            ->where('day_of_week', $now->dayOfWeekIso)
            ->where('is_active', true)
            ->whereNotNull('teacher_id')
            ->whereBetween('start_time', [$now->format('H:i:s'), $until->format('H:i:s')])
            ->get();

        $preferences = NotificationPreference::query()
            ->whereIn('user_id', $schedules->pluck('teacher_id')->filter()->unique()->all())
            ->get()
            ->keyBy('user_id');

        $sent = 0;

        foreach ($schedules as $jadwal) {
            $cacheKey = sprintf('lesson-reminder:%s:%s', $jadwal->id, $today);

            // Cache::add atomik: aman bila scheduler berjalan ganda.
            if (! Cache::add($cacheKey, true, $now->copy()->endOfDay())) {
                continue;
            }

            $alreadyCheckedIn = TeacherClassAttendance::query()
                ->where('jadwal_kbm_id', $jadwal->id)
                ->whereDate('attendance_date', $today)
                ->whereNotNull('actual_time_in')
                ->exists();

            if ($alreadyCheckedIn) {
                continue;
            }

            $start = substr((string) $jadwal->start_time, 0, 5);
            $subject = $jadwal->subject?->name ?? 'Jam Pelajaran';
            $group = $jadwal->studyGroup?->name ?? '-';

            $pref = $preferences->get($jadwal->teacher_id);
            $prefData = $pref
                ? $pref->only(['email_enabled', 'whatsapp_enabled', 'push_enabled', 'lesson_reminder'])
                : NotificationPreference::defaults();

            if (! ($prefData['lesson_reminder'] ?? true)) {
                continue;
            }

            $notifier->send($jadwal->teacher_id, [
                'module' => 'teacher-attendance',
                'reference_type' => JadwalKbm::class,
                'reference_id' => $jadwal->id,
                'type' => 'info',
                'action' => 'lesson_reminder',
                'title' => $minutes.' Menit Lagi: '.$subject,
                'message' => 'Jadwal '.$group.' dimulai pukul '.$start.'. Siapkan scan QR saat tiba di kelas.',
                'action_url' => route('user.teacher-qr.scan', ['userId' => $jadwal->teacher_id]),
                'action_text' => 'Buka Scan QR',
                'priority' => 'high',
                'send_email' => (bool) ($prefData['email_enabled'] ?? true),
                'send_whatsapp' => (bool) ($prefData['whatsapp_enabled'] ?? true),
                'send_push' => (bool) ($prefData['push_enabled'] ?? true),
                // Parameter template Meta: {1} mapel, {2} kelas, {3} jam mulai.
                'template_params' => [$subject, $group, $start],
                'data' => [
                    'jadwal_kbm_id' => $jadwal->id,
                    'study_group' => $group,
                    'subject' => $subject,
                    'start_time' => $start,
                    'minutes_before' => $minutes,
                ],
            ]);

            $sent++;
        }

        $this->info("Pengingat jam pelajaran terkirim: {$sent}.");

        return self::SUCCESS;
    }
}
