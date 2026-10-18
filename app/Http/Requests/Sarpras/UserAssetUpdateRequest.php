<?php

namespace App\Http\Requests\Sarpras;

use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;

class UserAssetUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $assetId = $this->route('id');

        return [
            'room_id' => 'nullable|exists:asset_rooms,id',
            'asset_category_id' => 'required|exists:asset_categories,id',
            'asset_name' => 'required|string|max:191',
            'asset_code' => 'nullable|string|max:50|unique:assets,asset_code,'.$assetId,
            'quantity' => 'nullable|integer|min:1|max:100000',
            'unit' => 'nullable|string|max:30',
            'useful_life_years' => 'nullable|integer|min:0|max:100',
            'depreciation_per_year' => 'nullable|numeric|min:0',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'acquisition_date' => 'nullable|date',
            'acquisition_price' => 'nullable|numeric|min:0',
            'condition' => 'required|in:'.implode(',', Asset::CONDITION_OPTIONS),
            'notes' => 'nullable|string',
        ];
    }
}
