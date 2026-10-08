<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AdminJurnalPembelajaran;
use App\Models\AlurTujuanPembelajaran;
use App\Models\AlurTujuanPembelajaranItem;
use App\Models\GradeLevel;
use App\Models\PerangkatPembelajaran;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\TujuanPembelajaran;
use App\Models\User;
use App\Services\KurikulumAccess;
use App\Services\PekanEfektifService;
use App\Services\TeachingHoursResolver;
use Illuminate\Http\Request;

/**
 * ATP — Alur Tujuan Pembelajaran.
 *
 * Menyusun TP secara sistematis dan membandingkan alokasi JP pada ATP
 * dengan JP efektif tersedia (minggu efektif × JP per minggu dari
 * resolver JP bersama). Sumber "tersedia" adalah Pekan Efektif dari
 * Kalender Pendidikan — tidak ada perhitungan paralel.
 */
class AlurTujuanPembelajaranController extends Controller
{
    public function __construct(
        private readonly KurikulumAccess $access,
        private readonly PekanEfektifService $pekanService,
        private readonly TeachingHoursResolver $hoursResolver,
    ) {}

    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $academicYears = AcademicYear::orderByDesc('start_date')->orderByDesc('created_at')->get();
        $activeAy = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $academicYearId = $request->input('academic_year_id', $activeAy?->id);
        $academicYear = $academicYears->firstWhere('id', $academicYearId);

        $semester = $request->input('semester', $academicYear?->semester === 'genap' ? 'genap' : 'ganjil');
        if (! in_array($semester, ['ganjil', 'genap'], true)) {
            $semester = 'ganjil';
        }

        $atpList = AlurTujuanPembelajaran::query()
            ->with(['subject:id,name,code', 'gradeLevel:id,name,fase', 'teacher:id,name'])
            ->withCount('items')
            ->bySchool($schoolId)
            ->byAcademicYear($academicYearId)
            ->bySemester($semester)
            ->when($request->filled('subject_id'), fn ($q) => $q->where('subject_id', $request->subject_id))
            ->when($request->filled('grade_level_id'), fn ($q) => $q->where('grade_level_id', $request->grade_level_id))
            ->orderBy('subject_id')
            ->get();

        $subjects = Subject::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->when(! $this->access->isKurikulumTeam($request->user()), function ($q) use ($request, $schoolId, $academicYearId) {
                // Guru hanya menyusun ATP untuk mapel yang diampu.
                $taught = TeachingAssignment::query()
                    ->where('teacher_id', $request->user()->id)
                    ->where('academic_year_id', $academicYearId)
                    ->where('status', 'active')
                    ->when($schoolId, fn ($q2) => $q2->where('school_id', $schoolId))
                    ->pluck('subject_id')
                    ->unique()
                    ->values()
                    ->all();

                $q->whereIn('id', $taught ?: ['-']);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $gradeLevels = GradeLevel::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('level')
            ->get(['id', 'name', 'fase']);

        return view('kurikulum.atp.index', compact(
            'userId',
            'academicYears',
            'academicYearId',
            'semester',
            'atpList',
            'subjects',
            'gradeLevels'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'subject_id' => 'required|exists:subjects,id',
            'grade_level_id' => 'nullable|exists:grade_levels,id',
            'teacher_id' => 'nullable|exists:users,id',
            'catatan' => 'nullable|string|max:2000',
        ]);

        $this->authorizeManage($request, $validated['subject_id'], $validated['academic_year_id'], $schoolId);

        $gradeLevel = ! empty($validated['grade_level_id']) ? GradeLevel::find($validated['grade_level_id']) : null;
        $subject = Subject::find($validated['subject_id']);

        $exists = AlurTujuanPembelajaran::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('semester', $validated['semester'])
            ->where('subject_id', $validated['subject_id'])
            ->when($validated['grade_level_id'] ?? null, fn ($q) => $q->where('grade_level_id', $validated['grade_level_id']), fn ($q) => $q->whereNull('grade_level_id'))
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'ATP untuk mapel & jenjang ini sudah ada pada tahun ajaran/semester tersebut.');
        }

        $atp = AlurTujuanPembelajaran::create([
            'school_id' => $schoolId,
            'academic_year_id' => $validated['academic_year_id'],
            'semester' => $validated['semester'],
            'subject_id' => $validated['subject_id'],
            'grade_level_id' => $validated['grade_level_id'] ?? null,
            'fase' => $gradeLevel?->fase,
            'teacher_id' => $validated['teacher_id'] ?? $request->user()->id,
            'status' => AlurTujuanPembelajaran::STATUS_DRAFT,
            'catatan' => $validated['catatan'] ?? null,
            'created_by' => $request->user()->id,
            'total_jp' => 0,
        ]);

        return redirect()
            ->route('user.kurikulum.atp.show', ['userId' => $userId, 'id' => $atp->id])
            ->with('success', 'ATP '.($subject?->name ?? '').' berhasil dibuat. Silakan susun TP.');
    }

    public function show(Request $request, string $userId, string $id)
    {
        $atp = AlurTujuanPembelajaran::with(['subject', 'gradeLevel', 'teacher', 'creator', 'items.tujuanPembelajaran'])
            ->findOrFail($id);

        $this->authorizeView($request, $atp);

        $semesterInt = $atp->semester === 'genap' ? 2 : 1;
        $ringkasan = $atp->school_id
            ? $this->pekanService->summary($atp->school_id, $atp->academic_year_id, $semesterInt)
            : null;

        $mingguEfektif = (int) ($ringkasan['minggu_efektif'] ?? 0);
        $weeklyHours = $atp->subject
            ? $this->hoursResolver->resolve(null, $atp->subject, null, $atp->grade_level_id)
            : 0;

        $jpTersedia = $weeklyHours * $mingguEfektif;
        $jpTerpakai = (int) $atp->items->sum('jp_alokasi');
        $selisih = $jpTerpakai - $jpTersedia;

        $statusAlokasi = match (true) {
            $selisih === 0 => 'pas',
            $selisih > 0 => 'lebih',
            default => 'kurang',
        };

        $usedTpIds = $atp->items->pluck('tujuan_pembelajaran_id')->all();

        $availableTps = TujuanPembelajaran::query()
            ->active()
            ->bySchool($atp->school_id)
            ->byAcademicYear($atp->academic_year_id)
            ->bySemester($atp->semester)
            ->where('subject_id', $atp->subject_id)
            ->when($atp->grade_level_id, function ($q) use ($atp) {
                $q->where(function ($q2) use ($atp) {
                    $q2->where('grade_level_id', $atp->grade_level_id)->orWhereNull('grade_level_id');
                });
            })
            ->whereNotIn('id', $usedTpIds ?: ['-'])
            ->orderBy('urutan')
            ->orderBy('kode_tp')
            ->get();

        $perangkatCount = PerangkatPembelajaran::where('atp_id', $atp->id)->count();

        // Realisasi dari jurnal pertemuan (Pelaksanaan Pembelajaran).
        $realisasiByTp = AdminJurnalPembelajaran::query()
            ->whereIn('tujuan_pembelajaran_id', $atp->items->pluck('tujuan_pembelajaran_id')->filter()->values())
            ->selectRaw('tujuan_pembelajaran_id, COUNT(*) as total')
            ->groupBy('tujuan_pembelajaran_id')
            ->pluck('total', 'tujuan_pembelajaran_id');

        $teachers = User::query()
            ->when($schoolId ?? $atp->school_id, function ($q, $sid) {
                $q->whereHas('employments', fn ($q2) => $q2->where('school_id', $sid));
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('kurikulum.atp.show', compact(
            'userId',
            'atp',
            'ringkasan',
            'mingguEfektif',
            'weeklyHours',
            'jpTersedia',
            'jpTerpakai',
            'selisih',
            'statusAlokasi',
            'availableTps',
            'perangkatCount',
            'realisasiByTp',
            'teachers'
        ));
    }

    public function update(Request $request, string $userId, string $id)
    {
        $atp = AlurTujuanPembelajaran::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');
        $this->authorizeManage($request, $atp->subject_id, $atp->academic_year_id, $schoolId);

        $validated = $request->validate([
            'teacher_id' => 'nullable|exists:users,id',
            'status' => 'required|in:draft,published',
            'catatan' => 'nullable|string|max:2000',
        ]);

        $atp->update($validated);
        $atp->recalculateTotal();

        return back()->with('success', 'ATP berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $atp = AlurTujuanPembelajaran::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');
        $this->authorizeManage($request, $atp->subject_id, $atp->academic_year_id, $schoolId);

        if (PerangkatPembelajaran::where('atp_id', $atp->id)->exists()) {
            return back()->with('error', 'ATP masih dipakai perangkat pembelajaran. Hapus/ubah perangkat terlebih dahulu.');
        }

        $atp->delete();

        return redirect()
            ->route('user.kurikulum.atp.index', ['userId' => $userId])
            ->with('success', 'ATP berhasil dihapus.');
    }

    // ── Item ATP ─────────────────────────────────────────────────────

    public function storeItem(Request $request, string $userId, string $id)
    {
        $atp = AlurTujuanPembelajaran::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');
        $this->authorizeManage($request, $atp->subject_id, $atp->academic_year_id, $schoolId);

        $validated = $request->validate([
            'tujuan_pembelajaran_id' => 'required|exists:tujuan_pembelajaran,id',
            'jp_alokasi' => 'nullable|integer|min:0|max:100',
            'catatan' => 'nullable|string|max:255',
        ]);

        $tp = TujuanPembelajaran::findOrFail($validated['tujuan_pembelajaran_id']);

        if ($tp->subject_id !== $atp->subject_id
            || $tp->academic_year_id !== $atp->academic_year_id
            || $tp->semester !== $atp->semester) {
            return back()->with('error', 'TP tidak sesuai dengan mapel/tahun ajaran/semester ATP ini.');
        }

        if (AlurTujuanPembelajaranItem::where('alur_tujuan_pembelajaran_id', $atp->id)
            ->where('tujuan_pembelajaran_id', $tp->id)
            ->exists()) {
            return back()->with('error', 'TP sudah ada di ATP ini.');
        }

        $nextUrutan = (int) AlurTujuanPembelajaranItem::where('alur_tujuan_pembelajaran_id', $atp->id)->max('urutan') + 1;

        AlurTujuanPembelajaranItem::create([
            'alur_tujuan_pembelajaran_id' => $atp->id,
            'tujuan_pembelajaran_id' => $tp->id,
            'urutan' => $nextUrutan,
            'jp_alokasi' => $validated['jp_alokasi'] ?? ($tp->alokasi_waktu ?: 2),
            'catatan' => $validated['catatan'] ?? null,
        ]);

        $atp->recalculateTotal();

        return back()->with('success', 'TP ditambahkan ke ATP.');
    }

    public function updateItem(Request $request, string $userId, string $id, string $itemId)
    {
        $atp = AlurTujuanPembelajaran::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');
        $this->authorizeManage($request, $atp->subject_id, $atp->academic_year_id, $schoolId);

        $item = AlurTujuanPembelajaranItem::where('alur_tujuan_pembelajaran_id', $atp->id)->findOrFail($itemId);

        $validated = $request->validate([
            'jp_alokasi' => 'required|integer|min:0|max:100',
            'catatan' => 'nullable|string|max:255',
        ]);

        $item->update($validated);
        $atp->recalculateTotal();

        return back()->with('success', 'Alokasi JP TP diperbarui.');
    }

    public function destroyItem(Request $request, string $userId, string $id, string $itemId)
    {
        $atp = AlurTujuanPembelajaran::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');
        $this->authorizeManage($request, $atp->subject_id, $atp->academic_year_id, $schoolId);

        $item = AlurTujuanPembelajaranItem::where('alur_tujuan_pembelajaran_id', $atp->id)->findOrFail($itemId);
        $item->delete();

        $atp->recalculateTotal();

        return back()->with('success', 'TP dikeluarkan dari ATP.');
    }

    public function moveItem(Request $request, string $userId, string $id, string $itemId)
    {
        $atp = AlurTujuanPembelajaran::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');
        $this->authorizeManage($request, $atp->subject_id, $atp->academic_year_id, $schoolId);

        $direction = $request->input('direction') === 'up' ? 'up' : 'down';

        $items = AlurTujuanPembelajaranItem::where('alur_tujuan_pembelajaran_id', $atp->id)
            ->orderBy('urutan')
            ->orderBy('created_at')
            ->get();

        $index = $items->search(fn ($item) => $item->id === $itemId);

        if ($index === false) {
            return back();
        }

        $swapWith = $direction === 'up' ? $index - 1 : $index + 1;

        if ($items->has($swapWith)) {
            $ordered = $items->pluck('id')->all();
            [$ordered[$index], $ordered[$swapWith]] = [$ordered[$swapWith], $ordered[$index]];

            foreach ($ordered as $position => $orderedId) {
                AlurTujuanPembelajaranItem::where('id', $orderedId)->update(['urutan' => $position + 1]);
            }
        }

        return back()->with('success', 'Urutan ATP diperbarui.');
    }

    // ── Authorization ────────────────────────────────────────────────

    private function authorizeView(Request $request, AlurTujuanPembelajaran $atp): void
    {
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $atp->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }
    }

    private function authorizeManage(Request $request, string $subjectId, string $academicYearId, ?string $schoolId): void
    {
        if (! $this->access->canManageSubject($request->user(), $subjectId, $academicYearId, $schoolId)) {
            abort(403, 'Anda tidak berwenang mengelola ATP untuk mata pelajaran ini.');
        }
    }
}
