<?php

namespace App\Http\Requests\Sarpras;

use Illuminate\Foundation\Http\FormRequest;

class AssetMovementStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'asset_id' => 'required|exists:assets,id',
            'to_room_id' => 'required|exists:asset_rooms,id',
            'moved_date' => 'required|date',
            'reason' => 'nullable|string',
        ];
    }
}
