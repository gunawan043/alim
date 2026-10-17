<?php

namespace App\Observers;

use App\Events\SubjectAssignedToStudyGroup;
use App\Events\TeachingAssignmentChanged;
use App\Models\StudyGroup;
use App\Models\TeacherAdminBook;
use App\Models\TeachingAssignment;
use App\Services\NotificationUniversalService;
use Illuminate\Support\Facades\Log;

class TeachingAssignmentObserver
{
    /** Mode senyap: tidak mengirim notifikasi (dipakai seeding penugasan massal). */
    public static bool $silent = false;

    /** Aktifkan provisioning buku nilai dari observer (dimatikan saat seeding massal). */
    public static bool $provision = true;

    public function created(TeachingAssignment $assignment): void
    {
        TeachingAssignmentChanged::dispatch(
            $assignment,
            'created',
            $assignment->school_id,
            $assignment->academic_year_id,
            $assignment->teacher_id,
            $assignment->subject_id,
            $assignment->study_group_id,
        );

        if (self::$provision) {
            $this->dispatchSubjectAssigned($assignment, 'created');
        }

        $this->syncBookOwnership($assignment);
        $this->notifyTeacher($assignment, 'created');
    }

    public function updated(TeachingAssignment $assignment): void
    {
        TeachingAssignmentChanged::dispatch(
            $assignment,
            'updated',
            $assignment->school_id,
            $assignment->academic_year_id,
            $assignment->teacher_id,
            $assignment->subject_id,
            $assignment->study_group_id,
        );

        if (self::$provision) {
            $this->dispatchSubjectAssigned($assignment, 'updated');
        }

        // Ganti guru di tengah tahun: buku nilai BERPINDAH kepemilikan (data nilai tidak direset).
        $this->syncBookOwnership($assignment);

        if ($assignment->wasChanged(['teacher_id', 'subject_id', 'study_group_id', 'weekly_hours', 'status'])) {
            $this->notifyTeacher($assignment, 'updated');
        }
    }

    public function deleted(TeachingAssignment $assignment): void
    {
        // Kirim null — SerializesModels tidak dapat fetch ulang model yang sudah terhapus.
        TeachingAssignmentChanged::dispatch(
            null,
            'deleted',
            $assignment->school_id,
            $assignment->academic_year_id,
            $assignment->teacher_id,
            $assignment->subject_id,
            $assignment->study_group_id,
        );

        if (self::$provision) {
            $this->dispatchSubjectAssigned($assignment, 'deleted');
        }

        $this->transferBooksAfterRemoval($assignment);
    }

    /**
     * Pastikan buku nilai dimiliki guru yang masih aktif pada konteks ini.
     * Buku TIDAK pernah direset — hanya teacher_id/teaching_id yang berpindah.
     */
    private function syncBookOwnership(TeachingAssignment $assignment): void
    {
        if (! $assignment->subject_id || ! $assignment->study_group_id || ! $assignment->academic_year_id) {
            return;
        }

        $books = TeacherAdminBook::query()
            ->where('subject_id', $assignment->subject_id)
            ->where('study_group_id', $assignment->study_group_id)
            ->where('academic_year_id', $assignment->academic_year_id)
            ->get();

        foreach ($books as $book) {
            // Pemilik saat ini masih mengajar konteks ini (mis. multi-guru/tahfidz)? biarkan.
            $currentStillActive = TeachingAssignment::query()
                ->where('teacher_id', $book->teacher_id)
                ->where('subject_id', $assignment->subject_id)
                ->where('study_group_id', $assignment->study_group_id)
                ->where('academic_year_id', $assignment->academic_year_id)
                ->where('status', 'active')
                ->exists();

            if ($currentStillActive) {
                continue;
            }

            if ((string) $book->teacher_id !== (string) $assignment->teacher_id) {
                // Catatan: kolom teaching_id punya FK legacy ke tabel `teachings`
                // (bukan teaching_assignments) — jangan diisi dengan id penugasan.
                $book->forceFill([
                    'teacher_id' => $assignment->teacher_id,
                ])->save();
            }
        }
    }

    /**
     * Saat penugasan dihapus/dinonaktifkan: pindahkan buku ke guru pengganti
     * yang masih aktif pada konteks yang sama.
     */
    private function transferBooksAfterRemoval(TeachingAssignment $assignment): void
    {
        if (! $assignment->subject_id || ! $assignment->study_group_id || ! $assignment->academic_year_id) {
            return;
        }

        $replacement = TeachingAssignment::query()
            ->where('subject_id', $assignment->subject_id)
            ->where('study_group_id', $assignment->study_group_id)
            ->where('academic_year_id', $assignment->academic_year_id)
            ->where('status', 'active')
            ->first();

        if (! $replacement) {
            return;
        }

        TeacherAdminBook::query()
            ->where('subject_id', $assignment->subject_id)
            ->where('study_group_id', $assignment->study_group_id)
            ->where('academic_year_id', $assignment->academic_year_id)
            ->where('teacher_id', $assignment->teacher_id)
            ->get()
            ->each(fn ($book) => $book->forceFill([
                'teacher_id' => $replacement->teacher_id,
            ])->save());
    }

    /**
     * Notifikasi individu ke guru saat penugasan mengajar dibuat/diubah.
     */
    private function notifyTeacher(TeachingAssignment $assignment, string $event): void
    {
        try {
            if (self::$silent) {
                return;
            }

            if (! $assignment->teacher_id || auth()->id() === $assignment->teacher_id) {
                return;
            }

            $subject = $assignment->subject?->name ?? 'Mata pelajaran';
            $group = $assignment->studyGroup?->name ?? 'rombel';
            $ay = $assignment->academicYear?->name;

            app(NotificationUniversalService::class)->send($assignment->teacher_id, [
                'module' => 'kurikulum',
                'reference_type' => TeachingAssignment::class,
                'reference_id' => $assignment->id,
                'type' => 'info',
                'action' => 'teaching_assignment_'.$event,
                'priority' => 'medium',
                'title' => $event === 'created' ? 'Penugasan Mengajar Baru' : 'Penugasan Mengajar Diperbarui',
                'message' => "Anda mengajar {$subject} di {$group}".($ay ? " ({$ay})" : '')." — {$assignment->weekly_hours} JP/pekan.",
                'work_unit_id' => $assignment->school?->work_unit_id,
                'action_url' => route('user.schools.guru-mapel.index', ['userId' => $assignment->teacher_id]),
                'action_text' => 'Buka Buku Nilai',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Notif penugasan mengajar gagal: '.$e->getMessage());
        }
    }

    /**
     * Mirror the StudyGroupSubjectObserver cascade: dispatch
     * SubjectAssignedToStudyGroup so the nilai provisioning job runs.
     */
    private function dispatchSubjectAssigned(TeachingAssignment $assignment, string $changeType): void
    {
        SubjectAssignedToStudyGroup::dispatch(
            $assignment->id,
            $assignment->study_group_id,
            $assignment->subject_id,
            $assignment->teacher_id,
            $assignment->school_id,
            $assignment->academic_year_id,
            $this->resolveGradeLevelId($assignment),
            $changeType,
        );
    }

    private function resolveGradeLevelId(TeachingAssignment $assignment): ?string
    {
        if (! $assignment->study_group_id) {
            return null;
        }

        $sg = StudyGroup::find($assignment->study_group_id);

        return $sg?->grade_level_id;
    }
}
