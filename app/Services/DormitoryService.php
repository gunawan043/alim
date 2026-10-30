<?php

namespace App\Services;

use App\Domain\Services\BoardingTimelineService;
use App\Models\BoardingTimelineEvent;
use App\Models\Dormitory;
use App\Models\DormitoryAttendance;
use App\Models\DormitoryAttendanceRecap;
use App\Models\DormitoryPermit;
use App\Models\DormitoryResident;
use App\Models\DormitoryReward;
use App\Models\DormitoryViolation;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\StudentHealthPermit;
use App\Models\StudentMahrom;
use App\Services\Notifications\GuardianWhatsAppNotifier;
use Illuminate\Support\Facades\Log;

class DormitoryService
{
    protected NotificationUniversalService $notifService;

    public function __construct(
        NotificationUniversalService $notifService,
        private readonly BoardingTimelineService $timeline
    ) {
        $this->notifService = $notifService;
    }

    /**
     * Kirim notifikasi ke mahrom utama saat izin disetujui.
     */
    public function notifyMahromOnPermitApproval(DormitoryPermit $permit): void
    {
        $student = $permit->student;
        if (! $student) {
            return;
        }

        try {
            app(GuardianWhatsAppNotifier::class)
                ->notifyPermitApproved($permit);
        } catch (\Throwable $e) {
            Log::warning('WA notif approve izin gagal: '.$e->getMessage());
        }
    }

    /**
     * Kirim notifikasi ke mahrom utama saat pelanggaran dicatat.
     */
    public function notifyMahromOnViolation(DormitoryViolation $violation): void
    {
        $student = $violation->student;
        if (! $student) {
            return;
        }

        $mahrom = $student->mahroms()->where('is_primary', true)->first()
            ?? $student->mahroms()->where('is_active', true)->first();

        $categoryLabel = match ($violation->violation_category) {
            'ringan' => 'Ringan',
            'sedang' => 'Sedang',
            'berat' => 'Berat',
            default => $violation->violation_category,
        };

        // WA langsung ke nomor wali/terpusat — tidak bergantung pada akun
        // login wali atau worker queue, sehingga notifikasi tetap terkirim.
        try {
            app(GuardianWhatsAppNotifier::class)
                ->notifyViolation(
                    $student,
                    $violation->violation_type,
                    optional($violation->violation_date)->format('d/m/Y'),
                    trim(($violation->notes ?? '').' '.($violation->action_taken ?? '')) ?: null,
                );
        } catch (\Throwable $e) {
            Log::warning('WA notif pelanggaran gagal: '.$e->getMessage());
        }

        // Notifikasi sistem untuk setiap akun wali yang terhubung ke santri.
        // Baris ini juga menjadi sumber data API mobile wali di masa depan.
        $this->notifyGuardiansForStudent($student, [
            'module' => 'dormitory',
            'reference_type' => 'dormitory_violation',
            'reference_id' => $violation->id,
            'type' => 'warning',
            'action' => 'violation_recorded',
            'title' => "Pelanggaran Asrama — {$student->name}",
            'message' => 'Bpk/Ibu '.($mahrom?->name ?? 'Wali').", anak Anda {$student->name} mendapat\n"
                ."pelanggaran {$violation->violation_type} (Kategori: {$categoryLabel})\n"
                ."dengan poin: {$violation->points}.\n"
                ."Tindakan: {$violation->action_taken}",
            'data' => [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'dormitory_id' => $violation->dormitory_id,
                'violation_id' => $violation->id,
                'violation_type' => $violation->violation_type,
                'violation_category' => $violation->violation_category,
                'points' => (int) $violation->points,
            ],
            'send_whatsapp' => false,
            'send_email' => false,
            'send_push' => false,
            'priority' => 'high',
        ]);
    }

    /**
     * Kirim notifikasi WA ke wali saat satuan pendidikan mencatat prestasi.
     * Ini dikirim sebelum approval asrama dan bukan notifikasi reward final.
     */
    public function notifyMahromOnAchievement(StudentAchievement $achievement): void
    {
        if (! $achievement->student) {
            return;
        }

        try {
            app(GuardianWhatsAppNotifier::class)->notifyAchievement($achievement);
        } catch (\Throwable $e) {
            Log::warning('WA notif prestasi santri gagal: '.$e->getMessage());
        }
    }

    /**
     * Kirim notifikasi WA ke wali saat penghargaan dicatat.
     */
    public function notifyMahromOnReward(DormitoryReward $reward): void
    {
        if (! $reward->student) {
            return;
        }

        $mahrom = $reward->student->mahroms()->where('is_primary', true)->first()
            ?? $reward->student->mahroms()->where('is_active', true)->first();

        try {
            app(GuardianWhatsAppNotifier::class)->notifyReward($reward);
        } catch (\Throwable $e) {
            Log::warning('WA notif penghargaan gagal: '.$e->getMessage());
        }

        $this->notifyGuardiansForStudent($reward->student, [
            'module' => 'dormitory',
            'reference_type' => 'dormitory_reward',
            'reference_id' => $reward->id,
            'type' => 'success',
            'action' => 'reward_recorded',
            'title' => "Penghargaan Asrama — {$reward->student->name}",
            'message' => 'Assalamu\'alaikum Bpk/Ibu '.($mahrom?->name ?? 'Wali').'. '
                ."Alhamdulillahilladzi bini'matihi tatimmush sholihaat. Ananda {$reward->student->name} telah disetujui menerima penghargaan "
                ."\"{$reward->title}\" ({$reward->category_text}, {$reward->level_text}) dengan poin +{$reward->points}.",
            'data' => [
                'student_id' => $reward->student->id,
                'student_name' => $reward->student->name,
                'dormitory_id' => $reward->dormitory_id,
                'reward_id' => $reward->id,
                'reward_title' => $reward->title,
                'category' => $reward->category,
                'level' => $reward->level,
                'points' => (int) $reward->points,
            ],
            'send_whatsapp' => false,
            'send_email' => false,
            'send_push' => false,
            'priority' => 'medium',
        ]);
    }

    /**
     * Kirim notifikasi ke mahrom utama saat aluno absen (alpa).
     */
    public function notifyMahromOnAlpa(DormitoryPermit $permit): void
    {
        $student = $permit->student;
        if (! $student) {
            return;
        }

        $mahrom = $student->mahroms()->where('is_primary', true)->first()
            ?? $student->mahroms()->where('is_active', true)->first();

        try {
            app(GuardianWhatsAppNotifier::class)
                ->notifyOverdue($permit);
        } catch (\Throwable $e) {
            Log::warning('WA notif overdue gagal: '.$e->getMessage());
        }

        $this->notifyGuardiansForStudent($student, [
            'module' => 'dormitory',
            'reference_type' => 'dormitory_permit',
            'reference_id' => $permit->id,
            'type' => 'danger',
            'action' => 'overdue_permit',
            'title' => "Terlambat Pulang — {$student->name}",
            'message' => 'Bpk/Ibu '.($mahrom?->name ?? 'Wali').", {$student->name} belum kembali\n"
                ."dari izin pulang hingga waktu yang dijadwalkan\n"
                ."({$permit->expected_return_datetime->format('d/m/Y H:i')}).\n"
                .'Mohon konfirmasi.',
            'data' => [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'dormitory_id' => $permit->dormitory_id,
                'permit_id' => $permit->id,
                'expected_return_datetime' => $permit->expected_return_datetime?->toIso8601String(),
            ],
            'send_whatsapp' => false,
            'send_email' => false,
            'send_push' => false,
            'priority' => 'high',
        ]);
    }

    /**
     * Kirim notifikasi broadcast darurat ke semua wali asrama.
     * Recipients diambil dari mahroms aktif dari resident students.
     */
    public function broadcastToDormitoryWalis(Dormitory $dormitory, array $data): int
    {
        $studentIds = $dormitory->residents()
            ->where('is_active', true)
            ->pluck('student_id')
            ->unique()
            ->values();

        if ($studentIds->isEmpty()) {
            return 0;
        }

        $via = $data['broadcast_via'] ?? 'all';

        return app(NotificationUniversalService::class)->notifyGuardiansForStudents(
            $studentIds,
            [
                'module' => 'dormitory',
                'reference_type' => 'dormitory_emergency_broadcast',
                'reference_id' => $dormitory->id,
                'type' => match ($data['severity'] ?? 'info') {
                    'emergency', 'urgent' => 'danger',
                    'warning' => 'warning',
                    default => 'info',
                },
                'action' => 'emergency_broadcast',
                'title' => $data['title'] ?? 'Pengumuman Asrama',
                'message' => $data['content'] ?? '',
                'priority' => in_array($data['severity'] ?? 'info', ['urgent', 'emergency'], true) ? 'high' : 'medium',
                'send_whatsapp' => in_array($via, ['whatsapp', 'all'], true),
                'send_push' => true,
            ],
        );
    }

    /**
     * Validasi companion apakah mahrom yang sah dari minimalis.
     */
    public function validateMahromCompanion(string $studentId, string $mahromId): bool
    {
        return StudentMahrom::where('id', $mahromId)
            ->where('student_id', $studentId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Cek apakah minimalis boleh mengajukan izin sakit
     * (harus ada StudentHealthPermit yang approved dari UKS).
     */
    public function canApplySickPermit(DormitoryPermit $permit): bool
    {
        return StudentHealthPermit::where('student_id', $permit->student_id)
            ->where('status', 'approved')
            ->whereNotNull('dormitory_id')
            ->whereDate('start_date', '<=', $permit->departure_datetime)
            ->whereDate('end_date', '>=', $permit->departure_datetime)
            ->exists();
    }

    /**
     * Proses eskalasi permit yang overdue.
     * Dipanggil oleh scheduled job setiap jam.
     */
    public function processOverduePermits(): int
    {
        $overduePermits = DormitoryPermit::where('status', 'approved')
            ->whereNull('actual_return_datetime')
            ->where('expected_return_datetime', '<', now())
            ->where('overdue_notified_count', '<', 3)
            ->get();

        $count = 0;
        foreach ($overduePermits as $permit) {
            $permit->increment('overdue_notified_count');
            $permit->update(['overdue_notified_at' => now()]);
            $this->notifyMahromOnAlpa($permit);
            $count++;
        }

        // Eskalasi ke admin jika sudah 3x notifikasi dan masih overdue > 3 jam
        DormitoryPermit::where('status', 'approved')
            ->whereNull('actual_return_datetime')
            ->where('overdue_notified_count', '>=', 3)
            ->where('expected_return_datetime', '<', now()->subHours(3))
            ->whereNull('escalation_triggered_at')
            ->each(function (DormitoryPermit $p) {
                $p->update(['status' => 'overdue', 'escalation_triggered_at' => now()]);

                // Emit a TYPE_LEAVE_OVERDUE timeline event so the audit
                // trail and per-student history reflect the escalation.
                $this->timeline->record(
                    studentId: $p->student_id,
                    eventType: BoardingTimelineEvent::TYPE_LEAVE_OVERDUE,
                    dormitoryId: $p->dormitory_id,
                    subjectRefs: [
                        'permit_id' => $p->id,
                        'permit_type' => $p->permit_type,
                        'expected_return_datetime' => $p->expected_return_datetime,
                    ],
                    payload: [
                        'overdue_notified_count' => $p->overdue_notified_count,
                        'overdue_minutes' => $p->expected_return_datetime
                            ? now()->diffInMinutes($p->expected_return_datetime)
                            : null,
                    ],
                    sourceSystem: 'dormitory.overdue-job',
                );
            });

        return $count;
    }

    /**
     * Simpan notifikasi sistem untuk seluruh akun wali yang terhubung
     * dengan satu santri. Ini adalah kanal "by sistem" yang nantinya
     * dikonsumsi aplikasi mobile wali; WA tetap dikirim terpisah.
     *
     * @param  array<string, mixed>  $data
     */
    public function notifyGuardiansForStudent(Student $student, array $data): int
    {
        try {
            return $this->notifService->notifyGuardiansForStudents([$student->id], $data);
        } catch (\Throwable $e) {
            Log::warning('Notif sistem wali gagal: '.$e->getMessage());

            return 0;
        }
    }

    /**
     * Notifikasi sistem saat santri dijemput keluar asrama.
     */
    public function notifyPermitPickedUpInApp(DormitoryPermit $permit): void
    {
        if (! $permit->student) {
            return;
        }

        $this->notifyGuardiansForStudent($permit->student, [
            'module' => 'dormitory',
            'reference_type' => 'dormitory_permit',
            'reference_id' => $permit->id,
            'type' => 'info',
            'action' => 'permit_picked_up',
            'title' => "Santri Keluar Asrama — {$permit->student->name}",
            'message' => "{$permit->student->name} telah dijemput keluar asrama"
                .($permit->destination ? " menuju {$permit->destination}" : '')
                .'. Estimasi kembali: '
                .($permit->expected_return_datetime?->format('d/m/Y H:i') ?? '-').'.',
            'data' => [
                'student_id' => $permit->student_id,
                'student_name' => $permit->student->name,
                'dormitory_id' => $permit->dormitory_id,
                'permit_id' => $permit->id,
                'permit_type' => $permit->permit_type,
                'status' => 'picked_up',
                'destination' => $permit->destination,
                'expected_return_datetime' => $permit->expected_return_datetime?->toIso8601String(),
            ],
            'send_whatsapp' => false,
            'send_email' => false,
            'send_push' => false,
            'priority' => 'medium',
        ]);
    }

    /**
     * Notifikasi sistem saat santri kembali ke asrama.
     */
    public function notifyPermitReturnedInApp(DormitoryPermit $permit): void
    {
        if (! $permit->student) {
            return;
        }

        $this->notifyGuardiansForStudent($permit->student, [
            'module' => 'dormitory',
            'reference_type' => 'dormitory_permit',
            'reference_id' => $permit->id,
            'type' => 'success',
            'action' => 'permit_returned',
            'title' => "Santri Kembali ke Asrama — {$permit->student->name}",
            'message' => "Alhamdulillah, {$permit->student->name} telah kembali ke asrama pada "
                .($permit->actual_return_datetime?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i')).'.',
            'data' => [
                'student_id' => $permit->student_id,
                'student_name' => $permit->student->name,
                'dormitory_id' => $permit->dormitory_id,
                'permit_id' => $permit->id,
                'permit_type' => $permit->permit_type,
                'status' => 'returned',
                'actual_return_datetime' => $permit->actual_return_datetime?->toIso8601String(),
            ],
            'send_whatsapp' => false,
            'send_email' => false,
            'send_push' => false,
            'priority' => 'medium',
        ]);
    }

    /**
     * Generate rekap absensi bulanan per penghuni.
     */
    public function generateMonthlyRecap(
        string $dormitoryId,
        string $academicYearId,
        int $month,
        int $year
    ): int {
        $residents = DormitoryResident::where('dormitory_id', $dormitoryId)
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->get();

        $count = 0;
        foreach ($residents as $resident) {
            $records = DormitoryAttendance::where('student_id', $resident->student_id)
                ->where('dormitory_id', $dormitoryId)
                ->whereMonth('attendance_date', $month)
                ->whereYear('attendance_date', $year)
                ->get();

            $semester = $month >= 7 ? 'ganjil' : 'genap';

            $recap = DormitoryAttendanceRecap::updateOrCreate(
                [
                    'student_id' => $resident->student_id,
                    'academic_year_id' => $academicYearId,
                    'recap_month' => $month,
                    'recap_year' => $year,
                ],
                [
                    'room_id' => $resident->room_id,
                    'dormitory_id' => $dormitoryId,
                    'semester' => $semester,
                    'total_hadir' => $records->where('status', 'hadir')->count(),
                    'total_izin' => $records->where('status', 'izin')->count(),
                    'total_sakit' => $records->where('status', 'sakit')->count(),
                    'total_alpa' => $records->where('status', 'alpa')->count(),
                    'total_pulang' => $records->where('status', 'pulang')->count(),
                ]
            );
            $count++;
        }

        return $count;
    }

    /**
     * Sync dormitory attendance from a Boarding Timeline event.
     *
     * Triggered by BoardingTimelineService when the rules engine allows
     * auto-sync. Maps timeline event types to dormitory_attendance rows.
     *
     * @param  array<string, mixed>  $payload  Timeline payload (permit_id, etc.)
     */
    public function syncBoardingAttendance(
        string $studentId,
        string $eventType,
        ?string $dormitoryId,
        array $payload = []
    ): void {
        $today = now()->toDateString();

        $statusMapping = [
            BoardingTimelineEvent::TYPE_LEAVE_STARTED => 'pulang',
            BoardingTimelineEvent::TYPE_LEAVE_APPROVED => 'izin',
            BoardingTimelineEvent::TYPE_RETURNED => 'hadir',
            BoardingTimelineEvent::TYPE_HOSPITALIZED => 'sakit',
            BoardingTimelineEvent::TYPE_RECOVERED => 'hadir',
            BoardingTimelineEvent::TYPE_VISIT_CHECK_IN => 'hadir',
            BoardingTimelineEvent::TYPE_VISIT_CHECK_OUT => 'pulang',
        ];

        $status = $statusMapping[$eventType] ?? null;
        if (! $status) {
            return;
        }

        $resident = DormitoryResident::where('student_id', $studentId)
            ->where('is_active', true)
            ->when($dormitoryId, fn ($q) => $q->where('dormitory_id', $dormitoryId))
            ->first();

        if (! $resident) {
            return;
        }

        $attendance = DormitoryAttendance::updateOrCreate(
            [
                'resident_id' => $resident->id,
                'attendance_date' => $today,
            ],
            [
                'status' => $status,
                'dormitory_id' => $resident->dormitory_id,
                'room_id' => $resident->room_id,
                'academic_year_id' => $resident->academic_year_id,
                'timeline_event_id' => $payload['timeline_event_id'] ?? null,
                'source_permitted_record_id' => $payload['source_permitted_record_id'] ?? null,
                'notes' => "Synced from Boarding Timeline ({$eventType}).",
            ]
        );

        // For leave_started, propagate to academic attendance when leaving extends past class hours
        if ($status === 'pulang' && ($payload['propagate_to_academic'] ?? true)) {
            $this->propagateToAcademicAttendance($resident->student_id, $eventType, $payload);
        }
    }

    /**
     * Propagate a leave event to academic attendance when policy says auto-sync.
     *
     * @param  array<string, mixed>  $payload
     */
    private function propagateToAcademicAttendance(
        string $studentId,
        string $eventType,
        array $payload
    ): void {
        // Reuse the existing akademik-attendance sync logic if available.
        // The implementation here intentionally defers to whatever the
        // academic-attendance side already does for permit-triggered sync.
        try {
            $academicService = app(AcademicAttendanceService::class);
            if (method_exists($academicService, 'syncFromDormitoryEvent')) {
                $academicService->syncFromDormitoryEvent(
                    studentId: $studentId,
                    eventType: $eventType,
                    payload: $payload,
                );
            }
        } catch (\Throwable $e) {
            // Don't fail the dormitory sync if academic sync isn't available
            logger()->warning('Academic attendance sync failed', [
                'student_id' => $studentId,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
