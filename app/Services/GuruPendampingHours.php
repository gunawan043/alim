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
     * JP per kelas untuk mapel tertentu. $studyGroupIds boleh berisi ID
     * bersarang (mapel => [kelas]) bila tiap mapel didampingi di kelas
     * yang berbeda; bentuk datar (mapel berlaku untuk semua kelas) juga
     * tetap didukung.
     *
     * @param  array<int|string, mixed>  $studyGroupIds
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

        // Bentuk bersarang (mapel => [kelas]) menentukan mapel mana di kelas
        // mana; bentuk datar berarti semua mapel berlaku di semua kelas.
        $pairing = $this->pairing($type, $studyGroupIds);
        $nested = collect($studyGroupIds)->contains(fn ($value) => is_array($value));
        $groupIds = $nested
            ? collect($pairing)->flatten()->unique()->values()->all()
            : array_values(array_unique(array_filter(array_map('strval', $studyGroupIds))));

        $groups = StudyGroup::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereIn('id', $groupIds)
            ->get(['id', 'name', 'grade_level_id']);

        $result = [];

        foreach ($groups as $group) {
            $applicable = $nested
                ? collect($pairing)->filter(fn ($list) => in_array((string) $group->id, $list, true))->keys()->all()
                : $subjectIds;

            if ($applicable === []) {
                continue;
            }

            $jpBySubject = GradeLevelSubject::query()
                ->where('grade_level_id', $group->grade_level_id)
                ->whereIn('subject_id', $applicable)
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
     * Total JP untuk pasangan mapel–kelas terpilih — nilai yang disimpan
     * ke tugas tambahan. Mapel yang sama di dua kelas dihitung dua kali;
     * dua mapel berbeda di kelas yang sama juga dijumlahkan.
     *
     * @param  array<int|string, mixed>  $studyGroupIds
     */
    public function totalHours(?AdditionalTaskType $type, array $studyGroupIds, ?string $schoolId): int
    {
        return (int) collect($this->forGroups($type, $studyGroupIds, $schoolId))->sum('total');
    }

    /**
     * Pasangan mapel–kelas yang akan disimpan. Mengembalikan daftar kelas
     * unik per mapel, dibatasi ke mapel yang menjadi bagian tipe tugas.
     *
     * @param  array<int|string, mixed>  $studyGroupIds
     * @return array<string, array<int, string>>  subject_id => [study_group_id, ...]
     */
    public function pairing(?AdditionalTaskType $type, array $studyGroupIds): array
    {
        if (! $type) {
            return [];
        }

        $allowed = $this->subjectRows($type)->pluck('subject_id')->map(fn ($id) => (string) $id)->all();
        $pairs = [];

        foreach ($studyGroupIds as $key => $value) {
            // Bentuk bersarang: mapel => [kelas, ...]
            if (is_array($value)) {
                $subjectId = (string) $key;
                if ($allowed !== [] && ! in_array($subjectId, $allowed, true)) {
                    continue;
                }
                $groups = array_values(array_unique(array_filter(array_map('strval', $value))));
                if ($groups !== []) {
                    $pairs[$subjectId] = $groups;
                }

                continue;
            }

            // Bentuk datar: semua kelas berlaku untuk semua mapel tipe tugas.
            foreach ($allowed as $subjectId) {
                $pairs[$subjectId][] = (string) $value;
            }
        }

        if ($pairs === [] && $allowed !== [] && $studyGroupIds !== []) {
            $groups = array_values(array_unique(array_filter(array_map('strval', $studyGroupIds))));
            foreach ($allowed as $subjectId) {
                $pairs[$subjectId] = $groups;
            }
        }

        return array_map(fn ($list) => array_values(array_unique($list)), $pairs);
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
