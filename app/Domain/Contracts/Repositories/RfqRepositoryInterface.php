<?php

namespace App\Domain\Contracts\Repositories;

use App\Models\Rfq;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface RfqRepositoryInterface
{
    public function find(int $id): ?Rfq;

    public function findOrFail(int $id): Rfq;

    public function create(array $data): Rfq;

    public function update(Rfq $rfq, array $data): Rfq;

    public function delete(Rfq $rfq): bool;

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function getByVendor(int $vendorId): Collection;

    public function getByStatus(string $status): Collection;

    public function getExpired(): Collection;
}
