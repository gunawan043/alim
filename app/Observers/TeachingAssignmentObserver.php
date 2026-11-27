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
     * Buku TIDAK pernah direset — hanya teacher_id yang berpindah.
     *
     * Hanya buku milik guru yang SEDANG DIGANTI yang ditangani. Buku milik
     * guru lain pada konteks yang sama (mis. co-teacher atau buku yang sudah
     * dibuat lebih dulu untuk guru pengganti) tidak boleh ikut dipindah:
     * memindahkannya membuat dua buku dengan guru + konteks yang sama, yang
     * melanggar unique_admin_book_per_teacher.
     */
    private function syncBookOwnership(TeachingAssignment $assignment): void
    {
        if (! $assignment->subject_id || ! $assignment->study_group_id || ! $assignment->academic_year_id) {
            return;
        }

        $outgoingTeacherId = $assignment->getOriginal('teacher_id') ?: $assignment->teacher_id;
        $incomingTeacherId = $assignment->teacher_id;

        // Tidak ada pergantian guru — kepemilikan buku tidak perlu disentuh.
        if ((string) $outgoingTeacherId === (string) $incomingTeacherId) {
            return;
        }

        // Guru lama ternyata masih mengajar konteks ini (multi-guru/tahfidz): biarkan.
        $outgoingStillActive = TeachingAssignment::query()
            ->where('teacher_id', $outgoingTeacherId)
            ->where('subject_id', $assignment->subject_id)
            ->where('study_group_id', $assignment->study_group_id)
            ->where('academic_year_id', $assignment->academic_year_id)
            ->where('status', 'active')
            ->exists();

        if ($outgoingStillActive) {
            return;
        }

        $books = TeacherAdminBook::query()
            ->where('teacher_id', $outgoingTeacherId)
            ->where('subject_id', $assignment->subject_id)
            ->where('study_group_id', $assignment->study_group_id)
            ->where('academic_year_id', $assignment->academic_year_id)
            ->get();

        foreach ($books as $book) {
            // Guru pengganti sudah punya buku pada konteks ini. Buku lama tidak
            // boleh ikut dipindah (melanggar aturan unik). Cukup nonaktifkan;
            // nilai lama tetap tersimpan pada buku lama.
            if ($this->hasBookFor($incomingTeacherId, $book, $book->id)) {
                if ($book->is_active) {
                    $book->forceFill(['is_active' => false])->save();
                }

                continue;
            }

            // Catatan: kolom teaching_id punya FK legacy ke tabel `teachings`
            // (bukan teaching_assignments) — jangan diisi dengan id penugasan.
            $book->forceFill([
                'teacher_id' => $incomingTeacherId,
            ])->save();
        }
    }

    /**
     * Apakah guru ini sudah memiliki buku lain (selain $except) pada konteks
     * buku yang diberikan (mapel + rombel + TA + semester).
     */
    private function hasBookFor(string $teacherId, TeacherAdminBook $book, ?string $exceptId = null): bool
    {
        return TeacherAdminBook::query()
            ->where('teacher_id', $teacherId)
            ->where('subject_id', $book->subject_id)
            ->where('study_group_id', $book->study_group_id)
            ->where('academic_year_id', $book->academic_year_id)
            ->where('semester', $book->semester)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->exists();
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
            ->where('teacher_id', '!=', $assignment->teacher_id)
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
            ->each(function (TeacherAdminBook $book) use ($replacement) {
                // Sama seperti syncBookOwnership: jangan tabrak aturan unik.
                if ($this->hasBookFor($replacement->teacher_id, $book, $book->id)) {
                    if ($book->is_active) {
                        $book->forceFill(['is_active' => false])->save();
                    }

                    return;
                }

                $book->forceFill(['teacher_id' => $replacement->teacher_id])->save();
            });
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
