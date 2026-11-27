<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\AdditionalTaskType;
use App\Models\GradeLevelSubject;
use App\Models\StudyGroup;
use Illuminate\Support\Collection;

/**
 * JP untuk tugas tambahan "Guru Pendamping".
 *
 * Angka JP TIDAK diketik manual: nilainya dibaca dari Peta Kurikulum
 * (grade_level_subjects.allocation_hours) untuk mapel × tingkat pada
 * tahun ajaran aktif. Beberapa kelas dijumlahkan, karena guru yang
 * mendampingi dua kelas memang mengampu JP dua kali.
 *
 * Guru Pendamping hanya untuk memenuhi JP — tidak menghasilkan buku
 * nilai maupun perangkat ajar (keduanya hanya membaca TeachingAssignment).
 */
class GuruPendampingHours
{
    /**
     * JP per kelas untuk mapel tertentu.
     *
     * @param  array<int, string>  $studyGroupIds
     * @return array<string, array{group_id:string, group_name:string, grade_level_id:?string, hours:int, subject_hours:array<string,int>, total:int}>
     */
    public function forGroups(?AdditionalTaskType $type, array $studyGroupIds, ?string $schoolId): array
    {
        if (! $type || $studyGroupIds === []) {
            return [];
        }

        $academicYearId = $this->activeAcademicYearId();
        $subjectIds = $this->subjectRows($type)->pluck('subject_id')->map(fn ($id) => (string) $id)->all();

        if ($subjectIds === []) {
            return [];
        }

        $groups = StudyGroup::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereIn('id', $studyGroupIds)
            ->get(['id', 'name', 'grade_level_id']);

        $result = [];

        foreach ($groups as $group) {
            $jpBySubject = GradeLevelSubject::query()
                ->where('grade_level_id', $group->grade_level_id)
                ->whereIn('subject_id', $subjectIds)
                ->forAcademicYear($academicYearId)
                ->orderByAcademicYearFirst()
                ->get(['subject_id', 'allocation_hours'])
                ->unique('subject_id')
                ->mapWithKeys(fn ($row) => [(string) $row->subject_id => (int) $row->allocation_hours])
                ->all();

            $result[$group->id] = [
                'group_id' => $group->id,
                'group_name' => $group->name,
                'grade_level_id' => $group->grade_level_id,
                'subject_hours' => $jpBySubject,
                'total' => array_sum($jpBySubject),
                'hours' => array_sum($jpBySubject),
            ];
        }

        return $result;
    }

    /**
     * Total JP seluruh kelas terpilih — nilai yang disimpan ke tugas tambahan.
     *
     * @param  array<int, string>  $studyGroupIds
     */
    public function totalHours(?AdditionalTaskType $type, array $studyGroupIds, ?string $schoolId): int
    {
        return (int) collect($this->forGroups($type, $studyGroupIds, $schoolId))->sum('total');
    }

    /**
     * Mapel yang sudah terhubung ke tipe tugas, lengkap dengan namanya.
     *
     * @return array<int, array{id:string, name:string, code:?string}>
     */
    public function subjectsFor(?AdditionalTaskType $type): array
    {
        if (! $type) {
            return [];
        }

        return $this->subjectRows($type)
            ->load('subject:id,name,code')
            ->filter(fn ($row) => $row->subject !== null)
            ->map(fn ($row) => [
                'id' => $row->subject->id,
                'name' => $row->subject->name,
                'code' => $row->subject->code,
            ])
            ->values()
            ->all();
    }

    /**
     * Baris mapel tipe tugas. Memakai relasi yang sudah di-set (pratinjau
     * sebelum tersimpan) bila ada, supaya tidak menimpa data dari form.
     */
    private function subjectRows(AdditionalTaskType $type): Collection
    {
        if ($type->relationLoaded('subjects')) {
            return $type->getRelation('subjects');
        }

        return $type->subjects()->get();
    }

    public function activeAcademicYearId(): ?string
    {
        return AcademicYear::where('is_active', true)->value('id')
            ?? AcademicYear::orderByDesc('start_date')->value('id');
    }
}
