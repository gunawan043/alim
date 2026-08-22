<?php

namespace App\Observers;

use App\Models\GtkEmployment;
use App\Models\Position;
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
     * - Ambil array `roles` dari Position (JSON cast → array).
     * - syncRoles() REPLACE semua role user, sehingga role lama yang tidak relevan hilang.
     */
    protected function syncRoles(GtkEmployment $employment): void
    {
        $user = $employment->user;
        if (! $user) {
            return;
        }

        $jabatanRoles = [];
        if ($employment->jabatan_id) {
            $jabatan = Position::find($employment->jabatan_id);
            $jabatanRoles = $jabatan?->roles ?? [];
        }

        $finalRoles = array_values(array_unique($jabatanRoles));

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
