<?php

namespace App\Services;

use App\Models\GtkEmployment;
use App\Models\GtkWorkUnit;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Support\Facades\DB;

class GtkPositionAssignmentService
{
    public function __construct(
        private readonly GtkPlacementLifecycleService $lifecycle
    ) {}

    /**
     * Tetapkan GTK ke jabatan struktural sekaligus pindahkan satuan kerja utamanya.
     *
     * Legacy behavior is retained while the lifecycle flag is disabled. When
     * enabled, placement identity is keyed by (user_id, work_unit_id) and the
     * period history is recorded by the lifecycle service.
     */
    public function assign(User $user, ?WorkUnit $workUnit, string $positionName, string $positionType): void
    {
        $positionId = DB::table('structural_positions')->where('name', $positionName)->value('id');

        DB::transaction(function () use ($user, $workUnit, $positionName, $positionType, $positionId) {
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

            if (config('gtk_work_unit.lifecycle.enabled', false)) {
                $this->assignWithLifecycle($user, $workUnit, $positionId, $positionName);

                return;
            }

            // Legacy path — kept verbatim for compatibility while flag is off.
            GtkWorkUnit::updateOrCreate(
                ['user_id' => $user->id, 'is_primary' => true],
                ['work_unit_id' => $workUnit->id, 'jabatan' => $positionName]
            );
        });
    }

    private function assignWithLifecycle(
        User $user,
        WorkUnit $workUnit,
        ?string $positionId,
        string $positionName
    ): void {
        $current = GtkWorkUnit::where('user_id', $user->id)
            ->where('is_primary', true)
            ->first();

        if ($current && (string) $current->work_unit_id === (string) $workUnit->id) {
            $this->lifecycle->updatePosition($current, $positionId, $positionName, auth()->id());

            return;
        }

        if ($current) {
            $this->lifecycle->transfer(
                $user->id,
                $current->work_unit_id,
                $workUnit->id,
                $positionId,
                today()->toDateString(),
                auth()->id(),
                null,
                $positionName
            );

            return;
        }

        $this->lifecycle->start(
            $user->id,
            $workUnit->id,
            $positionId,
            today()->toDateString(),
            auth()->id(),
            null,
            true,
            $positionName
        );
    }
}
