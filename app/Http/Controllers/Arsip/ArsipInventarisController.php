<?php

namespace App\Http\Controllers\Arsip;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ArchiveDocument;
use App\Models\User;
use App\Services\AcademicPeriodService;
use App\Services\Arsip\ArsipInventoryService;
use App\Services\Arsip\ArsipRegistry;
use App\Services\Arsip\DocumentArchiveService;
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

        $documents = $this->inventory->documents($selectedAcademicYearId, $schoolId)
            ->map(function (ArchiveDocument $document) {
                $document->setAttribute('file_available', $this->documents->exists($document));
                $document->setAttribute('file_verified', $document->file_available && $this->documents->verify($document));

                return $document;
            });

        $canManage = $this->canManage($request->user());
        $lockBlockReason = $selectedAy ? $this->periods->lockBlockReason($selectedAy) : null;
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
            'canManage',
            'lockBlockReason',
            'unsupportedModules',
        ));
    }

    public function lock(Request $request, string $userId, string $id)
    {
        abort_unless($this->canManage($request->user()), 403, 'Penutupan periode hanya untuk kepala/wakil satuan pendidikan.');

        $academicYear = AcademicYear::findOrFail($id);

        $validated = $request->validate([
            'lock_notes' => 'nullable|string|max:500',
        ]);

        $reason = $this->periods->lockBlockReason($academicYear);

        if ($reason) {
            return back()->with('error', $reason);
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
