<?php

namespace App\Http\Requests\Sarpras;

use Illuminate\Foundation\Http\FormRequest;

class UserRoomStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $rules = [
            'building_id' => 'nullable|exists:asset_buildings,id',
            'room_name' => 'required|string|max:191',
            'room_code' => 'nullable|string|max:30|unique:asset_rooms,room_code',
            'room_type' => 'required|in:'.implode(',', \App\Models\AssetRoom::ROOM_TYPE_OPTIONS),
            'floor' => 'nullable|integer|min:0|max:20',
            'capacity' => 'nullable|integer|min:0',
            'condition' => 'required|in:'.implode(',', \App\Models\AssetRoom::CONDITION_OPTIONS),
            'notes' => 'nullable|string',
        ];

        // Unique room_code for update: ignore current ID
        if ($roomId = $this->route('id')) {
            $rules['room_code'] .= ','.$roomId;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'room_code.unique' => 'Kode ruang sudah digunakan.',
        ];
    }
}
