<?php

namespace App\Repositories;

use App\Domain\Contracts\Repositories\WarehouseRepositoryInterface;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class WarehouseRepository implements WarehouseRepositoryInterface
{
    public function find(int $id): ?Warehouse
    {
        return Warehouse::find($id);
    }

    public function findOrFail(int $id): Warehouse
    {
        return Warehouse::findOrFail($id);
    }

    public function create(array $data): Warehouse
    {
        return Warehouse::create($data);
    }

    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        $warehouse->update($data);
        return $warehouse->fresh();
    }

    public function delete(Warehouse $warehouse): bool
    {
        return $warehouse->delete();
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Warehouse::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->orderBy('code')->paginate($perPage);
    }

    public function getActive(): Collection
    {
        return Warehouse::where('status', 'active')->orderBy('code')->get();
    }

    public function getStock(int $warehouseId, int $productId): ?WarehouseStock
    {
        return WarehouseStock::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->first();
    }
}