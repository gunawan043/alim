<?php

namespace App\Services\Sarpras;

use App\Events\AssetLifecycleEvent;
use App\Models\Asset;
use App\Models\QrScanHistory;
use Illuminate\Support\Facades\DB;

class AssetEventLogger
{
    public function log(Asset $asset, string $eventType, array $detail = [], ?int $actorId = null): void
    {
        try {
            DB::transaction(function () use ($asset, $eventType, $detail, $actorId) {
                AssetLifecycleEvent::dispatch($asset, $eventType, $detail, $actorId);
            });
        } catch (\Throwable $e) {
            // Defensive: lifecycle logging must never block the originating CRUD.
            report($e);
        }
    }

    public function logCreated(Asset $asset, ?int $actorId = null): void
    {
        $this->log($asset, 'asset_created', [
            'asset_name' => $asset->asset_name,
            'asset_code' => $asset->asset_code,
            'room_id' => $asset->room_id,
            'category_id' => $asset->asset_category_id,
        ], $actorId);
    }

    public function logMoved(Asset $asset, ?string $fromRoomName, ?string $toRoomName, ?int $actorId = null): void
    {
        $this->log($asset, 'asset_moved', [
            'from_room' => $fromRoomName,
            'to_room' => $toRoomName,
        ], $actorId);
    }

    public function logAudit(Asset $asset, ?int $actorId = null): void
    {
        $this->log($asset, 'asset_audit', [
            'condition' => $asset->condition,
            'audit_date' => $asset->last_audit_date?->toDateString(),
        ], $actorId);
    }

    public function logAssetEvent(
        Asset $asset,
        string $eventType,
        string $eventDetail = '',
        ?object $actor = null,
        array $metadata = [],
    ): void {
        $this->log($asset, $eventType, array_filter(array_merge([
            'detail' => $eventDetail,
            'actor_name' => $actor?->name ?? null,
            'actor_id' => $actor instanceof \App\Models\User ? $actor->id : null,
        ], $metadata)));
    }

    public function logScan(
        Asset $asset,
        ?object $user,
        array $payload = [],
    ): \App\Models\QrScanHistory {
        return QrScanHistory::create([
            'asset_id' => $asset->id,
            'scanned_by' => $user instanceof \App\Models\User ? $user->id : null,
            'scan_type' => $payload['scan_type'] ?? 'lookup',
            'lookup_value' => $payload['lookup_value'] ?? $asset->asset_code,
            'source' => $payload['source'] ?? 'web_scanner',
            'ip_address' => $payload['ip_address'] ?? null,
            'user_agent' => $payload['user_agent'] ?? null,
            'latitude' => $payload['latitude'] ?? null,
            'longitude' => $payload['longitude'] ?? null,
            'condition' => $payload['condition'] ?? null,
            'purpose' => $payload['purpose'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'session_id' => $payload['session_id'] ?? null,
            'scanned_at' => now(),
        ]);
    }

    public function logQrGenerated(Asset $asset, ?int $actorId = null): void
    {
        $this->log($asset, 'qr_generated', [
            'generated_at' => now()->toDateTimeString(),
        ], $actorId);
    }

    public function logDamageReport(Asset $asset, string $description, ?int $actorId = null): void
    {
        $this->log($asset, 'damage_reported', [
            'description' => $description,
        ], $actorId);
    }

    public function logLoanCreated(Asset $asset, string $borrowerName, ?int $actorId = null): void
    {
        $this->log($asset, 'loan_created', [
            'borrower' => $borrowerName,
        ], $actorId);
    }

    public function logLoanReturned(Asset $asset, string $borrowerName, ?int $actorId = null): void
    {
        $this->log($asset, 'loan_returned', [
            'borrower' => $borrowerName,
        ], $actorId);
    }

    /* ---------- Workflow lifecycle events ---------- */

    public function logRepairSubmitted(Asset $asset, string $requestNumber, string $title, ?int $actorId = null): void
    {
        $this->log($asset, 'repair_request_submitted', [
            'request_number' => $requestNumber,
            'title' => $title,
        ], $actorId);
    }

    public function logRepairVerified(Asset $asset, string $requestNumber, ?int $actorId = null): void
    {
        $this->log($asset, 'repair_request_verified', [
            'request_number' => $requestNumber,
        ], $actorId);
    }

    public function logRepairRejected(Asset $asset, string $requestNumber, string $reason, ?int $actorId = null): void
    {
        $this->log($asset, 'repair_request_rejected', [
            'request_number' => $requestNumber,
            'reason' => $reason,
        ], $actorId);
    }

    public function logWorkOrderCreated(Asset $asset, string $orderNumber, ?int $actorId = null): void
    {
        $this->log($asset, 'work_order_created', [
            'order_number' => $orderNumber,
        ], $actorId);
    }

    public function logWorkOrderAssigned(Asset $asset, string $orderNumber, int $assigneeId, ?int $actorId = null): void
    {
        $this->log($asset, 'work_order_assigned', [
            'order_number' => $orderNumber,
            'assignee_id' => $assigneeId,
        ], $actorId);
    }

    public function logRepairCompleted(Asset $asset, string $orderNumber, string $conditionAfter, float $totalCost, ?int $actorId = null): void
    {
        $this->log($asset, 'repair_completed', [
            'order_number' => $orderNumber,
            'condition_after' => $conditionAfter,
            'cost' => $totalCost,
            'performed_date' => now()->toDateString(),
        ], $actorId);
    }

    public function logRepairClosed(Asset $asset, string $orderNumber, ?int $actorId = null): void
    {
        $this->log($asset, 'repair_closed', [
            'order_number' => $orderNumber,
        ], $actorId);
    }

    public function logMaintenanceScheduled(Asset $asset, ?int $scheduleId, ?int $actorId = null): void
    {
        $this->log($asset, 'maintenance_scheduled', [
            'schedule_id' => $scheduleId,
        ], $actorId);
    }

    public function logMaintenanceCompleted(Asset $asset, int $scheduleId, string $conditionAfter, float $cost, ?int $actorId = null): void
    {
        $this->log($asset, 'maintenance_completed', [
            'schedule_id' => $scheduleId,
            'condition_after' => $conditionAfter,
            'cost' => $cost,
            'performed_date' => now()->toDateString(),
        ], $actorId);
    }

    public function logStockOpnameOpened(Asset $asset, string $sessionCode, ?int $actorId = null): void
    {
        $this->log($asset, 'stock_opname_opened', [
            'session_code' => $sessionCode,
        ], $actorId);
    }

    public function logAssetStatusChanged(Asset $asset, string $fromStatus, string $toStatus, string $reason, ?int $actorId = null): void
    {
        $this->log($asset, 'asset_status_changed', [
            'from' => $fromStatus,
            'to' => $toStatus,
            'reason' => $reason,
        ], $actorId);
    }

    /* ---------- Movement lifecycle events ---------- */

    public function logMovementRequested(\App\Models\AssetMovement $movement, ?int $actorId = null): void
    {
        $this->log($movement->asset, 'movement_requested', [
            'movement_number' => $movement->movement_number,
            'from_room_id' => $movement->from_room_id,
            'to_room_id' => $movement->to_room_id,
            'reason' => $movement->reason,
        ], $actorId);
    }

    public function logMovementApproved(\App\Models\AssetMovement $movement, ?int $actorId = null): void
    {
        $this->log($movement->asset, 'movement_approved', [
            'movement_number' => $movement->movement_number,
        ], $actorId);
    }

    public function logMovementRejected(\App\Models\AssetMovement $movement, ?int $actorId = null, string $reason = ''): void
    {
        $this->log($movement->asset, 'movement_rejected', [
            'movement_number' => $movement->movement_number,
            'reason' => $reason,
        ], $actorId);
    }

    public function logMovementInTransit(\App\Models\AssetMovement $movement, ?int $actorId = null, array $photos = []): void
    {
        $this->log($movement->asset, 'movement_in_transit', [
            'movement_number' => $movement->movement_number,
            'carrier_id' => $movement->carrier_id,
            'photo_count' => count($photos),
        ], $actorId);
    }

    public function logMovementReceived(\App\Models\AssetMovement $movement, ?int $actorId = null, array $payload = []): void
    {
        $this->log($movement->asset, 'movement_received', [
            'movement_number' => $movement->movement_number,
            'condition_after' => $movement->condition_after,
            'notes' => $payload['notes'] ?? null,
        ], $actorId);
    }

    public function logMovementVerified(\App\Models\AssetMovement $movement, ?int $actorId = null, array $payload = []): void
    {
        $this->log($movement->asset, 'movement_verified', [
            'movement_number' => $movement->movement_number,
            'verification_notes' => $payload['verification_notes'] ?? null,
        ], $actorId);
    }

    public function logMovementCompleted(\App\Models\AssetMovement $movement, ?int $actorId = null): void
    {
        $this->log($movement->asset, 'movement_completed', [
            'movement_number' => $movement->movement_number,
        ], $actorId);
    }

    public function logMovementCancelled(\App\Models\AssetMovement $movement, ?int $actorId = null, string $reason = ''): void
    {
        $this->log($movement->asset, 'movement_cancelled', [
            'movement_number' => $movement->movement_number,
            'reason' => $reason,
        ], $actorId);
    }
}
