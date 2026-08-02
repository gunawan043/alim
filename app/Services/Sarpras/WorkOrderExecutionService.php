<?php

namespace App\Services\Sarpras;

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
            if (! in_array($order->status, ['assigned', 'in_progress', 'paused'], true)) {
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
            $this->eventLogger->logWorkOrderAssigned($order->asset, $order->order_number, $technician->id, $technician->id);

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

            if (! empty($payload['condition_after'])) {
                $order->asset->update(['condition' => $payload['condition_after']]);
            }

            $this->logNote($order, $technician, "Completed by {$technician->name}", 'resolution');

            $this->cacheInvalidator->invalidateWorkOrder($order);
            $this->eventLogger->logRepairCompleted(
                $order->asset,
                $order->order_number,
                $payload['condition_after'] ?? 'baik',
                (float) ($payload['total_cost'] ?? 0),
                $technician->id
            );

            return $order->fresh();
        });
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
        $instance = \App\Models\ChecklistInstance::where('context_type', $order->getMorphClass())
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
