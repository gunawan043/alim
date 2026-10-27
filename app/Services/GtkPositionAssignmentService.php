<?php

namespace App\Services;

use App\Models\GtkEmployment;
use App\Models\GtkWorkUnit;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Support\Facades\DB;

class GtkPositionAssignmentService
{
    /**
     * Tetapkan GTK ke jabatan struktural sekaligus pindahkan satuan kerja utamanya.
     */
    public function assign(User $user, ?WorkUnit $workUnit, string $positionName, string $positionType): void
    {
        $positionId = DB::table('structural_positions')->where('name', $positionName)->value('id');

        $employment = GtkEmployment::firstOrNew(['user_id' => $user->id]);
        $employment->jabatan = $positionName;
        $employment->jabatan_id = $positionId;
        $employment->position_type = $positionType;

        if ($workUnit) {
            // Pakai nama asli dari DB (accessor name() mengubah kapitalisasi).
            $employment->satuan_kerja = $workUnit->getRawOriginal('name');
        }

        $employment->save();

        if (! $workUnit) {
            return;
        }

        GtkWorkUnit::updateOrCreate(
            ['user_id' => $user->id, 'is_primary' => true],
            ['work_unit_id' => $workUnit->id, 'jabatan' => $positionName]
        );
    }
}
