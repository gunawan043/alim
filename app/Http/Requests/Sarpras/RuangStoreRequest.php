<?php

namespace App\Http\Requests\Sarpras;

use App\Models\AssetRoom;
use Illuminate\Foundation\Http\FormRequest;

class RuangStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'school_id' => 'required|exists:schools,id',
            'building_id' => 'nullable|exists:asset_buildings,id',
            'room_name' => 'required|string|max:191',
            'room_code' => 'nullable|string|max:30|unique:asset_rooms,room_code',
            'room_type' => 'required|in:'.implode(',', AssetRoom::ROOM_TYPE_OPTIONS),
            'floor' => 'nullable|integer|min:0|max:20',
            'room_area' => 'nullable|numeric|min:0',
            'capacity' => 'nullable|integer|min:0',
            'condition' => 'required|in:'.implode(',', AssetRoom::CONDITION_OPTIONS),
            'facilities' => 'nullable|string',
            'is_bookable' => 'boolean',
            'booking_requires_approval' => 'boolean',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'room_name.required' => 'Nama ruang wajib diisi.',
            'room_type.required' => 'Tipe ruang wajib dipilih.',
            'condition.required' => 'Kondisi ruang wajib dipilih.',
        ];
    }
}
