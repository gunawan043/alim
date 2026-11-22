<?php

namespace App\Services;

use App\Exceptions\PlacementLifecycleException;
use App\Models\GtkWorkUnit;
use App\Models\GtkWorkUnitPeriod;
use App\Models\StructuralPosition;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Central lifecycle for GTK work-unit placements.
 *
 * The gtk_work_unit row is the current projection and its
 * (user_id, work_unit_id) identity must never change. Each start/end cycle is
 * appended to gtk_work_unit_periods.
 */
class GtkPlacementLifecycleService
{
    /**
     * Start (or reactivate) a placement period.
     */
    public function start(
        User|string $user,
        WorkUnit|string $workUnit,
        StructuralPosition|string|null $position = null,
        ?string $startDate = null,
        ?string $actorId = null,
        ?string $decreeId = null,
        ?bool $isPrimary = null,
        ?string $jabatan = null
    ): GtkWorkUnitPeriod {
        $userId = $this->id($user);
        $workUnitId = $this->id($workUnit);
        $positionId = $this->id($position);
        $start = $this->date($startDate);

        return DB::transaction(function () use ($userId, $workUnitId, $positionId, $start, $actorId, $decreeId, $isPrimary, $jabatan) {
            $placement = GtkWorkUnit::query()
                ->where('user_id', $userId)
                ->where('work_unit_id', $workUnitId)
                ->lockForUpdate()
                ->first();

            $this->assertNoOverlap($userId, $workUnitId, $start, null);

            $attributes = [];
            if ($positionId !== null) {
                $attributes['position_id'] = $positionId;
                if ($jabatan === null) {
                    $attributes['jabatan'] = StructuralPosition::whereKey($positionId)->value('name');
                }
            }
            if ($jabatan !== null) {
                $attributes['jabatan'] = $jabatan;
            }

            if (! $placement) {
                $placement = new GtkWorkUnit([
                    'user_id' => $userId,
                    'work_unit_id' => $workUnitId,
                    'jabatan' => $attributes['jabatan'] ?? null,
                    'position_id' => $attributes['position_id'] ?? null,
                    'is_primary' => $isPrimary ?? ! GtkWorkUnit::where('user_id', $userId)->exists(),
                ]);
                $placement->save();
            } else {
                if ($attributes !== []) {
                    $placement->fill($attributes);
                }

                $placement->save();
            }

            if ($isPrimary === true) {
                $this->setPrimary($placement);
            }

            return GtkWorkUnitPeriod::create([
                'placement_id' => $placement->id,
                'user_id' => $userId,
                'work_unit_id' => $workUnitId,
                'position_id' => $placement->position_id,
                'decree_id' => $decreeId,
                'status' => GtkWorkUnitPeriod::STATUS_ACTIVE,
                'start_date' => $start->toDateString(),
                'end_date' => null,
                'created_by' => $actorId,
            ]);
        });
    }

    /**
     * End the active period for a placement without deleting the main row.
     */
    public function end(
        GtkWorkUnit|string $placement,
        ?string $endDate = null,
        ?string $reason = null,
        ?string $actorId = null
    ): GtkWorkUnitPeriod {
        $placementId = $this->id($placement);
        $end = $this->date($endDate);

        return DB::transaction(function () use ($placementId, $end, $reason, $actorId) {
            $placement = GtkWorkUnit::whereKey($placementId)->lockForUpdate()->firstOrFail();

            $period = GtkWorkUnitPeriod::query()
                ->where('placement_id', $placement->id)
                ->where('status', GtkWorkUnitPeriod::STATUS_ACTIVE)
                ->whereNull('end_date')
                ->lockForUpdate()
                ->latest('start_date')
                ->first();

            // Legacy row without period history: create the missing period
            // only for the row being ended, never as a bulk backfill.
            if (! $period) {
                $period = GtkWorkUnitPeriod::create([
                    'placement_id' => $placement->id,
                    'user_id' => $placement->user_id,
                    'work_unit_id' => $placement->work_unit_id,
                    'position_id' => $placement->position_id,
                    'status' => GtkWorkUnitPeriod::STATUS_ACTIVE,
                    'start_date' => $placement->created_at?->toDateString() ?? $end->toDateString(),
                    'end_date' => null,
                    'created_by' => $actorId,
                ]);
            }

            if ($period->start_date && $period->start_date->gt($end)) {
                throw PlacementLifecycleException::invalidPeriod(
                    'Tanggal akhir tidak boleh sebelum tanggal mulai periode.'
                );
            }

            $period->update([
                'status' => GtkWorkUnitPeriod::STATUS_ENDED,
                'end_date' => $end->toDateString(),
                'ended_reason' => $reason,
                'ended_by' => $actorId,
            ]);

            $placement->update(['is_primary' => false]);

            return $period;
        });
    }

    /**
     * End the old unit and start/reactivate the destination unit atomically.
     *
     * @return array{ended: GtkWorkUnitPeriod, started: GtkWorkUnitPeriod}
     */
    public function transfer(
        User|string $user,
        WorkUnit|string $fromWorkUnit,
        WorkUnit|string $toWorkUnit,
        StructuralPosition|string|null $position = null,
        ?string $startDate = null,
        ?string $actorId = null,
        ?string $decreeId = null,
        ?string $jabatan = null
    ): array {
        $userId = $this->id($user);
        $fromId = $this->id($fromWorkUnit);
        $toId = $this->id($toWorkUnit);

        if ($fromId === $toId) {
            throw PlacementLifecycleException::invalidPeriod('Unit asal dan tujuan tidak boleh sama.');
        }

        return DB::transaction(function () use ($userId, $fromId, $toId, $position, $startDate, $actorId, $decreeId, $jabatan) {
            $from = GtkWorkUnit::query()
                ->where('user_id', $userId)
                ->where('work_unit_id', $fromId)
                ->lockForUpdate()
                ->first();

            if (! $from) {
                throw PlacementLifecycleException::placementNotFound($userId, $fromId);
            }

            $ended = $this->end($from, $startDate, 'Transfer penempatan', $actorId);

            $started = $this->start(
                $userId,
                $toId,
                $position,
                $startDate,
                $actorId,
                $decreeId,
                true,
                $jabatan
            );

            return ['ended' => $ended, 'started' => $started];
        });
    }

    /**
     * Reuse an existing main row for a new period.
     */
    public function reactivate(
        GtkWorkUnit|string $placement,
        StructuralPosition|string|null $position = null,
        ?string $startDate = null,
        ?string $actorId = null,
        ?string $decreeId = null,
        ?string $jabatan = null
    ): GtkWorkUnitPeriod {
        $placementId = $this->id($placement);
        $row = GtkWorkUnit::whereKey($placementId)->firstOrFail();

        return $this->start(
            $row->user_id,
            $row->work_unit_id,
            $position,
            $startDate,
            $actorId,
            $decreeId,
            true,
            $jabatan
        );
    }

    /**
     * Update position on both the current row and the active period.
     */
    public function updatePosition(
        GtkWorkUnit|string $placement,
        StructuralPosition|string|null $position = null,
        ?string $jabatan = null,
        ?string $actorId = null
    ): GtkWorkUnit {
        $placementId = $this->id($placement);
        $positionId = $this->id($position);

        return DB::transaction(function () use ($placementId, $positionId, $jabatan) {
            $row = GtkWorkUnit::whereKey($placementId)->lockForUpdate()->firstOrFail();

            $attributes = [];
            if ($positionId !== null) {
                $attributes['position_id'] = $positionId;
                if ($jabatan === null) {
                    $attributes['jabatan'] = StructuralPosition::whereKey($positionId)->value('name');
                }
            }
            if ($jabatan !== null) {
                $attributes['jabatan'] = $jabatan;
            }

            if ($attributes !== []) {
                $row->update($attributes);
            }

            $period = GtkWorkUnitPeriod::query()
                ->where('placement_id', $row->id)
                ->where('status', GtkWorkUnitPeriod::STATUS_ACTIVE)
                ->whereNull('end_date')
                ->latest('start_date')
                ->first();

            if ($period && $positionId !== null) {
                $period->update(['position_id' => $positionId]);
            }

            return $row;
        });
    }

    /**
     * Ensure only one active primary placement per user.
     */
    public function setPrimary(GtkWorkUnit|string $placement): GtkWorkUnit
    {
        $placementId = $this->id($placement);

        return DB::transaction(function () use ($placementId) {
            $row = GtkWorkUnit::whereKey($placementId)->lockForUpdate()->firstOrFail();

            GtkWorkUnit::where('user_id', $row->user_id)
                ->where('id', '!=', $row->id)
                ->update(['is_primary' => false]);

            if (! $row->is_primary) {
                $row->update(['is_primary' => true]);
            }

            return $row->refresh();
        });
    }

    /**
     * Reject overlapping active periods for the same user and unit.
     */
    private function assertNoOverlap(
        string $userId,
        string $workUnitId,
        Carbon $start,
        ?Carbon $end,
        ?string $ignorePeriodId = null
    ): void {
        $query = GtkWorkUnitPeriod::query()
            ->where('user_id', $userId)
            ->where('work_unit_id', $workUnitId)
            ->where('status', GtkWorkUnitPeriod::STATUS_ACTIVE)
            ->where(function ($q) use ($start) {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $start->toDateString());
            })
            ->when($end, fn ($q) => $q->whereDate('start_date', '<=', $end->toDateString()))
            ->when($ignorePeriodId, fn ($q) => $q->where('id', '!=', $ignorePeriodId));

        if ($query->exists()) {
            throw PlacementLifecycleException::activePeriodExists(
                $userId,
                $workUnitId,
                $start->toDateString()
            );
        }
    }

    private function id(User|WorkUnit|StructuralPosition|GtkWorkUnit|string|null $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof Model
            ? (string) $value->getKey()
            : (string) $value;
    }

    private function date(?string $value): Carbon
    {
        return $value
            ? Carbon::parse($value)->startOfDay()
            : Carbon::today();
    }
}
