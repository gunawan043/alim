<?php

namespace App\Services\Evaluasi;

use App\Models\AuditLog;
use App\Models\PaketSoal;
use App\Models\PerangkatPembelajaran;
use App\Models\ReviewAssignment;
use App\Models\Soal;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Services\SubjectGroupResolver;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Workflow review generik untuk Soal & Paket Soal.
 *
 * Reviewer adalah guru serumpun LINTAS SATUAN PENDIDIKAN (satu mapel/jenjang/TA),
 * bukan dibatasi sekolah yang sama. Semua reviewer yang ditetapkan harus
 * menyetujui sebelum status APPROVED; satu permintaan perbaikan menjadikan
 * dokumen PERLU PERBAIKAN dan approval lama tidak berlaku untuk versi baru.
 */
class ReviewWorkflowService
{
    public const MAX_AUTO_REVIEWERS = 4;

    /**
     * Ajukan dokumen untuk review + tugaskan reviewer serumpun.
     *
     * @param  array<int, string>|null  $reviewerIds
     * @return Collection<int, ReviewAssignment>
     */
    public function submit(Model $reviewable, User $submitter, ?array $reviewerIds = null): Collection
    {
        $reviewerIds = $reviewerIds ?: $this->resolveSerumpunReviewers($reviewable, $submitter);

        return DB::transaction(function () use ($reviewable, $reviewerIds, $submitter) {
            $reviewable->reviewAssignments()->delete();

            foreach (array_values(array_unique($reviewerIds)) as $reviewerId) {
                ReviewAssignment::create([
                    'reviewable_type' => $reviewable->getMorphClass(),
                    'reviewable_id' => $reviewable->getKey(),
                    'reviewer_id' => $reviewerId,
                    'status' => ReviewAssignment::STATUS_PENDING,
                ]);
            }

            $this->applyWorkflow($reviewable, Soal::WORKFLOW_REVIEW);
            $this->audit($reviewable, 'submit_review', $submitter, ['reviewers' => count($reviewerIds)]);

            return $reviewable->reviewAssignments()->with('reviewer:id,name')->get();
        });
    }

    /**
     * Tetapkan reviewer secara manual (mis. oleh kurikulum).
     *
     * @param  array<int, string>  $reviewerIds
     * @return Collection<int, ReviewAssignment>
     */
    public function assignReviewers(Model $reviewable, array $reviewerIds, User $by): Collection
    {
        return DB::transaction(function () use ($reviewable, $reviewerIds, $by) {
            foreach (array_values(array_unique($reviewerIds)) as $reviewerId) {
                ReviewAssignment::updateOrCreate(
                    [
                        'reviewable_type' => $reviewable->getMorphClass(),
                        'reviewable_id' => $reviewable->getKey(),
                        'reviewer_id' => $reviewerId,
                    ],
                    ['status' => ReviewAssignment::STATUS_PENDING, 'note' => null, 'decided_at' => null]
                );
            }

            $this->recalculate($reviewable);
            $this->audit($reviewable, 'assign_reviewers', $by, ['reviewers' => count($reviewerIds)]);

            return $reviewable->reviewAssignments()->with('reviewer:id,name')->get();
        });
    }

    /**
     * Keputusan reviewer: approve / minta perbaikan.
     */
    public function decide(Model $reviewable, string $assignmentId, User $reviewer, string $status, ?string $note = null): ReviewAssignment
    {
        $assignment = ReviewAssignment::query()
            ->where('reviewable_type', $reviewable->getMorphClass())
            ->where('reviewable_id', $reviewable->getKey())
            ->findOrFail($assignmentId);

        if ($assignment->reviewer_id !== $reviewer->id) {
            abort(403, 'Hanya reviewer yang ditetapkan yang dapat memberi keputusan.');
        }

        if (! in_array($status, [ReviewAssignment::STATUS_APPROVED, ReviewAssignment::STATUS_REVISION], true)) {
            abort(422, 'Status review tidak valid.');
        }

        DB::transaction(function () use ($assignment, $status, $note, $reviewable, $reviewer) {
            $assignment->update([
                'status' => $status,
                'note' => $note,
                'decided_at' => now(),
            ]);

            $this->recalculate($reviewable);

            $this->audit($reviewable, $status === ReviewAssignment::STATUS_APPROVED ? 'review_approved' : 'review_revision', $reviewer, [
                'assignment_id' => $assignment->id,
                'note' => $note,
            ]);
        });

        return $assignment->fresh();
    }

    /**
     * Hitung ulang status workflow dokumen dari keputusan seluruh reviewer:
     *  - ada revision  → PERLU PERBAIKAN
     *  - semua approved → APPROVED (dengan approved_by/at)
     *  - selain itu    → REVIEW
     */
    public function recalculate(Model $reviewable): void
    {
        $assignments = $reviewable->reviewAssignments()->get();

        if ($assignments->contains(fn (ReviewAssignment $a) => $a->status === ReviewAssignment::STATUS_REVISION)) {
            $this->applyWorkflow($reviewable, 'revisi');

            return;
        }

        if ($assignments->isNotEmpty()
            && $assignments->every(fn (ReviewAssignment $a) => $a->status === ReviewAssignment::STATUS_APPROVED)) {
            $approver = $assignments->sortByDesc('decided_at')->first()?->reviewer_id;
            $this->applyWorkflow($reviewable, Soal::WORKFLOW_APPROVED, $approver);

            return;
        }

        $this->applyWorkflow($reviewable, Soal::WORKFLOW_REVIEW);
    }

    /**
     * Ringkasan progres review.
     *
     * @return array{pending: int, approved: int, revision: int, total: int}
     */
    public function progress(Model $reviewable): array
    {
        $assignments = $reviewable->reviewAssignments()->get();

        return [
            'pending' => $assignments->where('status', ReviewAssignment::STATUS_PENDING)->count(),
            'approved' => $assignments->where('status', ReviewAssignment::STATUS_APPROVED)->count(),
            'revision' => $assignments->where('status', ReviewAssignment::STATUS_REVISION)->count(),
            'total' => $assignments->count(),
        ];
    }

    /**
     * Reviewer serumpun lintas satuan pendidikan.
     *
     * @return array<int, string>
     */
    public function resolveSerumpunReviewers(Model $reviewable, User $submitter, ?int $limit = null): array
    {
        $limit = $limit ?: self::MAX_AUTO_REVIEWERS;
        $context = $this->academicContext($reviewable);

        if (! $context['subject_id']) {
            return [];
        }

        // Mapel serumpun lintas satuan dapat memakai baris subject berbeda
        // (master mapel per sekolah) → cocokkan id + nama mapel.
        $subject = Subject::find($context['subject_id']);
        $subjectIds = $subject
            ? Subject::query()
                ->where('id', $subject->id)
                ->orWhere('name', $subject->name)
                ->pluck('id')
                ->all()
            : [$context['subject_id']];

        $base = TeachingAssignment::query()
            ->whereIn('subject_id', $subjectIds)
            ->where('status', 'active')
            ->when($context['academic_year_id'], fn ($q) => $q->where('academic_year_id', $context['academic_year_id']))
            ->when($context['grade_level_id'], fn ($q) => $q->whereHas('studyGroup', fn ($q2) => $q2->where('grade_level_id', $context['grade_level_id'])));

        $subjectTeacherIds = $this->extractTeacherIds($base, $submitter, $limit);

        // Kebijakan Tahap 1: koordinator rumpun adalah reviewer utama
        // (ditetapkan lewat tugas tambahan), bukan approver final otomatis.
        $resolver = app(SubjectGroupResolver::class);
        $group = $subject ? $resolver->groupForSubject($subject) : null;

        $coordinatorIds = [];
        if ($group) {
            // Koordinator rumpun per SATUAN PENDIDIKAN (tugas tambahan).
            // Bukan koordinator lintas satuan; kolaborasi lintas satuan
            // tetap via guru mapel yang sama.
            $coordinatorIds = collect($resolver->coordinatorUserIdsForGroup($group, $context['school_id'] ?? null))
                ->map(fn ($id) => (string) $id)
                ->reject(fn ($id) => $id === (string) $submitter->id)
                ->unique()
                ->sort()
                ->values()
                ->all();
        }

        $ids = collect($coordinatorIds)
            ->merge($subjectTeacherIds)
            ->unique()
            ->take($limit)
            ->values()
            ->all();

        // Fallback 1: mapel sama lintas tahun ajaran (tetap lintas satuan pendidikan).
        if ($ids === []) {
            $ids = $this->extractTeacherIds(
                TeachingAssignment::query()
                    ->whereIn('subject_id', $subjectIds)
                    ->where('status', 'active'),
                $submitter,
                $limit
            );
        }

        // Fallback 2: guru lain dalam rumpun yang sama (mapel berbeda satu rumpun).
        if ($ids === [] && $group) {
            $ids = collect($resolver->teacherIdsForGroup($group))
                ->map(fn ($id) => (string) $id)
                ->reject(fn ($id) => $id === (string) $submitter->id)
                ->unique()
                ->sort()
                ->take($limit)
                ->values()
                ->all();
        }

        return $ids;
    }

    /**
     * Approver final KEBIJAKAN = SELURUH anggota rumpun (keputusan kolektif).
     *
     * Approval paket/RPM bukan milik koordinator tunggal: semua anggota rumpun
     * yang ditetapkan harus menyetujui sebelum dokumen dinyatakan APPROVED.
     * Koordinator satuan asal tetap didahulukan sebagai pemimpin kolektif.
     * Pengaju/penulis dikecualikan; bila rumpun belum beranggota, fallback ke
     * penelaah serumpun agar alur tidak buntu.
     *
     * @return array<int, string>
     */
    public function resolveRumpunApprovers(Model $reviewable, User $submitter, ?int $limit = null): array
    {
        $context = $this->academicContext($reviewable);
        $resolver = app(SubjectGroupResolver::class);
        $group = $context['subject_id'] ? $resolver->groupForSubject($context['subject_id']) : null;

        if (! $group) {
            return $this->resolveSerumpunReviewers($reviewable, $submitter, $limit);
        }

        $members = collect($resolver->teacherIdsForGroup($group));

        if ($members->isEmpty()) {
            return $this->resolveSerumpunReviewers($reviewable, $submitter, $limit);
        }

        $coordinators = collect($resolver->coordinatorUserIdsForGroup($group, $context['school_id'] ?? null));

        $ordered = $coordinators
            ->merge($members)
            ->map(fn ($id) => (string) $id)
            ->reject(fn ($id) => $id === (string) $submitter->id)
            ->unique()
            ->values();

        return $limit ? $ordered->take($limit)->values()->all() : $ordered->all();
    }

    /**
     * @return array{subject_id: ?string, grade_level_id: ?string, academic_year_id: ?string, semester: ?string}
     */
    public function academicContext(Model $reviewable): array
    {
        if ($reviewable instanceof Soal) {
            $bank = $reviewable->bankSoal;

            return [
                'subject_id' => $bank?->subject_id,
                'grade_level_id' => $bank?->grade_level_id,
                'academic_year_id' => $bank?->academic_year_id,
                'semester' => $bank?->semester,
                'school_id' => $bank?->school_id,
            ];
        }

        if ($reviewable instanceof PaketSoal) {
            $kisi = $reviewable->kisiKisi;

            return [
                'subject_id' => $kisi?->subject_id,
                'grade_level_id' => $kisi?->grade_level_id,
                'academic_year_id' => $kisi?->academic_year_id,
                'semester' => $kisi?->semester,
                'school_id' => $kisi?->school_id,
            ];
        }

        if ($reviewable instanceof PerangkatPembelajaran) {
            return [
                'subject_id' => $reviewable->subject_id,
                'grade_level_id' => $reviewable->grade_level_id,
                'academic_year_id' => $reviewable->academic_year_id,
                'semester' => $reviewable->semester,
                'school_id' => $reviewable->school_id,
            ];
        }

        return ['subject_id' => null, 'grade_level_id' => null, 'academic_year_id' => null, 'semester' => null, 'school_id' => null];
    }

    /**
     * @return array<int, string>
     */
    private function extractTeacherIds($query, User $submitter, int $limit): array
    {
        return $query
            ->where('teacher_id', '<>', $submitter->id)
            ->orderBy('teacher_id')
            ->pluck('teacher_id')
            ->unique()
            ->take($limit)
            ->values()
            ->all();
    }

    private function applyWorkflow(Model $reviewable, string $workflow, ?string $approvedBy = null): void
    {
        if ($reviewable instanceof Soal) {
            $reviewable->syncWorkflowStatus($workflow);
            if ($workflow === Soal::WORKFLOW_APPROVED && $approvedBy) {
                $reviewable->forceFill(['approved_by' => $approvedBy])->save();
            }

            return;
        }

        if ($reviewable instanceof PaketSoal) {
            $map = [
                Soal::WORKFLOW_APPROVED => PaketSoal::WORKFLOW_APPROVED,
                Soal::WORKFLOW_REVISI => PaketSoal::WORKFLOW_REVISI,
                Soal::WORKFLOW_REVIEW => PaketSoal::WORKFLOW_REVIEW,
                Soal::WORKFLOW_DRAFT => PaketSoal::WORKFLOW_DRAFT,
            ];

            $reviewable->forceFill([
                'workflow_status' => $map[$workflow] ?? $workflow,
                'approved_at' => $workflow === Soal::WORKFLOW_APPROVED ? ($reviewable->approved_at ?: now()) : null,
                'approved_by' => $workflow === Soal::WORKFLOW_APPROVED ? ($approvedBy ?: $reviewable->approved_by) : null,
            ])->save();

            return;
        }

        // Tahap 2: RPM — approval kolektif anggota rumpun; final dikunci.
        if ($reviewable instanceof PerangkatPembelajaran) {
            $map = [
                Soal::WORKFLOW_APPROVED => PerangkatPembelajaran::WORKFLOW_APPROVED,
                Soal::WORKFLOW_REVISI => PerangkatPembelajaran::WORKFLOW_REVISI,
                Soal::WORKFLOW_REVIEW => PerangkatPembelajaran::WORKFLOW_REVIEW,
                Soal::WORKFLOW_DRAFT => PerangkatPembelajaran::WORKFLOW_DRAFT,
            ];

            $payload = ['workflow_status' => $map[$workflow] ?? $workflow];

            if ($workflow === Soal::WORKFLOW_REVIEW) {
                $payload['submitted_at'] = $reviewable->submitted_at ?: now();
            }

            if ($workflow === Soal::WORKFLOW_REVISI) {
                $payload['approved_at'] = null;
                $payload['approved_by'] = null;
            }

            if ($workflow === Soal::WORKFLOW_APPROVED) {
                $payload['approved_at'] = $reviewable->approved_at ?: now();
                $payload['approved_by'] = $approvedBy ?: $reviewable->approved_by;
                $payload['status'] = PerangkatPembelajaran::STATUS_FINAL;
                $payload['locked_at'] = $reviewable->locked_at ?: now();
                $payload['locked_by'] = $approvedBy ?: $reviewable->locked_by;
            }

            $reviewable->forceFill($payload)->save();

            return;
        }

        // Model lain: fallback kolom workflow_status bila tersedia.
        if (array_key_exists('workflow_status', $reviewable->getAttributes())) {
            $reviewable->forceFill(['workflow_status' => $workflow])->save();
        } elseif (method_exists($reviewable, 'syncWorkflowStatus')) {
            $reviewable->syncWorkflowStatus($workflow);
        }
    }

    /**
     * Audit trail aktivitas penting bank soal.
     *
     * @param  array<string, mixed>  $meta
     */
    public function audit(Model $reviewable, string $action, ?User $user = null, array $meta = []): void
    {
        try {
            AuditLog::create([
                'user_id' => $user?->id,
                'action' => $action,
                'table_name' => $reviewable->getTable(),
                'record_id' => $reviewable->getKey(),
                'record_type' => $reviewable->getMorphClass(),
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 255),
            ]);
        } catch (\Throwable) {
            // Audit tidak boleh memblokir alur akademik.
        }
    }
}
