<?php

namespace App\Services;

use App\Models\GtkEmployment;
use App\Models\GtkWorkUnit;
use App\Models\School;
use App\Models\TeacherScheduleAvailability;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Ketersediaan guru untuk penempatan jadwal.
 *
 * Baris tanpa pengaturan berarti guru tersedia pada semua slot (kompatibel
 * dengan data lama). Jika pengaturan tersedia, hanya slot yang tercantum pada
 * JSON yang boleh dipakai generator.
 */
class TeacherAvailabilityService
{
    /** @var array<string, array<int, array<int, int>>|null> */
    private array $cache = [];

    /**
     * @return array<int, array<int, int>>|null Null = semua slot tersedia.
     */
    public function allowedSlots(?string $teacherId, ?string $schoolId): ?array
    {
        if (! $teacherId || ! $schoolId) {
            return null;
        }

        $key = $schoolId.'|'.$teacherId;

        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $row = TeacherScheduleAvailability::query()
            ->where('school_id', $schoolId)
            ->where('teacher_id', $teacherId)
            ->first();

        if (! $row || $row->available_slots === null) {
            return $this->cache[$key] = null;
        }

        $allowed = [];

        foreach ((array) $row->available_slots as $day => $slots) {
            $allowed[(int) $day] = array_values(array_unique(array_map(
                'intval',
                is_array($slots) ? $slots : []
            )));
        }

        return $this->cache[$key] = $allowed;
    }

    public function isAllowed(?string $teacherId, ?string $schoolId, int $day, int $slot): bool
    {
        $allowed = $this->allowedSlots($teacherId, $schoolId);

        if ($allowed === null) {
            return true;
        }

        return in_array($slot, $allowed[$day] ?? [], true);
    }

    public function forget(?string $teacherId = null, ?string $schoolId = null): void
    {
        if ($teacherId && $schoolId) {
            unset($this->cache[$schoolId.'|'.$teacherId]);

            return;
        }

        $this->cache = [];
    }

    /**
     * Semua GTK aktif pada sekolah (bukan hanya yang punya permission guru),
     * ditambah guru yang masih memiliki penugasan aktif.
     *
     * @return Collection<int, User>
     */
    public function teachersForSchool(?string $schoolId): Collection
    {
        $ids = collect();

        if ($schoolId) {
            $ids = $ids->merge(
                GtkEmployment::query()
                    ->where('school_id', $schoolId)
                    ->pluck('user_id')
            );

            $ids = $ids->merge(
                TeachingAssignment::query()
                    ->where('school_id', $schoolId)
                    ->where('status', 'active')
                    ->pluck('teacher_id')
            );

            $workUnitId = School::query()->whereKey($schoolId)->value('work_unit_id');

            if ($workUnitId) {
                $ids = $ids->merge(
                    GtkWorkUnit::query()
                        ->where('work_unit_id', $workUnitId)
                        ->pluck('user_id')
                );
            }
        }

        $ids = $ids->filter()->unique()->values();

        if ($ids->isEmpty()) {
            // Jangan biarkan halaman kosong hanya karena data kepegawaian lama
            // belum terhubung ke sekolah; admin tetap bisa memilih user aktif.
            return User::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return User::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return Collection<int, TeacherScheduleAvailability>
     */
    public function rowsForSchool(?string $schoolId): Collection
    {
        if (! $schoolId) {
            return collect();
        }

        return TeacherScheduleAvailability::query()
            ->where('school_id', $schoolId)
            ->get()
            ->keyBy('teacher_id');
    }
}
