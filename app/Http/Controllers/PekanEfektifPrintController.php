<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\PekanEfektif;
use App\Models\PekanEfektifPrintSetting;
use App\Models\School;
use App\Models\SystemSetting;
use App\Services\Pdf\CurriculumPdfStyle;
use App\Services\PekanEfektifService;
use App\Support\UserJabatan;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Pengaturan cetak dan PDF "Analisis Pekan Efektif" per sekolah.
 * Pengaturan margin/kertas/TTD hanya untuk KSP, Waka, Kurikulum, dan TU.
 */
class PekanEfektifPrintController extends Controller
{
    public function __construct(
        private readonly PekanEfektifService $pekanService,
    ) {}

    public function updateSettings(Request $request, string $userId)
    {
        $school = $this->resolveSchool($request);
        abort_unless($this->canManagePrintSettings($request), 403, 'Pengaturan cetak hanya untuk KSP, Waka, Kurikulum, dan TU.');

        $validated = $request->validate([
            'margin_top' => 'required|numeric|min:0|max:50',
            'margin_right' => 'required|numeric|min:0|max:50',
            'margin_bottom' => 'required|numeric|min:0|max:50',
            'margin_left' => 'required|numeric|min:0|max:50',
            'font_size' => 'required|numeric|min:6|max:24',
            'title_font_size' => 'required|numeric|min:8|max:30',
            'table_font_size' => 'required|numeric|min:6|max:20',
            'info_font_size' => 'required|numeric|min:6|max:20',
            'footer_font_size' => 'required|numeric|min:5|max:16',
            'line_height' => 'required|numeric|min:1|max:2.5',
            'paper_size' => 'required|in:a4,folio,letter',
            'show_kop' => 'nullable|boolean',
            'show_signature' => 'nullable|boolean',
            'city' => 'nullable|string|max:100',
            'guru_nama' => 'nullable|string|max:150',
            'guru_nip' => 'nullable|string|max:50',
            'footer_form_code' => 'nullable|string|max:150',
            'footer_revision' => 'nullable|string|max:150',
        ]);

        $values = $validated;
        $values['show_kop'] = $request->boolean('show_kop');
        $values['show_signature'] = $request->boolean('show_signature');
        $values['updated_by'] = $request->user()?->id;

        PekanEfektifPrintSetting::updateOrCreate(
            ['school_id' => $school->id],
            $values
        );

        // Footer dokumen adalah pengaturan global khusus Super Admin.
        if ($request->user()?->isSuperAdmin()) {
            SystemSetting::set(
                'pekan_efektif_pdf_footer_form',
                trim((string) ($validated['footer_form_code'] ?? '')) ?: 'PAH-KSP-FORM-09-05',
                'academic'
            );
            SystemSetting::set(
                'pekan_efektif_pdf_footer_revision',
                trim((string) ($validated['footer_revision'] ?? '')) ?: 'Rev. 01/10 September 2025',
                'academic'
            );
            Cache::forget('system_settings');
            Cache::forget('system_settings_map');
        }

        return back()->with('success', 'Pengaturan cetak PDF Pekan Efektif untuk '.$school->name.' berhasil disimpan.');
    }

    public function pdf(Request $request, string $userId)
    {
        $school = $this->resolveSchool($request);
        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $academicYear = $request->filled('academic_year_id')
            ? $academicYears->firstWhere('id', $request->input('academic_year_id'))
            : ($academicYears->firstWhere('is_active', true) ?? $academicYears->first());

        abort_unless($academicYear, 404, 'Tahun ajaran tidak ditemukan.');

        $setting = PekanEfektifPrintSetting::forSchool($school->id);
        $layout = $setting?->layoutSettings() ?? PekanEfektifPrintSetting::defaultLayout();
        // Nama guru mengikuti akun yang mencetak; bukan input manual.
        $layout['guru_nama'] = $request->user()?->name ?: $layout['guru_nama'];
        $layout['guru_nip'] = $request->user()?->employment?->nupy ?: $layout['guru_nip'];
        $footerForm = SystemSetting::get('pekan_efektif_pdf_footer_form', 'PAH-KSP-FORM-09-05');
        $footerRevision = SystemSetting::get('pekan_efektif_pdf_footer_revision', 'Rev. 01/10 September 2025');
        $minDays = $this->pekanService->minEffectiveDays($school->id);

        $semesters = [];
        foreach ([1, 2] as $semester) {
            $semesterAy = $this->pekanService->academicYearForSemester($academicYear, $semester)
                ?? $academicYear;

            $rows = PekanEfektif::query()
                ->bySchool($school->id)
                ->byAcademicYear($semesterAy->id)
                ->bySemester($semester)
                ->orderBy('minggu_ke')
                ->get();

            if ($rows->isEmpty()) {
                $rows = collect($this->pekanService
                    ->computeWeeks($school->id, $semesterAy->id, $semester)['rows'])
                    ->map(fn (array $row) => (object) $row);
            }

            $months = $semester === 1 ? range(7, 12) : range(1, 6);
            $monthRows = [];
            $totalWeeks = $totalEffective = 0;

            foreach ($months as $month) {
                $bucket = $rows->filter(fn ($row) => (int) Carbon::parse($row->tanggal_mulai)->month === $month);

                $weeks = $bucket->count();
                $effective = $bucket->filter(fn ($row) => $this->isEffectiveWeek($row, $minDays))->count();
                $tidakEfektif = $weeks - $effective;

                $totalWeeks += $weeks;
                $totalEffective += $effective;

                $monthRows[] = [
                    'month' => Carbon::create(2000, $month, 1)->locale('id')->translatedFormat('F'),
                    'weeks' => $weeks,
                    'effective' => $effective,
                    'not_effective' => $tidakEfektif,
                    'notes' => $bucket->pluck('keterangan')->filter()->unique()->values()->all(),
                ];
            }

            $semesters[] = [
                'number' => $semester,
                'months' => $monthRows,
                'total_weeks' => $totalWeeks,
                'total_effective' => $totalEffective,
                'total_not_effective' => $totalWeeks - $totalEffective,
            ];
        }

        $layout['city'] = $layout['city'] ?: 'Mataram';
        $html = view('cetak.pekan-efektif-analisis', [
            'school' => $school,
            'academicYear' => $academicYear,
            'semesters' => $semesters,
            'layout' => $layout,
            'setting' => $setting,
        ])->render();

        return $this->renderPdf($html, 'Analisis-Pekan-Efektif-'.str_replace(['/', ' '], '-', $academicYear->name), $layout, $footerForm, $footerRevision);
    }

    private function isEffectiveWeek(object $row, int $minDays): bool
    {
        return (int) ($row->jumlah_hari ?? 0) >= $minDays
            && ! in_array($row->jenis, [PekanEfektif::JENIS_LIBUR, PekanEfektif::JENIS_TIDAK_EFEKTIF], true);
    }

    private function resolveSchool(Request $request): School
    {
        $user = $request->user();
        $contextId = $request->attributes->get('schoolContextId');
        $requestedId = $request->input('school_id');

        $schoolId = $user?->isSuperAdmin() ? ($requestedId ?: $contextId) : $contextId;
        if (! $schoolId && $user?->isSuperAdmin()) {
            $schoolId = School::where('is_active', true)->orderBy('name')->value('id');
        }

        abort_unless($schoolId, 403, 'Satuan pendidikan belum dipilih.');
        $school = School::findOrFail($schoolId);

        if (! $user?->isSuperAdmin()) {
            abort_unless($contextId && (string) $contextId === (string) $school->id, 403);
        }

        return $school;
    }

    public function canManagePrintSettings(Request $request): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isSystemAdmin()) {
            return true;
        }

        if (function_exists('canPermission')
            && (canPermission('kaldik-admin-tu') || canPermission('nilaikelas-admin-tu'))) {
            return true;
        }

        if ($user->hasRole('Kurikulum') || $user->hasRole('Waka Kurikulum')) {
            return true;
        }

        return UserJabatan::hasAny($user, [
            'kepala sekolah',
            'kepala satuan pendidikan',
            'wakil kepala sekolah',
            'wakil kepala satuan pendidikan',
            'wakil kepala bidang kurikulum',
            'waka kurikulum',
            'koordinator kurikulum',
            'tim kurikulum',
            'kepala tata usaha',
            'tata usaha',
            'staf tata usaha',
            'kepala tu sekolah',
            'tu sekolah',
            'staf tu sekolah',
            'tata usaha sekolah',
        ]);
    }

    private function renderPdf(string $html, string $filename, array $layout, string $footerForm, string $footerRevision)
    {
        $options = new Options;
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $fontDir = storage_path('fonts');
        if (! is_dir($fontDir)) {
            mkdir($fontDir, 0755, true);
        }
        $options->setFontDir($fontDir);
        $options->setFontCache($fontDir);

        $dompdf = new Dompdf($options);
        if (class_exists(CurriculumPdfStyle::class)) {
            app(CurriculumPdfStyle::class)->registerCambria($dompdf);
        }

        $dompdf->loadHtml($html);
        $dompdf->setPaper($layout['paper_size'] ?? 'a4', 'portrait');
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $footerY = $canvas->get_height() - 20;
        $font = 'Cambria';
        $size = (float) ($layout['footer_font_size'] ?? 8);

        $canvas->page_text(25, $footerY, $footerForm, $font, $size, [0.2, 0.2, 0.2]);
        $canvas->page_text(
            ($canvas->get_width() / 2) - 95,
            $footerY,
            $footerRevision,
            $font,
            $size,
            [0.2, 0.2, 0.2]
        );
        $canvas->page_text(
            $canvas->get_width() - 150,
            $footerY,
            'Hal. {PAGE_NUM} dari {PAGE_COUNT}',
            $font,
            $size,
            [0.2, 0.2, 0.2]
        );

        $safe = preg_replace('/[^A-Za-z0-9\-]+/', '-', $filename) ?: 'Analisis-Pekan-Efektif';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$safe.'.pdf"',
        ]);
    }
}
