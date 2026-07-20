<?php

namespace App\Services;

use App\Models\VendorContract;
use App\Models\Vendor;
use App\Models\AuditTrail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class VendorContractService
{
    private const DISK = 'public';
    private const FOLDER = 'vendor-contracts';

    public function __construct() {}

    public function create(Vendor $vendor, int $userId, array $data): VendorContract
    {
        $filePath = null;
        if (isset($data['attachment_file']) && $data['attachment_file'] instanceof UploadedFile) {
            $filePath = $data['attachment_file']->store(
                self::FOLDER,
                self::DISK
            );
            unset($data['attachment_file']);
        }

        $contract = $vendor->contracts()->create([
            'contract_number' => 'VC-' . strtoupper(substr(md5((string) time()), 0, 12)),
            'title' => $data['title'] ?? null,
            'scope' => $data['scope'] ?? null,
            'terms_and_conditions' => $data['terms_and_conditions'] ?? null,
            'start_date' => $data['start_date'] ?? now()->toDateString(),
            'end_date' => $data['end_date'] ?? now()->addYear()->toDateString(),
            'renewal_type' => $data['renewal_type'] ?? 'manual',
            'status' => VendorContract::STATUS_DRAFT,
            'annual_value' => $data['annual_value'] ?? 0,
            'monthly_value' => $data['monthly_value'] ?? 0,
            'slas' => $data['slas'] ?? null,
            'attachment_path' => $filePath,
            'user_id' => $userId,
        ]);

        $this->audit($contract, 'created');

        return $contract;
    }

    public function update(VendorContract $contract, array $data): VendorContract
    {
        $contract->update(array_filter($data, fn ($v) => !is_null($v) || in_array($v, [0, false, ''], true)));

        $this->audit($contract, 'updated');

        return $contract->fresh();
    }

    public function sendForSigning(VendorContract $contract, int $vendorId): VendorContract
    {
        if ($contract->status !== VendorContract::STATUS_DRAFT) {
            throw new \InvalidArgumentException('Cannot send non-draft contract.');
        }

        $contract->update(['status' => VendorContract::STATUS_ACTIVE]);

        $this->audit($contract, 'sent_for_signing', ['vendor_id' => $vendorId]);

        return $contract->fresh();
    }

    public function vendorSign(VendorContract $contract): VendorContract
    {
        if ($contract->status !== VendorContract::STATUS_ACTIVE) {
            throw new \InvalidArgumentException('Contract is not active.');
        }

        $contract->update([
            'signed_by_vendor' => $contract->vendor_id,
            'signed_at' => now(),
        ]);

        $this->audit($contract, 'vendor_signed');

        return $contract->fresh();
    }

    public function adminSign(VendorContract $contract, int $adminId): VendorContract
    {
        if ($contract->status !== VendorContract::STATUS_ACTIVE) {
            throw new \InvalidArgumentException('Contract is not active.');
        }

        $contract->update([
            'signed_by_admin' => $adminId,
            'admin_signed_at' => now(),
        ]);

        $this->audit($contract, 'admin_signed', ['admin_id' => $adminId]);

        return $contract->fresh();
    }

    public function renew(VendorContract $contract, ?array $data = null): VendorContract
    {
        $newEndDate = $contract->end_date->copy()->addMonths(
            ($data['extension_months'] ?? 12)
        );

        $contract->update([
            'end_date' => $newEndDate->toDateString(),
            'auto_renewal_date' => $data['auto_renewal_date'] ?? $contract->auto_renewal_date,
            'renewal_type' => $data['renewal_type'] ?? $contract->renewal_type,
        ]);

        $this->audit($contract, 'renewed', ['new_end_date' => $newEndDate]);

        return $contract->fresh();
    }

    public function terminate(VendorContract $contract, ?string $reason = null): VendorContract
    {
        $contract->update([
            'status' => VendorContract::STATUS_TERMINATED,
        ]);

        $this->audit($contract, 'terminated', ['reason' => $reason]);

        return $contract->fresh();
    }

    public function suspend(VendorContract $contract): VendorContract
    {
        $contract->update(['status' => VendorContract::STATUS_SUSPENDED]);
        $this->audit($contract, 'suspended');

        return $contract->fresh();
    }

    public function resume(VendorContract $contract): VendorContract
    {
        $contract->update(['status' => VendorContract::STATUS_ACTIVE]);
        $this->audit($contract, 'resumed');

        return $contract->fresh();
    }

    public function download(VendorContract $contract)
    {
        if (!$contract->attachment_path) {
            throw new \RuntimeException('No attachment found.');
        }

        $this->audit($contract, 'download');

        return Storage::disk(self::DISK)->download($contract->attachment_path);
    }

    public function getExpiring(): array
    {
        return VendorContract::where('status', VendorContract::STATUS_ACTIVE)
            ->where('end_date', '<=', now()->addDays(30))
            ->where('end_date', '>=', now())
            ->orderBy('end_date')
            ->get()
            ->all();
    }

    private function audit(VendorContract $contract, string $action, array $meta = []): void
    {
        AuditTrail::create([
            'actor_id' => auth()->id(),
            'action' => "contract.{$action}",
            'entity_type' => VendorContract::class,
            'entity_id' => $contract->id,
            'metadata' => array_merge([
                'vendor_id' => $contract->vendor_id,
                'contract_number' => $contract->contract_number,
            ], $meta),
        ]);
    }
}