<?php

namespace App\Domain\Contracts\Repositories;

use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface WarehouseRepositoryInterface
{
    public function find(int $id): ?Warehouse;

    public function findOrFail(int $id): Warehouse;

    public function create(array $data): Warehouse;

    public function update(Warehouse $warehouse, array $data): Warehouse;

    public function delete(Warehouse $warehouse): bool;

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function getActive(): Collection;

    public function getStock(int $warehouseId, int $productId): ?WarehouseStock;
}
