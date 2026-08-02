<?php

namespace App\Http\Requests\Dormitory;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('put');

        return [
            'room_id' => 'required|exists:dormitory_rooms,id',
            'item_name' => 'required|string|max:191',
            'item_code' => 'nullable|string|max:50',
            'quantity' => $isUpdate ? 'nullable|integer|min:0' : 'required|integer|min:1',
            'condition' => 'required|in:baik,rusak,perbaikan,hilang', // ubah "hibahan" ke "hilang" agar sesuai enum DB
            'last_checked_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'category_id' => 'required|exists:asset_categories,id', // wajib FK
        ];
    }

    public function messages(): array
    {
        return [
            'item_name.required' => 'Nama barang wajib diisi.',
            'condition.required' => 'Kondisi wajib dipilih.',
            'condition.in' => 'Pilih kondisi yang valid: baik, rusak, atau perbaikan.',
            'category_id.exists' => 'Kategori barang tidak valid.',
        ];
    }
}
