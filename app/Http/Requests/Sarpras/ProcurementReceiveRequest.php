<?php

namespace App\Http\Requests\Sarpras;

use Illuminate\Foundation\Http\FormRequest;

class ProcurementReceiveRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'delivery_date' => 'required|date',
            'received_by' => 'required|exists:users,id',
            'total_actual_cost' => 'nullable|numeric|min:0',
            'purchase_order_number' => 'nullable|string|max:100',
            'purchase_order_date' => 'nullable|date',
            'vendor_name' => 'nullable|string|max:191',
            'items' => 'nullable|array',
            'items.*.id' => 'required|exists:procurement_request_items,id',
            'items.*.actual_quantity_received' => 'nullable|integer|min:0',
            'items.*.actual_price_per_unit' => 'nullable|numeric|min:0',
            'items.*.received_date' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'delivery_date.required' => 'Tanggal pengiriman wajib diisi.',
            'received_by.required' => 'Penerima barang wajib dipilih.',
            'received_by.exists' => 'Penerima tidak valid.',
        ];
    }
}
