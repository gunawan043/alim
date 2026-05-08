<?php

namespace App\Services;

use App\Models\Dormitory;
use App\Models\DormitoryPermit;
use App\Models\DormitoryViolation;
use App\Models\DormitoryResident;
use App\Models\DormitoryActivityLog;
use App\Models\StudentMahrom;
use App\Models\Student;
use App\Models\User;
use App\Services\NotificationUniversalService;

class DormitoryService
{
    protected NotificationUniversalService $notifService;

    public function __construct(NotificationUniversalService $notifService)
    {
        $this->notifService = $notifService;
    }

    /**
     * Kirim notifikasi ke mahrom utama saat izin disetujui.
     */
    public function notifyMahromOnPermitApproval(DormitoryPermit $permit): void
    {
        $student = $permit->student;
        if (!$student) return;

        $mahrom = $student->mahroms()->where('is_primary', true)->first()
            ?? $student->mahroms()->where('is_active', true)->first();

        if (!$mahrom || !$mahrom->phone) return;

        $this->notifService->sendToRole('Wali Santri', [
            'module' => 'dormitory',
            'reference_type' => 'dormitory_permit',
            'reference_id' => $permit->id,
            'type' => 'success',
            'action' => 'permit_approved',
            'title' => "Izin Pulang Disetujui — {$student->name}",
            'message' => "Bpk/Ibu {$mahrom->name}, izin pulang untuk {$student->name} telah disetujui.\n"
                . "Penjemput: {$permit->companion_name} ({$permit->companion_relation})\n"
                . "Rencanaback: {$permit->expected_return_datetime->format('d/m/Y H:i')}",
            'send_whatsapp' => true,
            'priority' => 'medium',
        ]);
    }

    /**
     * Kirim notifikasi ke mahrom utama saat pelanggaran dicatat.
     */
    public function notifyMahromOnViolation(DormitoryViolation $violation): void
    {
        $student = $violation->student;
        if (!$student) return;

        $mahrom = $student->mahroms()->where('is_primary', true)->first()
            ?? $student->mahroms()->where('is_active', true)->first();

        if (!$mahrom) return;

        $categoryLabel = match ($violation->violation_category) {
            'ringan' => 'Ringan',
            'sedang' => 'Sedang',
            'berat' => 'Berat',
            default => $violation->violation_category,
        };

        $this->notifService->sendToRole('Wali Santri', [
            'module' => 'dormitory',
            'reference_type' => 'dormitory_violation',
            'reference_id' => $violation->id,
            'type' => 'warning',
            'action' => 'violation_recorded',
            'title' => "Pelanggaran Asrama — {$student->name}",
            'message' => "Bpk/Ibu {$mahrom->name}, anak Anda {$student->name} mendapat\n"
                . "pelanggaran {$violation->violation_type} (Kategori: {$categoryLabel})\n"
                . "dengan poin: {$violation->points}.\n"
                . "Tindakan: {$violation->action_taken}",
            'send_whatsapp' => true,
            'priority' => 'high',
        ]);
    }

    /**
     * Kirim notifikasi ke mahrom utama saat aluno absen (alpa).
     */
    public function notifyMahromOnAlpa(DormitoryPermit $permit): void
    {
        $student = $permit->student;
        if (!$student) return;

        $mahrom = $student->mahroms()->where('is_primary', true)->first()
            ?? $student->mahroms()->where('is_active', true)->first();

        if (!$mahrom) return;

        $this->notifService->sendToRole('Wali Santri', [
            'module' => 'dormitory',
            'reference_type' => 'dormitory_permit',
            'reference_id' => $permit->id,
            'type' => 'danger',
            'action' => 'overdue_permit',
            'title' => "Terlambat Pulang — {$student->name}",
            'message' => "Bpk/Ibu {$mahrom->name}, {$student->name} belum kembali\n"
                . "dari izin pulang hingga waktu yang dijadwalkan\n"
                . "({$permit->expected_return_datetime->format('d/m/Y H:i')}).\n"
                . "Mohon konfirmasi.",
            'send_whatsapp' => true,
            'priority' => 'high',
        ]);
    }

    /**
     * Kirim notifikasi broadcast darurat ke semua wali asrama.
     * Recipients diambil dari mahroms aktif dari resident students.
     */
    public function broadcastToDormitoryWalis(Dormitory $dormitory, array $data): void
    {
        $mahromIds = $dormitory->residents()
            ->where('is_active', true)
            ->with('student.mahroms')
            ->get()
            ->pluck('student.mahroms')
            ->flatten()
            ->filter(fn($m) => $m->is_active)
            ->pluck('id')
            ->unique();

        // Untuk broadcast, notifikasi dikirim via system.
        // Recipient bergantung pada sistem waliregistration yang akan datang.
        // Untuk sekarang, broadcast hanya disimpan di tabel emergency broadcast.
        // Implementasi kirim WA/Email ke mahrom dapat ditambahkan setelah
        // sistem registrasi wali tersedia.

        \App\Models\DormitoryEmergencyBroadcast::create([
            'dormitory_id' => $dormitory->id,
            'title' => $data['title'] ?? '',
            'content' => $data['content'] ?? '',
            'severity' => $data['severity'] ?? 'info',
            'broadcast_via' => $data['broadcast_via'] ?? 'all',
            'ack_required' => $data['ack_required'] ?? false,
            'expires_at' => $data['expires_at'] ?? null,
            'created_by' => auth()->id(),
        ]);
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
        return \App\Models\StudentHealthPermit::where('student_id', $permit->student_id)
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
            ->each(fn($p) => $p->update(['status' => 'overdue', 'escalation_triggered_at' => now()]));

        return $count;
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
            $records = \App\Models\DormitoryAttendance::where('resident_id', $resident->id)
                ->whereMonth('attendance_date', $month)
                ->whereYear('attendance_date', $year)
                ->get();

            $semester = $month >= 7 ? 'ganjil' : 'genap';

            $recap = \App\Models\DormitoryAttendanceRecap::updateOrCreate(
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
                    'total_izin'  => $records->where('status', 'izin')->count(),
                    'total_sakit' => $records->where('status', 'sakit')->count(),
                    'total_alpa'  => $records->where('status', 'alpa')->count(),
                    'total_pulang'=> $records->where('status', 'pulang')->count(),
                ]
            );
            $count++;
        }

        return $count;
    }
}
