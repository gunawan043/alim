<?php

namespace App\Http\Requests\Sarpras;

use Illuminate\Foundation\Http\FormRequest;

class AsetMoveRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'new_room_id' => 'nullable|exists:asset_rooms,id',
            'new_building_id' => 'nullable|exists:asset_buildings,id',
            'move_notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'new_room_id.required' => 'Ruang tujuan wajib dipilih.',
            'new_building_id.required' => 'Gedung tujuan wajib dipilih.',
        ];
    }
}
