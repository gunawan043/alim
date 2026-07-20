<?php

namespace App\Repositories;

use App\Domain\Contracts\Repositories\PurchaseOrderRepositoryInterface;
use App\Models\PurchaseOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PurchaseOrderRepository implements PurchaseOrderRepositoryInterface
{
    public function find(int $id): ?PurchaseOrder
    {
        return PurchaseOrder::find($id);
    }

    public function findOrFail(int $id): PurchaseOrder
    {
        return PurchaseOrder::findOrFail($id);
    }

    public function create(array $data): PurchaseOrder
    {
        return PurchaseOrder::create($data);
    }

    public function update(PurchaseOrder $po, array $data): PurchaseOrder
    {
        $po->update($data);
        return $po->fresh();
    }

    public function delete(PurchaseOrder $po): bool
    {
        return $po->delete();
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = PurchaseOrder::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function getByVendor(int $vendorId): Collection
    {
        return PurchaseOrder::where('vendor_id', $vendorId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getByStatus(string $status): Collection
    {
        return PurchaseOrder::where('status', $status)->get();
    }
}
