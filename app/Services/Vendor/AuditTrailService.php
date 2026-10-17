<?php

namespace App\Services\Vendor;

use App\Models\VendorAuditTrail;
use Illuminate\Database\Eloquent\Model;

class AuditTrailService
{
    public function record(
        Model|string $entity,
        int|string $entityId,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        $actor = null
    ): VendorAuditTrail {
        $entityType = is_string($entity) ? $entity : $this->resolveEntityType($entity);

        return VendorAuditTrail::record(
            $entityType,
            (int) $entityId,
            $action,
            $oldValues,
            $newValues,
            $actor
        );
    }

    public function recordCreated(Model $entity, $actor = null): VendorAuditTrail
    {
        return $this->record(
            $entity,
            $entity->id,
            'created',
            null,
            $entity->getAttributes(),
            $actor
        );
    }

    public function recordUpdated(Model $entity, array $oldValues, $actor = null): VendorAuditTrail
    {
        return $this->record(
            $entity,
            $entity->id,
            'updated',
            $oldValues,
            $entity->getChanges(),
            $actor
        );
    }

    public function recordStateTransition(Model $entity, string $fromState, string $toState, $actor = null): VendorAuditTrail
    {
        return $this->record(
            $entity,
            $entity->id,
            'state_transition',
            ['status' => $fromState],
            ['status' => $toState],
            $actor
        );
    }

    public function recordAction(string $entityType, int $entityId, string $action, array $payload = [], $actor = null): VendorAuditTrail
    {
        return $this->record(
            $entityType,
            $entityId,
            $action,
            null,
            $payload,
            $actor
        );
    }

    protected function resolveEntityType(Model $entity): string
    {
        return strtolower(class_basename($entity));
    }

    public function getTrail(string $entityType, int $entityId)
    {
        return VendorAuditTrail::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderByDesc('created_at')
            ->get();
    }
}
