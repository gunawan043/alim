<?php

namespace App\Services;

use App\Models\GtkEmployment;
use App\Models\GtkWorkUnit;
use App\Models\School;

class TeacherRosterService
{
    /**
     * Daftar ID guru untuk dropdown assignment/jadwal.
     *
     * Prioritas:
     *  1. Snapshot permission (general_teacher.readable / general_tutor.readable)
     *  2. Data kepegawaian — jenis GTK / jabatan mengandung "guru"
     *  3. Penempatan pada satuan kerja sekolah (gtk_work_unit) — guru yang
     *     ditempatkan di unit ini tanpa baris kepegawaian tetap ikut tampil
     *  4. Seluruh GTK pada sekolah tersebut (jaring terakhir agar dropdown tidak kosong)
     *
     * @return array<int, string>
     */
    public function idsForSchool(?string $schoolId): array
    {
        $fromPermissions = array_merge(
            usersHavingPermission('general_teacher.readable'),
            usersHavingPermission('general_tutor.readable')
        );

        if (! empty($fromPermissions)) {
            return array_values(array_unique(array_map('strval', $fromPermissions)));
        }

        $fromEmployment = GtkEmployment::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where(function ($q) {
                $q->where('jenis_gtk', 'like', '%Guru%')
                    ->orWhere('jabatan', 'like', '%guru%');
            })
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (! empty($fromEmployment)) {
            return array_map('strval', $fromEmployment);
        }

        $fromEmploymentAll = GtkEmployment::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (! empty($fromEmploymentAll)) {
            return array_map('strval', $fromEmploymentAll);
        }

        // Sebagian sekolah mencatat GTK hanya lewat penempatan satuan kerja,
        // tanpa baris kepegawaian. Tanpa cabang ini matriks SK tampil kosong.
        $workUnitId = $schoolId ? School::whereKey($schoolId)->value('work_unit_id') : null;

        if (! $workUnitId) {
            return [];
        }

        return GtkWorkUnit::query()
            ->where('work_unit_id', $workUnitId)
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values()
            ->map(fn ($id) => (string) $id)
            ->all();
    }
}
