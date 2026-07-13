<?php

namespace App\Http\Requests\Dormitory;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wing_id' => 'required|exists:dormitory_wings,id',
            'code' => 'required|string|max:20|unique:dormitory_rooms,code',
            'name' => 'nullable|string|max:100',
            'floor' => 'nullable|integer|min:0',
            'capacity' => 'required|integer|min:1',
            'room_type' => 'required|in:reguler,khusus,isolasi,musyrif',
            'facility_notes' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'wing_id.required' => 'Gedung wajib dipilih.',
            'code.required' => 'Kode kamar wajib diisi.',
            'capacity.required' => 'Kapasitas kamar wajib diisi.',
            'capacity.min' => 'Kapasitas minimal 1.',
            'room_type.required' => 'Tipe kamar wajib dipilih.',
        ];
    }
}
