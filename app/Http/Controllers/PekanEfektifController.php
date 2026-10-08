<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\PekanEfektif;
use App\Models\StudyGroup;
use App\Models\TeachingAssignment;
use App\Services\PekanEfektifService;
use Illuminate\Http\Request;

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

        $academicYears = AcademicYear::orderByDesc('start_date')->orderByDesc('created_at')->get();
        $activeAy = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        $academicYearId = $request->input('academic_year_id', $activeAy?->id);
        $semester = (int) $request->input('semester', $activeAy?->semester === 'genap' ? 2 : 1);
        if (! in_array($semester, [PekanEfektif::SEMESTER_GANJIL, PekanEfektif::SEMESTER_GENAP], true)) {
            $semester = PekanEfektif::SEMESTER_GANJIL;
        }

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

        return view('pekan-efektif.index', compact(
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
        ));
    }
}
