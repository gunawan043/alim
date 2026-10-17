<?php

declare(strict_types=1);

namespace App\Services\Ljk;

use App\Models\LjkExam;
use App\Models\TeacherAdminBook;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\KurikulumAccess;

/**
 * Otorisasi modul LJK — berbasis penugasan & konteks (bukan Gate global).
 *
 * - Petugas lintas kelas (kurikulum/TU/kesiswaan/admin sistem) dapat
 *   melihat & memverifikasi.
 * - Guru pengampu (pemilik buku nilai atau co-teacher pada mapel+rombel+TA
 *   yang sama) dapat mengelola, memindai, dan menyinkronkan ujiannya.
 */
class LjkAccess
{
    public function __construct(private readonly KurikulumAccess $kurikulum) {}

    public function isOfficer(User $user): bool
    {
        if ($this->kurikulum->isKurikulumTeam($user) || $this->kurikulum->isTataUsaha($user)) {
            return true;
        }

        $jabatan = mb_strtolower((string) ($user->employment?->jabatan ?? ''));

        if (str_contains($jabatan, 'kesiswaan')) {
            return true;
        }

        try {
            return canUserPermission($user, 'super-admin-only');
        } catch (\Throwable) {
            return false;
        }
    }

    public function managesBook(?User $user, ?TeacherAdminBook $book): bool
    {
        if (! $user || ! $book) {
            return false;
        }

        if ((string) $book->teacher_id === (string) $user->id) {
            return true;
        }

        return TeachingAssignment::query()
            ->where('teacher_id', $user->id)
            ->where('subject_id', $book->subject_id)
            ->where('study_group_id', $book->study_group_id)
            ->where('academic_year_id', $book->academic_year_id)
            ->where('status', 'active')
            ->exists();
    }

    public function canView(User $user, LjkExam $exam): bool
    {
        if ($this->isOfficer($user)) {
            return true;
        }

        if ((string) $exam->created_by === (string) $user->id) {
            return true;
        }

        return $this->managesBook($user, $exam->adminBook ?? $exam->adminBook()->first());
    }

    public function canManage(User $user, LjkExam $exam): bool
    {
        if ($exam->isFinalized() && ! $this->isOfficer($user)) {
            return false;
        }

        return $this->canView($user, $exam);
    }

    public function canScan(User $user, LjkExam $exam): bool
    {
        return $exam->canScan() && $this->canView($user, $exam);
    }

    public function canReview(User $user, LjkExam $exam): bool
    {
        return in_array($exam->status, [
            LjkExam::STATUS_PENGUMPULAN,
            LjkExam::STATUS_REVIEW,
            LjkExam::STATUS_FINAL,
            LjkExam::STATUS_TERSINKRON,
        ], true) && $this->canView($user, $exam);
    }

    public function canSync(User $user, LjkExam $exam): bool
    {
        if (! $exam->admin_book_id) {
            return false;
        }

        if ($exam->status === LjkExam::STATUS_TERSINKRON && ! $this->isOfficer($user)) {
            return false;
        }

        return $this->canView($user, $exam);
    }
}
