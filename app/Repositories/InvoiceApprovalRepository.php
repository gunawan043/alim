<?php

namespace App\Repositories;

use App\Domain\Contracts\Repositories\InvoiceApprovalRepositoryInterface;
use App\Models\InvoiceApproval;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class InvoiceApprovalRepository implements InvoiceApprovalRepositoryInterface
{
    public function find(int $id): ?InvoiceApproval
    {
        return InvoiceApproval::find($id);
    }

    public function findOrFail(int $id): InvoiceApproval
    {
        return InvoiceApproval::findOrFail($id);
    }

    public function create(array $data): InvoiceApproval
    {
        return InvoiceApproval::create($data);
    }

    public function update(InvoiceApproval $invoice, array $data): InvoiceApproval
    {
        $invoice->update($data);

        return $invoice->fresh();
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = InvoiceApproval::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function getPendingApproval(): Collection
    {
        return InvoiceApproval::whereIn('status', ['submitted', 'reviewing'])
            ->orderBy('created_at')
            ->get();
    }

    public function getByVendor(int $vendorId): Collection
    {
        return InvoiceApproval::where('vendor_id', $vendorId)
            ->orderByDesc('created_at')
            ->get();
    }
}
