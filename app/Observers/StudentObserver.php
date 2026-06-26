<?php

namespace App\Observers;

use App\Models\Student;
use App\Models\WaliSantri;
use App\Services\NotificationUniversalService;
use Illuminate\Support\Facades\Log;

class StudentObserver
{
    public function __construct(
        private readonly NotificationUniversalService $notifier,
    ) {}

    /**
     * Safety net: if any code path bypasses the event system and writes
     * Student.status directly, the observer logs a warning so regressions
     * are visible in CI / log greps.
     */
    public function updating(Student $student): void
    {
        if (! $student->isDirty('status')) {
            return;
        }

        $from = $student->getOriginal('status');
        $to = $student->status;
        if ($from === $to) {
            return;
        }

        $states = ['active', 'inactive', 'graduate', 'dropped', 'transfer_in', 'transfer_out'];
        if (! in_array($to, $states, true)) {
            return;
        }

        Log::warning('Student.status changed outside event bus', [
            'student_id' => $student->id,
            'from' => $from,
            'to' => $to,
        ]);
    }

    /**
     * When a student's name or guardian info changes, sync to related Walisantri records.
     * This prevents stale display names in the wali app.
     */
    public function updated(Student $student): void
    {
        if (empty($student->getDirty())) {
            return;
        }

        // Name change → cascade to Walisantri
        $nameChanged = in_array('name', $student->getDirty());
        if ($nameChanged) {
            $this->syncStudentNameToWalisantri($student);
        }

        // Guardian contact changed → notify wali
        $contactFields = ['guardian_name', 'guardian_phone', 'guardian_email'];
        $guardianChanged = array_intersect_key($student->getDirty(), array_flip($contactFields));
        if ($guardianChanged) {
            $this->notifyGuardianChange($student, array_keys($guardianChanged));
        }
    }

    /**
     * Sync student's updated name into Walisantri display context.
     * Since Walisantri doesn't store the student name directly, the
     * relationship (student().name) resolves at query time, so the
     * walisantri views already show the current name without migration.
     *
     * However, if there's any cached display, admins can refresh via
     * the walisantri show page which re-fetches the relationship.
     */
    private function syncStudentNameToWalisantri(Student $student): void
    {
        // Walisantri.belongsTo(student) → name resolves dynamically.
        // No migration needed. Views that show wali_app.blade.php already
        // reference $wali->student->name which reflects the update.
    }

    private function notifyGuardianChange(Student $student, array $fields): void
    {
        $walis = WaliSantri::where('student_id', $student->id)
            ->where('status', WaliSantri::STATUS_ACTIVE)
            ->with('user')
            ->get();

        $fieldLabels = [
            'guardian_name' => 'nama wali',
            'guardian_phone' => 'nomor telepon wali',
            'guardian_email' => 'email wali',
        ];

        foreach ($walis as $wali) {
            if (! $wali->user) {
                continue;
            }

            $changedDescriptions = array_map(fn ($f) => $fieldLabels[$f] ?? $f, $fields);

            try {
                $this->notifier->send(
                    recipient: $wali->user,
                    title: 'Data Wali Santri Diperbarui',
                    message: 'Data '.implode(', ', $changedDescriptions)." atas nama {$student->name} telah diperbarui oleh administrator.",
                    category: 'wali_santri',
                    reference: $wali->id,
                    referenceType: 'wali_santri',
                    schoolId: $wali->user->school_id,
                );
            } catch (\Throwable $e) {
                Log::warning('StudentObserver: gagal kirim notifikasi perubahan wali', [
                    'student_id' => $student->id,
                    'walisantri_id' => $wali->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
