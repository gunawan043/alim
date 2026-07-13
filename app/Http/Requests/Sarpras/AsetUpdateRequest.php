<?php

namespace App\Http\Requests\Sarpras;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AsetUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $aset = \App\Models\Asset::find($this->route('id'));

        return [
            'room_id' => 'nullable|exists:asset_rooms,id',
            'asset_category_id' => 'required|exists:asset_categories,id',
            'asset_name' => 'required|string|max:191',
            'asset_code' => ['nullable', 'string', 'max:50', Rule::unique('assets', 'asset_code')->ignore($aset?->id)],
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:50',
            'specification' => 'nullable|string',
            'acquisition_date' => 'nullable|date',
            'acquisition_price' => 'nullable|numeric|min:0',
            'acquisition_source' => 'nullable|in:'.implode(',', \App\Models\Asset::ACQUISITION_SOURCE_OPTIONS),
            'funding_source' => 'nullable|string|max:100',
            'condition' => 'required|in:'.implode(',', \App\Models\Asset::CONDITION_OPTIONS),
            'status' => 'nullable|in:'.implode(',', \App\Models\Asset::STATUS_OPTIONS),
            'is_bookable' => 'boolean',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            'photos' => 'nullable|array',
            'photos.*' => 'image|max:5120',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'asset_name.required' => 'Nama aset wajib diisi.',
            'asset_category_id.required' => 'Kategori aset wajib dipilih.',
            'condition.required' => 'Kondisi aset wajib dipilih.',
            'photos.*.image' => 'Foto harus berupa gambar.',
            'photos.*.max' => 'Ukuran foto maksimal 5MB.',
        ];
    }
}
