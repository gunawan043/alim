<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * ARSIP PERIODE — satu gerbang penutupan tahun ajaran.
 *
 * Aturan tulis (konsisten di seluruh modul yang mengadopsi service ini):
 *  - Tahun ajaran terkunci (`locked_at`) → data TIDAK dapat diubah, bahkan
 *    bila tahun ajaran tersebut diaktifkan kembali, sampai kuncinya dibuka.
 *  - Tahun ajaran aktif → dapat diubah.
 *  - Tahun ajaran lain (tidak aktif, belum dikunci) → arsip berjalan,
 *    hanya boleh dibaca; kecuali modul perencanaan (dokumen kurikulum) yang
 *    memakai assertNotLocked agar masih dapat disiapkan sebelum aktivasi.
 */
class AcademicPeriodService
{
    public const STATUS_ACTIVE = 'aktif';

    public const STATUS_OPEN = 'terbuka';

    public const STATUS_LOCKED = 'terkunci';

    public function activeAcademicYearId(): ?string
    {
        return AcademicYear::where('is_active', true)->value('id');
    }

    public function isLocked(?string $academicYearId): bool
    {
        if (! $academicYearId) {
            return false;
        }

        return AcademicYear::whereKey($academicYearId)->whereNotNull('locked_at')->exists();
    }

    public function statusOf(AcademicYear $academicYear): string
    {
        if ($academicYear->isLocked()) {
            return self::STATUS_LOCKED;
        }

        return $academicYear->is_active ? self::STATUS_ACTIVE : self::STATUS_OPEN;
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_ACTIVE => 'Aktif',
            self::STATUS_LOCKED => 'Terkunci (Arsip)',
            default => 'Terbuka',
        };
    }

    /**
     * Tolak bila periode ditutup/dikunci — dipakai modul perencanaan
     * (dokumen kurikulum) yang masih boleh disiapkan untuk TA mendatang.
     */
    public function assertNotLocked(?string $academicYearId, string $context = 'Data'): void
    {
        if (! $academicYearId) {
            return;
        }

        $academicYear = AcademicYear::find($academicYearId);

        if ($academicYear?->isLocked()) {
            abort(403, "{$context} tahun ajaran {$academicYear->name} sudah ditutup (arsip terkunci) dan tidak dapat diubah.");
        }
    }

    /**
     * Tolak bila periode tidak dapat diubah: terkunci ATAU bukan TA aktif.
     * Dipakai modul transaksional (nilai, absensi, jurnal, dsb.).
     */
    public function assertWritable(?string $academicYearId, string $context = 'Data'): void
    {
        if (! $academicYearId) {
            return;
        }

        $academicYear = AcademicYear::find($academicYearId);

        if (! $academicYear) {
            return;
        }

        if ($academicYear->isLocked()) {
            abort(403, "{$context} tahun ajaran {$academicYear->name} sudah ditutup (arsip terkunci) dan tidak dapat diubah.");
        }

        $activeId = $this->activeAcademicYearId();

        if ($activeId && (string) $academicYear->id !== (string) $activeId) {
            abort(403, "{$context} tahun ajaran {$academicYear->name} bersifat arsip dan tidak dapat diubah.");
        }
    }

    /**
     * Resolusi tahun ajaran dari sebuah tanggal (untuk data berbasis tanggal
     * yang tidak menyimpan academic_year_id, mis. absensi GTK).
     */
    public function academicYearIdForDate(\DateTimeInterface|string|null $date): ?string
    {
        if (! $date) {
            return null;
        }

        $day = $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : (string) $date;

        return AcademicYear::query()
            ->whereDate('start_date', '<=', $day)
            ->whereDate('end_date', '>=', $day)
            ->orderByDesc('start_date')
            ->value('id');
    }

    /**
     * Alasan periode tidak dapat dikunci (null = boleh dikunci).
     */
    public function lockBlockReason(AcademicYear $academicYear): ?string
    {
        if ($academicYear->isLocked()) {
            return 'Tahun ajaran ini sudah ditutup (terkunci).';
        }

        if ($academicYear->is_active) {
            return 'Tahun ajaran aktif tidak dapat dikunci. Aktifkan tahun ajaran berikutnya terlebih dahulu.';
        }

        return null;
    }

    public function lock(AcademicYear $academicYear, ?User $user, ?string $notes = null): void
    {
        DB::transaction(function () use ($academicYear, $user, $notes) {
            $academicYear->forceFill([
                'locked_at' => now(),
                'locked_by' => $user?->id,
                'lock_notes' => $notes,
            ])->save();

            $this->audit('ACADEMIC_YEAR_LOCKED', $academicYear, $user, $notes);
        });
    }

    public function unlock(AcademicYear $academicYear, ?User $user, ?string $notes = null): void
    {
        DB::transaction(function () use ($academicYear, $user, $notes) {
            $academicYear->forceFill([
                'locked_at' => null,
                'locked_by' => null,
                'lock_notes' => null,
            ])->save();

            $this->audit('ACADEMIC_YEAR_UNLOCKED', $academicYear, $user, $notes);
        });
    }

    private function audit(string $action, AcademicYear $academicYear, ?User $user, ?string $notes): void
    {
        AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'table_name' => 'academic_years',
            'record_id' => $academicYear->id,
            'record_type' => AcademicYear::class,
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 500),
        ]);
    }
}
