<?php

namespace App\Services;

use App\Jobs\RecordLifecycleAuditJob;
use App\Models\Student;

/**
 * Jejak jalur masuk & keluar santri.
 *
 * Santri bisa masuk lewat empat jalur: tambah manual, import Excel, mutasi
 * masuk, dan registrasi wali dari aplikasi mobile. Hanya jalur mutasi yang
 * punya surat; tiga jalur lain tetap wajib tercatat agar tidak ada data yang
 * "muncul begitu saja" tanpa asal-usul.
 */
class StudentEnrollmentService
{
    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_IMPORT = 'import';

    public const SOURCE_MUTATION = 'mutation';

    public const SOURCE_GUARDIAN = 'guardian_mobile';

    public const EXIT_MUTATION = 'mutation';

    public const EXIT_GRADUATION = 'graduation';

    public const EXIT_DROPOUT = 'dropout';

    /**
     * Catat jalur masuk + tulis audit.
     *
     * @param  array<string, mixed>  $context
     */
    public function recordEntry(Student $student, string $source, ?string $entryDate = null, ?string $actorId = null, array $context = []): void
    {
        $entryDate = $entryDate ?: now()->toDateString();

        $this->markEntry($student, $source, $entryDate);
        $this->clearExit($student);

        RecordLifecycleAuditJob::dispatch([
            'event' => 'student.enrolled',
            'student_id' => $student->id,
            'school_id' => $student->school_id,
            'actor_id' => $actorId ?? auth()->id(),
            'payload' => array_merge([
                'source' => $source,
                'entry_date' => $entryDate,
            ], $context),
            'occurred_at' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Tandai jalur masuk tanpa menulis audit tambahan (dipakai bila event
     * lifecycle sudah punya audit sendiri, mis. mutasi masuk).
     */
    public function markEntry(Student $student, string $source, ?string $entryDate = null): void
    {
        $student->forceFill([
            'entry_source' => $source,
            'entry_date' => $student->entry_date ?: ($entryDate ?: now()->toDateString()),
            'exit_reason' => null,
            'exit_date' => null,
        ])->save();
    }

    /**
     * Catat alasan & tanggal keluar.
     */
    public function markExit(Student $student, string $reason, ?string $exitDate = null): void
    {
        $student->forceFill([
            'exit_reason' => $reason,
            'exit_date' => $exitDate ?: now()->toDateString(),
        ])->save();
    }

    /**
     * Hapus jejak keluar saat santri kembali aktif.
     */
    public function clearExit(Student $student): void
    {
        if ($student->exit_reason === null && $student->exit_date === null) {
            return;
        }

        $student->forceFill([
            'exit_reason' => null,
            'exit_date' => null,
        ])->save();
    }
}
