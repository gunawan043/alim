<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\Notification;
use App\Models\Warehouse;
use Illuminate\Http\UploadedFile;

class WarehouseService
{
    public function __construct() {}

    public function create(int $userId, array $data): Warehouse
    {
        return Warehouse::create([
            'code' => $data['code'] ?? $this->generateCode(),
            'name' => $data['name'] ?? null,
            'type' => in_array($data['type'] ?? null, ['main', 'secondary', 'transit', 'vendor_consignment'], true)
                ? $data['type']
                : 'main',
            'work_unit_id' => $data['work_unit_id'] ?? null,
            'building_id' => $data['building_id'] ?? null,
            'manager_user_id' => $data['manager_user_id'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        $warehouse->update(array_filter(array_keys($data), fn ($key) => !empty($data[$key])));

        $this->audit($warehouse, 'updated');

        return $warehouse;
    }

    public function uploadDocument(Warehouse $warehouse, UploadedFile $file, string $type = 'general'): string
    {
        $path = $file->store('warehouses/documents', 'public');

        $this->audit($warehouse, 'document_uploaded', ['type' => $type, 'path' => $path]);

        return $path;
    }

    public function deactivate(Warehouse $warehouse): Warehouse
    {
        $warehouse->update(['is_active' => false]);
        $this->audit($warehouse, 'deactivated');

        return $warehouse;
    }

    public function activate(Warehouse $warehouse): Warehouse
    {
        $warehouse->update(['is_active' => true]);
        $this->audit($warehouse, 'activated');

        return $warehouse;
    }

    public function assignManager(Warehouse $warehouse, string $userId): Warehouse
    {
        $warehouse->update(['manager_user_id' => $userId]);
        $this->audit($warehouse, 'manager_assigned');

        // Notify manager
        Notification::create([
            'user_id' => $userId,
            'type' => 'warehouse_manager_assigned',
            'title' => 'Warehouse Manager Assignment',
            'message' => "You have been assigned as manager for warehouse {$warehouse->name}",
            'data' => ['warehouse_id' => $warehouse->id],
        ]);

        return $warehouse;
    }

    public function getActive(): array
    {
        return Warehouse::where('is_active', true)->get()->all();
    }

    private function generateCode(): string
    {
        $prefix = 'WH';
        $year = date('Y');
        $seq = Warehouse::whereYear('created_at', $year)->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $year, $seq);
    }

    private function audit(Warehouse $warehouse, string $action, array $meta = []): void
    {
        AuditTrail::create([
            'actor_id' => auth()->id(),
            'action' => "warehouse.{$action}",
            'entity_type' => Warehouse::class,
            'entity_id' => $warehouse->id,
            'metadata' => array_merge([
                'warehouse_code' => $warehouse->code,
                'warehouse_name' => $warehouse->name,
            ], $meta),
        ]);
    }
}