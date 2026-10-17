<?php

namespace App\Domain\Contracts\Repositories;

use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface VendorRepositoryInterface
{
    public function find(int $id): ?Vendor;

    public function findByCode(string $code): ?Vendor;

    public function findOrFail(int $id): Vendor;

    public function create(array $data): Vendor;

    public function update(Vendor $vendor, array $data): Vendor;

    public function delete(Vendor $vendor): bool;

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function getActive(): Collection;

    public function existsByCode(string $code): bool;
}
