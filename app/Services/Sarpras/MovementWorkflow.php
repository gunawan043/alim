<?php

namespace App\Services\Sarpras;

use App\Models\Asset;
use App\Models\AssetEventLog;
use App\Models\AssetMovement;
use App\Models\MovementApproval;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\SarprasCacheInvalidator;

/**
 * MovementWorkflow coordinates the multi-stage asset transfer:
 *  draft → requested → approved → in_transit → received → verified → completed
 *  with rejections allowed at any approval step.
 */
class MovementWorkflow
{
    public const STAGE_DRAFT = 'draft';
    public const STAGE_REQUESTED = 'requested';
    public const STAGE_APPROVED = 'approved';
    public const STAGE_REJECTED = 'rejected';
    public const STAGE_IN_TRANSIT = 'in_transit';
    public const STAGE_RECEIVED = 'received';
    public const STAGE_VERIFIED = 'verified';
    public const STAGE_COMPLETED = 'completed';

    public function __construct(
        protected AssetEventLogger $eventLogger,
        protected PhotoDocumentationService $photoService,
        protected ChecklistEngine $checklistEngine,
        protected SarprasCacheInvalidator $cacheInvalidator,
    ) {}

    public function request(User $requester, array $payload): AssetMovement
    {
        return DB::transaction(function () use ($requester, $payload) {
            $asset = Asset::where('id', $payload['asset_id'])->firstOrFail();

            $movement = AssetMovement::create([
                'id' => (string) Str::uuid(),
                'asset_id' => $asset->id,
                'work_unit_id' => $asset->work_unit_id,
                'from_room_id' => $asset->room_id,
                'to_room_id' => $payload['to_room_id'] ?? null,
                'from_holder_id' => $payload['from_holder_id'] ?? null,
                'to_holder_id' => $payload['to_holder_id'] ?? null,
                'reason' => $payload['reason'],
                'justification' => $payload['justification'] ?? null,
                'status' => self::STAGE_REQUESTED,
                'requester_id' => $requester->id,
                'condition_snapshot' => $asset->condition,
            ]);

            $this->eventLogger->logMovementRequested($movement, $requester->id);
            $this->cacheInvalidator->invalidateMovement($movement);

            return $movement;
        });
    }

    public function approve(AssetMovement $movement, User $approver, array $payload): AssetMovement
    {
        return DB::transaction(function () use ($movement, $approver, $payload) {
            if ($movement->status !== self::STAGE_REQUESTED) {
                throw new \DomainException("Movement must be in 'requested' status, got: {$movement->status}");
            }

            $movement->update([
                'status' => self::STAGE_APPROVED,
                'approver_id' => $approver->id,
                'approved_at' => now(),
                'carrier_id' => $payload['carrier_id'] ?? null,
            ]);

            MovementApproval::create([
                'id' => (string) Str::uuid(),
                'movement_id' => $movement->id,
                'user_id' => $approver->id,
                'action' => 'approved',
                'notes' => $payload['notes'] ?? null,
            ]);

            $this->eventLogger->logMovementApproved($movement, $approver->id);
            $this->cacheInvalidator->invalidateMovement($movement);

            return $movement->fresh();
        });
    }

    public function reject(AssetMovement $movement, User $approver, string $reason): AssetMovement
    {
        return DB::transaction(function () use ($movement, $approver, $reason) {
            $movement->update([
                'status' => self::STAGE_REJECTED,
                'approver_id' => $approver->id,
                'verification_notes' => "Rejected: {$reason}",
            ]);

            MovementApproval::create([
                'id' => (string) Str::uuid(),
                'movement_id' => $movement->id,
                'user_id' => $approver->id,
                'action' => 'rejected',
                'notes' => $reason,
            ]);

            $this->cacheInvalidator->invalidateMovement($movement);

            return $movement->fresh();
        });
    }

    public function startTransit(AssetMovement $movement, User $carrier, array $payload = []): AssetMovement
    {
        return DB::transaction(function () use ($movement, $carrier, $payload) {
            $movement->update([
                'status' => self::STAGE_IN_TRANSIT,
                'carrier_id' => $carrier->id,
                'in_transit_at' => now(),
            ]);

            if (! empty($payload['photos'])) {
                $this->photoService->uploadMany($movement, $payload['photos'], [
                    'photo_type' => 'before',
                    'uploaded_by' => $carrier->id,
                ]);
            }

            $this->eventLogger->logMovementInTransit($movement, $carrier->id);
            $this->cacheInvalidator->invalidateMovement($movement);

            return $movement->fresh();
        });
    }

    public function confirmReceived(AssetMovement $movement, User $receiver, array $payload = []): AssetMovement
    {
        return DB::transaction(function () use ($movement, $receiver, $payload) {
            $movement->update([
                'status' => self::STAGE_RECEIVED,
                'receiver_id' => $receiver->id,
                'received_at' => now(),
                'condition_after' => $payload['condition_after'] ?? $movement->condition_snapshot,
            ]);

            if (! empty($payload['photos'])) {
                $this->photoService->uploadMany($movement, $payload['photos'], [
                    'photo_type' => 'after',
                    'uploaded_by' => $receiver->id,
                ]);
            }

            $this->cacheInvalidator->invalidateMovement($movement);

            return $movement->fresh();
        });
    }

    public function verify(AssetMovement $movement, User $verifier, array $payload = []): AssetMovement
    {
        return DB::transaction(function () use ($movement, $verifier, $payload) {
            $movement->update([
                'status' => self::STAGE_VERIFIED,
                'verifier_id' => $verifier->id,
                'verified_at' => now(),
                'verification_notes' => $payload['notes'] ?? null,
            ]);

            $this->cacheInvalidator->invalidateMovement($movement);

            return $movement->fresh();
        });
    }

    public function complete(AssetMovement $movement, User $completer): AssetMovement
    {
        return DB::transaction(function () use ($movement, $completer) {
            if ($movement->status !== self::STAGE_VERIFIED) {
                throw new \DomainException("Movement must be verified before completion. Current: {$movement->status}");
            }

            $movement->update([
                'status' => self::STAGE_COMPLETED,
                'completed_at' => now(),
            ]);

            // Move asset to destination
            if ($movement->to_room_id) {
                $movement->asset->update([
                    'room_id' => $movement->to_room_id,
                    'condition' => $movement->condition_after ?? $movement->asset->condition,
                ]);
            }

            $this->eventLogger->logMovementCompleted($movement, $completer->id);
            $this->cacheInvalidator->invalidateMovement($movement);
            $this->cacheInvalidator->invalidateAsset($movement->asset);

            return $movement->fresh();
        });
    }

    public function snapshotFor(AssetMovement $movement): array
    {
        return [
            'movement' => $movement->only([
                'id', 'movement_number', 'status', 'reason', 'justification',
                'from_room_id', 'to_room_id', 'requester_id', 'approver_id',
                'approved_at', 'in_transit_at', 'received_at', 'verified_at', 'completed_at',
            ]),
            'asset' => $movement->asset?->only([
                'id', 'asset_name', 'asset_code', 'condition', 'room_id',
            ]),
            'photos' => $this->photoService->forContext($movement)->map(fn ($p) => [
                'id' => $p->id,
                'photo_type' => $p->photo_type,
                'file_path' => $p->file_path ?: $p->photo_path,
                'taken_at' => $p->taken_at?->toDateTimeString(),
            ]),
            'approvals' => $movement->approvals()->with('user:id,name')->get()
                ->map(fn ($a) => [
                    'action' => $a->action,
                    'notes' => $a->notes,
                    'user' => $a->user?->name,
                    'created_at' => $a->created_at?->toDateTimeString(),
                ]),
        ];
    }
}