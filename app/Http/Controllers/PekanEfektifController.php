<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\PekanEfektif;
use App\Models\PekanEfektifPrintSetting;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\SystemSetting;
use App\Models\TeachingAssignment;
use App\Services\PekanEfektifService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Halaman Pekan Efektif level user (read-only).
 *
 * Guru & Kurikulum melihat pekan efektif sesuai satuan pendidikan dan
 * tahun ajaran, beserta alokasi JP efektif per kelas sebagai dasar
 * perencanaan pembelajaran.
 */
class PekanEfektifController extends Controller
{
    public function __construct(private readonly PekanEfektifService $pekanService) {}

    public function index(Request $request, string $userId)
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId');
        $canChooseSchool = $user?->isSuperAdmin() ?? false;
        $schools = collect();

        if ($canChooseSchool) {
            $schools = School::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);

            $requestedSchoolId = $request->input('school_id');
            if ($requestedSchoolId && $schools->contains('id', $requestedSchoolId)) {
                $schoolId = $requestedSchoolId;
            } elseif (! $schoolId || ! $schools->contains('id', $schoolId)) {
                $schoolId = $schools->first()?->id;
            }
        }

        $academicYears = AcademicYear::orderByDesc('start_date')->orderByDesc('created_at')->get();
        $activeAy = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        $selectedAy = $academicYears->firstWhere('id', $request->input('academic_year_id'))
            ?? $activeAy;
        $semester = (int) $request->input('semester', $selectedAy?->semester === 'genap' ? 2 : 1);
        if (! in_array($semester, [PekanEfektif::SEMESTER_GANJIL, PekanEfektif::SEMESTER_GENAP], true)) {
            $semester = PekanEfektif::SEMESTER_GANJIL;
        }

        $academicYear = $this->pekanService->academicYearForSemester($selectedAy, $semester);
        $academicYearId = $academicYear?->id ?? $selectedAy?->id;

        // ── Pekan efektif dari Kalender Pendidikan ─────────────────────
        $rows = PekanEfektif::query()
            ->bySchool($schoolId)
            ->byAcademicYear($academicYearId)
            ->bySemester($semester)
            ->orderBy('minggu_ke')
            ->get()
            ->map(fn (PekanEfektif $p) => [
                'minggu_ke' => $p->minggu_ke,
                'tanggal_mulai' => $p->tanggal_mulai?->toDateString(),
                'tanggal_selesai' => $p->tanggal_selesai?->toDateString(),
                'jenis' => $p->jenis,
                'jumlah_hari' => $p->hari_efektif,
                'keterangan' => $p->keterangan,
                'is_generated' => (bool) $p->is_generated,
            ]);

        $isPreview = false;

        if ($rows->isEmpty() && $schoolId && $academicYearId) {
            // Belum digenerate oleh Kurikulum → tampilkan hitungan langsung dari kalender.
            $computed = $this->pekanService->computeWeeks($schoolId, $academicYearId, $semester);
            $rows = collect($computed['rows'])->map(fn (array $r) => $r + ['is_generated' => false]);
            $isPreview = true;
        }

        $ringkasan = ($schoolId && $academicYearId)
            ? $this->pekanService->summary($schoolId, $academicYearId, $semester)
            : null;

        // ── Alokasi JP efektif per kelas (fondasi perencanaan guru) ────
        $roles = method_exists($user, 'effectiveRoles') ? $user->effectiveRoles() : [];
        $isGuru = in_array('guru', $roles, true);

        $studyGroups = collect();

        if ($academicYearId) {
            $studyGroups = StudyGroup::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where('academic_year_id', $academicYearId)
                ->orderBy('name')
                ->get(['id', 'name']);

            if ($isGuru) {
                $taughtIds = TeachingAssignment::query()
                    ->where('teacher_id', $user->id)
                    ->where('academic_year_id', $academicYearId)
                    ->where('status', 'active')
                    ->pluck('study_group_id')
                    ->unique()
                    ->values();

                $studyGroups = $studyGroups->whereIn('id', $taughtIds)->values();
            }
        }

        $selectedGroup = $studyGroups->firstWhere('id', $request->input('study_group_id'))
            ?? $studyGroups->first();

        $jpRows = ($schoolId && $academicYearId && $selectedGroup)
            ? $this->pekanService->effectiveJpForStudyGroup(
                $schoolId,
                $selectedGroup->id,
                $academicYearId,
                $semester
            )
            : [];

        $mingguEfektif = (int) ($ringkasan['minggu_efektif'] ?? 0);
        $minEffectiveDays = $this->pekanService->minEffectiveDays($schoolId);
        $canManageSettings = $user->isSuperAdmin();
        $canManagePrintSettings = $schoolId
            && app(PekanEfektifPrintController::class)->canManagePrintSettings($request);
        $printLayout = $schoolId
            ? (PekanEfektifPrintSetting::forSchool($schoolId)?->layoutSettings() ?? PekanEfektifPrintSetting::defaultLayout())
            : PekanEfektifPrintSetting::defaultLayout();
        $pdfPreviewUrl = ($schoolId && $academicYearId)
            ? route('user.pekan-efektif.pdf', [
                'userId' => $userId,
                'academic_year_id' => $academicYearId,
                'school_id' => $schoolId,
            ])
            : null;
        $footerFormCode = SystemSetting::get('pekan_efektif_pdf_footer_form', 'PAH-KSP-FORM-09-05');
        $footerRevision = SystemSetting::get('pekan_efektif_pdf_footer_revision', 'Rev. 01/10 September 2025');

        return view('pekan-efektif.index', compact(
            'minEffectiveDays',
            'canManageSettings',
            'canManagePrintSettings',
            'printLayout',
            'pdfPreviewUrl',
            'footerFormCode',
            'footerRevision',
            'userId',
            'academicYears',
            'academicYearId',
            'semester',
            'rows',
            'ringkasan',
            'isPreview',
            'isGuru',
            'studyGroups',
            'selectedGroup',
            'jpRows',
            'mingguEfektif',
            'schools',
            'canChooseSchool',
            'schoolId',
        ));
    }

    public function updateSettings(Request $request, string $userId)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'min_effective_days' => 'required|integer|min:1|max:6',
        ]);

        SystemSetting::set('pekan_efektif_min_hari', (string) $validated['min_effective_days'], 'academic');
        Cache::forget('system_settings');
        Cache::forget('system_settings_map');

        return back()->with('success', 'Batas minimal hari efektif per pekan berhasil disimpan.');
    }
}
