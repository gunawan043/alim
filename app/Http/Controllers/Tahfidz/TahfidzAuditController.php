<?php

namespace App\Http\Controllers\Tahfidz;

use App\Exports\TahfidzAuditExport;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\TahfidzCertificate;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzJuzProgress;
use App\Models\TahfidzMutabaah;
use App\Models\TahfidzProgressRecap;
use App\Models\TahfidzSetoran;
use App\Models\TahfidzStudentTarget;
use App\Models\TahfidzTasmianParticipant;
use App\Services\TahfidzAccess;
use App\Services\TahfidzProgressService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TahfidzAuditController extends Controller
{
    public function __construct(
        private readonly TahfidzAccess $access,
        private readonly TahfidzProgressService $progress,
    ) {}

    public function index(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $academicYear = AcademicYear::where('is_active', true)->first()
            ?? AcademicYear::orderByDesc('start_date')->first();
        $schoolId = $this->schoolId($request);
        $groups = $this->accessibleGroups($request, $academicYear?->id, $schoolId);
        $groupIds = $groups->pluck('id');

        $members = TahfidzGroupMember::with(['student:id,name,nis,school_id', 'group:id,name,school_id'])
            ->whereIn('tahfidz_group_id', $groupIds)
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->where('status', 'aktif')
            ->get();
        $studentIds = $members->pluck('student_id')->unique()->values();

        $pending = TahfidzSetoran::with(['student:id,name,nis', 'group:id,name'])
            ->whereIn('tahfidz_group_id', $groupIds)
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->where('verification_status', 'pending')
            ->orderBy('setoran_date')
            ->get();
        $pendingOld = $pending->filter(fn ($row) => $row->setoran_date?->lte(now()->subDays(7)));

        $progressStudentIds = TahfidzJuzProgress::whereIn('student_id', $studentIds)
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->pluck('student_id')
            ->unique();
        $recapStudentIds = TahfidzProgressRecap::whereIn('student_id', $studentIds)
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->pluck('student_id')
            ->unique();
        $targetStudentIds = TahfidzStudentTarget::whereIn('student_id', $studentIds)
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->pluck('student_id')
            ->unique();

        $verifiedWithoutProgress = TahfidzSetoran::with(['student:id,name,nis', 'group:id,name'])
            ->whereIn('tahfidz_group_id', $groupIds)
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->where('verification_status', 'verified')
            ->where('setoran_type', 'ziyadah')
            ->whereNotNull('juz')
            ->whereNotIn('student_id', $progressStudentIds)
            ->orderByDesc('setoran_date')
            ->limit(20)
            ->get();

        $lastMutabaah = TahfidzMutabaah::whereIn('student_id', $studentIds)
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->selectRaw('student_id, MAX(record_date) as last_date')
            ->groupBy('student_id')
            ->pluck('last_date', 'student_id');
        $membersWithoutMutabaah = $members->filter(function ($member) use ($lastMutabaah) {
            $last = $lastMutabaah[$member->student_id] ?? null;

            return ! $last || Carbon::parse($last)->lt(now()->subDays(7));
        });

        $tasmiWithoutCertificate = TahfidzTasmianParticipant::with([
            'student:id,name,nis', 'session:id,session_name,school_id,session_date', 'score',
        ])
            ->whereHas('score')
            ->whereDoesntHave('certificate')
            ->when(! $this->access->canViewAllSchools($request->user()), fn ($q) => $q->whereHas('session', fn ($session) => $session->where('school_id', $schoolId)))
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->latest()
            ->limit(20)
            ->get();

        $certificateIssues = TahfidzCertificate::with(['student:id,name,nis,name_ar', 'participant.score'])
            ->when(! $this->access->canViewAllSchools($request->user()), fn ($q) => $q->where('school_id', $schoolId))
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->get()
            ->filter(function ($certificate) {
                return blank($certificate->student?->name_ar)
                    || $certificate->nilai_akhir === null
                    || blank($certificate->predikat)
                    || blank($certificate->certificate_number)
                    || ! $certificate->participant?->score;
            })
            ->take(20);

        $targetMissing = $members->reject(fn ($member) => $targetStudentIds->contains($member->student_id))->take(20);
        $recapMissing = $members
            ->filter(fn ($member) => $progressStudentIds->contains($member->student_id) && ! $recapStudentIds->contains($member->student_id))
            ->take(20);

        $stats = [
            'pending' => $pending->count(),
            'pending_old' => $pendingOld->count(),
            'verified_without_progress' => $verifiedWithoutProgress->count(),
            'recap_missing' => $recapMissing->count(),
            'target_missing' => $members->reject(fn ($member) => $targetStudentIds->contains($member->student_id))->count(),
            'mutabaah_missing' => $membersWithoutMutabaah->count(),
            'tasmi_without_certificate' => $tasmiWithoutCertificate->count(),
            'certificate_issues' => $certificateIssues->count(),
        ];
        $stats['total_issues'] = array_sum($stats);

        if ($request->boolean('export')) {
            return Excel::download(
                new TahfidzAuditExport($this->exportRows(
                    $pendingOld, $verifiedWithoutProgress, $recapMissing, $targetMissing,
                    $membersWithoutMutabaah, $tasmiWithoutCertificate, $certificateIssues
                )),
                'Audit-Tahfidz-'.now()->format('Ymd-His').'.xlsx'
            );
        }

        return view('tahfidz.audit.index', compact(
            'userId', 'academicYear', 'groups', 'members', 'pendingOld', 'verifiedWithoutProgress',
            'recapMissing', 'targetMissing', 'membersWithoutMutabaah', 'tasmiWithoutCertificate',
            'certificateIssues', 'stats'
        ));
    }

    public function autoVerifyPending(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $academicYear = AcademicYear::where('is_active', true)->first()
            ?? AcademicYear::orderByDesc('start_date')->first();
        $groups = $this->accessibleGroups($request, $academicYear?->id, $this->schoolId($request));
        $pending = TahfidzSetoran::whereIn('tahfidz_group_id', $groups->pluck('id'))
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->where('verification_status', 'pending')
            ->get();

        foreach ($pending as $setoran) {
            $setoran->forceFill([
                'verification_status' => 'verified',
                'verified_by' => $setoran->teacher_id,
                'verified_at' => now(),
                'verification_notes' => 'auto-verified',
            ])->save();
            $this->progress->recalculateStudent(
                $setoran->student_id,
                $setoran->academic_year_id,
                $setoran->tahfidz_group_id,
            );
        }

        return back()->with('success', $pending->count().' setoran pending berhasil diverifikasi otomatis dan progres dihitung ulang.');
    }

    private function exportRows($pending, $verifiedWithoutProgress, $recapMissing, $targetMissing, $mutabaahMissing, $tasmi, $certificates)
    {
        $rows = collect();

        foreach ($pending as $row) {
            $rows->push(['category' => 'Setoran pending > 7 hari', 'student' => $row->student?->name, 'context' => $row->group?->name, 'detail' => $row->setoran_date?->format('d/m/Y').' — nilai '.($row->nilai_setoran ?? '—')]);
        }
        foreach ($verifiedWithoutProgress as $row) {
            $rows->push(['category' => 'Setoran terverifikasi tanpa progres', 'student' => $row->student?->name, 'context' => $row->group?->name, 'detail' => 'Juz '.($row->juz ?? '—')]);
        }
        foreach ($recapMissing as $member) {
            $rows->push(['category' => 'Progres tanpa rekap', 'student' => $member->student?->name, 'context' => $member->group?->name, 'detail' => 'Rekap capaian belum terbentuk']);
        }
        foreach ($targetMissing as $member) {
            $rows->push(['category' => 'Target belum diatur', 'student' => $member->student?->name, 'context' => $member->group?->name, 'detail' => 'Target hafalan kosong']);
        }
        foreach ($mutabaahMissing as $member) {
            $rows->push(['category' => 'Mutaba’ah tertinggal', 'student' => $member->student?->name, 'context' => $member->group?->name, 'detail' => 'Tidak ada mutaba’ah 7 hari terakhir']);
        }
        foreach ($tasmi as $participant) {
            $rows->push(['category' => 'Tasmi tanpa syahadah', 'student' => $participant->student?->name, 'context' => $participant->session?->session_name, 'detail' => 'Nilai '.($participant->score?->nilai_akhir ?? '—')]);
        }
        foreach ($certificates as $certificate) {
            $rows->push(['category' => 'Syahadah belum lengkap', 'student' => $certificate->student?->name, 'context' => $certificate->certificate_number ?: 'Tanpa nomor', 'detail' => 'Nilai '.($certificate->nilai_akhir ?? '—').' / '.($certificate->predikat ?: 'tanpa predikat')]);
        }

        return $rows;
    }

    private function accessibleGroups(Request $request, ?string $academicYearId, ?string $schoolId)
    {
        $user = $request->user();

        $query = TahfidzGroup::query()
            ->with('school:id,name')
            ->where('is_active', true)
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId));

        if (! $this->access->canViewAllSchools($user)) {
            if ($this->access->isSchoolCoordinator($user, $schoolId) || $this->access->isSchoolLeader($user)) {
                $query->where('school_id', $schoolId);
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('teacher_id', $user->id)
                        ->orWhere('coordinator_id', $user->id)
                        ->orWhereHas('teachers', fn ($teacher) => $teacher->where('user_id', $user->id)->where('is_active', true));
                });
            }
        }

        return $query->orderBy('name')->get();
    }

    private function authorizeView(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $this->access->canViewAllSchools($user)
            || $this->access->isSchoolCoordinator($user, $this->schoolId($request))
            || $this->access->isSchoolLeader($user)
            || $this->access->isTahfidzTeacher($user)
            || (function_exists('canPermission') && canPermission('tahfidz_view')),
            403,
            'Anda tidak berwenang melihat audit Tahfidz.'
        );
    }

    private function schoolId(Request $request): ?string
    {
        return $request->attributes->get('schoolContextId') ?: $request->user()?->employment?->school_id;
    }
}
