<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\GtkAdditionalTask;
use App\Models\School;
use App\Models\Student;
use App\Models\TahfidzCoordinator;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzSetoran;
use App\Services\NotificationUniversalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendTahfidzMonitoringReminders extends Command
{
    protected $signature = 'tahfidz:send-monitoring-reminders
                            {--days=7 : Setoran pending yang lebih lama dari jumlah hari ini}
                            {--email : Kirim juga melalui email jika preferensi pengguna aktif}
                            {--whatsapp : Kirim juga melalui WhatsApp jika nomor tersedia}
                            {--push : Kirim juga melalui push notification}
                            {--dry-run : Tampilkan jumlah tanpa mengirim notifikasi}';

    protected $description = 'Kirim pengingat monitoring Tahfidz ke koordinator dan guru halaqah';

    public function handle(NotificationUniversalService $notifications): int
    {
        $academicYear = AcademicYear::where('is_active', true)->first()
            ?? AcademicYear::orderByDesc('start_date')->first();

        if (! $academicYear) {
            $this->warn('Tahun ajaran belum tersedia.');

            return self::SUCCESS;
        }

        $pendingDays = max(1, (int) $this->option('days'));
        $dryRun = (bool) $this->option('dry-run');
        $schoolIds = Student::where('status', 'active')->pluck('school_id')->filter()->unique();
        $schools = School::whereIn('id', $schoolIds)->get();
        $sent = 0;
        $skipped = 0;

        foreach ($schools as $school) {
            $groupIds = TahfidzGroup::query()
                ->where('school_id', $school->id)
                ->where('academic_year_id', $academicYear->id)
                ->where('is_active', true)
                ->pluck('id');

            if ($groupIds->isEmpty()) {
                continue;
            }

            $memberIds = TahfidzGroupMember::query()
                ->whereIn('tahfidz_group_id', $groupIds)
                ->where('academic_year_id', $academicYear->id)
                ->where('status', 'aktif')
                ->pluck('student_id')
                ->unique()
                ->values();

            $pending = TahfidzSetoran::query()
                ->whereIn('tahfidz_group_id', $groupIds)
                ->where('verification_status', 'pending')
                ->whereDate('setoran_date', '<=', now()->subDays($pendingDays)->toDateString())
                ->count();

            $recentStudents = TahfidzSetoran::query()
                ->whereIn('student_id', $memberIds)
                ->where('academic_year_id', $academicYear->id)
                ->where('verification_status', 'verified')
                ->whereDate('setoran_date', '>=', now()->subDays(14)->toDateString())
                ->pluck('student_id')
                ->unique();

            $inactiveStudents = $memberIds->diff($recentStudents)->count();

            if ($pending === 0 && $inactiveStudents === 0) {
                continue;
            }

            $recipients = $this->recipients($school, $academicYear, $groupIds);

            if ($recipients->isEmpty()) {
                $this->line("{$school->name}: tidak ada penerima.");

                continue;
            }

            if ($dryRun) {
                $this->line("{$school->name}: {$pending} pending, {$inactiveStudents} santri tanpa setoran 14 hari, {$recipients->count()} penerima.");

                continue;
            }

            foreach ($recipients as $recipientId) {
                $cacheKey = 'tahfidz:monitoring-reminder:'.$academicYear->id.':'.$school->id.':'.$recipientId.':'.now()->toDateString();

                if (! Cache::add($cacheKey, true, now()->endOfDay())) {
                    $skipped++;

                    continue;
                }

                $notifications->send($recipientId, [
                    'module' => 'education',
                    'reference_type' => 'tahfidz_monitoring',
                    'reference_id' => $groupIds->first(),
                    'type' => 'warning',
                    'action' => 'tahfidz_monitoring',
                    'title' => 'Pengingat Monitoring Tahfidz — '.$school->name,
                    'message' => "Ada {$pending} setoran pending dan {$inactiveStudents} santri tanpa setoran terverifikasi 14 hari terakhir. Mohon buka menu Monitoring Tahfidz.",
                    'action_url' => route('user.tahfidz.monitoring.index', [
                        'userId' => $recipientId,
                        'academic_year_id' => $academicYear->id,
                        'school_id' => $school->id,
                    ]),
                    'action_text' => 'Buka Monitoring',
                    'priority' => 'high',
                    'send_email' => (bool) $this->option('email'),
                    'send_whatsapp' => (bool) $this->option('whatsapp'),
                    'send_push' => (bool) $this->option('push'),
                    'work_unit_id' => $school->work_unit_id,
                    'data' => [
                        'school_id' => $school->id,
                        'academic_year_id' => $academicYear->id,
                        'pending_setoran' => $pending,
                        'inactive_students' => $inactiveStudents,
                    ],
                ]);

                $sent++;
            }
        }

        $this->info("Pengingat Tahfidz: {$sent} terkirim, {$skipped} dilewati karena sudah dikirim hari ini.");

        return self::SUCCESS;
    }

    private function recipients(School $school, AcademicYear $academicYear, $groupIds)
    {
        return TahfidzCoordinator::query()
            ->where('school_id', $school->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('is_active', true)
            ->pluck('user_id')
            ->merge(GtkAdditionalTask::query()
                ->where('work_unit_id', $school->work_unit_id)
                ->where('nama_tugas', 'like', '%Koordinator Guru Tahfidz%')
                ->pluck('user_id'))
            ->merge(TahfidzGroup::whereIn('id', $groupIds)->pluck('teacher_id'))
            ->merge(TahfidzGroup::whereIn('id', $groupIds)->pluck('coordinator_id'))
            ->filter()
            ->unique()
            ->values();
    }
}
