<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Prosem;
use App\Models\ProsemItem;
use App\Models\Prota;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\KurikulumAccess;
use App\Services\ProsemService;
use Illuminate\Http\Request;

/**
 * PROSEM — Program Semester.
 *
 * Distribusi PROTA/ATP ke bulan & pekan efektif dari Kalender Pendidikan
 * (via Pekan Efektif). Minggu libur tidak dipakai; pekan ujian/kegiatan
 * ditandai otomatis pada keterangan.
 */
class ProsemController extends Controller
{
    public function __construct(
        private readonly KurikulumAccess $access,
        private readonly ProsemService $prosemService,
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

        $prosemList = Prosem::query()
            ->with(['subject:id,name,code', 'gradeLevel:id,name,fase', 'teacher:id,name', 'prota:id,total_jp,synced_at'])
            ->withCount('items')
            ->bySchool($schoolId)
            ->byAcademicYear($academicYearId)
            ->bySemester($semester)
            ->when($request->filled('subject_id'), fn ($q) => $q->where('subject_id', $request->subject_id))
            ->orderBy('subject_id')
            ->get();

        // PROTA yang belum punya PROSEM (mapel diampu atau semua untuk tim kurikulum).
        $isKurikulumTeam = $this->access->isKurikulumTeam($user);

        $protaQuery = Prota::query()
            ->with(['subject:id,name,code', 'gradeLevel:id,name,fase'])
            ->withCount('items')
            ->bySchool($schoolId)
            ->byAcademicYear($academicYearId)
            ->bySemester($semester)
            ->whereDoesntHave('prosem');

        if (! $isKurikulumTeam) {
            $taught = $this->taughtSubjectIds($user, $academicYearId, $schoolId);
            $protaQuery->whereIn('subject_id', $taught ?: ['-']);
        }

        $protaOptions = $protaQuery->orderBy('subject_id')->get();

        $staleIds = [];
        foreach ($prosemList as $prosem) {
            if ($this->prosemService->staleness($prosem)['stale']) {
                $staleIds[] = $prosem->id;
            }
        }

        return view('kurikulum.prosem.index', compact(
            'userId',
            'academicYears',
            'academicYearId',
            'semester',
            'prosemList',
            'protaOptions',
            'staleIds'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $validated = $request->validate([
            'prota_id' => 'required|exists:prota,id',
            'catatan' => 'nullable|string|max:2000',
        ]);

        $prota = Prota::with('subject')->findOrFail($validated['prota_id']);

        if (! $this->access->canManageSubject($request->user(), $prota->subject_id, $prota->academic_year_id, $schoolId)) {
            abort(403, 'Anda tidak berwenang menyusun PROSEM untuk mata pelajaran ini.');
        }

        $existing = Prosem::where('prota_id', $prota->id)->first();

        if ($existing) {
            return redirect()
                ->route('user.kurikulum.prosem.show', ['userId' => $userId, 'id' => $existing->id])
                ->with('error', 'PROSEM untuk PROTA ini sudah ada — silakan sinkronkan bila sumber berubah.');
        }

        $prosem = $this->prosemService->createFromProta($prota, $request->user());

        if (! empty($validated['catatan'])) {
            $prosem->forceFill(['catatan' => $validated['catatan']])->save();
        }

        return redirect()
            ->route('user.kurikulum.prosem.show', ['userId' => $userId, 'id' => $prosem->id])
            ->with('success', 'PROSEM berhasil disusun dari PROTA '.($prota->subject?->name ?? '').'.');
    }

    public function show(Request $request, string $userId, string $id)
    {
        $prosem = Prosem::with([
            'subject', 'gradeLevel', 'academicYear', 'teacher', 'creator',
            'prota.subject', 'prota.gradeLevel', 'prota.items.tujuanPembelajaran',
            'items.tujuanPembelajaran', 'items.protaItem',
        ])->findOrFail($id);

        $this->authorizeView($request, $prosem);

        $stale = $this->prosemService->staleness($prosem);

        // Peta pekan dari Pekan Efektif (satu sumber — tanpa kalender kedua).
        $weeks = $this->prosemService->pekanEfektif($prosem)->keyBy('minggu_ke');
        $effectiveWeeks = $weeks->filter(fn ($p) => (int) $p->hari_efektif > 0)->count();
        $totalJp = (int) $prosem->items->sum('jp');
        $overflowJp = $prosem->items
            ->filter(fn (ProsemItem $i) => str_contains((string) $i->keterangan, 'melebihi pekan efektif'))
            ->count();

        $teachers = $this->teachersForSchool($prosem->school_id);

        return view('kurikulum.prosem.show', compact(
            'userId',
            'prosem',
            'stale',
            'weeks',
            'effectiveWeeks',
            'totalJp',
            'overflowJp',
            'teachers'
        ));
    }

    public function update(Request $request, string $userId, string $id)
    {
        $prosem = Prosem::findOrFail($id);
        $this->authorizeManage($request, $prosem);

        $validated = $request->validate([
            'teacher_id' => 'nullable|exists:users,id',
            'status' => 'required|in:draft,final',
            'catatan' => 'nullable|string|max:2000',
        ]);

        $prosem->update($validated);

        return back()->with('success', 'PROSEM berhasil diperbarui.');
    }

    /**
     * Sinkronkan distribusi PROSEM dengan PROTA & Pekan Efektif terbaru.
     * regenerate=1 (default) → distribusi pekan dihitung ulang.
     */
    public function sync(Request $request, string $userId, string $id)
    {
        $prosem = Prosem::findOrFail($id);
        $this->authorizeManage($request, $prosem);

        $this->prosemService->sync($prosem, $request->boolean('regenerate', true));

        return back()->with('success', 'PROSEM disinkronkan dengan PROTA & Pekan Efektif terbaru.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $prosem = Prosem::findOrFail($id);
        $this->authorizeManage($request, $prosem);

        $prosem->delete();

        return redirect()
            ->route('user.kurikulum.prosem.index', ['userId' => $userId])
            ->with('success', 'PROSEM berhasil dihapus.');
    }

    // ── Helpers ──────────────────────────────────────────────────────

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

    private function authorizeView(Request $request, Prosem $prosem): void
    {
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $prosem->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $user = $request->user();
        $allowed = $this->access->isKurikulumTeam($user)
            || $this->access->teachesSubject($user, $prosem->subject_id, $prosem->academic_year_id, $schoolId)
            || $prosem->created_by === $user->id
            || $prosem->teacher_id === $user->id;

        if (! $allowed) {
            abort(403, 'PROSEM ini bukan untuk mata pelajaran yang Anda ampu.');
        }
    }

    private function authorizeManage(Request $request, Prosem $prosem): void
    {
        $this->authorizeView($request, $prosem);

        $user = $request->user();
        $isOwner = $prosem->created_by === $user->id || $prosem->teacher_id === $user->id;

        if (! $isOwner && ! $this->access->isKurikulumTeam($user)) {
            abort(403, 'Hanya penyusun PROSEM atau tim kurikulum yang dapat mengubahnya.');
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
