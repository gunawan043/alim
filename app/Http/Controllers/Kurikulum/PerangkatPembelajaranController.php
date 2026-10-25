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
use App\Services\CurriculumProfileFilterService;
use App\Services\CurriculumProfileService;
use App\Services\KurikulumAccess;
use App\Services\Kurikulum\PerangkatFormatResolver;
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

        $profileService = app(CurriculumProfileService::class);
        $curriculumProfile = $profileService->resolve($schoolId, $academicYearId);
        $profileScope = app(CurriculumProfileFilterService::class)->scope($request);

        $perangkatQuery = PerangkatPembelajaran::query()
            ->with(['subject:id,name,code', 'gradeLevel:id,name', 'studyGroup:id,name', 'atp:id,total_jp,status', 'teacher:id,name', 'curriculumProfile:id,code,name,authority'])
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
            ->orderByDesc('created_at');

        $perangkatList = app(CurriculumProfileFilterService::class)
            ->apply($perangkatQuery, $curriculumProfile?->id, $profileScope)
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
        app(CurriculumProfileFilterService::class)->apply($atpQuery, $curriculumProfile?->id, $profileScope);

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

        $curriculumLabels = $profileService->labels($curriculumProfile);
        $isKspSupervisor = $this->access->isKurikulumSupervisor($user);

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
            'mySubjectIds',
            'curriculumProfile',
            'curriculumLabels',
            'profileScope',
            'isKspSupervisor'
        ));
    }

    public function kspReport(Request $request, string $userId)
    {
        abort_unless($this->access->isKurikulumSupervisor($request->user()), 403, 'Laporan KSP hanya untuk Kepala/Wakil Satuan Pendidikan.');

        $schoolId = $request->attributes->get('schoolContextId');
        $academicYears = AcademicYear::orderByDesc('start_date')->orderByDesc('created_at')->get();
        $activeAy = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $academicYearId = $request->input('academic_year_id', $activeAy?->id);
        $semester = $request->input('semester', 'ganjil');

        $rows = PerangkatPembelajaran::with([
            'subject:id,name', 'teacher:id,name', 'gradeLevel:id,name', 'studyGroup:id,name',
        ])
            ->where('school_id', $schoolId)
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->where('semester', $semester)
            ->orderByDesc('updated_at')
            ->get();

        $stats = [
            'total' => $rows->count(),
            'rumpun' => $rows->where('workflow_status', PerangkatPembelajaran::WORKFLOW_APPROVED)->count(),
            'ksp' => $rows->filter(fn ($row) => $row->isKspApproved())->count(),
            'draft' => $rows->where('status', PerangkatPembelajaran::STATUS_DRAFT)->count(),
        ];

        return view('kurikulum.perangkat.ksp-report', compact(
            'userId', 'rows', 'stats', 'academicYears', 'academicYearId', 'semester'
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

        $formatType = app(PerangkatFormatResolver::class)->resolve($atp->subject);

        if ($formatType === PerangkatFormatResolver::TAHFIDZ) {
            return redirect()
                ->route('user.tahfidz.rpp.index', ['userId' => $userId])
                ->with('error', 'Mapel Tahfidz disusun melalui modul RPP Tahfizh di Departemen.');
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
            'tipe' => $formatType === PerangkatFormatResolver::AGAMA
                ? PerangkatPembelajaran::TIPE_AGAMA
                : PerangkatPembelajaran::TIPE_UMUM,
            'source' => 'manual',
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
        $canApproveKsp = $this->access->isKurikulumSupervisor($request->user())
            && $perangkat->workflow_status === PerangkatPembelajaran::WORKFLOW_APPROVED
            && $perangkat->status === PerangkatPembelajaran::STATUS_FINAL
            && ! $perangkat->isKspApproved();

        $profileService = app(CurriculumProfileService::class);
        $curriculumProfile = $perangkat->curriculumProfile
            ?? $profileService->resolve($perangkat->school_id, $perangkat->academic_year_id);
        $perangkat->setRelation('curriculumProfile', $curriculumProfile);
        $curriculumLabels = $profileService->labels($curriculumProfile);
        $kbcChecklist = $perangkat->kbcChecklist();
        $kbcPercentage = $perangkat->kbcCompletionPercentage();
        $kbcMissing = $perangkat->missingKbcFields('final');
        $kbcReviewMissing = $perangkat->missingKbcFields('review');
        $kbcFormatIssues = $perangkat->kbcFormatIssues();
        $formatType = app(PerangkatFormatResolver::class)->forPerangkat($perangkat);

        $printVersion = in_array($request->query('version'), ['pondok', 'dinas', 'kemenag'], true)
            ? $request->query('version')
            : 'pondok';
        $layout = $perangkat->layoutSettings();
        $previewUrl = route('user.kurikulum.cetak.rpm', [
            'userId' => $userId,
            'id' => $perangkat->id,
            'version' => $printVersion,
            'v' => $perangkat->updated_at?->timestamp,
        ]);
        $versionOptions = [
            'pondok' => 'Pondok',
            'dinas' => 'Dinas Pendidikan',
            'kemenag' => 'Kemenag',
        ];
        $versionUrls = [];
        foreach ($versionOptions as $versionKey => $versionLabel) {
            $versionUrls[$versionKey] = route('user.kurikulum.perangkat.show', [
                'userId' => $userId,
                'id' => $perangkat->id,
                'version' => $versionKey,
            ]);
        }
        $canManagePrint = $isOwner
            || $this->access->isKurikulumEditor($request->user())
            || $this->access->hasEditGrant($request->user(), $perangkat->subject_id, $perangkat->academic_year_id, $schoolId);

        return view('kurikulum.perangkat.show', compact(
            'userId', 'perangkat', 'studyGroups', 'isOwner', 'canEdit',
            'workflowProgress', 'versions', 'myReviewAssignment', 'canSubmitReview', 'canCreateVersion', 'canApproveKsp',
            'curriculumProfile', 'curriculumLabels', 'kbcChecklist', 'kbcPercentage', 'kbcMissing', 'kbcReviewMissing', 'kbcFormatIssues',
            'formatType', 'printVersion', 'layout', 'previewUrl', 'versionOptions', 'versionUrls', 'canManagePrint'
        ));
    }

    public function preview(Request $request, string $userId, string $id)
    {
        return redirect()->route('user.kurikulum.perangkat.show', [
            'userId' => $userId,
            'id' => $id,
            'version' => $request->query('version', 'pondok'),
        ]);
    }

    public function saveLayout(Request $request, string $userId, string $id)
    {
        $perangkat = PerangkatPembelajaran::findOrFail($id);
        $this->authorizeView($request, $perangkat);

        $isOwner = $this->isOwner($request, $perangkat);
        abort_unless(
            $isOwner
                || $this->access->isKurikulumEditor($request->user())
                || $this->access->hasEditGrant($request->user(), $perangkat->subject_id, $perangkat->academic_year_id, $request->attributes->get('schoolContextId')),
            403,
            'Anda tidak berwenang mengatur tampilan perangkat ini.'
        );

        $validated = $request->validate([
            'margin_top' => 'required|numeric|min:0|max:50',
            'margin_right' => 'required|numeric|min:0|max:50',
            'margin_bottom' => 'required|numeric|min:0|max:50',
            'margin_left' => 'required|numeric|min:0|max:50',
            'font_size' => 'required|numeric|min:6|max:24',
            'title_font_size' => 'required|numeric|min:8|max:30',
            'table_font_size' => 'required|numeric|min:6|max:20',
            'line_height' => 'required|numeric|min:1|max:2.5',
            'paper_size' => 'required|in:a4,folio,letter',
            'show_kop' => 'nullable|boolean',
            'use_digital_signature' => 'nullable|boolean',
            'show_stamp' => 'nullable|boolean',
        ]);

        $layout = array_merge($perangkat->layoutSettings(), [
            'margin_top' => (float) $validated['margin_top'],
            'margin_right' => (float) $validated['margin_right'],
            'margin_bottom' => (float) $validated['margin_bottom'],
            'margin_left' => (float) $validated['margin_left'],
            'font_size' => (float) $validated['font_size'],
            'title_font_size' => (float) $validated['title_font_size'],
            'table_font_size' => (float) $validated['table_font_size'],
            'line_height' => (float) $validated['line_height'],
            'paper_size' => $validated['paper_size'],
            'show_kop' => $request->boolean('show_kop'),
            'use_digital_signature' => $request->boolean('use_digital_signature'),
            'show_stamp' => $request->boolean('show_stamp'),
        ]);

        $perangkat->update(['layout_settings' => $layout]);

        return redirect()
            ->route('user.kurikulum.perangkat.show', [
                'userId' => $userId,
                'id' => $perangkat->id,
                'version' => $request->input('version', 'pondok'),
            ])
            ->with('success', 'Pengaturan margin, font, dan kertas berhasil disimpan.');
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

        // Final bukan aksi edit manual: harus melalui approval kolektif guru serumpun.
        if ($validated['status'] === PerangkatPembelajaran::STATUS_FINAL
            && ! $perangkat->isApproved()) {
            return back()->with('error', 'Final hanya dapat ditetapkan setelah seluruh guru serumpun menyetujui dokumen ini melalui Ajukan Approval Rumpun.');
        }

        // Bagian desain yang tidak dikirim tetap dipertahankan (merge, bukan overwrite).
        $desain = $perangkat->mergeDesain($validated['desain'] ?? []);

        // Dokumen yang sedang review/revisi: perubahan konten membatalkan approval lama.
        $resetReview = in_array($perangkat->workflow_status, [
            PerangkatPembelajaran::WORKFLOW_REVIEW,
            PerangkatPembelajaran::WORKFLOW_REVISI,
        ], true);
        if ($resetReview) {
            $perangkat->reviewAssignments()->delete();
        }

        $profile = $perangkat->curriculumProfile
            ?? app(CurriculumProfileService::class)->resolve($perangkat->school_id, $perangkat->academic_year_id);

        if ($validated['status'] === PerangkatPembelajaran::STATUS_FINAL && $profile?->isKemenag()) {
            $missingKbc = $perangkat->missingKbcFields('final', $desain);
            $formatIssues = $perangkat->kbcFormatIssues($desain);

            if ($missingKbc !== [] || $formatIssues !== []) {
                $labels = collect($missingKbc)->map(fn ($key) => PerangkatPembelajaran::KBC_FIELDS[$key] ?? $key)->implode(', ');
                $messages = array_filter([
                    $missingKbc !== [] ? 'Belum diisi: '.$labels.'.' : null,
                    ...$formatIssues,
                ]);

                return back()->withInput()->with('error', 'Perangkat KBC belum valid. '.implode(' ', $messages));
            }
        }

        $formatType = app(PerangkatFormatResolver::class)->resolve($perangkat->subject);
        $payload = [
            'judul' => $validated['judul'],
            'study_group_id' => $validated['study_group_id'] ?? null,
            'tipe' => $formatType === PerangkatFormatResolver::AGAMA
                ? PerangkatPembelajaran::TIPE_AGAMA
                : PerangkatPembelajaran::TIPE_UMUM,
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

        if ($resetReview) {
            $payload = array_merge($payload, [
                'workflow_status' => PerangkatPembelajaran::WORKFLOW_DRAFT,
                'submitted_at' => null,
                'approved_at' => null,
                'approved_by' => null,
            ]);
        }

        $perangkat->update($payload);

        return back()->with('success', $resetReview
            ? 'Perangkat diperbarui. Approval sebelumnya dibatalkan dan dokumen kembali ke draft untuk diajukan ulang.'
            : 'RPM / perangkat pembelajaran berhasil disimpan.');
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

        $profile = $perangkat->curriculumProfile
            ?? app(CurriculumProfileService::class)->resolve($perangkat->school_id, $perangkat->academic_year_id);
        if ($profile?->isKemenag()) {
            $missingKbc = $perangkat->missingKbcFields('final');
            $formatIssues = $perangkat->kbcFormatIssues();
            if ($missingKbc !== [] || $formatIssues !== []) {
                $labels = collect($missingKbc)->map(fn ($key) => PerangkatPembelajaran::KBC_FIELDS[$key] ?? $key)->implode(', ');
                $messages = array_filter([
                    $missingKbc !== [] ? 'Belum diisi: '.$labels.'.' : null,
                    ...$formatIssues,
                ]);

                return back()->with('error', 'Lengkapi integrasi KBC sebelum mengajukan review. '.implode(' ', $messages));
            }
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
     * Persetujuan KSP setelah approval rumpun. TTD/stempel digital baru boleh
     * dipakai pada cetak setelah approval ini.
     */
    public function approveKsp(Request $request, string $userId, string $id)
    {
        $perangkat = PerangkatPembelajaran::findOrFail($id);
        $this->authorizeView($request, $perangkat);

        abort_unless(
            $this->access->isKurikulumSupervisor($request->user()),
            403,
            'Persetujuan KSP hanya dapat dilakukan Kepala/Wakil Satuan Pendidikan.'
        );

        if ($perangkat->workflow_status !== PerangkatPembelajaran::WORKFLOW_APPROVED
            || $perangkat->status !== PerangkatPembelajaran::STATUS_FINAL) {
            return back()->with('error', 'Perangkat harus disetujui rumpun dan berstatus final sebelum disetujui KSP.');
        }

        if ($perangkat->isKspApproved()) {
            return back()->with('error', 'Perangkat ini sudah disetujui KSP.');
        }

        $perangkat->update([
            'ksp_approved_at' => now(),
            'ksp_approved_by' => $request->user()->id,
            'ksp_note' => $request->input('ksp_note'),
        ]);

        return back()->with('success', 'Perangkat disetujui KSP. TTD dan stempel digital dapat diaktifkan pada halaman preview.');
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
