<?php

namespace App\Observers;

use App\Models\GtkEmployment;
use App\Models\StructuralAssignment;
use App\Models\StructuralPosition;
use Illuminate\Support\Facades\Log;

class GtkEmploymentObserver
{
    public function created(GtkEmployment $employment): void
    {
        $this->syncRoles($employment);
    }

    public function updated(GtkEmployment $employment): void
    {
        if ($employment->wasChanged('jabatan_id')) {
            $this->syncRoles($employment);
        }
    }

    public function deleted(GtkEmployment $employment): void
    {
        $this->removeGtkRolesIfOrphaned($employment);
    }

    /**
     * Sinkronkan Spatie role user berdasarkan jabatan GTK.
     *
     * Logika:
     * - Prefer StructuralAssignment (work-unit-aware domain) over the seeder-snapshot
     *   position. This ensures GTK placed in a Satuan Pendidikan work unit but
     *   holding a "Staf Tata Usaha" position (seeder domain: Keuangan) get the
     *   correct Satuan Pendidikan role.
     * - Fallback to position->role_id from the employment record if no assignment.
     * - syncRoles() REPLACE semua role user, sehingga role lama yang tidak relevan hilang.
     */
    protected function syncRoles(GtkEmployment $employment): void
    {
        $user = $employment->user;
        if (! $user) {
            return;
        }

        $finalRoles = [];

        // 1. Try StructuralAssignment first (work-unit-aware domain)
        $assignment = StructuralAssignment::where('user_id', $user->id)
            ->where('status', 'active')
            ->with(['position.domain', 'position.role'])
            ->first();

        if ($assignment && $assignment->position) {
            $pos = $assignment->position;
            if ($pos->role) {
                $finalRoles[] = $pos->role->name;
            }
        }

        // 2. Fallback: use the employment's jabatan_id directly
        if (empty($finalRoles) && $employment->jabatan_id) {
            $jabatan = StructuralPosition::with('role')->find($employment->jabatan_id);
            if ($jabatan?->role) {
                $finalRoles[] = $jabatan->role->name;
            }
            foreach (($jabatan?->roles ?? []) as $r) {
                if (! in_array($r, $finalRoles)) {
                    $finalRoles[] = $r;
                }
            }
        }

        $finalRoles = array_values(array_unique($finalRoles));

        try {
            $user->syncRoles($finalRoles);
        } catch (\Throwable $e) {
            Log::warning('GtkEmploymentObserver: syncRoles gagal', [
                'user_id' => $user->id,
                'jabatan_id' => $employment->jabatan_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Saat GtkEmployment dihapus, jika user tidak punya GtkEmployment aktif lain
     * dan tidak punya peran sistem (is_system_admin), cabut role GTK & role spesifik.
     */
    protected function removeGtkRolesIfOrphaned(GtkEmployment $employment): void
    {
        $user = $employment->user;
        if (! $user || $user->isSystemAdmin()) {
            return;
        }

        $stillHasEmployment = GtkEmployment::where('user_id', $user->id)->exists();
        if ($stillHasEmployment) {
            return;
        }

        try {
            $currentRoles = $user->getRoleNames()->toArray();
            $remaining = $currentRoles;
            $user->syncRoles($remaining);
        } catch (\Throwable $e) {
            Log::warning('GtkEmploymentObserver: removeGtkRoles gagal', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
