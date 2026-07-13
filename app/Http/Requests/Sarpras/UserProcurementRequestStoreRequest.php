<?php

namespace App\Http\Requests\Sarpras;

use Illuminate\Foundation\Http\FormRequest;

class UserProcurementRequestStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'item_name' => 'required|string|max:191',
            'quantity' => 'required|integer|min:1',
            'unit' => 'nullable|string|max:30',
            'purpose' => 'required|string',
            'urgency' => 'required|in:biasa,urgent,kritis',
            'estimated_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ];
    }
}
