<?php

namespace App\Services\Sarpras;

use App\Models\AssetMaintenanceLog;
use App\Models\ChecklistInstance;
use App\Models\MaintenanceHistory;
use App\Models\RepairRequest;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPauseEvent;
use App\Models\WorkOrderProgressNote;
use App\Services\SarprasCacheInvalidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Delegates the low-level work order lifecycle to the service so controllers remain thin.
 * Also acts as the "execution engine" for the offline sync — mobile workers hit these
 * methods to progress through the order states.
 */
class WorkOrderExecutionService
{
    public function __construct(
        protected AssetEventLogger $eventLogger,
        protected PhotoDocumentationService $photoService,
        protected ChecklistEngine $checklistEngine,
        protected SarprasCacheInvalidator $cacheInvalidator,
    ) {}

    public function start(WorkOrder $order, User $technician, array $payload = []): WorkOrder
    {
        return DB::transaction(function () use ($order, $technician, $payload) {
            if (! in_array($order->status, ['dispatched', 'draft', 'in_progress', 'waiting_parts', 'paused'], true)) {
                throw new \DomainException("Cannot start — order is '{$order->status}'.");
            }

            $order->update([
                'status' => 'in_progress',
                'actual_start' => now(),
            ]);

            $this->logNote($order, $technician, "Work started by {$technician->name}", 'observation');

            if (! empty($payload['before_photos'])) {
                $this->photoService->uploadMany($order, $payload['before_photos'], [
                    'photo_type' => 'before',
                    'uploaded_by' => $technician->id,
                ]);
            }

            $this->cacheInvalidator->invalidateWorkOrder($order);

            if ($order->asset) {
                $this->eventLogger->logWorkOrderAssigned($order->asset, $order->order_number, $technician->id, $technician->id);
            }

            return $order->fresh();
        });
    }

    public function pause(WorkOrder $order, User $technician, string $reasonCode, ?string $reasonText = null): WorkOrderPauseEvent
    {
        return DB::transaction(function () use ($order, $technician, $reasonCode, $reasonText) {
            $event = WorkOrderPauseEvent::create([
                'id' => (string) Str::uuid(),
                'work_order_id' => $order->id,
                'user_id' => $technician->id,
                'reason_code' => $reasonCode,
                'reason_text' => $reasonText,
            ]);

            $order->update(['status' => 'paused']);

            $this->logNote($order, $technician, "Paused: {$reasonText} [{$reasonCode}]", 'pause_reason');

            $this->cacheInvalidator->invalidateWorkOrder($order);

            return $event;
        });
    }

    public function resume(WorkOrder $order, User $technician, ?string $notes = null): WorkOrder
    {
        return DB::transaction(function () use ($order, $technician, $notes) {
            $lastPause = WorkOrderPauseEvent::where('work_order_id', $order->id)
                ->whereNull('resumed_at')
                ->latest('paused_at')
                ->first();

            if ($lastPause) {
                $duration = now()->diffInSeconds($lastPause->paused_at);
                $lastPause->update(['resumed_at' => now(), 'pause_duration_seconds' => $duration]);
            }

            $order->update(['status' => 'in_progress']);

            if ($notes) {
                $this->logNote($order, $technician, "Resumed: {$notes}", 'comment');
            }

            $this->cacheInvalidator->invalidateWorkOrder($order);

            return $order->fresh();
        });
    }

    public function complete(WorkOrder $order, User $technician, array $payload = []): WorkOrder
    {
        return DB::transaction(function () use ($order, $technician, $payload) {
            $asset = $order->asset;
            $schedule = $order->maintenanceSchedule;
            $conditionBefore = $asset?->condition;
            $conditionAfter = $payload['condition_after'] ?? $conditionBefore;

            $order->update([
                'status' => 'completed',
                'actual_end' => now(),
                'completion_notes' => $payload['notes'] ?? null,
                'total_cost' => (float) ($payload['total_cost'] ?? 0),
            ]);

            if (! empty($payload['after_photos'])) {
                $this->photoService->uploadMany($order, $payload['after_photos'], [
                    'photo_type' => 'after',
                    'uploaded_by' => $technician->id,
                ]);
            }

            if ($asset && ! empty($payload['condition_after'])) {
                $asset->update([
                    'condition' => $payload['condition_after'],
                    'status' => 'tersedia',
                    'last_condition_update' => today(),
                ]);
            }

            $nextDueDate = null;

            if ($schedule) {
                $nextDueDate = $this->calculateNextMaintenanceDate(today(), $schedule->frequency);

                $schedule->update([
                    'last_maintenance_date' => today(),
                    'next_maintenance_date' => $nextDueDate,
                    'status' => 'completed',
                    'unit_approval_status' => 'approved',
                ]);

                AssetMaintenanceLog::create([
                    'schedule_id' => $schedule->id,
                    'work_unit_id' => $schedule->work_unit_id,
                    'school_id' => $schedule->school_id,
                    'asset_id' => $order->asset_id,
                    'building_id' => $order->building_id,
                    'room_id' => $order->room_id,
                    'maintenance_type' => $schedule->maintenance_type,
                    'maintenance_date' => today(),
                    'performed_by' => $technician->id,
                    'handled_by' => $technician->id,
                    'actual_cost' => (float) ($payload['total_cost'] ?? 0),
                    'cost' => (float) ($payload['total_cost'] ?? 0),
                    'condition_before' => $conditionBefore,
                    'condition_after' => $conditionAfter,
                    'work_description' => $payload['notes'] ?? $order->scope_of_work,
                    'description' => $payload['notes'] ?? $order->scope_of_work,
                    'parts_replaced' => $payload['parts_replaced'] ?? null,
                    'next_due_date' => $nextDueDate,
                    'created_by' => $technician->id,
                ]);
            }

            if ($order->repairRequest) {
                $order->repairRequest->update([
                    'status' => RepairRequest::STATUS_COMPLETED,
                    'completed_at' => now(),
                    'result_description' => $payload['notes'] ?? null,
                    'labor_cost' => (float) ($payload['total_cost'] ?? 0),
                ]);
            }

            if ($asset) {
                MaintenanceHistory::create([
                    'asset_id' => $asset->id,
                    'maintenance_type' => $schedule?->maintenance_type ?? 'korektif',
                    'performed_date' => today(),
                    'performed_by_name' => $technician->name,
                    'condition_before' => $conditionBefore,
                    'condition_after' => $conditionAfter,
                    'work_description' => trim(($payload['notes'] ?? $order->scope_of_work).(! empty($payload['parts_replaced']) ? ' | Ganti: '.$payload['parts_replaced'] : '')),
                    'cost' => (float) ($payload['total_cost'] ?? 0),
                    'next_due_date' => $nextDueDate,
                ]);
            }

            $this->logNote($order, $technician, "Completed by {$technician->name}", 'resolution');

            $this->cacheInvalidator->invalidateWorkOrder($order);

            if ($asset) {
                if ($schedule) {
                    $this->eventLogger->log($asset, 'maintenance_completed', [
                        'order_number' => $order->order_number,
                        'schedule_id' => $schedule->id,
                        'condition_after' => $conditionAfter,
                        'cost' => (float) ($payload['total_cost'] ?? 0),
                        'performed_date' => today()->toDateString(),
                    ], $technician->id);
                } else {
                    $this->eventLogger->logRepairCompleted(
                        $asset,
                        $order->order_number,
                        $conditionAfter ?? 'baik',
                        (float) ($payload['total_cost'] ?? 0),
                        $technician->id
                    );
                }
            }

            return $order->fresh();
        });
    }

    private function calculateNextMaintenanceDate($date, string $frequency): string
    {
        $carbon = \Carbon\Carbon::parse($date);

        $next = match ($frequency) {
            'harian' => $carbon->addDay(),
            'mingguan' => $carbon->addWeek(),
            'bulanan' => $carbon->addMonth(),
            'triwulan' => $carbon->addMonths(3),
            'semester' => $carbon->addMonths(6),
            'tahunan' => $carbon->addYear(),
            default => $carbon->addMonth(),
        };

        return $next->toDateString();
    }

    public function logNote(WorkOrder $order, User $user, string $note, string $type = 'comment'): WorkOrderProgressNote
    {
        return WorkOrderProgressNote::create([
            'id' => (string) Str::uuid(),
            'work_order_id' => $order->id,
            'user_id' => $user->id,
            'note' => $note,
            'note_type' => $type,
            'metadata' => $note,
        ]);
    }

    public function syncSnapshot(WorkOrder $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'asset' => $order->asset ? $order->asset->only(['id', 'asset_name', 'asset_code', 'condition']) : null,
            'progress_notes' => WorkOrderProgressNote::where('work_order_id', $order->id)
                ->latest('id')
                ->limit(20)
                ->get()
                ->map(fn ($n) => [
                    'id' => $n->id,
                    'note' => $n->note,
                    'note_type' => $n->note_type,
                    'user_id' => $n->user_id,
                    'created_at' => $n->created_at,
                ]),
            'photos' => $this->photoService->forContext($order)->map(fn ($p) => [
                'id' => $p->id,
                'file_path' => $p->file_path ?: $p->photo_path,
                'photo_type' => $p->photo_type,
                'taken_at' => $p->taken_at,
                'caption' => $p->caption,
            ]),
            'checklist' => $this->latestChecklistFor($order),
        ];
    }

    protected function latestChecklistFor(WorkOrder $order): ?array
    {
        $instance = ChecklistInstance::where('context_type', $order->getMorphClass())
            ->where('context_id', $order->id)
            ->latest()
            ->first();
        if (! $instance) {
            return null;
        }

        return [
            'id' => $instance->id,
            'status' => $instance->status,
            'progress' => $this->checklistEngine->progress($instance),
        ];
    }
}
