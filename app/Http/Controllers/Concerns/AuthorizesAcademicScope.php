<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Models\StudyGroup;
use App\Models\TeacherAdminBook;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\KurikulumAccess;
use Illuminate\Http\Request;

/**
 * Guard scope akademik berbasis penugasan (Tahap 1/2 kebijakan):
 * - Batas satuan pendidikan (school context).
 * - Wali kelas hanya untuk kelasnya; petugas berwenang lintas kelas.
 * - Buku nilai: guru pengampu / co-teacher pada mapel+rombel+TA yang sama.
 */
trait AuthorizesAcademicScope
{
    protected function contextSchoolId(Request $request): ?string
    {
        return $request->attributes->get('schoolContextId');
    }

    protected function ensureSchoolScope(Request $request, ?string $schoolId, string $message = 'Data tidak ditemukan pada satuan pendidikan Anda.'): void
    {
        $context = $this->contextSchoolId($request);

        if ($context && $schoolId && (string) $schoolId !== (string) $context) {
            abort(404, $message);
        }
    }

    /**
     * Petugas yang boleh lintas kelas: Kurikulum/Kepala/Wakil, TU, Kesiswaan.
     */
    protected function isCrossClassOfficer(User $user): bool
    {
        $access = app(KurikulumAccess::class);

        if ($access->isKurikulumTeam($user) || $access->isTataUsaha($user)) {
            return true;
        }

        $jabatan = mb_strtolower((string) ($user->employment?->jabatan ?? ''));

        return str_contains($jabatan, 'kesiswaan');
    }

    /**
     * Scope rombel: semua pegawai boleh melihat dalam satuan pendidikan;
     * perubahan hanya wali kelas atau petugas berwenang.
     */
    protected function authorizeStudyGroupScope(Request $request, StudyGroup $studyGroup, bool $write = false): void
    {
        $this->ensureSchoolScope($request, $studyGroup->school_id);

        if (! $write) {
            return;
        }

        $user = $request->user();

        if ($this->isCrossClassOfficer($user)) {
            return;
        }

        if ((string) $studyGroup->homeroom_teacher_id !== (string) $user->id) {
            abort(403, 'Hanya wali kelas atau petugas berwenang yang dapat mengubah data kelas ini.');
        }
    }

    /**
     * Scope buku nilai: satuan pendidikan + guru pengampu (termasuk co-teacher).
     */
    protected function authorizeAdminBookScope(Request $request, TeacherAdminBook $book): void
    {
        $this->ensureSchoolScope($request, $book->school_id, 'Buku nilai tidak ditemukan pada satuan pendidikan Anda.');

        $user = $request->user();

        if ($this->isCrossClassOfficer($user)) {
            return;
        }

        if ((string) $book->teacher_id === (string) $user->id) {
            return;
        }

        $coTeacher = TeachingAssignment::query()
            ->where('teacher_id', $user->id)
            ->where('subject_id', $book->subject_id)
            ->where('study_group_id', $book->study_group_id)
            ->where('academic_year_id', $book->academic_year_id)
            ->where('status', 'active')
            ->exists();

        if (! $coTeacher) {
            abort(403, 'Anda tidak berwenang mengelola nilai pada buku ini.');
        }
    }
}
