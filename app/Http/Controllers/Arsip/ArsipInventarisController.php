<?php

namespace App\Http\Controllers\Arsip;

use App\Http\Controllers\Controller;
use App\Jobs\ArsipkanLegerRombelJob;
use App\Jobs\ArsipkanRaporRombelJob;
use App\Models\AcademicYear;
use App\Models\ArchiveDocument;
use App\Models\StudyGroup;
use App\Models\User;
use App\Services\AcademicPeriodService;
use App\Services\Arsip\ArsipInventoryService;
use App\Services\Arsip\ArsipRegistry;
use App\Services\Arsip\DocumentArchiveService;
use App\Services\Arsip\PeriodClosureChecklistService;
use App\Services\KurikulumAccess;
use App\Support\UserJabatan;
use Illuminate\Http\Request;

/**
 * SISTEM INVENTARISASI ARSIP — halaman sentral arsip per tahun ajaran:
 * status periode, kelengkapan data per modul, penutupan/pembukaan periode,
 * dan dokumen arsip (snapshot) beserta verifikasi hash.
 */
class ArsipInventarisController extends Controller
{
    public function __construct(
        private readonly AcademicPeriodService $periods,
        private readonly ArsipInventoryService $inventory,
        private readonly ArsipRegistry $registry,
        private readonly DocumentArchiveService $documents,
    ) {}

    public function index(Request $request, string $userId)
    {
        abort_unless($this->canView($request->user()), 403, 'Arsip hanya untuk pengguna berwenang.');

        $schoolId = $request->attributes->get('schoolContextId');
        $academicYears = $this->inventory->academicYears();
        $activeAcademicYearId = $academicYears->firstWhere('is_active', true)?->id;

        $selectedAcademicYearId = $request->filled('academic_year_id')
            ? $request->academic_year_id
            : ($activeAcademicYearId ?? $academicYears->first()?->id);

        $selectedAy = $academicYears->firstWhere('id', $selectedAcademicYearId);
        $status = $selectedAy ? $this->periods->statusOf($selectedAy) : null;
        $statusLabel = $status ? $this->periods->statusLabel($status) : null;

        $modules = $this->inventory->summary($selectedAcademicYearId, $schoolId);
        $overview = $this->inventory->overview($selectedAcademicYearId, $schoolId);

        $documentFilters = [
            'type' => in_array($request->input('doc_type'), ['snapshot_nilai', 'rapor', 'leger'], true) ? $request->input('doc_type') : null,
            'jenis' => in_array($request->input('doc_jenis'), ['sts', 'sas'], true) ? $request->input('doc_jenis') : null,
            'language' => in_array($request->input('doc_language'), ['id', 'ar'], true) ? $request->input('doc_language') : null,
        ];

        $documents = $this->inventory->documents($selectedAcademicYearId, $schoolId, 50, $documentFilters)
            ->map(function (ArchiveDocument $document) {
                $document->setAttribute('file_available', $this->documents->exists($document));
                $document->setAttribute('file_verified', $document->file_available && $this->documents->verify($document));

                return $document;
            });

        // Rombel pada TA terpilih (untuk aksi arsipkan rapor).
        $studyGroups = $selectedAcademicYearId
            ? StudyGroup::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where('academic_year_id', $selectedAcademicYearId)
                ->orderBy('name')
                ->get(['id', 'name', 'grade_level_id'])
            : collect();

        $canManage = $this->canManage($request->user());
        $lockBlockReason = $selectedAy ? $this->periods->lockBlockReason($selectedAy) : null;
        $closure = app(PeriodClosureChecklistService::class)->evaluate($selectedAcademicYearId, $schoolId);

        if (! $lockBlockReason && ($closure['errors'] ?? 0) > 0) {
            $lockBlockReason = 'Kelengkapan periode belum memenuhi syarat: '.$closure['errors'].' item wajib belum tersedia.';
        }

        $unsupportedModules = $this->registry->unsupportedModules();

        return view('arsip.index', compact(
            'userId',
            'academicYears',
            'activeAcademicYearId',
            'selectedAcademicYearId',
            'selectedAy',
            'status',
            'statusLabel',
            'modules',
            'overview',
            'documents',
            'documentFilters',
            'studyGroups',
            'canManage',
            'lockBlockReason',
            'closure',
            'unsupportedModules',
        ));
    }

    public function lock(Request $request, string $userId, string $id)
    {
        abort_unless($this->canManage($request->user()), 403, 'Penutupan periode hanya untuk kepala/wakil satuan pendidikan.');

        $academicYear = AcademicYear::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        $validated = $request->validate([
            'lock_notes' => 'nullable|string|max:500',
            'confirm_warnings' => 'boolean',
        ]);

        $reason = $this->periods->lockBlockReason($academicYear);

        if ($reason) {
            return back()->with('error', $reason);
        }

        $closure = app(PeriodClosureChecklistService::class)->evaluate($academicYear->id, $schoolId);

        if (($closure['errors'] ?? 0) > 0) {
            return back()->with('error', 'Periode belum dapat ditutup: '.$closure['errors'].' item wajib belum tersedia.');
        }

        if (($closure['warnings'] ?? 0) > 0 && ! $request->boolean('confirm_warnings')) {
            return back()->with('error', 'Tinjau dan centang konfirmasi peringatan kelengkapan arsip sebelum menutup periode.');
        }

        $this->periods->lock($academicYear, $request->user(), $validated['lock_notes'] ?? null);

        return back()->with('success', "Tahun ajaran {$academicYear->name} ditutup dan dikunci sebagai arsip. Perubahan data periode ini diblokir sampai kunci dibuka.");
    }

    public function unlock(Request $request, string $userId, string $id)
    {
        abort_unless($this->canManage($request->user()), 403, 'Pembukaan kunci periode hanya untuk kepala/wakil satuan pendidikan.');

        $academicYear = AcademicYear::findOrFail($id);

        if (! $academicYear->isLocked()) {
            return back()->with('error', 'Tahun ajaran ini belum dikunci.');
        }

        $validated = $request->validate([
            'lock_notes' => 'nullable|string|max:500',
        ]);

        $this->periods->unlock($academicYear, $request->user(), $validated['lock_notes'] ?? null);

        return back()->with('success', "Kunci arsip tahun ajaran {$academicYear->name} dibuka (tercatat pada audit). Data baru dapat diubah bila tahun ajaran diaktifkan kembali.");
    }

    public function arsipkanRapor(Request $request, string $userId, string $id)
    {
        abort_unless($this->canView($request->user()), 403, 'Arsip hanya untuk pengguna berwenang.');

        $academicYear = AcademicYear::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        $validated = $request->validate([
            'study_group_id' => 'required|exists:study_groups,id',
            'semester' => 'required|in:ganjil,genap',
            'jenis' => 'nullable|in:sts,sas,all',
            'bahasa' => 'nullable|in:id,ar,all',
        ]);

        $studyGroup = StudyGroup::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($validated['study_group_id']);

        if ((string) $studyGroup->academic_year_id !== (string) $academicYear->id) {
            return back()->with('error', 'Rombel yang dipilih bukan milik tahun ajaran tersebut.');
        }

        $semester = $validated['semester'];
        $jenis = $validated['jenis'] ?? 'sas';
        $bahasa = $validated['bahasa'] ?? 'all';

        $contextQuery = fn () => ArchiveDocument::query()
            ->where('type', 'rapor')
            ->where('academic_year_id', $academicYear->id)
            ->where('study_group_id', $studyGroup->id)
            ->where('semester', $semester);

        $before = $contextQuery()->count();

        ArsipkanRaporRombelJob::dispatch(
            $studyGroup->id,
            $academicYear->id,
            $semester,
            $jenis,
            $request->user()?->id,
            $bahasa,
        );

        $created = $contextQuery()->count() - $before;

        $variantLabel = [];
        $variantLabel[] = $jenis === 'all' ? 'STS & SAS' : strtoupper($jenis);
        $variantLabel[] = $bahasa === 'all' ? 'Indonesia & Arab' : ($bahasa === 'ar' ? 'Arab' : 'Indonesia');

        return back()->with(
            'success',
            $created > 0
                ? 'Rapor diarsipkan: '.$created.' dokumen PDF baru untuk '.$studyGroup->name
                    .' ('.implode(' · ', $variantLabel).', Semester '.ucfirst($semester).').'
                : "Tidak ada dokumen baru — seluruh varian rapor {$studyGroup->name} pada semester tersebut sudah terarsip."
        );
    }

    public function arsipkanLeger(Request $request, string $userId, string $id)
    {
        abort_unless($this->canView($request->user()), 403, 'Arsip hanya untuk pengguna berwenang.');

        $academicYear = AcademicYear::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        $validated = $request->validate([
            'study_group_id' => 'required|exists:study_groups,id',
            'semester' => 'required|in:ganjil,genap',
            'jenis' => 'nullable|in:sts,sas',
        ]);

        $studyGroup = StudyGroup::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($validated['study_group_id']);

        if ((string) $studyGroup->academic_year_id !== (string) $academicYear->id) {
            return back()->with('error', 'Rombel yang dipilih bukan milik tahun ajaran tersebut.');
        }

        $semester = $validated['semester'];
        $jenis = $validated['jenis'] ?? 'sas';

        $contextQuery = fn () => ArchiveDocument::query()
            ->where('type', 'leger')
            ->where('academic_year_id', $academicYear->id)
            ->where('study_group_id', $studyGroup->id)
            ->where('semester', $semester)
            ->where('jenis', $jenis);

        $before = $contextQuery()->count();

        ArsipkanLegerRombelJob::dispatch(
            $studyGroup->id,
            $academicYear->id,
            $semester,
            $jenis,
            $request->user()?->id,
        );

        $created = $contextQuery()->count() - $before;

        return back()->with(
            'success',
            $created > 0
                ? 'Leger diarsipkan: PDF '.strtoupper($jenis).' untuk '.$studyGroup->name.' (Semester '.ucfirst($semester).').'
                : "Leger {$studyGroup->name} (".strtoupper($jenis).', Semester '.ucfirst($semester).') sudah terarsip.'
        );
    }

    public function download(Request $request, string $userId, string $documentId)
    {
        abort_unless($this->canView($request->user()), 403, 'Arsip hanya untuk pengguna berwenang.');

        $document = ArchiveDocument::findOrFail($documentId);

        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $document->school_id && (string) $document->school_id !== (string) $schoolId) {
            abort(403, 'Dokumen arsip bukan milik satuan pendidikan Anda.');
        }

        return $this->documents->download($document);
    }

    private function canView(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if (canPermission('super-admin-only')) {
            return true;
        }

        if (app(KurikulumAccess::class)->isKurikulumTeam($user)) {
            return true;
        }

        return UserJabatan::hasAny($user, [
            'kepala tata usaha',
            'tata usaha',
            'staf tata usaha',
            'kepala tu sekolah',
            'tu sekolah',
            'staf tu sekolah',
            'tata usaha sekolah',
        ]);
    }

    private function canManage(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if (canPermission('super-admin-only')) {
            return true;
        }

        return UserJabatan::hasAny($user, [
            'kepala satuan pendidikan',
            'wakil kepala satuan pendidikan',
            'kepala sekolah',
            'wakil kepala sekolah',
        ]);
    }
}
