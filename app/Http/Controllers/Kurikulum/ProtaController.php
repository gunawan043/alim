<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Concerns\AssertsPeriodLock;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AlurTujuanPembelajaran;
use App\Models\Prosem;
use App\Models\Prota;
use App\Models\ProtaItem;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\CurriculumProfileFilterService;
use App\Services\CurriculumProfileService;
use App\Services\KurikulumAccess;
use App\Services\ProtaService;
use Illuminate\Http\Request;

/**
 * PROTA — Program Tahunan.
 *
 * Dibuat dari ATP (TP + alokasi JP) + Pekan Efektif (minggu & JP efektif),
 * jadi guru tidak perlu mengisi ulang pekan efektif atau jumlah JP.
 * Indikator "perlu diperbarui" muncul bila sumber data berubah.
 */
class ProtaController extends Controller
{
    use AssertsPeriodLock;

    public function __construct(
        private readonly KurikulumAccess $access,
        private readonly ProtaService $protaService,
    ) {}

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

        $protaQuery = Prota::query()
            ->with(['subject:id,name,code', 'gradeLevel:id,name,fase', 'teacher:id,name', 'atp:id,total_jp,status', 'curriculumProfile:id,code,name,authority'])
            ->withCount('items')
            ->bySchool($schoolId)
            ->byAcademicYear($academicYearId)
            ->bySemester($semester)
            ->when(! $this->access->isKurikulumTeam($user), function ($q) use ($user, $schoolId, $academicYearId) {
                // Guru: hanya PROTA mapel yang diampu / serumpun.
                $viewable = $this->access->viewableSubjectIds($user, $schoolId, $academicYearId);
                $q->whereIn('subject_id', $viewable ?: ['-']);
            })
            ->when($request->filled('subject_id'), fn ($q) => $q->where('subject_id', $request->subject_id))
            ->orderBy('subject_id');

        $protaList = app(CurriculumProfileFilterService::class)
            ->apply($protaQuery, $curriculumProfile?->id, $profileScope)
            ->get();

        // Sumber ATP yang bisa dijadikan PROTA (mapel yang diampu atau semua untuk editor kurikulum).
        $isKurikulumTeam = $this->access->isKurikulumTeam($user);
        $isKurikulumEditor = $this->access->isKurikulumEditor($user);

        $atpQuery = AlurTujuanPembelajaran::query()
            ->with(['subject:id,name,code', 'gradeLevel:id,name,fase'])
            ->withCount('items')
            ->bySchool($schoolId)
            ->byAcademicYear($academicYearId)
            ->bySemester($semester);
        app(CurriculumProfileFilterService::class)->apply($atpQuery, $curriculumProfile?->id, $profileScope);

        if (! $isKurikulumEditor) {
            $taught = $this->taughtSubjectIds($user, $academicYearId, $schoolId);
            $atpQuery->whereIn('subject_id', $taught ?: ['-']);
        }

        $atpOptions = $atpQuery->orderBy('subject_id')->get();

        $existingAtpIds = Prota::query()
            ->bySchool($schoolId)
            ->byAcademicYear($academicYearId)
            ->bySemester($semester)
            ->whereNotNull('atp_id')
            ->pluck('atp_id')
            ->all();

        // Indikator perlu diperbarui (dihitung dari sumber yang sama).
        $staleIds = [];
        foreach ($protaList as $prota) {
            if ($this->protaService->staleness($prota)['stale']) {
                $staleIds[] = $prota->id;
            }
        }

        $subjects = Subject::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return view('kurikulum.prota.index', compact(
            'userId',
            'academicYears',
            'academicYearId',
            'semester',
            'protaList',
            'atpOptions',
            'existingAtpIds',
            'staleIds',
            'subjects',
            'curriculumProfile',
            'profileScope'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $validated = $request->validate([
            'atp_id' => 'required|exists:alur_tujuan_pembelajaran,id',
            'catatan' => 'nullable|string|max:2000',
        ]);

        $atp = AlurTujuanPembelajaran::with('subject')->findOrFail($validated['atp_id']);
        $this->assertPeriodNotLocked($atp->academic_year_id, 'Dokumen PROTA');

        if (! $this->access->canAuthorSubject($request->user(), $atp->subject_id, $atp->academic_year_id, $schoolId)) {
            abort(403, 'Anda hanya dapat menyusun PROTA untuk mata pelajaran yang diampu.');
        }

        $existing = Prota::query()
            ->where('school_id', $atp->school_id)
            ->where('academic_year_id', $atp->academic_year_id)
            ->where('semester', $atp->semester)
            ->where('subject_id', $atp->subject_id)
            ->when($atp->grade_level_id, fn ($q) => $q->where('grade_level_id', $atp->grade_level_id), fn ($q) => $q->whereNull('grade_level_id'))
            ->first();

        if ($existing) {
            return redirect()
                ->route('user.kurikulum.prota.show', ['userId' => $userId, 'id' => $existing->id])
                ->with('error', 'PROTA untuk ATP ini sudah ada — silakan sinkronkan bila sumber berubah.');
        }

        $prota = $this->protaService->createFromAtp($atp, $request->user());

        if (! empty($validated['catatan'])) {
            $prota->forceFill(['catatan' => $validated['catatan']])->save();
        }

        return redirect()
            ->route('user.kurikulum.prota.show', ['userId' => $userId, 'id' => $prota->id])
            ->with('success', 'PROTA berhasil disusun dari ATP '.($atp->subject?->name ?? '').'.');
    }

    public function show(Request $request, string $userId, string $id)
    {
        $prota = Prota::with([
            'subject', 'gradeLevel', 'academicYear', 'teacher', 'creator', 'atp.subject', 'atp.gradeLevel',
            'items.tujuanPembelajaran.capaianPembelajaran',
        ])->findOrFail($id);

        $this->authorizeView($request, $prota);

        $stale = $this->protaService->staleness($prota);
        $prosem = Prosem::where('prota_id', $prota->id)->first();

        $teachers = $this->teachersForSchool($prota->school_id);

        $itemsTotal = (int) $prota->items->sum('alokasi_jp');
        $printVersion = $this->normalizePrintVersion($request->query('version'));
        $layout = $prota->layoutSettings();
        $previewUrl = route('user.kurikulum.cetak.prota', [
            'userId' => $userId,
            'id' => $prota->id,
            'version' => $printVersion,
            'v' => $prota->updated_at?->timestamp,
        ]);
        $versionUrls = [
            'pemerintah' => route('user.kurikulum.prota.show', ['userId' => $userId, 'id' => $prota->id, 'version' => 'pemerintah']),
            'pondok' => route('user.kurikulum.prota.show', ['userId' => $userId, 'id' => $prota->id, 'version' => 'pondok']),
        ];
        $canManagePrint = $prota->created_by === $request->user()->id
            || $prota->teacher_id === $request->user()->id
            || $this->access->isKurikulumEditor($request->user());

        return view('kurikulum.prota.show', compact(
            'userId',
            'prota',
            'stale',
            'prosem',
            'teachers',
            'itemsTotal',
            'printVersion',
            'layout',
            'previewUrl',
            'versionUrls',
            'canManagePrint'
        ));
    }

    public function update(Request $request, string $userId, string $id)
    {
        $prota = Prota::findOrFail($id);
        $this->authorizeManage($request, $prota);
        $this->assertPeriodNotLocked($prota->academic_year_id, 'Dokumen PROTA');

        $validated = $request->validate([
            'teacher_id' => 'nullable|exists:users,id',
            'status' => 'required|in:draft,final',
            'catatan' => 'nullable|string|max:2000',
        ]);

        $prota->update($validated);

        return back()->with('success', 'PROTA berhasil diperbarui.');
    }

    public function storeItem(Request $request, string $userId, string $id)
    {
        $prota = Prota::findOrFail($id);
        $this->authorizeManage($request, $prota);
        $this->assertPeriodNotLocked($prota->academic_year_id, 'Dokumen PROTA');

        $validated = $request->validate([
            'bab' => 'nullable|string|max:150',
            'materi' => 'required|string|max:1000',
            'alokasi_jp' => 'required|integer|min:0|max:200',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $nextUrutan = (int) $prota->items()->max('urutan') + 1;
        $validated['urutan'] = $nextUrutan;

        $prota->items()->create($validated);
        $this->recalculateTotal($prota);

        return back()->with('success', 'Baris PROTA ditambahkan.');
    }

    public function updateItem(Request $request, string $userId, string $id, string $itemId)
    {
        $prota = Prota::findOrFail($id);
        $this->authorizeManage($request, $prota);
        $this->assertPeriodNotLocked($prota->academic_year_id, 'Dokumen PROTA');

        $item = ProtaItem::where('prota_id', $prota->id)->findOrFail($itemId);

        $validated = $request->validate([
            'bab' => 'nullable|string|max:150',
            'materi' => 'required|string|max:1000',
            'alokasi_jp' => 'required|integer|min:0|max:200',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $item->update($validated);
        $this->recalculateTotal($prota);

        return back()->with('success', 'Baris PROTA diperbarui.');
    }

    public function destroyItem(Request $request, string $userId, string $id, string $itemId)
    {
        $prota = Prota::findOrFail($id);
        $this->authorizeManage($request, $prota);
        $this->assertPeriodNotLocked($prota->academic_year_id, 'Dokumen PROTA');

        ProtaItem::where('prota_id', $prota->id)->findOrFail($itemId)->delete();
        $this->recalculateTotal($prota);

        return back()->with('success', 'Baris PROTA dihapus.');
    }

    /**
     * Sinkronkan PROTA dengan ATP/Pekan Efektif terbaru.
     * rebuild=1 → item dibangun ulang dari ATP.
     */
    public function sync(Request $request, string $userId, string $id)
    {
        $prota = Prota::findOrFail($id);
        $this->authorizeManage($request, $prota);
        $this->assertPeriodNotLocked($prota->academic_year_id, 'Dokumen PROTA');

        $rebuild = $request->boolean('rebuild');
        $this->protaService->sync($prota, $rebuild);

        return back()->with(
            'success',
            $rebuild
                ? 'PROTA disinkronkan & item dibangun ulang dari ATP.'
                : 'PROTA disinkronkan dengan Pekan Efektif/ATP terbaru.'
        );
    }

    public function preview(Request $request, string $userId, string $id)
    {
        return redirect()->route('user.kurikulum.prota.show', [
            'userId' => $userId,
            'id' => $id,
            'version' => $this->normalizePrintVersion($request->query('version')),
        ]);
    }

    public function saveLayout(Request $request, string $userId, string $id)
    {
        $prota = Prota::findOrFail($id);
        $this->authorizeManage($request, $prota);
        $layout = $this->validatedLayout($request);
        $prota->update(['layout_settings' => $layout]);

        return redirect()
            ->route('user.kurikulum.prota.show', [
                'userId' => $userId,
                'id' => $prota->id,
                'version' => $this->normalizePrintVersion($request->input('version')),
            ])
            ->with('success', 'Tata letak PROTA berhasil disimpan.');
    }

    private function validatedLayout(Request $request): array
    {
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
        ]);

        return array_merge(Prota::defaultLayout(), [
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
        ]);
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $prota = Prota::findOrFail($id);
        $this->authorizeManage($request, $prota);
        $this->assertPeriodNotLocked($prota->academic_year_id, 'Dokumen PROTA');

        // PROSEM turunan ikut terhapus (FK cascade) — beri peringatan yang jelas.
        $prota->delete();

        return redirect()
            ->route('user.kurikulum.prota.index', ['userId' => $userId])
            ->with('success', 'PROTA berhasil dihapus.');
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function normalizePrintVersion(?string $version): string
    {
        return match ($version) {
            'resmi', 'pemerintah' => 'pemerintah',
            default => 'pondok',
        };
    }

    private function recalculateTotal(Prota $prota): void
    {
        $prota->forceFill(['total_jp' => $prota->items()->sum('alokasi_jp')])->save();
    }

    /**
     * @return array<int, string>
     */
    private function taughtSubjectIds(User $user, string $academicYearId, ?string $schoolId): array
    {
        return TeachingAssignment::query()
            ->where('teacher_id', $user->id)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'active')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->pluck('subject_id')
            ->unique()
            ->values()
            ->all();
    }

    private function authorizeView(Request $request, Prota $prota): void
    {
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $prota->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $user = $request->user();
        $allowed = $this->access->canViewSubjectDocs($user, $prota->subject_id, $schoolId, $prota->academic_year_id)
            || $prota->created_by === $user->id
            || $prota->teacher_id === $user->id;

        if (! $allowed) {
            abort(403, 'PROTA ini bukan untuk mata pelajaran yang Anda ampu.');
        }
    }

    private function authorizeManage(Request $request, Prota $prota): void
    {
        $this->authorizeView($request, $prota);

        $user = $request->user();
        $isOwner = $prota->created_by === $user->id || $prota->teacher_id === $user->id;

        if (! $isOwner && ! $this->access->isKurikulumEditor($user)) {
            abort(403, 'Hanya penyusun PROTA atau tim kurikulum yang dapat mengubahnya.');
        }
    }

    private function teachersForSchool(?string $schoolId)
    {
        return User::query()
            ->when($schoolId, fn ($q) => $q->whereHas('employments', fn ($q2) => $q2->where('school_id', $schoolId)))
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
