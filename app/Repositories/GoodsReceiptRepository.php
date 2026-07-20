<?php

namespace App\Repositories;

use App\Domain\Contracts\Repositories\GoodsReceiptRepositoryInterface;
use App\Models\GoodsReceipt;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class GoodsReceiptRepository implements GoodsReceiptRepositoryInterface
{
    public function find(int $id): ?GoodsReceipt
    {
        return GoodsReceipt::find($id);
    }

    public function findOrFail(int $id): GoodsReceipt
    {
        return GoodsReceipt::findOrFail($id);
    }

    public function create(array $data): GoodsReceipt
    {
        return GoodsReceipt::create($data);
    }

    public function update(GoodsReceipt $gr, array $data): GoodsReceipt
    {
        $gr->update($data);
        return $gr->fresh();
    }

    public function delete(GoodsReceipt $gr): bool
    {
        return $gr->delete();
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = GoodsReceipt::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function getByPurchaseOrder(int $poId): Collection
    {
        return GoodsReceipt::where('purchase_order_id', $poId)->get();
    }

    public function getByVendor(int $vendorId): Collection
    {
        return GoodsReceipt::where('vendor_id', $vendorId)
            ->orderByDesc('created_at')
            ->get();
    }
}
