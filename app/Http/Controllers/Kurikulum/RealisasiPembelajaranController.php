<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\AdminJurnalPembelajaran;
use App\Models\AcademicYear;
use App\Models\AlurTujuanPembelajaran;
use App\Models\NilaiFormatif;
use App\Models\NilaiSumatif;
use App\Models\TeacherAdminBook;
use App\Services\KurikulumAccess;
use Illuminate\Http\Request;

/**
 * Realisasi Pembelajaran — jembatan Pelaksanaan → Nilai.
 *
 * Menghitung realisasi TP/ATP dari jurnal pertemuan (wizard2 Buku Administrasi),
 * status asesmen formatif/sumatif per buku, serta tautan ke Buku Administrasi →
 * Leger → Rapor. Semua dari sumber data yang sama (ATP/PROSEM/RPM → Jurnal).
 */
class RealisasiPembelajaranController extends Controller
{
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

        $isTeam = $this->access->isKurikulumTeam($user);

        // Buku administrasi guru (KBM) — dasar jurnal & asesmen.
        $books = TeacherAdminBook::with([
            'subject:id,name,code',
            'studyGroup:id,name,grade_level_id',
            'studyGroup.gradeLevel:id,name,fase',
            'teacher:id,name',
            'academicYear:id,name',
        ])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->where('is_active', true)
            ->when(! $isTeam, fn ($q) => $q->where('teacher_id', $user->id))
            ->orderBy('subject_id')
            ->get();

        // ATP acuan per mapel + jenjang.
        $atps = AlurTujuanPembelajaran::with(['items:id,alur_tujuan_pembelajaran_id,tujuan_pembelajaran_id'])
            ->bySchool($schoolId)
            ->byAcademicYear($academicYearId)
            ->bySemester($semester)
            ->get()
            ->keyBy(fn ($atp) => $atp->subject_id.'|'.($atp->grade_level_id ?? 'null'));

        $bookIds = $books->pluck('id');

        $journals = AdminJurnalPembelajaran::query()
            ->whereIn('admin_book_id', $bookIds)
            ->get(['id', 'admin_book_id', 'tujuan_pembelajaran_id', 'prosem_item_id'])
            ->groupBy('admin_book_id');

        $formatifCounts = NilaiFormatif::query()
            ->whereIn('admin_book_id', $bookIds)
            ->selectRaw('admin_book_id, COUNT(*) as total')
            ->groupBy('admin_book_id')
            ->pluck('total', 'admin_book_id');

        $sumatifCounts = NilaiSumatif::query()
            ->whereIn('admin_book_id', $bookIds)
            ->selectRaw('admin_book_id, COUNT(*) as total')
            ->groupBy('admin_book_id')
            ->pluck('total', 'admin_book_id');

        // ── Baris per buku administrasi ───────────────────────────────
        $rows = $books->map(function (TeacherAdminBook $book) use ($atps, $journals, $formatifCounts, $sumatifCounts) {
            $gradeId = $book->studyGroup?->grade_level_id;
            $atp = $atps->get($book->subject_id.'|'.($gradeId ?? 'null'));
            $planned = (int) ($atp?->items->count() ?? 0);

            $bookJournals = $journals->get($book->id, collect());
            $realized = $bookJournals->pluck('tujuan_pembelajaran_id')->filter()->unique()->count();
            $meetings = $bookJournals->count();

            return (object) [
                'book' => $book,
                'atp' => $atp,
                'planned_tp' => $planned,
                'realized_tp' => $realized,
                'meetings' => $meetings,
                'progress' => $planned > 0 ? (int) round($realized / $planned * 100) : 0,
                'formatif' => (int) ($formatifCounts[$book->id] ?? 0),
                'sumatif' => (int) ($sumatifCounts[$book->id] ?? 0),
            ];
        });

        // ── Agregat per ATP (kelas-kelas yang memakai ATP sama) ───────
        $atpRows = $atps->map(function (AlurTujuanPembelajaran $atp) use ($books, $journals) {
            $matchingBooks = $books->filter(function (TeacherAdminBook $book) use ($atp) {
                return $book->subject_id === $atp->subject_id
                    && ($book->studyGroup?->grade_level_id ?? null) === $atp->grade_level_id;
            });

            $ids = $matchingBooks->pluck('id')->all();
            $atpJournals = $journals
                ->filter(fn ($group, $key) => in_array($key, $ids, true))
                ->flatten(1);

            $realizedTp = $atpJournals->pluck('tujuan_pembelajaran_id')->filter()->unique()->count();
            $plannedTp = (int) $atp->items->count();

            return (object) [
                'atp' => $atp,
                'classes' => $matchingBooks->count(),
                'planned_tp' => $plannedTp,
                'realized_tp' => $realizedTp,
                'meetings' => $atpJournals->count(),
                'progress' => $plannedTp > 0 ? (int) round($realizedTp / $plannedTp * 100) : 0,
            ];
        })->filter(fn ($row) => $row->classes > 0)->values();

        $stats = [
            'books' => $rows->count(),
            'meetings' => $rows->sum('meetings'),
            'realized_tp' => $rows->sum('realized_tp'),
            'planned_tp' => $rows->sum('planned_tp'),
        ];
        $stats['progress'] = $stats['planned_tp'] > 0 ? (int) round($stats['realized_tp'] / $stats['planned_tp'] * 100) : 0;

        return view('kurikulum.realisasi.index', compact(
            'userId',
            'academicYears',
            'academicYearId',
            'semester',
            'rows',
            'atpRows',
            'stats',
            'isTeam'
        ));
    }
}
