<?php

namespace App\Domain\Contracts\Repositories;

use App\Models\InvoiceApproval;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface InvoiceApprovalRepositoryInterface
{
    public function find(int $id): ?InvoiceApproval;

    public function findOrFail(int $id): InvoiceApproval;

    public function create(array $data): InvoiceApproval;

    public function update(InvoiceApproval $invoice, array $data): InvoiceApproval;

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function getPendingApproval(): Collection;

    public function getByVendor(int $vendorId): Collection;
}
