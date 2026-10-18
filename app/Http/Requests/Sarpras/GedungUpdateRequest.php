<?php

namespace App\Http\Requests\Sarpras;

use App\Models\AssetBuilding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GedungUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $buildingId = $this->route('id');

        return [
            'school_id' => 'nullable|uuid|exists:schools,id',
            'building_name' => 'required|string|max:191',
            'building_code' => ['nullable', 'string', 'max:30', Rule::unique('asset_buildings', 'building_code')->ignore($buildingId)],
            'building_type' => 'required|in:'.implode(',', AssetBuilding::BUILDING_TYPE_OPTIONS),
            'total_floors' => 'nullable|integer|min:1|max:20',
            'building_area' => 'nullable|numeric|min:0',
            'build_year' => 'nullable|integer|min:1900|max:2100',
            'structure_condition' => 'required|in:'.implode(',', AssetBuilding::CONDITION_OPTIONS),
            'ownership_status' => 'nullable|in:'.implode(',', AssetBuilding::OWNERSHIP_OPTIONS),
            'imb_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}
