<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VendorStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('sarpras.vendor.create');
    }

    public function rules(): array
    {
        $vendorId = $this->route('vendor')?->id;

        return [
            'vendor_code' => 'nullable|string|max:50|unique:vendors,vendor_code'.($vendorId ? ','.$vendorId : ''),
            'name' => 'required|string|max:200',
            'legal_name' => 'nullable|string|max:200',
            'npwp' => 'nullable|string|max:30',
            'category_id' => 'nullable|integer|exists:vendor_categories,id',
            'vendor_type' => 'nullable|in:supplier,service_provider,manufacturer,distributor,contractor',
            'status' => 'nullable|in:active,inactive,prospective,blacklist,suspended',
            'phone' => 'nullable|string|max:30',
            'phone_alt' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:150',
            'website' => 'nullable|url|max:255',
            'established_year' => 'nullable|integer|min:1900|max:'.(date('Y') + 1),
            'total_employees' => 'nullable|integer|min:0',
            'rating_avg' => 'nullable|numeric|min:0|max:5',
            'risk_classification' => 'nullable|in:low,medium,high,critical',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_term_days' => 'nullable|integer|min:0',
            'preferred_currency' => 'nullable|string|size:3',
            'notes' => 'nullable|string',

            // Contacts (nested array)
            'contacts' => 'nullable|array',
            'contacts.*.name' => 'required_with:contacts|string|max:150',
            'contacts.*.position' => 'nullable|string|max:100',
            'contacts.*.phone' => 'nullable|string|max:30',
            'contacts.*.email' => 'nullable|email',
            'contacts.*.contact_type' => 'nullable|in:sales,technical,billing,cs,owner,other',
            'contacts.*.is_primary' => 'nullable|boolean',

            // Addresses (nested array)
            'addresses' => 'nullable|array',
            'addresses.*.address_type' => 'required_with:addresses|in:headquarters,branch,billing,shipping,factory',
            'addresses.*.street_address' => 'required_with:addresses|string|max:255',
            'addresses.*.city' => 'required_with:addresses|string|max:100',
            'addresses.*.province' => 'nullable|string|max:100',
            'addresses.*.postal_code' => 'nullable|string|max:10',
            'addresses.*.country' => 'nullable|string|size:2',
            'addresses.*.is_default' => 'nullable|boolean',

            // Banks (nested array)
            'banks' => 'nullable|array',
            'banks.*.bank_name' => 'required_with:banks|string|max:100',
            'banks.*.account_number' => 'required_with:banks|string|max:50',
            'banks.*.account_holder' => 'required_with:banks|string|max:150',
            'banks.*.swift_code' => 'nullable|string|max:20',
            'banks.*.currency' => 'nullable|string|size:3',

            // Tax
            'tax.npwp' => 'nullable|string|max:30',
            'tax.pkp_status' => 'nullable|in:pkp,non_pkp',
            'tax.pkp_number' => 'nullable|string|max:50',
        ];
    }
}
