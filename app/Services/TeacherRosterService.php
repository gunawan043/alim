<?php

namespace App\Services;

use App\Models\GtkEmployment;

class TeacherRosterService
{
    /**
     * Daftar ID guru untuk dropdown assignment/jadwal.
     *
     * Prioritas:
     *  1. Snapshot permission (general_teacher.readable / general_tutor.readable)
     *  2. Data kepegawaian — jenis GTK / jabatan mengandung "guru"
     *  3. Seluruh GTK pada sekolah tersebut (jaring terakhir agar dropdown tidak kosong)
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

        return GtkEmployment::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values()
            ->map(fn ($id) => (string) $id)
            ->all();
    }
}
