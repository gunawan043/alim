<?php

namespace App\Domain\Contracts\Repositories;

use App\Models\GoodsReceipt;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface GoodsReceiptRepositoryInterface
{
    public function find(int $id): ?GoodsReceipt;

    public function findOrFail(int $id): GoodsReceipt;

    public function create(array $data): GoodsReceipt;

    public function update(GoodsReceipt $gr, array $data): GoodsReceipt;

    public function delete(GoodsReceipt $gr): bool;

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function getByPurchaseOrder(int $poId): Collection;

    public function getByVendor(int $vendorId): Collection;
}
