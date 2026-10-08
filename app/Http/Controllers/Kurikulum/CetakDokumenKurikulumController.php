<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AlurTujuanPembelajaran;
use App\Models\Kaldik;
use App\Models\PekanEfektif;
use App\Models\PerangkatPembelajaran;
use App\Models\Prosem;
use App\Models\Prota;
use App\Models\School;
use App\Services\KurikulumAccess;
use App\Services\PekanEfektifService;
use App\Services\ProsemService;
use App\Services\TeachingHoursResolver;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;

/**
 * Cetak PDF dokumen kurikulum — template A4 resmi (bukan screenshot halaman web):
 *  Kalender Pendidikan, Pekan Efektif, ATP, PROTA, PROSEM, RPM.
 *
 * Semua dokumen memakai identitas satuan pendidikan dari data sekolah,
 * header konsisten, tanda tangan, dan nomor halaman.
 */
class CetakDokumenKurikulumController extends Controller
{
    public function __construct(
        private readonly KurikulumAccess $access,
        private readonly PekanEfektifService $pekanService,
        private readonly ProsemService $prosemService,
        private readonly TeachingHoursResolver $hoursResolver,
    ) {}

    // ── 1. Kalender Pendidikan ───────────────────────────────────────

    public function kaldik(Request $request, string $userId)
    {
        $school = $this->school($request);
        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $academicYearId = $request->input('academic_year_id', $academicYears->firstWhere('is_active', true)?->id ?? $academicYears->first()?->id);
        $academicYear = $academicYears->firstWhere('id', $academicYearId);

        $events = Kaldik::with(['workUnit', 'academicYear'])
            ->active()
            ->byAcademicYear($academicYearId)
            ->orderBy('start_date')
            ->get();

        $html = view('cetak.kaldik', compact('school', 'academicYear', 'events'))->render();

        return $this->pdf($html, 'Kalender-Pendidikan-'.($academicYear?->name ?? ''), 'landscape');
    }

    // ── 2. Pekan Efektif ─────────────────────────────────────────────

    public function pekanEfektif(Request $request, string $userId)
    {
        $school = $this->school($request, required: true);
        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $academicYearId = $request->input('academic_year_id', $academicYears->firstWhere('is_active', true)?->id ?? $academicYears->first()?->id);
        $academicYear = $academicYears->firstWhere('id', $academicYearId);

        $semester = (int) $request->input('semester', $academicYear?->semester === 'genap' ? 2 : 1);
        if (! in_array($semester, [1, 2], true)) {
            $semester = 1;
        }

        $weeks = PekanEfektif::with('academicYear')
            ->bySchool($school->id)
            ->byAcademicYear($academicYearId)
            ->bySemester($semester)
            ->orderBy('minggu_ke')
            ->get();

        $ringkasan = $this->pekanService->summary($school->id, $academicYearId, $semester);

        $html = view('cetak.pekan-efektif', compact('school', 'academicYear', 'semester', 'weeks', 'ringkasan'))->render();

        return $this->pdf($html, 'Pekan-Efektif-Semester-'.$semester.'-'.($academicYear?->name ?? ''), 'landscape');
    }

    // ── 3. ATP ───────────────────────────────────────────────────────

    public function atp(Request $request, string $userId, string $id)
    {
        $atp = AlurTujuanPembelajaran::with([
            'subject', 'gradeLevel', 'academicYear', 'teacher', 'school',
            'items.tujuanPembelajaran.capaianPembelajaran',
        ])->findOrFail($id);

        $this->authorizeSchool($request, $atp->school_id);

        $semesterInt = $atp->semester === 'genap' ? 2 : 1;
        $summary = $this->pekanService->summary($atp->school_id, $atp->academic_year_id, $semesterInt);
        $mingguEfektif = (int) ($summary['minggu_efektif'] ?? 0);
        $weeklyHours = $this->hoursResolver->resolve(null, $atp->subject, null, $atp->grade_level_id);
        $jpEfektif = $weeklyHours * $mingguEfektif;
        $jpTerpakai = (int) $atp->items->sum('jp_alokasi');

        $school = School::find($atp->school_id);

        $html = view('cetak.atp', compact('school', 'atp', 'mingguEfektif', 'weeklyHours', 'jpEfektif', 'jpTerpakai'))->render();

        return $this->pdf($html, 'ATP-'.($atp->subject?->name ?? '').'-'.($atp->gradeLevel?->name ?? ''), 'portrait');
    }

    // ── 4. PROTA ─────────────────────────────────────────────────────

    public function prota(Request $request, string $userId, string $id)
    {
        $prota = Prota::with([
            'subject', 'gradeLevel', 'academicYear', 'teacher', 'school',
            'items.tujuanPembelajaran',
        ])->findOrFail($id);

        $this->authorizeKurikulumDoc($request, $prota->school_id, $prota->subject_id, $prota->academic_year_id);

        $school = School::find($prota->school_id);
        $totalJp = (int) $prota->items->sum('alokasi_jp');

        $html = view('cetak.prota', compact('school', 'prota', 'totalJp'))->render();

        return $this->pdf($html, 'PROTA-'.($prota->subject?->name ?? '').'-'.($prota->gradeLevel?->name ?? ''), 'portrait');
    }

    // ── 5. PROSEM ────────────────────────────────────────────────────

    public function prosem(Request $request, string $userId, string $id)
    {
        $prosem = Prosem::with([
            'subject', 'gradeLevel', 'academicYear', 'teacher', 'school',
            'prota.subject', 'items.tujuanPembelajaran',
        ])->findOrFail($id);

        $this->authorizeKurikulumDoc($request, $prosem->school_id, $prosem->subject_id, $prosem->academic_year_id);

        $school = School::find($prosem->school_id);
        $weeks = $this->prosemService->pekanEfektif($prosem)->keyBy('minggu_ke');
        $totalJp = (int) $prosem->items->sum('jp');

        $html = view('cetak.prosem', compact('school', 'prosem', 'weeks', 'totalJp'))->render();

        return $this->pdf($html, 'PROSEM-'.($prosem->subject?->name ?? '').'-'.($prosem->gradeLevel?->name ?? ''), 'landscape');
    }

    // ── 6. RPM / Perangkat Pembelajaran ──────────────────────────────

    public function rpm(Request $request, string $userId, string $id)
    {
        $perangkat = PerangkatPembelajaran::with([
            'subject', 'gradeLevel', 'studyGroup', 'teacher', 'school', 'academicYear',
            'atp.items.tujuanPembelajaran.capaianPembelajaran',
        ])->findOrFail($id);

        $this->authorizeKurikulumDoc($request, $perangkat->school_id, $perangkat->subject_id, $perangkat->academic_year_id);

        $school = School::find($perangkat->school_id);
        $groups = $perangkat->groups();
        $legacyFilled = $perangkat->legacyFilledSections();

        $html = view('cetak.rpm', compact('school', 'perangkat', 'groups', 'legacyFilled'))->render();

        return $this->pdf($html, 'RPM-'.($perangkat->subject?->name ?? '').'-'.($perangkat->judul ?? ''), 'portrait');
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function pdf(string $html, string $filename, string $orientation = 'portrait')
    {
        $options = new Options;
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();

        // Nomor halaman (kanan bawah).
        $canvas = $dompdf->getCanvas();
        $width = $canvas->get_width();
        $height = $canvas->get_height();
        $canvas->page_text($width - 170, $height - 22, 'Halaman {PAGE_NUM} dari {PAGE_COUNT}', 'helvetica', 9, [0.35, 0.35, 0.35]);

        $safe = preg_replace('/[^A-Za-z0-9\-]+/', '-', $filename) ?: 'Dokumen';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$safe.'.pdf"',
        ]);
    }

    private function school(Request $request, bool $required = false): ?School
    {
        $schoolId = $request->attributes->get('schoolContextId') ?: $request->input('school_id');

        if (! $schoolId) {
            if ($required) {
                abort(404, 'Konteks satuan pendidikan tidak ditemukan.');
            }

            return null;
        }

        return School::find($schoolId);
    }

    private function authorizeSchool(Request $request, ?string $schoolId): void
    {
        $ctx = $request->attributes->get('schoolContextId');

        if ($ctx && $schoolId && $ctx !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }
    }

    private function authorizeKurikulumDoc(Request $request, ?string $schoolId, string $subjectId, string $academicYearId): void
    {
        $this->authorizeSchool($request, $schoolId);

        $user = $request->user();
        $ctx = $request->attributes->get('schoolContextId');

        $allowed = $this->access->isKurikulumTeam($user)
            || $this->access->teachesSubject($user, $subjectId, $academicYearId, $ctx);

        if (! $allowed) {
            abort(403, 'Dokumen ini bukan untuk mata pelajaran yang Anda ampu.');
        }
    }
}
