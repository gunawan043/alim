<?php

namespace App\Http\Requests\Sarpras;

use Illuminate\Foundation\Http\FormRequest;

class ProcurementConvertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:procurement_request_items,id',
            'items.*.asset_name' => 'required|string|max:191',
            'items.*.room_id' => 'nullable|exists:asset_rooms,id',
            'items.*.quantity' => 'nullable|integer|min:1',
        ];
    }
}
