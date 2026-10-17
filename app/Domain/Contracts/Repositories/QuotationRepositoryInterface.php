<?php

namespace App\Domain\Contracts\Repositories;

use App\Models\Quotation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface QuotationRepositoryInterface
{
    public function find(int $id): ?Quotation;

    public function findOrFail(int $id): Quotation;

    public function create(array $data): Quotation;

    public function update(Quotation $quotation, array $data): Quotation;

    public function delete(Quotation $quotation): bool;

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function getByRfq(int $rfqId): Collection;

    public function getByVendor(int $vendorId): Collection;

    public function getAwardedForRfq(int $rfqId): Collection;
}
