<?php

namespace App\Services\Sarpras;

use App\Models\Asset;
use App\Models\AssetMaintenanceLog;
use App\Models\AssetMaintenanceSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Workflow 3 — Preventive Maintenance.
 *
 * Lifecycle: schedule -> assign technician -> execute -> checklist -> condition
 * update -> next-due-date calculation -> timeline updated.
 */
class MaintenanceWorkflow
{
    public function __construct(
        protected AssetEventLogger $eventLogger,
        protected StateMachine $stateMachine
    ) {
        $this->stateMachine->define(
            StateMachineRegistry::ASSET_STATUS,
            StateMachineRegistry::ASSET_STATUS_TRANSITIONS
        );
    }

    public const MAINTENANCE_STATE = 'maintenance_state';

    public const MAINTENANCE_TRANSITIONS = [
        'scheduled' => ['assigned', 'cancelled'],
        'assigned' => ['in_progress'],
        'in_progress' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    /**
     * Create the maintenance schedule for an asset.
     */
    public function createSchedule(
        Asset $asset,
        User $creator,
        string $frequency,
        string $firstMaintenanceDate,
        ?User $responsible = null,
        ?string $vendorName = null,
        float $estimatedCost = 0
    ): AssetMaintenanceSchedule {
        return DB::transaction(function () use (
            $asset,
            $creator,
            $frequency,
            $firstMaintenanceDate,
            $responsible,
            $vendorName,
            $estimatedCost
        ) {
            $schedule = AssetMaintenanceSchedule::create([
                'asset_id' => $asset->id,
                'work_unit_id' => $asset->work_unit_id,
                'school_id' => $asset->school_id ?? null,
                'building_id' => $asset->building_id ?? null,
                'room_id' => $asset->room_id,
                'maintenance_type' => 'preventive',
                'frequency' => $frequency,
                'last_maintenance_date' => null,
                'next_maintenance_date' => $firstMaintenanceDate,
                'responsible_user_id' => $responsible?->id,
                'vendor_name' => $vendorName,
                'estimated_cost' => $estimatedCost,
                'reminder_days_before' => 7,
                'is_active' => true,
                'notes' => null,
                'created_by' => $creator->id,
            ]);

            $this->eventLogger->logMaintenanceScheduled(
                $asset,
                $schedule->id,
                $creator->id
            );

            return $schedule;
        });
    }

    /**
     * Assign a technician responsible for the upcoming maintenance.
     */
    public function assignTechnician(AssetMaintenanceSchedule $schedule, User $technician, User $assigner): AssetMaintenanceSchedule
    {
        return DB::transaction(function () use ($schedule, $technician, $assigner) {
            $schedule->update([
                'responsible_user_id' => $technician->id,
            ]);

            return $schedule;
        });
    }

    /**
     * Execute maintenance: writes an AssetMaintenanceLog, updates
     * the schedule, the asset condition, and the lifecycle timeline.
     */
    public function executeMaintenance(
        AssetMaintenanceSchedule $schedule,
        User $technician,
        string $conditionBefore,
        string $conditionAfter,
        string $workDescription,
        float $actualCost,
        ?string $partsReplaced = null,
        ?string $photoPath = null,
        ?string $checklistJson = null
    ): AssetMaintenanceLog {
        return DB::transaction(function () use (
            $schedule,
            $technician,
            $conditionBefore,
            $conditionAfter,
            $workDescription,
            $actualCost,
            $partsReplaced,
            $photoPath,
            $checklistJson
        ) {
            $asset = $schedule->asset;

            // Mark asset as under_maintenance for the duration of the work.
            if ($asset->status === 'active') {
                $this->moveAssetStatus($asset, 'under_maintenance', $technician->id, 'preventive maintenance in progress');
            }

            $log = AssetMaintenanceLog::create([
                'schedule_id' => $schedule->id,
                'asset_id' => $asset->id,
                'work_unit_id' => $schedule->work_unit_id,
                'school_id' => $schedule->school_id,
                'building_id' => $schedule->building_id,
                'room_id' => $schedule->room_id,
                'maintenance_type' => 'preventive',
                'maintenance_date' => Carbon::today(),
                'performed_by' => $technician->id,
                'vendor_name' => $schedule->vendor_name,
                'actual_cost' => $actualCost,
                'condition_before' => $conditionBefore,
                'condition_after' => $conditionAfter,
                'work_description' => $workDescription,
                'parts_replaced' => $partsReplaced,
                'photo_path' => $photoPath,
                'notes' => $checklistJson, // checklist serialised as JSON
                'created_by' => $technician->id,
            ]);

            // Update schedule dates
            $next = $this->calculateNextDate($schedule->next_maintenance_date, $schedule->frequency);
            $schedule->update([
                'last_maintenance_date' => Carbon::today(),
                'next_maintenance_date' => $next,
            ]);

            // Update asset condition (drives the listener)
            $this->eventLogger->logMaintenanceCompleted(
                $asset,
                $schedule->id,
                $conditionAfter,
                $actualCost,
                $technician->id
            );

            // If asset was under_maintenance only for the maintenance, restore it.
            if ($asset->status === 'under_maintenance') {
                $this->moveAssetStatus($asset, 'active', $technician->id, 'maintenance finished');
            }

            return $log;
        });
    }

    /**
     * Calculate next maintenance date based on frequency.
     */
    protected function calculateNextDate($fromDate, string $frequency): Carbon
    {
        $base = $fromDate instanceof Carbon ? $fromDate->copy() : Carbon::parse($fromDate);

        return match ($frequency) {
            'harian' => $base->addDay(),
            'mingguan' => $base->addWeek(),
            'bulanan' => $base->addMonth(),
            'triwulan' => $base->addMonths(3),
            'semester' => $base->addMonths(6),
            'tahunan' => $base->addYear(),
            'sesuai_kebutuhan' => $base,
            default => $base->addMonth(),
        };
    }

    protected function moveAssetStatus(Asset $asset, string $target, ?int $actorId, string $reason): Asset
    {
        $from = $asset->status ?: 'active';
        if ($from === $target) {
            return $asset;
        }

        $this->stateMachine->assert(
            StateMachineRegistry::ASSET_STATUS,
            $from,
            $target
        );

        $asset->update(['status' => $target]);
        $this->eventLogger->logAssetStatusChanged($asset, $from, $target, $reason, $actorId);

        return $asset;
    }
}