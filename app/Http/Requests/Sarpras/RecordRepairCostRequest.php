<?php

namespace App\Http\Requests\Sarpras;

use Illuminate\Foundation\Http\FormRequest;

class RecordRepairCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array(auth()->user()->role ?? '', ['admin', 'sarpras_pic']);
    }

    public function rules(): array
    {
        return [
            'cost_category' => 'required|in:labor,transport,sparepart,vendor_service,other',
            'description' => 'required|string|max:5000',
            'amount' => 'required|numeric|min:0|max:999999999',
            'incurred_date' => 'required|date',
            'document_number' => 'nullable|string|max:255',
            'vendor_name' => 'nullable|string|max:255',
        ];
    }
}