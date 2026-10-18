<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Concerns\AssertsPeriodLock;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AlurTujuanPembelajaran;
use App\Models\PerangkatPembelajaran;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Services\Evaluasi\ReviewWorkflowService;
use App\Services\KurikulumAccess;
use Illuminate\Http\Request;

/**
 * Perangkat Pembelajaran — fondasi perencanaan pembelajaran yang disiapkan
 * dari ATP, kelas/mapel/guru pada tahun ajaran & semester yang sama.
 *
 * Desain pembelajarannya dirancang mengikuti prinsip Pembelajaran Mendalam
 * (berkesadaran, bermakna, menggembirakan) melalui bagian-bagian tetap:
 * pertanyaan pemantik → pemahaman bermakna → pengalaman memahami,
 * mengaplikasi, merefleksi → konteks nyata → asesmen → diferensiasi.
 */
class PerangkatPembelajaranController extends Controller
{
    use AssertsPeriodLock;

    public function __construct(private readonly KurikulumAccess $access) {}

    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $user = $request->user();

        $academicYears = AcademicYear::orderByDesc('start_date')->orderByDesc('created_at')->get();
        $activeAy = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $academicYearId = $request->input('academic_year_id', $activeAy?->id);
        $academicYear = $academicYears->firstWhere('id', $academicYearId);

        $semester = $request->input('semester', $academicYear?->semester === 'genap' ? 'genap' : 'ganjil');
        if (! in_array($semester, ['ganjil', 'genap'], true)) {
            $semester = 'ganjil';
        }

        $perangkatList = PerangkatPembelajaran::query()
            ->with(['subject:id,name,code', 'gradeLevel:id,name', 'studyGroup:id,name', 'atp:id,total_jp,status', 'teacher:id,name'])
            ->bySchool($schoolId)
            ->byAcademicYear($academicYearId)
            ->bySemester($semester)
            ->when(! $this->access->isKurikulumTeam($user), function ($q) use ($user, $schoolId, $academicYearId) {
                // Guru: hanya RPM mapel yang diampu / serumpun.
                $viewable = $this->access->viewableSubjectIds($user, $schoolId, $academicYearId);
                $q->whereIn('subject_id', $viewable ?: ['-']);
            })
            ->when($request->filled('subject_id'), fn ($q) => $q->where('subject_id', $request->subject_id))
            ->when($request->filled('study_group_id'), fn ($q) => $q->where('study_group_id', $request->study_group_id))
            ->orderByDesc('created_at')
            ->get();

        $isKurikulumTeam = $this->access->isKurikulumTeam($user);

        // Mapel yang diampu user — untuk badge "serumpun" di daftar.
        $mySubjectIds = TeachingAssignment::query()
            ->where('teacher_id', $user->id)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'active')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->pluck('subject_id')
            ->unique()
            ->values()
            ->all();

        // ATP yang boleh dipakai user: semua (tim kurikulum) atau mapel yang diampu.
        $atpQuery = AlurTujuanPembelajaran::query()
            ->with(['subject:id,name,code', 'gradeLevel:id,name'])
            ->bySchool($schoolId)
            ->byAcademicYear($academicYearId)
            ->bySemester($semester)
            ->orderBy('subject_id');

        if (! $this->access->isKurikulumEditor($user)) {
            $taughtSubjectIds = TeachingAssignment::query()
                ->where('teacher_id', $user->id)
                ->where('academic_year_id', $academicYearId)
                ->where('status', 'active')
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->pluck('subject_id')
                ->unique()
                ->values()
                ->all();

            $atpQuery->whereIn('subject_id', $taughtSubjectIds ?: ['-']);
        }

        $atpOptions = $atpQuery->get();

        $studyGroups = StudyGroup::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('academic_year_id', $academicYearId)
            ->orderBy('name')
            ->get(['id', 'name', 'grade_level_id']);

        $subjects = Subject::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return view('kurikulum.perangkat.index', compact(
            'userId',
            'academicYears',
            'academicYearId',
            'semester',
            'perangkatList',
            'atpOptions',
            'studyGroups',
            'subjects',
            'isKurikulumTeam',
            'mySubjectIds'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $validated = $request->validate([
            'atp_id' => 'required|exists:alur_tujuan_pembelajaran,id',
            'study_group_id' => 'nullable|exists:study_groups,id',
            'judul' => 'required|string|max:255',
            'tipe' => 'nullable|in:umum,agama',
            'catatan' => 'nullable|string|max:2000',
        ]);

        $atp = AlurTujuanPembelajaran::with('subject')->findOrFail($validated['atp_id']);
        if (! $this->access->canAuthorSubject($request->user(), $atp->subject_id, $atp->academic_year_id, $schoolId)) {
            abort(403, 'Anda hanya dapat menyusun RPM untuk mata pelajaran yang diampu.');
        }
        $this->assertPeriodNotLocked($atp->academic_year_id, 'Dokumen RPM/Perangkat');

        // Kebijakan Tahap 2: SATU dokumen current per konteks akademik
        // (mapel + jenjang + tahun ajaran + semester). Revisi = versi baru.
        $existing = PerangkatPembelajaran::query()
            ->where('school_id', $atp->school_id)
            ->where('academic_year_id', $atp->academic_year_id)
            ->where('semester', $atp->semester)
            ->where('subject_id', $atp->subject_id)
            ->where('grade_level_id', $atp->grade_level_id)
            ->where('is_current', true)
            ->first();

        if ($existing) {
            return redirect()
                ->route('user.kurikulum.perangkat.show', ['userId' => $userId, 'id' => $existing->id])
                ->with('error', 'RPM untuk konteks ini sudah ada — gunakan "Versi Baru" untuk revisi, bukan membuat dokumen kedua.');
        }

        PerangkatPembelajaran::create([
            'school_id' => $atp->school_id,
            'academic_year_id' => $atp->academic_year_id,
            'semester' => $atp->semester,
            'subject_id' => $atp->subject_id,
            'grade_level_id' => $atp->grade_level_id,
            'study_group_id' => $validated['study_group_id'] ?? null,
            'atp_id' => $atp->id,
            'teacher_id' => $request->user()->id,
            'judul' => $validated['judul'],
            'tipe' => $validated['tipe'] ?? PerangkatPembelajaran::TIPE_UMUM,
            'status' => PerangkatPembelajaran::STATUS_DRAFT,
            'desain' => PerangkatPembelajaran::defaultDesain(),
            'catatan' => $validated['catatan'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('user.kurikulum.perangkat.index', ['userId' => $userId, 'subject_id' => $atp->subject_id])
            ->with('success', 'Perangkat pembelajaran berhasil dibuat dari ATP '.($atp->subject?->name ?? '').'.');
    }

    public function show(Request $request, string $userId, string $id)
    {
        $perangkat = PerangkatPembelajaran::with([
            'subject', 'gradeLevel', 'studyGroup', 'teacher', 'creator',
            'atp.items.tujuanPembelajaran',
        ])->findOrFail($id);

        $this->authorizeView($request, $perangkat);

        $schoolId = $request->attributes->get('schoolContextId');

        $studyGroups = StudyGroup::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('academic_year_id', $perangkat->academic_year_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $isOwner = $this->isOwner($request, $perangkat);
        $canEdit = $perangkat->isEditable() && ($isOwner
            || $this->access->isKurikulumEditor($request->user())
            || $this->access->hasEditGrant($request->user(), $perangkat->subject_id, $perangkat->academic_year_id, $schoolId));

        $workflowService = app(ReviewWorkflowService::class);
        $workflowProgress = $workflowService->progress($perangkat);
        $versions = PerangkatPembelajaran::query()
            ->where('root_id', $perangkat->root_id ?: $perangkat->id)
            ->orderByDesc('version')
            ->get();
        $myReviewAssignment = $perangkat->reviewAssignments()
            ->where('reviewer_id', $request->user()->id)
            ->where('status', 'pending')
            ->first();
        $canSubmitReview = $canEdit
            && $perangkat->workflow_status !== PerangkatPembelajaran::WORKFLOW_REVIEW;
        $canCreateVersion = ($isOwner
            || $this->access->isKurikulumEditor($request->user())
            || $this->access->hasEditGrant($request->user(), $perangkat->subject_id, $perangkat->academic_year_id, $schoolId))
            && $perangkat->isLocked();

        return view('kurikulum.perangkat.show', compact(
            'userId', 'perangkat', 'studyGroups', 'isOwner', 'canEdit',
            'workflowProgress', 'versions', 'myReviewAssignment', 'canSubmitReview', 'canCreateVersion'
        ));
    }

    public function update(Request $request, string $userId, string $id)
    {
        $perangkat = PerangkatPembelajaran::findOrFail($id);
        $this->authorizeEdit($request, $perangkat);
        $this->assertPeriodNotLocked($perangkat->academic_year_id, 'Dokumen RPM/Perangkat');

        // Final terkunci: revisi harus melalui versi baru.
        if ($perangkat->isLocked()) {
            abort(403, 'RPM sudah final dan terkunci — gunakan "Versi Baru" untuk revisi.');
        }

        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'study_group_id' => 'nullable|exists:study_groups,id',
            'tipe' => 'nullable|in:umum,agama',
            'status' => 'required|in:draft,final',
            'catatan' => 'nullable|string|max:2000',
            'desain' => 'nullable|array',
            'desain.*' => 'nullable|string|max:5000',
        ]);

        // Final = kunci: hanya tim kurikulum, atau melalui approval kolektif
        // seluruh anggota rumpun (kebijakan Tahap 2).
        if ($validated['status'] === PerangkatPembelajaran::STATUS_FINAL
            && ! $this->access->isKurikulumEditor($request->user())) {
            return back()->with('error', 'Penetapan final RPM dilakukan oleh tim kurikulum atau melalui approval anggota rumpun.');
        }

        // Bagian desain yang tidak dikirim tetap dipertahankan (merge, bukan overwrite).
        $desain = $perangkat->mergeDesain($validated['desain'] ?? []);

        $payload = [
            'judul' => $validated['judul'],
            'study_group_id' => $validated['study_group_id'] ?? null,
            'tipe' => $validated['tipe'] ?? $perangkat->tipe,
            'status' => $validated['status'],
            'catatan' => $validated['catatan'] ?? null,
            'desain' => $desain,
        ];

        if ($validated['status'] === PerangkatPembelajaran::STATUS_FINAL) {
            $payload['workflow_status'] = PerangkatPembelajaran::WORKFLOW_APPROVED;
            $payload['approved_at'] = $perangkat->approved_at ?: now();
            $payload['approved_by'] = $perangkat->approved_by ?: $request->user()->id;
            $payload['locked_at'] = $perangkat->locked_at ?: now();
            $payload['locked_by'] = $perangkat->locked_by ?: $request->user()->id;
        }

        $perangkat->update($payload);

        return back()->with('success', 'RPM / perangkat pembelajaran berhasil disimpan.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $perangkat = PerangkatPembelajaran::findOrFail($id);
        $this->authorizeEdit($request, $perangkat);
        $this->assertPeriodNotLocked($perangkat->academic_year_id, 'Dokumen RPM/Perangkat');

        if ($perangkat->isLocked()) {
            abort(403, 'RPM final terkunci dan menjadi riwayat — tidak dapat dihapus.');
        }

        $perangkat->delete();

        return redirect()
            ->route('user.kurikulum.perangkat.index', ['userId' => $userId])
            ->with('success', 'Perangkat pembelajaran berhasil dihapus.');
    }

    /**
     * Ajukan RPM untuk approval kolektif anggota rumpun (bukan koordinator tunggal).
     */
    public function submitReview(Request $request, string $userId, string $id)
    {
        $perangkat = PerangkatPembelajaran::findOrFail($id);
        $this->authorizeEdit($request, $perangkat);
        $this->assertPeriodNotLocked($perangkat->academic_year_id, 'Dokumen RPM/Perangkat');

        if ($perangkat->isLocked()) {
            return back()->with('error', 'RPM sudah final dan terkunci — buat versi baru untuk revisi.');
        }

        if ($perangkat->workflow_status === PerangkatPembelajaran::WORKFLOW_REVIEW) {
            return back()->with('error', 'RPM sedang dalam proses review.');
        }

        $workflow = app(ReviewWorkflowService::class);
        $assignments = $workflow->submit(
            $perangkat,
            $request->user(),
            $workflow->resolveRumpunApprovers($perangkat, $request->user())
        );

        return back()->with('success', 'RPM diajukan untuk approval ('.count($assignments).' anggota rumpun).');
    }

    /**
     * Revisi = versi baru. Dokumen lama tetap tersimpan sebagai riwayat.
     */
    public function newVersion(Request $request, string $userId, string $id)
    {
        $perangkat = PerangkatPembelajaran::findOrFail($id);
        $this->authorizeEdit($request, $perangkat);
        $this->assertPeriodNotLocked($perangkat->academic_year_id, 'Dokumen RPM/Perangkat');

        $new = $perangkat->createNewVersion();

        return redirect()
            ->route('user.kurikulum.perangkat.show', ['userId' => $userId, 'id' => $new->id])
            ->with('success', 'Versi baru (v'.$new->version.') dibuat — dokumen lama tetap menjadi riwayat.');
    }

    private function authorizeManage(Request $request, string $subjectId, string $academicYearId, ?string $schoolId): void
    {
        if (! $this->access->canManageSubject($request->user(), $subjectId, $academicYearId, $schoolId)) {
            abort(403, 'Anda tidak berwenang mengelola perangkat untuk mata pelajaran ini.');
        }
    }

    /**
     * Lihat RPM: tim kurikulum, guru serumpun (mapel + AY sama), atau pemilik.
     */
    private function authorizeView(Request $request, PerangkatPembelajaran $perangkat): void
    {
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $perangkat->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $user = $request->user();
        $allowed = $this->access->canViewSubjectDocs($user, $perangkat->subject_id, $schoolId, $perangkat->academic_year_id)
            || $this->isOwner($request, $perangkat);

        if (! $allowed) {
            abort(403, 'RPM ini bukan untuk mata pelajaran yang Anda ampu.');
        }
    }

    /**
     * Ubah RPM: penyusun (guru pembuat/pemilik) atau tim kurikulum.
     * Guru serumpun tetap dapat melihat & mencetak sebagai RPM bersama.
     */
    private function authorizeEdit(Request $request, PerangkatPembelajaran $perangkat): void
    {
        $this->authorizeView($request, $perangkat);

        $schoolId = $request->attributes->get('schoolContextId');

        if (! $this->isOwner($request, $perangkat)
            && ! $this->access->isKurikulumEditor($request->user())
            && ! $this->access->hasEditGrant($request->user(), $perangkat->subject_id, $perangkat->academic_year_id, $schoolId)) {
            abort(403, 'Hanya penyusun RPM, pemegang izin edit, atau tim kurikulum yang dapat mengubah RPM ini.');
        }
    }

    private function isOwner(Request $request, PerangkatPembelajaran $perangkat): bool
    {
        $user = $request->user();

        return $perangkat->created_by === $user->id || $perangkat->teacher_id === $user->id;
    }
}
