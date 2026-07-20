<?php

namespace App\Repositories;

use App\Domain\Contracts\Repositories\VendorRepositoryInterface;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class VendorRepository implements VendorRepositoryInterface
{
    public function find(int $id): ?Vendor
    {
        return Vendor::find($id);
    }

    public function findByCode(string $code): ?Vendor
    {
        return Vendor::where('code', $code)->first();
    }

    public function findOrFail(int $id): Vendor
    {
        return Vendor::findOrFail($id);
    }

    public function create(array $data): Vendor
    {
        return Vendor::create($data);
    }

    public function update(Vendor $vendor, array $data): Vendor
    {
        $vendor->update($data);
        return $vendor->fresh();
    }

    public function delete(Vendor $vendor): bool
    {
        return $vendor->delete();
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Vendor::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function getActive(): Collection
    {
        return Vendor::where('status', 'active')->orderBy('name')->get();
    }

    public function existsByCode(string $code): bool
    {
        return Vendor::where('code', $code)->exists();
    }
}
