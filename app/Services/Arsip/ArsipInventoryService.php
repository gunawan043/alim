<?php

namespace App\Services\Arsip;

use App\Models\AcademicYear;
use App\Models\ArchiveDocument;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Inventaris arsip: hitung data per modul untuk satu tahun ajaran,
 * dengan filter satuan pendidikan (best-effort sesuai kolom/join nyata).
 */
class ArsipInventoryService
{
    public function __construct(private readonly ArsipRegistry $registry) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function summary(?string $academicYearId, ?string $schoolId = null): array
    {
        if (! $academicYearId) {
            return [];
        }

        return collect($this->registry->modules())
            ->map(function (array $module) use ($academicYearId, $schoolId) {
                $module['count'] = $this->countModule($module, $academicYearId, $schoolId);

                return $module;
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $module
     */
    public function countModule(array $module, string $academicYearId, ?string $schoolId = null): int
    {
        $table = $module['table'];
        $query = DB::table($table);

        foreach ($module['joins'] ?? [] as [$joinTable, $first, $second]) {
            $query->join($joinTable, $first, '=', $second);
        }

        $query->where($module['ay_column'] ?? $table.'.academic_year_id', $academicYearId);

        if ($schoolId && ! empty($module['school_column'])) {
            $query->where($module['school_column'], $schoolId);
        }

        return (int) $query->count();
    }

    /**
     * @return Collection<int, ArchiveDocument>
     */
    public function documents(?string $academicYearId, ?string $schoolId = null, int $limit = 50): Collection
    {
        return ArchiveDocument::query()
            ->with(['generator:id,name', 'studyGroup:id,name'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function documentCount(?string $academicYearId, ?string $schoolId = null): int
    {
        return ArchiveDocument::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->count();
    }

    /**
     * Statistik ringkas periode: jumlah data per kategori besar.
     *
     * @return array{modules: int, filled: int, documents: int}
     */
    public function overview(?string $academicYearId, ?string $schoolId = null): array
    {
        $summary = $this->summary($academicYearId, $schoolId);

        return [
            'modules' => count($summary),
            'filled' => collect($summary)->where('count', '>', 0)->count(),
            'documents' => $this->documentCount($academicYearId, $schoolId),
        ];
    }

    /**
     * Total tahun ajaran + status untuk pemilih periode.
     *
     * @return Collection<int, AcademicYear>
     */
    public function academicYears(): Collection
    {
        return AcademicYear::orderByDesc('start_date')->orderByDesc('name')->get();
    }
}
