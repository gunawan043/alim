<?php

namespace App\Services;

use App\Models\AdminPresensiHarian;
use App\Models\ApprovalRequest;
use App\Models\AppFeedbackReport;
use App\Models\AcademicYear;
use App\Models\Kaldik;
use App\Models\NilaiSumatif;
use App\Models\PerangkatPembelajaran;
use App\Models\ReviewAssignment;
use App\Models\School;
use App\Models\StudentClassHistory;
use App\Models\StudentMutationOut;
use App\Models\StudyGroup;
use App\Models\TeacherAdminBook;
use App\Models\Todo;
use App\Models\User;
use App\Services\Evaluasi\ReviewWorkflowService;
use App\Support\UserJabatan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/**
 * Angka indikator sidebar: pekerjaan yang benar-benar menunggu pengguna.
 *
 * Prinsip:
 * - Tidak menampilkan badge untuk pekerjaan yang bukan kewenangan user.
 * - Mengikuti sekolah + tahun ajaran aktif.
 * - Absensi tidak dihitung pada hari libur / di luar tahun ajaran.
 * - Hasil di-cache sebentar agar sidebar tidak membebani setiap request.
 */
class SidebarIndicatorService
{
    public function forUser(?User $user, ?string $schoolId = null): array
    {
        if (! $user) {
            return $this->empty();
        }

        $schoolId = $schoolId ?: $this->resolveSchoolId($user);
        $activeYear = AcademicYear::where('is_active', true)->first();
        $cacheKey = implode(':', [
            'sidebar-indicators',
            $user->id,
            $schoolId ?: 'all',
            $activeYear?->id ?: 'no-ay',
            now()->toDateString(),
        ]);

        // Jangan cache di test agar data yang baru dibuat langsung terlihat.
        if (app()->environment('testing')) {
            return $this->compute($user, $schoolId, $activeYear);
        }

        return Cache::remember($cacheKey, now()->addMinute(), function () use ($user, $schoolId, $activeYear) {
            return $this->compute($user, $schoolId, $activeYear);
        });
    }

    private function compute(User $user, ?string $schoolId, ?AcademicYear $activeYear): array
    {
        $indicators = $this->empty();

        try {
            $indicators['notifications'] = app(NotificationUniversalService::class)->getUnreadCount($user->id);
        } catch (\Throwable) {
            $indicators['notifications'] = 0;
        }

        if (! $activeYear) {
            $indicators['todo_overdue'] = $this->todoOverdueCount($user);
            $indicators['feedback_baru'] = $this->feedbackBaruCount($user);
            $indicators['approvals'] = $this->approvalPendingCount($user);
            $indicators['review_pending'] = $this->reviewPendingCount($user);

            return $indicators;
        }

        $semester = $activeYear->semester ?: 'ganjil';
        $waliGroups = $this->waliGroups($user, $schoolId, $activeYear->id);
        $isGlobalAdmin = (method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin())
            || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin());
        $hasSchoolScope = $schoolId !== null;

        // Jangan menghitung agregat lintas sekolah untuk admin global tanpa
        // konteks sekolah: query bisa sangat berat dan angkanya tidak actionable.
        $indicators['nilai_kelas'] = (! $isGlobalAdmin || $hasSchoolScope)
            ? $this->nilaiKelasPending($user)
            : 0;
        $indicators['nilai_guru'] = $this->nilaiGuruPending($user, $activeYear, $semester);
        $indicators['absensi_wali'] = $this->absensiWaliPending($waliGroups, $activeYear, $semester);
        $indicators['absensi_all'] = $hasSchoolScope && $this->canManageAllAttendance($user)
            ? $this->absensiAllPending($schoolId, $activeYear, $semester)
            : 0;
        $indicators['rombel_tanpa_wali'] = $hasSchoolScope && $this->canManageRombel($user)
            ? $this->rombelTanpaWaliCount($schoolId, $activeYear->id)
            : 0;
        $indicators['mutasi_pending'] = $hasSchoolScope && $this->canManageMutation($user)
            ? $this->mutationPendingCount($schoolId)
            : 0;
        $indicators['perangkat_review'] = $this->perangkatReviewCount($user);
        $indicators['review_pending'] = $this->reviewPendingCount($user);
        $indicators['perangkat_ksp'] = $hasSchoolScope
            ? $this->perangkatKspPendingCount($user, $schoolId, $activeYear, $semester)
            : 0;
        $indicators['todo_overdue'] = $this->todoOverdueCount($user);
        $indicators['feedback_baru'] = $this->feedbackBaruCount($user);
        $indicators['approvals'] = $this->approvalPendingCount($user);

        return $indicators;
    }

    private function empty(): array
    {
        return [
            'notifications' => 0,
            'approvals' => 0,
            'nilai_kelas' => 0,
            'nilai_guru' => 0,
            'absensi_wali' => 0,
            'absensi_all' => 0,
            'rombel_tanpa_wali' => 0,
            'mutasi_pending' => 0,
            'perangkat_review' => 0,
            'review_pending' => 0,
            'perangkat_ksp' => 0,
            'todo_overdue' => 0,
            'feedback_baru' => 0,
        ];
    }

    private function resolveSchoolId(User $user): ?string
    {
        $context = request()->attributes->get('schoolContextId');
        if ($context) {
            return $context;
        }

        return $user->employment?->school_id
            ?: $user->gtkWorkUnits()
                ->where('is_primary', true)
                ->with('workUnit.school')
                ->first()?->workUnit?->school?->id;
    }

    private function waliGroups(User $user, ?string $schoolId, string $academicYearId)
    {
        return StudyGroup::withoutGlobalScope('school_context')
            ->where('homeroom_teacher_id', $user->id)
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->get(['id', 'school_id', 'name']);
    }

    private function nilaiKelasPending(User $user): int
    {
        try {
            return app(NilaiReminderService::class)->countForUser($user);
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Buku nilai milik guru yang masih memiliki nilai STS/SAS kosong.
     */
    private function nilaiGuruPending(User $user, AcademicYear $activeYear, string $semester): int
    {
        $books = TeacherAdminBook::with('subject')
            ->where('teacher_id', $user->id)
            ->where('academic_year_id', $activeYear->id)
            ->where('semester', $semester)
            ->where('is_active', true)
            ->get()
            ->reject(fn ($book) => preg_match(
                '/liqo/i',
                (string) ($book->subject?->name ?? '').' '.(string) ($book->subject?->code ?? '')
            ));

        if ($books->isEmpty()) {
            return 0;
        }

        $studentIdsByGroup = StudentClassHistory::query()
            ->whereIn('study_group_id', $books->pluck('study_group_id')->unique())
            ->where('academic_year_id', $activeYear->id)
            ->where('is_active', true)
            ->get(['study_group_id', 'student_id'])
            ->groupBy('study_group_id')
            ->map(fn ($rows) => $rows->pluck('student_id')->unique()->values());

        $rows = NilaiSumatif::query()
            ->whereIn('admin_book_id', $books->pluck('id'))
            ->where('semester', $semester)
            ->get()
            ->keyBy(fn ($row) => $row->admin_book_id.'|'.$row->student_id);

        $tahfidz = app(TahfidzRaporService::class);
        $pending = 0;

        foreach ($books as $book) {
            $studentIds = $studentIdsByGroup->get($book->study_group_id, collect());

            // Buku tanpa santri bukan pekerjaan input nilai yang bisa diselesaikan guru.
            if ($studentIds->isEmpty()) {
                continue;
            }

            foreach ($studentIds as $studentId) {
                $row = $rows->get($book->id.'|'.$studentId);

                if ($tahfidz->isTahfidzSubject($book->subject)) {
                    $sts = $tahfidz->scoreFor($studentId, $book, $activeYear->id, $semester, 'sts', $row);
                    $sas = $tahfidz->scoreFor($studentId, $book, $activeYear->id, $semester, 'sas', $row);
                } else {
                    $sts = $row?->sts;
                    $sas = $row?->nr_final ?? $row?->sas;
                }

                if ($sts === null || $sas === null) {
                    $pending++;

                    continue 2;
                }
            }
        }

        return $pending;
    }

    private function absensiWaliPending($waliGroups, AcademicYear $activeYear, string $semester): int
    {
        if ($waliGroups->isEmpty() || ! $this->isAttendanceDay($waliGroups->first()->school_id, $activeYear)) {
            return 0;
        }

        $recordedGroupIds = AdminPresensiHarian::query()
            ->whereIn('study_group_id', $waliGroups->pluck('id'))
            ->where('academic_year_id', $activeYear->id)
            ->where('semester', $semester)
            ->whereDate('attendance_date', now()->toDateString())
            ->distinct()
            ->pluck('study_group_id')
            ->all();

        $groupsWithStudents = StudentClassHistory::query()
            ->whereIn('study_group_id', $waliGroups->pluck('id'))
            ->where('academic_year_id', $activeYear->id)
            ->where('is_active', true)
            ->distinct()
            ->pluck('study_group_id')
            ->all();

        return count(array_diff($groupsWithStudents, $recordedGroupIds));
    }

    private function absensiAllPending(?string $schoolId, AcademicYear $activeYear, string $semester): int
    {
        if (! $this->isAttendanceDay($schoolId, $activeYear)) {
            return 0;
        }

        $groupIds = StudyGroup::withoutGlobalScope('school_context')
            ->where('academic_year_id', $activeYear->id)
            ->where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->pluck('id');

        if ($groupIds->isEmpty()) {
            return 0;
        }

        $groupsWithStudents = StudentClassHistory::query()
            ->whereIn('study_group_id', $groupIds)
            ->where('academic_year_id', $activeYear->id)
            ->where('is_active', true)
            ->distinct()
            ->pluck('study_group_id')
            ->all();

        $recorded = AdminPresensiHarian::query()
            ->whereIn('study_group_id', $groupsWithStudents)
            ->where('academic_year_id', $activeYear->id)
            ->where('semester', $semester)
            ->whereDate('attendance_date', now()->toDateString())
            ->distinct()
            ->pluck('study_group_id')
            ->all();

        return count(array_diff($groupsWithStudents, $recorded));
    }

    /**
     * Absensi hanya relevan pada hari sekolah: bukan Minggu, di dalam
     * rentang TA, dan tidak tercatat sebagai libur pada Kaldik.
     */
    private function isAttendanceDay(?string $schoolId, AcademicYear $activeYear): bool
    {
        $today = now()->toDateString();

        if (now()->isSunday()) {
            return false;
        }

        if ($activeYear->start_date && $today < $activeYear->start_date->toDateString()) {
            return false;
        }

        if ($activeYear->end_date && $today > $activeYear->end_date->toDateString()) {
            return false;
        }

        $workUnitId = $schoolId ? School::whereKey($schoolId)->value('work_unit_id') : null;

        return ! Kaldik::query()
            ->where('is_active', true)
            ->where('type', Kaldik::TYPE_LIBUR)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->when($workUnitId, fn ($q) => $q->where(fn ($qq) => $qq->whereNull('work_unit_id')->orWhere('work_unit_id', $workUnitId)))
            ->exists();
    }

    private function rombelTanpaWaliCount(?string $schoolId, string $academicYearId): int
    {
        return StudyGroup::withoutGlobalScope('school_context')
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereNull('homeroom_teacher_id')
            ->count();
    }

    private function mutationPendingCount(?string $schoolId): int
    {
        return StudentMutationOut::query()
            ->where('status', 'submitted')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->count();
    }

    private function perangkatReviewCount(User $user): int
    {
        return ReviewAssignment::query()
            ->where('reviewer_id', $user->id)
            ->where('status', ReviewAssignment::STATUS_PENDING)
            ->where('reviewable_type', (new PerangkatPembelajaran)->getMorphClass())
            ->count();
    }

    private function reviewPendingCount(User $user): int
    {
        return ReviewAssignment::query()
            ->where('reviewer_id', $user->id)
            ->where('status', ReviewAssignment::STATUS_PENDING)
            ->count();
    }

    private function perangkatKspPendingCount(User $user, ?string $schoolId, AcademicYear $activeYear, string $semester): int
    {
        if (! app(KurikulumAccess::class)->isKurikulumSupervisor($user)) {
            return 0;
        }

        return PerangkatPembelajaran::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $activeYear->id)
            ->where('semester', $semester)
            ->where('workflow_status', PerangkatPembelajaran::WORKFLOW_APPROVED)
            ->where('status', PerangkatPembelajaran::STATUS_FINAL)
            ->whereNull('ksp_approved_at')
            ->count();
    }

    private function todoOverdueCount(User $user): int
    {
        return Todo::query()
            ->overdue()
            ->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhere('delegated_by', $user->id);
            })
            ->count();
    }

    private function feedbackBaruCount(User $user): int
    {
        if (! $this->canManageFeedback($user)) {
            return 0;
        }

        return AppFeedbackReport::query()->where('status', 'baru')->count();
    }

    private function approvalPendingCount(User $user): int
    {
        if (! $this->hasApprovalMenu($user)) {
            return 0;
        }

        $isAdmin = (method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin())
            || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin());

        $requests = ApprovalRequest::with('actions')
            ->where('status', 'PENDING')
            ->get();

        return $requests->filter(function (ApprovalRequest $request) use ($user, $isAdmin) {
            if ($isAdmin) {
                return true;
            }

            $step = $request->actions
                ->where('action', 'PENDING')
                ->sortBy('step_order')
                ->first();

            if (! $step) {
                return false;
            }

            if ($step->step_permission && function_exists('canPermission') && canPermission($step->step_permission)) {
                return true;
            }

            return $step->role_name
                && method_exists($user, 'hasRole')
                && $user->hasRole($step->role_name);
        })->count();
    }

    private function canManageAllAttendance(User $user): bool
    {
        if ((method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin())
            || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())) {
            return true;
        }

        if (function_exists('canPermission') && (
            canPermission('absensi-admin-tu')
            || canPermission('absensi-tata-usaha')
            || canPermission('absensi-wakil-kepala-sekolah')
            || canPermission('absensi-kepala-sekolah')
            || canPermission('absensi-kepala-sekolah-pondok')
        )) {
            return true;
        }

        return UserJabatan::hasAny($user, [
            'kepala satuan pendidikan',
            'wakil kepala satuan pendidikan',
            'kepala tata usaha',
            'tata usaha',
            'staf tata usaha',
            'koordinator kurikulum',
            'koordinator kesiswaan',
            'kepala tu sekolah',
            'tu sekolah',
            'staf tu sekolah',
            'tata usaha sekolah',
        ]);
    }

    private function canManageRombel(User $user): bool
    {
        return $this->canManageAllAttendance($user);
    }

    private function canManageMutation(User $user): bool
    {
        return $this->canManageAllAttendance($user);
    }

    private function canManageFeedback(User $user): bool
    {
        if ((method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin())
            || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())) {
            return true;
        }

        try {
            return $user->hasRole('Pimpinan') || $user->hasRole('Teknologi Informasi');
        } catch (\Throwable) {
            return false;
        }
    }

    private function hasApprovalMenu(User $user): bool
    {
        return $this->canManageAllAttendance($user)
            || (method_exists($user, 'hasRole') && $user->hasRole('Satuan Pendidikan'));
    }
}
