<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\StudentClassHistory;
use App\Models\SubjectKktp;
use App\Models\TeacherAdminBook;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Service for provisioning academic structure when a Subject is
 * assigned (or updated/removed) to a StudyGroup.
 *
 * Cascade responsibilities (idempotent — safe to re-run):
 *
 *   1. teacher_admin_books   — upsert one row per (teacher, subject, study_group, academic_year, semester)
 *      This is the canonical anchor that the rest of the system queries
 *      (admin_nilai_sumatif, admin_presensi_mapel, raport aggregation).
 *
 *   2. subject_kktp context  — resolve the KKTP record that matches
 *      (school, grade_level, subject, academic_year, semester) and link it
 *      to the teacher_admin_book so grades can be auto-capped at KKTP.
 *
 *   3. admin_nilai_sumatif   — for every active student in the rombel,
 *      insert a placeholder row keyed by (admin_book, student, semester)
 *      so the raport aggregator has all the slots it needs.
 *
 *   4. admin_presensi_mapel  — no row created here (presensi is per-session),
 *      but the teacher_admin_book id is now ready to receive sessions.
 *
 *   5. raport registration   — confirmed via admin_book link; no separate
 *      per-subject row needed (raport is per-student-per-rombel).
 *
 * The provisioner is NOT a Model observer — it's invoked from a queued
 * Job (ProvisionStudyGroupSubjectAcademicStructureJob) so it runs off
 * the request lifecycle.
 */
class StudyGroupSubjectProvisioner
{
    protected string $studyGroupSubjectId;

    protected string $studyGroupId;

    protected string $subjectId;

    protected ?string $teacherId;

    protected ?string $schoolId;

    protected ?string $academicYearId;

    protected ?string $gradeLevelId;

    protected string $changeType;

    public function __construct(
        string $studyGroupSubjectId,
        string $studyGroupId,
        string $subjectId,
        ?string $teacherId,
        ?string $schoolId,
        ?string $academicYearId,
        ?string $gradeLevelId,
        string $changeType,
    ) {
        $this->studyGroupSubjectId = $studyGroupSubjectId;
        $this->studyGroupId = $studyGroupId;
        $this->subjectId = $subjectId;
        $this->teacherId = $teacherId;
        $this->schoolId = $schoolId;
        $this->academicYearId = $academicYearId;
        $this->gradeLevelId = $gradeLevelId;
        $this->changeType = $changeType;
    }

    /**
     * Run the full cascade. Returns counts of side effects.
     */
    public function provision(): array
    {
        $counts = [
            'admin_book' => 0,
            'kktp_linked' => false,
            'nilai_placeholders' => 0,
            'nilai_formatif_placeholders' => 0,
            'deleted' => 0,
        ];

        // For deletion, run teardown in a transaction.
        if ($this->changeType === 'deleted') {
            $counts['deleted'] = $this->teardown();

            return $counts;
        }

        if (! $this->schoolId || ! $this->academicYearId) {
            Log::warning('StudyGroupSubjectProvisioner: missing school or academic year, skipping', [
                'sgs_id' => $this->studyGroupSubjectId,
                'school_id' => $this->schoolId,
                'academic_year_id' => $this->academicYearId,
            ]);

            return $counts;
        }

        $semester = $this->resolveSemester();

        DB::beginTransaction();
        try {
            $book = $this->upsertAdminBook($semester);
            $counts['admin_book'] = $book ? 1 : 0;

            $kktp = $this->linkKktpContext($book, $semester);
            $counts['kktp_linked'] = $kktp !== null;

            if ($book) {
                [$sumatif, $formatif] = $this->createNilaiPlaceholders($book->id, $semester);
                $counts['nilai_placeholders'] = $sumatif;
                $counts['nilai_formatif_placeholders'] = $formatif;
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('StudyGroupSubjectProvisioner gagal', [
                'sgs_id' => $this->studyGroupSubjectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        return $counts;
    }

    // ── Step 1 — Upsert TeacherAdminBook ──────────────────────────

    private function upsertAdminBook(string $semester): ?TeacherAdminBook
    {
        if (! $this->teacherId) {
            // Cannot create admin_book without a teacher.
            // teacher_id is nullable on study_group_subjects, but
            // teacher_admin_books requires it (every subject-class needs
            // an instructor to record grades and attendance).
            Log::info('StudyGroupSubjectProvisioner: skip admin_book (no teacher assigned)', [
                'sgs_id' => $this->studyGroupSubjectId,
            ]);

            return null;
        }

        return TeacherAdminBook::updateOrCreate(
            [
                'teacher_id' => $this->teacherId,
                'subject_id' => $this->subjectId,
                'study_group_id' => $this->studyGroupId,
                'academic_year_id' => $this->academicYearId,
                'semester' => $semester,
            ],
            [
                'school_id' => $this->schoolId,
                'is_active' => true,
            ]
        );
    }

    // ── Step 2 — KKTP context link ────────────────────────────────

    private function linkKktpContext(?TeacherAdminBook $book, string $semester): ?SubjectKktp
    {
        if (! $book || ! $this->gradeLevelId) {
            return null;
        }

        $kktp = SubjectKktp::where('school_id', $this->schoolId)
            ->where('subject_id', $this->subjectId)
            ->where('grade_level_id', $this->gradeLevelId)
            ->where('academic_year_id', $this->academicYearId)
            ->where('semester', $semester)
            ->first();

        if ($kktp) {
            $book->kktp_id = $kktp->id;
            $book->save();
        }

        return $kktp;
    }

    // ── Step 3 — Per-student nilai placeholders ──────────────────

    /**
     * Create per-student placeholder rows in both admin_nilai_sumatif and
     * admin_nilai_formatif. Returns [sumatif_created, formatif_created].
     */
    private function createNilaiPlaceholders(string $adminBookId, string $semester): array
    {
        // Find every active student in this rombel for this academic year.
        $studentIds = StudentClassHistory::where('study_group_id', $this->studyGroupId)
            ->where('academic_year_id', $this->academicYearId)
            ->where('is_active', true)
            ->pluck('student_id');

        if ($studentIds->isEmpty()) {
            return [0, 0];
        }

        $now = now();
        $sumatifRows = [];
        $formatifRows = [];
        foreach ($studentIds as $studentId) {
            $sumatifRows[] = [
                'id' => (string) Str::uuid(),
                'admin_book_id' => $adminBookId,
                'student_id' => $studentId,
                'academic_year_id' => $this->academicYearId,
                'semester' => $semester,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $formatifRows[] = [
                'id' => (string) Str::uuid(),
                'admin_book_id' => $adminBookId,
                'student_id' => $studentId,
                'academic_year_id' => $this->academicYearId,
                'semester' => $semester,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Idempotent inserts — re-running does not duplicate.
        $sumatifCreated = DB::table('admin_nilai_sumatif')->insertOrIgnore($sumatifRows);
        $formatifCreated = DB::table('admin_nilai_formatif')->insertOrIgnore($formatifRows);

        return [$sumatifCreated, $formatifCreated];
    }

    // ── Teardown on delete ────────────────────────────────────────

    private function teardown(): int
    {
        // Soft-delete by deactivating the admin_book (don't hard-delete
        // because historical grades must remain).
        // Placeholder nilai_sumatif rows are preserved as historical records.
        return TeacherAdminBook::where('study_group_id', $this->studyGroupId)
            ->where('subject_id', $this->subjectId)
            ->where('academic_year_id', $this->academicYearId)
            ->update(['is_active' => false]);
    }

    // ── Helpers ──────────────────────────────────────────────────

    private function resolveSemester(): string
    {
        if (! $this->academicYearId) {
            return 'ganjil';
        }

        $ay = AcademicYear::find($this->academicYearId);

        return $ay?->semester ?? 'ganjil';
    }
}
