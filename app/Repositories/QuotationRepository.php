<?php

namespace App\Repositories;

use App\Domain\Contracts\Repositories\QuotationRepositoryInterface;
use App\Models\Quotation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class QuotationRepository implements QuotationRepositoryInterface
{
    public function find(int $id): ?Quotation
    {
        return Quotation::find($id);
    }

    public function findOrFail(int $id): Quotation
    {
        return Quotation::findOrFail($id);
    }

    public function create(array $data): Quotation
    {
        return Quotation::create($data);
    }

    public function update(Quotation $quotation, array $data): Quotation
    {
        $quotation->update($data);
        return $quotation->fresh();
    }

    public function delete(Quotation $quotation): bool
    {
        return $quotation->delete();
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Quotation::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        if (isset($filters['rfq_id'])) {
            $query->where('rfq_id', $filters['rfq_id']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function getByRfq(int $rfqId): Collection
    {
        return Quotation::where('rfq_id', $rfqId)
            ->orderBy('total_amount')
            ->get();
    }

    public function getByVendor(int $vendorId): Collection
    {
        return Quotation::where('vendor_id', $vendorId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getAwardedForRfq(int $rfqId): Collection
    {
        return Quotation::where('rfq_id', $rfqId)
            ->where('status', 'awarded')
            ->get();
    }
}
