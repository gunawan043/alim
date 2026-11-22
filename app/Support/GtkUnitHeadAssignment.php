<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\GtkWorkUnit;
use App\Models\Role;
use App\Models\School;
use App\Models\StructuralAssignment;
use App\Models\StructuralPosition;
use App\Models\User;
use App\Models\WorkUnit;

/**
 * Menerapkan penetapan kepala/wakil satuan kerja ke data yang dipakai
 * menu dan hak akses: jabatan kepegawaian, role, penempatan GTK, dan
 * penugasan struktural.
 *
 * Tanpa ini, penetapan di halaman Kepala & Wakil hanya tersimpan sebagai
 * metadata dan tidak berpengaruh ke menu pengguna.
 */
class GtkUnitHeadAssignment
{
    public const HEAD = 'head';

    public const VICE = 'vice';

    /**
     * Tetapkan user sebagai kepala/wakil unit.
     *
     * @return array<string, mixed> data jabatan sebelumnya, untuk dikembalikan saat dilepas
     */
    public function assign(User $user, WorkUnit $unit, string $type): array
    {
        $position = $this->positionFor($unit, $type);
        $title = $position?->name ?? $this->titleFor($unit, $type);

        $employment = $user->employment;

        $placement = GtkWorkUnit::firstOrNew([
            'user_id' => $user->id,
            'work_unit_id' => $unit->id,
        ]);

        $previous = [
            'user_id' => $user->id,
            'jabatan' => $employment?->jabatan,
            'jabatan_id' => $employment?->jabatan_id,
            'placement_jabatan' => $placement->jabatan,
            'placement_position_id' => $placement->position_id,
        ];

        if (config('gtk_work_unit.lifecycle.enabled', false)) {
            $lifecycle = app(\App\Services\GtkPlacementLifecycleService::class);

            if ($placement->exists) {
                $lifecycle->updatePosition($placement, $position?->id, $title, auth()->id());
            } else {
                $lifecycle->start(
                    $user->id,
                    $unit->id,
                    $position?->id,
                    today()->toDateString(),
                    auth()->id(),
                    null,
                    ! GtkWorkUnit::where('user_id', $user->id)->exists(),
                    $title
                );
            }
        } else {
            if (! $placement->exists) {
                $placement->is_primary = ! GtkWorkUnit::where('user_id', $user->id)->exists();
            }

            $placement->jabatan = $title;
            $placement->position_id = $position?->id;
            $placement->save();
        }

        if ($employment) {
            $employment->update([
                'jabatan' => $title,
                'jabatan_id' => $position?->id,
            ]);
        }

        $this->syncRole($user, $unit, $position);
        $this->syncStructuralAssignment($user, $unit, $position);

        return $previous;
    }

    /**
     * Lepas penetapan kepala/wakil dan kembalikan jabatan sebelumnya.
     *
     * @param  array<string, mixed>|null  $previous
     */
    public function release(User $user, WorkUnit $unit, ?array $previous, string $type): void
    {
        $position = $this->positionFor($unit, $type);
        $title = $position?->name ?? $this->titleFor($unit, $type);

        $placement = GtkWorkUnit::where('user_id', $user->id)
            ->where('work_unit_id', $unit->id)
            ->first();

        if ($placement && $placement->jabatan === $title) {
            $placement->update([
                'jabatan' => $previous['placement_jabatan'] ?? null,
                'position_id' => $previous['placement_position_id'] ?? null,
            ]);
        }

        $employment = $user->employment;

        if ($employment && $employment->jabatan === $title) {
            $employment->update([
                'jabatan' => $previous['jabatan'] ?? null,
                'jabatan_id' => $previous['jabatan_id'] ?? null,
            ]);
        }

        $employment?->refresh();

        // Role mengikuti jabatan yang dipulihkan, bukan role unit.
        $this->syncRoleFromEmployment($user, $employment);
        $this->endStructuralAssignment($user, $position);
    }

    /**
     * Posisi struktural kepala/wakil yang paling sesuai untuk unit ini.
     */
    public function positionFor(WorkUnit $unit, string $type): ?StructuralPosition
    {
        $prefix = $type === self::VICE ? 'Wakil Kepala' : 'Kepala';
        $all = StructuralPosition::query()->where('is_active', true);

        // 1. Nama persis: "Kepala Perpustakaan", "Wakil Kepala Humas & Personalia".
        foreach ([$unit->name, $this->shortUnitName($unit->name)] as $name) {
            if ($name === '') {
                continue;
            }

            $exact = (clone $all)
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($prefix.' '.$name)])
                ->first();

            if ($exact) {
                return $exact;
            }
        }

        // Pimpinan pondok memakai jabatan Mudir / Wadir, bukan "Kepala ...".
        $domainName = strtolower((string) $unit->domain?->name);

        if (str_contains($domainName, 'pimpinan') || str_contains(strtolower($unit->name), 'pimpinan')) {
            $pimpinan = $type === self::VICE ? 'Wadir 1' : 'Mudir';

            $position = (clone $all)
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($pimpinan)])
                ->first();

            if ($position) {
                return $position;
            }
        }

        $candidates = (clone $all)
            ->when($unit->domain_id, fn ($q) => $q->where('domain_id', $unit->domain_id))
            ->where('name', 'like', $prefix.'%')
            ->orderBy('urutan')
            ->get();

        if ($candidates->isEmpty() && $unit->domain_id) {
            $candidates = (clone $all)->where('name', 'like', $prefix.'%')->orderBy('urutan')->get();
        }

        // 2. Kandidat yang namanya berbagi kata dengan unit, mis. asrama -> "Kepala Asrama".
        $unitTokens = $this->unitTokens($unit->name);
        $best = null;
        $bestOverlap = 0;

        foreach ($candidates as $candidate) {
            $overlap = count(array_intersect($unitTokens, $this->unitTokens($candidate->name)));

            if ($overlap > $bestOverlap) {
                $bestOverlap = $overlap;
                $best = $candidate;
            }
        }

        if ($best && $bestOverlap > 0) {
            return $best;
        }

        // 3. Unit sekolah memakai jabatan baku "Kepala Satuan Pendidikan".
        if (School::where('work_unit_id', $unit->id)->exists()) {
            $schoolPosition = (clone $all)
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($prefix.' Satuan Pendidikan')])
                ->first();

            if ($schoolPosition) {
                return $schoolPosition;
            }
        }

        // Tidak ada jabatan yang benar-benar cocok: biarkan judul dibentuk
        // dari nama unit, jangan pakai jabatan unit lain.
        return null;
    }

    public function titleFor(WorkUnit $unit, string $type): string
    {
        $prefix = $type === self::VICE ? 'Wakil Kepala' : 'Kepala';

        return $prefix.' '.$unit->name;
    }

    /**
     * Nama unit tanpa awalan "Unit"/"Satuan", mis. "Unit Gizi" -> "Gizi".
     */
    private function shortUnitName(string $name): string
    {
        $short = trim((string) preg_replace('/^(unit|satuan)\s+/i', '', $name));

        return strcasecmp($short, $name) === 0 ? '' : $short;
    }

    /**
     * Kata pembeda pada nama unit/jabatan, tanpa kata umum.
     *
     * @return array<int, string>
     */
    private function unitTokens(string $name): array
    {
        $generic = ['unit', 'satuan', 'kepala', 'wakil', 'dan', 'top', 'management', 'of', 'the'];

        $tokens = preg_split('/[^a-z0-9]+/', strtolower($name)) ?: [];

        return array_values(array_unique(array_filter(
            $tokens,
            fn (string $token) => $token !== '' && ! in_array($token, $generic, true),
        )));
    }

    private function syncRole(User $user, WorkUnit $unit, ?StructuralPosition $position): void
    {
        $role = $position?->role?->name ?? GtkRoleResolver::fromWorkUnit($unit->id);

        if (! $role && $user->roles()->doesntExist()) {
            $role = GtkRoleResolver::defaultRole();
        }

        // TAMBAHKAN role, jangan ganti. Satu GTK dapat merangkap kepala
        // sekolah dan kepala asrama; syncRoles() akan menghapus peran lain
        // dan membuat area kerjanya hilang.
        if ($role && Role::where('name', $role)->exists()) {
            $user->assignRole($role);
        }
    }

    private function syncRoleFromEmployment(User $user, mixed $employment): void
    {
        $role = GtkRoleResolver::resolve($employment?->jabatan, $employment?->jabatan_id);

        if ($role && Role::where('name', $role)->exists()) {
            $user->assignRole($role);
        }
    }

    private function syncStructuralAssignment(User $user, WorkUnit $unit, ?StructuralPosition $position): void
    {
        if (! $position) {
            return;
        }

        $schoolId = School::where('work_unit_id', $unit->id)->value('id');

        if (! $schoolId) {
            return;
        }

        $academicYearId = AcademicYear::where('is_active', true)
            ->orderBy('start_date', 'desc')
            ->value('id');

        if (! $academicYearId) {
            return;
        }

        StructuralAssignment::updateOrCreate(
            [
                'user_id' => $user->id,
                'position_id' => $position->id,
                'school_id' => $schoolId,
                'academic_year_id' => $academicYearId,
            ],
            [
                'status' => 'active',
                'start_date' => now()->toDateString(),
                'end_date' => null,
                'notes' => 'Ditetapkan lewat menu Kepala & Wakil Satuan Kerja',
            ]
        );
    }

    private function endStructuralAssignment(User $user, ?StructuralPosition $position): void
    {
        if (! $position) {
            return;
        }

        StructuralAssignment::where('user_id', $user->id)
            ->where('position_id', $position->id)
            ->where('status', 'active')
            ->update([
                'status' => 'inactive',
                'end_date' => now()->toDateString(),
            ]);
    }
}
