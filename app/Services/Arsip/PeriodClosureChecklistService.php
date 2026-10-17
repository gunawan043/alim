<?php

namespace App\Services\Arsip;

use App\Models\GtkEmployment;
use App\Models\Student;
use App\Models\User;
use App\Services\SchoolGroupService;

/**
 * Checklist kesiapan penutupan periode (sebelum tahun ajaran dikunci).
 *
 * - error   : menghalangi penutupan (data inti tidak ada).
 * - warning : boleh ditutup, tetapi pengguna wajib meninjau & mengonfirmasi.
 * - ok      : lengkap.
 */
class PeriodClosureChecklistService
{
    public function __construct(private readonly ArsipInventoryService $inventory) {}

    /**
     * @return array{items: array<int, array<string, mixed>>, errors: int, warnings: int, ready: bool}
     */
    public function evaluate(?string $academicYearId, ?string $schoolId = null): array
    {
        if (! $academicYearId) {
            return ['items' => [], 'errors' => 0, 'warnings' => 0, 'ready' => false];
        }

        $modules = collect($this->inventory->summary($academicYearId, $schoolId))->keyBy('key');
        $documentCount = $this->inventory->documentCount($academicYearId, $schoolId);

        $count = fn (string $key): int => (int) ($modules[$key]['count'] ?? 0);
        $items = [];

        $add = function (string $key, string $label, string $status, string $value, ?string $hint = null, ?string $route = null) use (&$items) {
            $items[] = compact('key', 'label', 'status', 'value', 'hint', 'route');
        };

        // ── Wajib (error) ───────────────────────────────────────────
        $rombel = $count('rombel');
        $add(
            'rombel',
            'Rombel periode',
            $rombel > 0 ? 'ok' : 'error',
            (string) $rombel,
            $rombel > 0 ? null : 'Rombel tahun ajaran ini belum dibuat.',
            'user.study-groups.index'
        );

        $penempatan = $count('penempatan_santri');
        $add(
            'penempatan',
            'Penempatan santri di rombel',
            $penempatan > 0 ? 'ok' : 'error',
            (string) $penempatan,
            $penempatan > 0 ? null : 'Belum ada santri yang ditempatkan pada rombel periode ini.',
            'user.students.index'
        );

        // ── Perlu ditinjau (warning) ────────────────────────────────
        foreach ([
            ['buku_nilai', 'Buku nilai guru', 'user.schools.nilai.index'],
            ['nilai_sumatif', 'Nilai sumatif', 'user.schools.nilai.index'],
            ['presensi_harian', 'Presensi harian santri', 'user.absensi.harian.index'],
            ['jurnal_pembelajaran', 'Jurnal pembelajaran', 'user.kurikulum.realisasi.index'],
            ['rapor', 'Registrasi rapor', 'user.schools.nilai.index'],
        ] as [$key, $label, $route]) {
            $value = $count($key);
            $add(
                $key,
                $label,
                $value > 0 ? 'ok' : 'warning',
                (string) $value,
                $value > 0 ? null : 'Belum ada data pada periode ini.',
                $route
            );
        }

        $add(
            'dokumen',
            'Dokumen arsip (rapor/leger)',
            $documentCount > 0 ? 'ok' : 'warning',
            (string) $documentCount,
            $documentCount > 0 ? null : 'Arsipkan minimal rapor atau leger sebelum menutup periode.',
            'user.arsip.index'
        );

        $pendingStudents = Student::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('name_ar_status', 'pending')
            ->count();

        $gtkIds = $schoolId ? SchoolGroupService::userIdsForSchool($schoolId) : null;
        $pendingGtk = User::query()
            ->whereHas('employment')
            ->when($gtkIds !== null, fn ($q) => $q->whereIn('id', $gtkIds))
            ->where('name_ar_status', 'pending')
            ->count();

        $pendingArabic = $pendingStudents + $pendingGtk;
        $add(
            'nama_arab',
            'Nama Arab menunggu tinjauan',
            $pendingArabic === 0 ? 'ok' : 'warning',
            (string) $pendingArabic,
            $pendingArabic === 0 ? null : 'Tinjau & setujui nama Arab agar rapor resmi konsisten.',
            'user.students.review-nama-arab'
        );

        $duplicateNis = Student::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereNotNull('nis')->where('nis', '!=', '')
            ->select('nis')->groupBy('nis')->havingRaw('COUNT(*) > 1')->pluck('nis')->count();

        $duplicateNupy = GtkEmployment::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereNotNull('nupy')->where('nupy', '!=', '')
            ->select('nupy')->groupBy('nupy')->havingRaw('COUNT(*) > 1')->pluck('nupy')->count();

        $duplicates = $duplicateNis + $duplicateNupy;
        $add(
            'duplikat',
            'NIS/NUPY ganda',
            $duplicates === 0 ? 'ok' : 'warning',
            $duplicateNis.' / '.$duplicateNupy,
            $duplicates === 0 ? null : 'Periksa NIS santri / NUPY GTK yang ganda.',
            'user.students.review-nama-arab'
        );

        $errors = count(array_filter($items, fn ($item) => $item['status'] === 'error'));
        $warnings = count(array_filter($items, fn ($item) => $item['status'] === 'warning'));

        return [
            'items' => $items,
            'errors' => $errors,
            'warnings' => $warnings,
            'ready' => $errors === 0,
        ];
    }
}
