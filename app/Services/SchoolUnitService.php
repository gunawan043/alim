<?php

namespace App\Services;

use App\Models\School;
use App\Models\SchoolUnitLink;
use Illuminate\Database\Eloquent\Builder;

/**
 * Relasi unit induk ↔ unit operasional.
 *
 * Data induk santri tetap pada sekolah primer. Unit operasional hanya
 * mengelola penempatan/rombel operasional dan tidak boleh mengubah data induk.
 */
class SchoolUnitService
{
    public function isOperational(?string $schoolId): bool
    {
        if (! $schoolId) {
            return false;
        }

        return SchoolUnitLink::query()
            ->where('operational_school_id', $schoolId)
            ->where('is_active', true)
            ->exists();
    }

    public function primarySchoolId(?string $schoolId): ?string
    {
        if (! $schoolId) {
            return null;
        }

        return SchoolUnitLink::query()
            ->where('operational_school_id', $schoolId)
            ->where('is_active', true)
            ->value('primary_school_id') ?: $schoolId;
    }

    /**
     * Unit operasional yang terhubung ke sekolah primer.
     *
     * @return array<int, string>
     */
    public function operationalSchoolIds(?string $primarySchoolId): array
    {
        if (! $primarySchoolId) {
            return [];
        }

        return SchoolUnitLink::query()
            ->where('primary_school_id', $primarySchoolId)
            ->where('is_active', true)
            ->pluck('operational_school_id')
            ->all();
    }

    public function canReadSchool(?string $contextSchoolId, ?string $targetSchoolId): bool
    {
        if (! $contextSchoolId || ! $targetSchoolId) {
            return true;
        }

        if ((string) $contextSchoolId === (string) $targetSchoolId) {
            return true;
        }

        return in_array($targetSchoolId, $this->operationalSchoolIds($contextSchoolId), true);
    }

    public function canManageSchool(?string $contextSchoolId, ?string $targetSchoolId): bool
    {
        if (! $contextSchoolId) {
            return true;
        }

        return (string) $contextSchoolId === (string) $targetSchoolId;
    }

    public function dataOwnerSchool(?string $schoolId): ?School
    {
        $primaryId = $this->primarySchoolId($schoolId);

        return $primaryId ? School::find($primaryId) : null;
    }

    /**
     * Batasi query santri sesuai konteks unit.
     *
     * - Unit induk: semua santri miliknya (termasuk yang ditempatkan di unit operasional).
     * - Unit operasional: santri yang punya riwayat kelas aktif di unit tersebut.
     */
    public function scopeStudents(Builder $query, ?string $schoolId, ?string $academicYearId = null): Builder
    {
        if (! $schoolId) {
            return $query;
        }

        if ($this->isOperational($schoolId)) {
            return $query->whereHas('classHistories', function (Builder $history) use ($schoolId, $academicYearId) {
                $history->where('is_active', true)
                    ->when($academicYearId, fn (Builder $q) => $q->where('academic_year_id', $academicYearId))
                    ->whereHas('studyGroup', fn (Builder $group) => $group->where('school_id', $schoolId));
            });
        }

        return $query->where('students.school_id', $schoolId);
    }

    /**
     * @return array{primary_school_id: ?string, operational_school_id: ?string, is_operational: bool}
     */
    public function context(?string $schoolId): array
    {
        return [
            'primary_school_id' => $this->primarySchoolId($schoolId),
            'operational_school_id' => $this->isOperational($schoolId) ? $schoolId : null,
            'is_operational' => $this->isOperational($schoolId),
        ];
    }
}
