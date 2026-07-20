<?php

namespace App\Domain\Contracts\Repositories;

use App\Models\PurchaseOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PurchaseOrderRepositoryInterface
{
    public function find(int $id): ?PurchaseOrder;

    public function findOrFail(int $id): PurchaseOrder;

    public function create(array $data): PurchaseOrder;

    public function update(PurchaseOrder $po, array $data): PurchaseOrder;

    public function delete(PurchaseOrder $po): bool;

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function getByVendor(int $vendorId): Collection;

    public function getByStatus(string $status): Collection;
}
