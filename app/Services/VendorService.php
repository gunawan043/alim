<?php

namespace App\Services;

use App\Models\Vendor;
use App\Models\VendorContract;
use App\Models\VendorDocument;
use App\Models\VendorContact;
use Illuminate\Support\Str;

class VendorService
{
    /**
     * Create a new vendor record.
     */
    public function create(array $data): Vendor
    {
        return Vendor::create(array_merge($data, [
            'vendor_code' => 'VND-' . strtoupper(Str::random(8)),
        ]));
    }

    /**
     * Update vendor with guarded fields.
     */
    public function update(Vendor $vendor, array $data): Vendor
    {
        $fillable = [
            'name',
            'contact_name',
            'email',
            'phone',
            'address',
            'city',
            'province',
            'postal_code',
            'country',
            'website',
            'category',
            'sub_category',
            'status',
            'tax_id',
            'npwp',
            'bank_name',
            'bank_account',
            'bank_account_holder',
            'payment_terms',
            'lead_time_days',
            'warranty_days',
            'rating_avg',
            'rating_count',
        ];

        $vendor->update(array_intersect_key($data, array_flip($fillable)));

        return $vendor->refresh();
    }

    /**
     * Soft-delete a vendor (set inactive instead of soft-delete).
     */
    public function deactivate(Vendor $vendor): Vendor
    {
        $vendor->update(['status' => 'inactive']);

        return $vendor;
    }

    /**
     * Re-activate a previously deactivated vendor.
     */
    public function activate(Vendor $vendor): Vendor
    {
        $vendor->update(['status' => 'active']);

        return $vendor;
    }

    /**
     * Bulk create or update contacts.
     */
    public function syncContacts(Vendor $vendor, array $contacts): void
    {
        $existingIds = $contacts
            ? collect($contacts)->pluck('id')->filter()->all()
            : [];

        // Remove contacts not in the incoming list
        if ($existingIds) {
            $vendor->contacts()->whereNotIn('id', $existingIds)->delete();
        }

        foreach ($contacts as $contactData) {
            if (isset($contactData['id'])) {
                $contact = $vendor->contacts()->find($contactData['id']);
                if ($contact) {
                    $contact->update($contactData);
                }
            } else {
                $vendor->contacts()->create($contactData);
            }
        }
    }

    /**
     * Get vendor by code or ID.
     */
    public function findByCodeOrId(string|int $identifier): ?Vendor
    {
        if (is_numeric($identifier)) {
            return Vendor::find($identifier);
        }

        return Vendor::where('vendor_code', $identifier)->first();
    }

    /**
     * Rate a vendor (add rating and recalculate average).
     */
    public function rateVendor(Vendor $vendor, float $rating, string $comment = '', ?int $userId = null): Vendor
    {
        $vendor->ratings()->create([
            'user_id' => $userId,
            'overall_score' => $rating,
            'comment' => $comment,
        ]);

        // Recalculate average
        $avg = $vendor->ratings()->avg('overall_score') ?? $vendor->rating_avg ?? 0;
        $vendor->update([
            'rating_avg' => round($avg, 2),
            'rating_count' => $vendor->ratings()->count(),
        ]);

        return $vendor;
    }

    /**
     * Update overall vendor status based on aggregated data.
     */
    public function refreshStatus(Vendor $vendor): Vendor
    {
        $hasActiveContracts = $vendor->contracts()->active()->exists();
        $hasValidDocs = $vendor->documents()->verified()->doesntExist()
            || $vendor->documents()->where('status', 'verified')->exists();
        $recentIssues = $vendor->rmAs()
            ->where('status', 'open')
            ->orWhere('status', 'in_return')
            ->orWhere('status', 'approved')
            ->count();

        if ($recentIssues > 3) {
            $status = 'suspended';
        } elseif (!$hasValidDocs && !$hasActiveContracts) {
            $status = 'inactive';
        } else {
            $status = 'active';
        }

        if ($vendor->status !== $status) {
            $vendor->update(['status' => $status]);
        }

        return $vendor;
    }

    /**
     * Get vendor dashboard summary for a single vendor.
     */
    public function getDashboardSummary(Vendor $vendor): array
    {
        return [
            'outstanding_rfq' => $vendor->rfqs()->published()->count(),
            'quotation_waiting' => $vendor->quotations()->whereNull('award_id')->count(),
            'po_active' => $vendor->purchaseOrders()->whereNotIn('status', ['closed', 'cancelled'])->count(),
            'shipment_active' => $vendor->purchaseOrders()->whereIn('status', ['shipped', 'preparing', 'packed'])->count(),
            'invoice_waiting' => $vendor->invoiceApprovals()
                ->whereIn('status', ['draft', 'submitted', 'in_review'])
                ->count(),
            'contract_expiring' => $vendor->contracts()
                ->where('status', 'active')
                ->where('end_date', '<=', now()->addDays(30))
                ->count(),
            'performance' => [
                'rating' => $vendor->rating_avg ?? 0,
                'total_orders' => $vendor->purchaseOrders()->count(),
                'on_time_rate' => 0, // Computed elsewhere
            ],
            'notifications' => $vendor->notificationCount(),
        ];
    }
}