<?php

namespace App\Http\Requests\Dormitory;

use Illuminate\Foundation\Http\FormRequest;

class StoreWingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_building_id' => 'required|exists:asset_buildings,id',
            'code' => 'nullable|string|max:20',
            'name' => 'nullable|string|max:100',
            'floor' => 'nullable|integer|min:0',
            'gender' => 'nullable|in:putra,putri',
            'capacity' => 'nullable|integer|min:0',
            'supervisor_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'asset_building_id.required' => 'Gedung wajib dipilih.',
            'asset_building_id.exists' => 'Gedung tidak ditemukan pada data Sarpras.',
        ];
    }
}
