<?php

namespace App\Services\Sarpras;

use App\Models\Asset;
use App\Models\AssetHealthMetric;
use App\Models\RepairRequest;
use App\Models\TechnicianAvailability;
use App\Models\TechnicianSkill;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderProgressNote;
use App\Models\WorkOrderPauseEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\SarprasCacheInvalidator;

class TechnicianWorkspaceService
{
    public function __construct(
        protected AssetEventLogger $eventLogger,
        protected PhotoDocumentationService $photoService,
        protected ChecklistEngine $checklistEngine,
        protected SarprasCacheInvalidator $cacheInvalidator,
    ) {}

    public function availableOrdersFor(User $technician, array $filters = []): array
    {
        $skillCategories = $technician->technicianSkills()
            ->pluck('category_slug')
            ->filter()
            ->values()
            ->all();

        $query = WorkOrder::with(['asset.category', 'repairRequest'])
            ->whereIn('status', ['assigned', 'in_progress', 'paused']);

        if (! empty($skillCategories)) {
            $query->whereHas('asset.category', function ($q) use ($skillCategories) {
                $q->whereIn('slug', $skillCategories)
                    ->orWhereIn('parent_slug', $skillCategories);
            });
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $orders = $query->orderByRaw("FIELD(priority, 'urgent', 'critical', 'high', 'medium', 'low')")
            ->orderBy('scheduled_date')
            ->limit($filters['limit'] ?? 30)
            ->get();

        return $orders->map(function (WorkOrder $order) use ($technician) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'asset_id' => $order->asset_id,
                'asset_name' => $order->asset?->asset_name,
                'asset_code' => $order->asset?->asset_code,
                'category' => $order->asset?->category?->name,
                'status' => $order->status,
                'priority' => $order->priority ?? 'medium',
                'scheduled_date' => $order->scheduled_date?->toDateString(),
                'actual_start' => $order->actual_start?->toDateTimeString(),
                'scope_of_work' => $order->scope_of_work,
                'is_assigned_to_me' => $order->assignee_id === $technician->id,
                'health_score' => $order->asset?->healthMetric?->health_score,
            ];
        })->all();
    }

    public function startWork(WorkOrder $order, User $technician, array $payload = []): WorkOrder
    {
        return DB::transaction(function () use ($order, $technician, $payload) {
            if (! in_array($order->status, ['assigned', 'in_progress', 'paused'], true)) {
                throw new \DomainException(
                    "Cannot start work on order in status: {$order->status}"
                );
            }

            $order->update([
                'status' => 'in_progress',
                'actual_start' => $order->actual_start ?? now(),
            ]);

            $this->ensureAvailability($technician)->increment('current_active_orders');

            $this->logProgress($order, $technician, "Work started by {$technician->name}", 'observation');

            if (! empty($payload['photos'])) {
                $this->photoService->uploadMany($order, $payload['photos'], [
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
                'work_order_id' => $order->id,
                'user_id' => $technician->id,
                'reason_code' => $reasonCode,
                'reason_text' => $reasonText,
                'paused_at' => now(),
            ]);

            $order->update(['status' => 'paused']);

            $this->logProgress($order, $technician, "Paused: {$reasonText} [{$reasonCode}]", 'pause_reason');

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
                $lastPause->update([
                    'resumed_at' => now(),
                    'pause_duration_seconds' => $duration,
                ]);
            }

            $order->update(['status' => 'in_progress']);

            if ($notes) {
                $this->logProgress($order, $technician, "Resumed: {$notes}", 'comment');
            }

            $this->cacheInvalidator->invalidateWorkOrder($order);

            return $order->fresh();
        });
    }

    public function complete(WorkOrder $order, User $technician, array $payload): WorkOrder
    {
        return DB::transaction(function () use ($order, $technician, $payload) {
            $order->update([
                'status' => 'completed',
                'actual_end' => now(),
                'completion_notes' => $payload['notes'] ?? null,
                'total_cost' => $payload['total_cost'] ?? 0,
            ]);

            if (! empty($payload['condition_after'])) {
                $order->asset->update(['condition' => $payload['condition_after']]);
            }

            if (! empty($payload['photos'])) {
                $this->photoService->uploadMany($order, $payload['photos'], [
                    'photo_type' => 'after',
                    'uploaded_by' => $technician->id,
                ]);
            }

            $this->ensureAvailability($technician)->decrement('current_active_orders');

            $this->logProgress($order, $technician, "Completed by {$technician->name}", 'resolution');

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

    public function progressFor(WorkOrder $order, User $technician): array
    {
        return [
            'order' => $order->only([
                'id', 'order_number', 'status', 'priority', 'scheduled_date',
                'actual_start', 'actual_end', 'scope_of_work',
            ]),
            'asset' => $order->asset?->only([
                'id', 'asset_name', 'asset_code', 'condition', 'room_id',
            ]),
            'checklist' => $this->latestChecklistFor($order),
            'progress_notes' => WorkOrderProgressNote::where('work_order_id', $order->id)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get()
                ->map(fn ($n) => [
                    'id' => $n->id,
                    'note' => $n->note,
                    'note_type' => $n->note_type,
                    'user_id' => $n->user_id,
                    'created_at' => $n->created_at?->toDateTimeString(),
                ]),
            'photos' => $this->photoService->forContext($order)->map(fn ($p) => [
                'id' => $p->id,
                'file_path' => $p->file_path ?: $p->photo_path,
                'photo_type' => $p->photo_type,
                'taken_at' => $p->taken_at?->toDateTimeString(),
                'caption' => $p->caption,
            ]),
        ];
    }

    public function logProgress(WorkOrder $order, User $technician, string $note, string $type = 'comment'): WorkOrderProgressNote
    {
        return WorkOrderProgressNote::create([
            'work_order_id' => $order->id,
            'user_id' => $technician->id,
            'note' => $note,
            'note_type' => $type,
        ]);
    }

    protected function ensureAvailability(User $technician): TechnicianAvailability
    {
        return TechnicianAvailability::firstOrCreate(
            ['user_id' => $technician->id],
            ['status' => 'available', 'max_concurrent_orders' => 3]
        );
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