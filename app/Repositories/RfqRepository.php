<?php

namespace App\Repositories;

use App\Domain\Contracts\Repositories\RfqRepositoryInterface;
use App\Models\Rfq;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class RfqRepository implements RfqRepositoryInterface
{
    public function find(int $id): ?Rfq
    {
        return Rfq::find($id);
    }

    public function findOrFail(int $id): Rfq
    {
        return Rfq::findOrFail($id);
    }

    public function create(array $data): Rfq
    {
        return Rfq::create($data);
    }

    public function update(Rfq $rfq, array $data): Rfq
    {
        $rfq->update($data);

        return $rfq->fresh();
    }

    public function delete(Rfq $rfq): bool
    {
        return $rfq->delete();
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Rfq::query();

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
        return Rfq::where('vendor_id', $vendorId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getByStatus(string $status): Collection
    {
        return Rfq::where('status', $status)->get();
    }

    public function getExpired(): Collection
    {
        return Rfq::where('status', 'published')
            ->where('deadline_at', '<', now())
            ->get();
    }
}
